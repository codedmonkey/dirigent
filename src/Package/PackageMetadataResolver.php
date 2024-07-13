<?php

namespace CodedMonkey\Conductor\Package;

use CodedMonkey\Conductor\Composer\ConfigFactory;
use CodedMonkey\Conductor\Composer\HttpDownloaderOptionsFactory;
use CodedMonkey\Conductor\Doctrine\Entity\Credentials;
use CodedMonkey\Conductor\Doctrine\Entity\CredentialsType;
use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Conductor\Doctrine\Entity\SuggestLink;
use CodedMonkey\Conductor\Doctrine\Entity\Version;
use CodedMonkey\Conductor\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\AliasPackage;
use Composer\Package\CompletePackageInterface;
use Composer\Pcre\Preg;
use Composer\Repository\ComposerRepository;
use Composer\Repository\VcsRepository;
use Composer\Util\HttpDownloader;
use Doctrine\ORM\EntityManagerInterface;

class PackageMetadataResolver
{
    private const SUPPORTED_LINK_TYPES = [
        'require' => [
            'method' => 'getRequires',
            'entity' => 'RequireLink',
        ],
        'conflict' => [
            'method' => 'getConflicts',
            'entity' => 'ConflictLink',
        ],
        'provide' => [
            'method' => 'getProvides',
            'entity' => 'ProvideLink',
        ],
        'replace' => [
            'method' => 'getReplaces',
            'entity' => 'ReplaceLink',
        ],
        'devRequire' => [
            'method' => 'getDevRequires',
            'entity' => 'DevRequireLink',
        ],
    ];

    public function __construct(
        private readonly PackageProviderManager $providerManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly RegistryRepository $registryRepository,
        private readonly VersionRepository $versionRepository,
    ) {
    }

    public function isFresh(Package $package, ?\DateTimeImmutable $crawledAt = null): bool
    {
        $crawledAt ??= (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));

        if (null !== $lastCrawledAt = $package->getCrawledAt()) {
            $interval = $crawledAt->getTimestamp() - $lastCrawledAt->getTimestamp();
            $delay = 3600;

            if ($interval < $delay) {
                return true;
            }
        }

        return false;
    }

    public function resolve(Package $package): void
    {
        if ($this->isFresh($package)) {
            return;
        }

        if (null !== $registry = $package->getMirrorRegistry()) {
            $composerPackages = $this->resolveFromRegistry($package->getName(), $registry);
        } elseif (null !== $repositoryUrl = $package->getRepositoryUrl()) {
            $composerPackages = $this->resolveVcsRepository($repositoryUrl, $package->getRepositoryType(), $package->getRepositoryCredentials());
        } else {
            // todo resolve from other sources
            throw new \LogicException();
        }

        $crawledAt = new \DateTime();
        $package->setCrawledAt($crawledAt);

        $this->updatePackage($package, $composerPackages);

        $this->providerManager->dump($package, $composerPackages);
    }

    public function findPackageProvider(string $packageName): ?Registry
    {
        $registries = $this->registryRepository->findByPackageMirroring(RegistryPackageMirroring::Automatic);

        foreach ($registries as $registry) {
            if ($this->provides($packageName, $registry)) {
                return $registry;
            }
        }

        return null;
    }

    public function provides(string $packageName, Registry $registry): bool
    {
        $composerPackages = $this->resolveFromRegistry($packageName, $registry);

        return count($composerPackages) > 0;
    }

    private function resolveFromRegistry(string $packageName, Registry $registry): array
    {
        $io = new NullIO();
        $config = ConfigFactory::createForRegistry($registry);
        $io->loadConfiguration($config);
        $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());

        $repository = new ComposerRepository(['url' => $registry->url], $io, $config, $httpDownloader);
        return $repository->findPackages($packageName);
    }

    private function resolveVcsRepository(string $repositoryUrl, ?string $repositoryType, ?Credentials $repositoryCredentials): array
    {
        $io = new NullIO();
        $config = ConfigFactory::createForVcsRepository($repositoryUrl, $repositoryCredentials);
        $io->loadConfiguration($config);
        $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());
        $repository = new VcsRepository(['url' => $repositoryUrl], $io, $config, $httpDownloader);

        $driver = $repository->getDriver();
        if (!$driver) {
            throw new \LogicException();
        }
        $information = $driver->getComposerInformation($driver->getRootIdentifier());
        if (!isset($information['name']) || !is_string($information['name'])) {
            throw new \LogicException();
        }
        $packageName = trim($information['name']);

        return $repository->findPackages($packageName);
    }

    /**
     * @param CompletePackageInterface[] $composerPackages
     */
    private function updatePackage(Package $package, array $composerPackages): void
    {
        $versionRepository = $this->entityManager->getRepository(Version::class);

        $existingVersions = $versionRepository->getVersionMetadataForUpdate($package);

        foreach ($composerPackages as $composerPackage) {
            if ($composerPackage instanceof AliasPackage) {
                continue;
            }

            $version = $this->versionRepository->findOneBy(['package' => $package, 'normalizedVersion' => $composerPackage->getVersion()]) ?: new Version();

            if (!$package->getVersions()->contains($version)) {
                $package->getVersions()->add($version);
            }

            $this->updateVersion($package, $version, $composerPackage);

            unset($existingVersions[$version->getNormalizedVersion()]);
        }

        foreach ($existingVersions as $version) {
            $versionEntity = $versionRepository->find($version['id']);

            $this->entityManager->remove($versionEntity);
        }

        $this->entityManager->persist($package);
    }

    private function updateVersion(Package $package, Version $version, CompletePackageInterface $data): void
    {
        $em = $this->entityManager;

        $description = $this->sanitize($data->getDescription());

        $version->setName($package->getName());
        $version->setVersion($data->getPrettyVersion());
        $version->setNormalizedVersion($data->getVersion());
        $version->setDescription($description);
        $version->setDevelopment($data->isDev());
        $version->setPhpExt($data->getPhpExt());
        $version->setDefaultBranch($data->isDefaultBranch());
        $version->setTargetDir($data->getTargetDir());
        $version->setAutoload($data->getAutoload());
        $version->setExtra($data->getExtra());
        $version->setBinaries($data->getBinaries());
        $version->setIncludePaths($data->getIncludePaths());
        $version->setSupport($data->getSupport());
        $version->setFunding($data->getFunding());

        $version->setHomepage($data->getHomepage());
        $version->setLicense($data->getLicense() ?: []);

        $version->setPackage($package);
        $version->setUpdatedAt(new \DateTime());
        $version->setReleasedAt($data->getReleaseDate());

        if ($data->getSourceType()) {
            $source['type'] = $data->getSourceType();
            $source['url'] = $data->getSourceUrl();
            // force public URLs even if the package somehow got downgraded to a GitDriver
            if (is_string($source['url']) && Preg::isMatch('{^git@github.com:(?P<repo>.*?)\.git$}', $source['url'], $match)) {
                $source['url'] = 'https://github.com/'.$match['repo'];
            }
            $source['reference'] = $data->getSourceReference();
            $version->setSource($source);
        } else {
            $version->setSource(null);
        }

        if ($data->getDistType()) {
            $dist['type'] = $data->getDistType();
            $dist['url'] = $data->getDistUrl();
            $dist['reference'] = $data->getDistReference();
            $dist['shasum'] = $data->getDistSha1Checksum();
            $version->setDist($dist);
        } else {
            $version->setDist(null);
        }

        if ($data->isDefaultBranch()) {
            $package->setDescription($description);
            $package->setRepositoryType($this->sanitize($data->getType()));
            if ($data->isAbandoned() && !$package->isAbandoned()) {
                //$io->write('Marking package abandoned as per composer metadata from '.$version->getVersion());
                $package->setAbandoned(true);
                if ($data->getReplacementPackage()) {
                    $package->setReplacementPackage($data->getReplacementPackage());
                }
            }
        }

        // handle links
        foreach (self::SUPPORTED_LINK_TYPES as $linkType => $opts) {
            $links = [];
            foreach ($data->{$opts['method']}() as $link) {
                $constraint = $link->getPrettyConstraint();
                if (false !== strpos($constraint, ',') && false !== strpos($constraint, '@')) {
                    $constraint = Preg::replaceCallback('{([><]=?\s*[^@]+?)@([a-z]+)}i', static function ($matches) {
                        if ($matches[2] === 'stable') {
                            return $matches[1];
                        }

                        return $matches[1].'-'.$matches[2];
                    }, $constraint);
                }

                $links[$link->getTarget()] = $constraint;
            }

            foreach ($version->{'get'.$linkType}() as $link) {
                // clear links that have changed/disappeared (for updates)
                if (!isset($links[$link->getPackageName()]) || $links[$link->getPackageName()] !== $link->getPackageVersion()) {
                    $version->{'get'.$linkType}()->removeElement($link);
                    $em->remove($link);
                } else {
                    // clear those that are already set
                    unset($links[$link->getPackageName()]);
                }
            }

            foreach ($links as $linkPackageName => $linkPackageVersion) {
                $class = 'CodedMonkey\Conductor\Doctrine\Entity\\'.$opts['entity'];
                $link = new $class();
                $link->setPackageName((string) $linkPackageName);
                $link->setPackageVersion($linkPackageVersion);
                $version->{'add'.$linkType.'Link'}($link);
                $link->setVersion($version);
                $em->persist($link);
            }
        }

        // handle suggests
        if ($suggests = $data->getSuggests()) {
            foreach ($version->getSuggest() as $link) {
                // clear links that have changed/disappeared (for updates)
                if (!isset($suggests[$link->getPackageName()]) || $suggests[$link->getPackageName()] !== $link->getPackageVersion()) {
                    $version->getSuggest()->removeElement($link);
                    $em->remove($link);
                } else {
                    // clear those that are already set
                    unset($suggests[$link->getPackageName()]);
                }
            }

            foreach ($suggests as $linkPackageName => $linkPackageVersion) {
                $link = new SuggestLink();
                $link->setPackageName($linkPackageName);
                $link->setPackageVersion($linkPackageVersion);
                $version->addSuggestLink($link);
                $link->setVersion($version);
                $em->persist($link);
            }
        } elseif (count($version->getSuggest())) {
            // clear existing suggests if present
            foreach ($version->getSuggest() as $link) {
                $em->remove($link);
            }
            $version->getSuggest()->clear();
        }

        $em->persist($version);
    }

    private function sanitize(?string $str): ?string
    {
        if (null === $str) {
            return null;
        }

        // remove escape chars
        $str = Preg::replace("{\x1B(?:\[.)?}u", '', $str);

        return Preg::replace("{[\x01-\x1A]}u", '', $str);
    }
}
