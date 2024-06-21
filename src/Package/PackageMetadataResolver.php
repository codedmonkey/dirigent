<?php

namespace CodedMonkey\Conductor\Package;

use CodedMonkey\Conductor\Composer\HttpDownloaderOptionsFactory;
use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Conductor\Doctrine\Entity\SuggestLink;
use CodedMonkey\Conductor\Doctrine\Entity\Version;
use CodedMonkey\Conductor\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Pcre\Preg;
use Composer\Repository\ComposerRepository;
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
        private readonly PackageProviderPool $providerPool,
        private readonly VersionRepository   $versionRepository,
        private readonly RegistryRepository  $registryRepository, private readonly EntityManagerInterface $entityManager,
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

        $io = new NullIO();
        $config = Factory::createConfig();
        $io->loadConfiguration($config);
        $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());

        if ($registry = $package->getMirrorRegistry()) {
            $repository = new ComposerRepository(['url' => $registry->url], $io, $config, $httpDownloader);
            $composerPackages = $repository->findPackages($package->getName());

            $crawledAt = new \DateTime();
            $package->setCrawledAt($crawledAt);

            $this->updatePackage($package, $composerPackages);
        } else {
            // todo resolve from other sources
            throw new \LogicException();
        }

        $this->dumpProviders($package, $composerPackages);
    }

    public function whatProvides(Package $package): ?Registry
    {
        $io = new NullIO();
        $config = Factory::createConfig();
        $io->loadConfiguration($config);
        $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());

        $registries = $this->registryRepository->findByPackageMirroring(RegistryPackageMirroring::Automatic);

        foreach ($registries as $registry) {
            $repository = new ComposerRepository(['url' => $registry->url], $io, $config, $httpDownloader);
            $composerPackages = $repository->findPackages($package->getName());

            if (count($composerPackages) > 0) {
                return $registry;
            }
        }

        return null;
    }

    private function dumpProviders(Package $package, array $composerPackages): void
    {
        $releasePackages = [];
        $devPackages = [];

        foreach ($composerPackages as $composerPackage) {
            if (!$composerPackage->isDev()) {
                $releasePackages[] = $composerPackage;
            } else {
                $devPackages[] = $composerPackage;
            }
        }

        $this->providerPool->write($package->getName(), $this->compileProvider($package->getName(), $releasePackages));
        $this->providerPool->write("{$package->getName()}~dev", $this->compileProvider($package->getName(), $devPackages));
    }

    private function compileProvider(string $packageName, array $composerPackages): array
    {
        $data = array_map([new ArrayDumper(), 'dump'], $composerPackages);

        return [
            'minified' => 'composer/2.0',
            'packages' => [
                $packageName => MetadataMinifier::minify($data),
            ],
        ];
    }

    private function updatePackage(Package $package, array $composerPackages): void
    {
        foreach ($composerPackages as $composerPackage) {
            $version = $this->versionRepository->findOneBy(['package' => $package, 'version' => $composerPackage->getVersion()]) ?: new Version();

            if (!$package->getVersions()->contains($version)) {
                $package->getVersions()->add($version);
            }

            $this->updateVersion($package, $version, $composerPackage);
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
