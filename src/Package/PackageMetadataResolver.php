<?php

namespace CodedMonkey\Conductor\Package;

use cebe\markdown\GithubMarkdown;
use CodedMonkey\Conductor\Composer\ConfigFactory;
use CodedMonkey\Conductor\Composer\HttpDownloaderOptionsFactory;
use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Conductor\Doctrine\Entity\SuggestLink;
use CodedMonkey\Conductor\Doctrine\Entity\Version;
use CodedMonkey\Conductor\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use CodedMonkey\Conductor\Message\DumpPackageProvider;
use Composer\IO\NullIO;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Pcre\Preg;
use Composer\Repository\ComposerRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;
use Composer\Util\HttpDownloader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class PackageMetadataResolver
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
        private MessageBusInterface $messenger,
        private EntityManagerInterface $entityManager,
        private RegistryRepository $registryRepository,
        private VersionRepository $versionRepository,
    ) {
    }

    public function resolve(Package $package): void
    {
        if (null !== $package->getMirrorRegistry()) {
            $this->resolveRegistryPackage($package);
        } elseif (null !== $package->getRepositoryUrl()) {
            $this->resolveVcsPackage($package);
        } else {
            // todo resolve from other sources
            throw new \LogicException();
        }

        $this->messenger->dispatch(new DumpPackageProvider($package->getId()));
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
        $repository = $this->getRegistryRepository($registry);
        $composerPackages = $repository->findPackages($packageName);

        return count($composerPackages) > 0;
    }

    private function getRegistryRepository(Registry $registry): RepositoryInterface
    {
        $io = new NullIO();
        $config = ConfigFactory::createForRegistry($registry);
        $io->loadConfiguration($config);
        $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());

        return new ComposerRepository(['url' => $registry->getUrl()], $io, $config, $httpDownloader);
    }

    private function resolveRegistryPackage(Package $package, ?Registry $registry = null): void
    {
        $packageName = $package->getName();
        $registry ??= $package->getMirrorRegistry();

        if (!$registry) {
            throw new \LogicException("No registry provided for $packageName.");
        }

        $repository = $this->getRegistryRepository($registry);
        $composerPackages = $repository->findPackages($packageName);

        $this->updatePackage($package, $composerPackages);
    }

    private function resolveVcsPackage(Package $package): void
    {
        $repositoryUrl = $package->getRepositoryUrl();
        $repositoryCredentials = $package->getRepositoryCredentials();

        $io = new NullIO();
        $config = ConfigFactory::createForVcsRepository($repositoryUrl, $repositoryCredentials);
        $io->loadConfiguration($config);
        $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());
        $repository = new VcsRepository(['url' => $repositoryUrl], $io, $config, $httpDownloader);

        $driver = $repository->getDriver();
        if (!$driver) {
            throw new \LogicException("Unable to resolve VCS driver for repository: $repositoryUrl");
        }
        $information = $driver->getComposerInformation($driver->getRootIdentifier());
        if (!isset($information['name']) || !is_string($information['name'])) {
            throw new \LogicException();
        }
        $packageName = trim($information['name']);

        $composerPackages = $repository->findPackages($packageName);

        $this->updatePackage($package, $composerPackages, $driver);
    }

    /**
     * @param PackageInterface[] $composerPackages
     */
    private function updatePackage(Package $package, array $composerPackages, ?VcsDriverInterface $driver = null): void
    {
        $existingVersions = $this->versionRepository->getVersionMetadataForUpdate($package);

        foreach ($composerPackages as $composerPackage) {
            if ($composerPackage instanceof AliasPackage) {
                continue;
            }

            $version = $this->versionRepository->findOneBy(['package' => $package, 'normalizedVersion' => $composerPackage->getVersion()]) ?: new Version();

            if (!$package->getVersions()->contains($version)) {
                $package->getVersions()->add($version);
            }

            $this->updateVersion($package, $version, $composerPackage, $driver);

            unset($existingVersions[$version->getNormalizedVersion()]);
        }

        foreach ($existingVersions as $version) {
            $versionEntity = $this->versionRepository->find($version['id']);

            $this->entityManager->remove($versionEntity);
        }

        $updatedAt = new \DateTime();
        $package->setUpdatedAt($updatedAt);

        $this->entityManager->persist($package);
    }

    private function updateVersion(Package $package, Version $version, PackageInterface $data, ?VcsDriverInterface $driver = null): void
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
        $version->setType($this->sanitize($data->getType()));

        $version->setPackage($package);
        $version->setUpdatedAt(new \DateTime());
        $version->setReleasedAt($data->getReleaseDate());

        $version->setAuthors([]);
        if ($data->getAuthors()) {
            $authors = [];
            foreach ($data->getAuthors() as $authorData) {
                $author = [];

                foreach (['email', 'name', 'homepage', 'role'] as $field) {
                    if (isset($authorData[$field])) {
                        $author[$field] = trim($authorData[$field]);
                        if ('' === $author[$field]) {
                            unset($author[$field]);
                        }
                    }
                }

                // skip authors with no information
                if (!isset($authorData['email']) && !isset($authorData['name'])) {
                    continue;
                }

                $authors[] = $author;
            }
            $version->setAuthors($authors);
        }

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
            $package->setType($this->sanitize($data->getType()));
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

        if ($driver) {
            $this->updateReadme($version, $driver);
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

    private function updateReadme(Version $version, VcsDriverInterface $driver): void
    {
        try {
            $composerInfo = $driver->getComposerInformation($driver->getRootIdentifier());
            if (isset($composerInfo['readme']) && is_string($composerInfo['readme'])) {
                $readmeFile = $composerInfo['readme'];
            } else {
                $readmeFile = 'README.md';
            }

            $ext = substr($readmeFile, (int) strrpos($readmeFile, '.'));
            if ($ext === $readmeFile) {
                $ext = '.txt';
            }

            switch ($ext) {
                case '.txt':
                    $source = $driver->getFileContent($readmeFile, $version->getSource()['reference']);

                    if (!empty($source)) {
                        $version->setReadme('<pre>' . htmlspecialchars($source) . '</pre>');
                    }

                    break;

                case '.md':
                    $source = $driver->getFileContent($readmeFile, $version->getSource()['reference']);

                    if (!empty($source)) {
                        $parser = new GithubMarkdown();
                        $readme = $parser->parse($source);

                        if (!empty($readme)) {
                            if (Preg::isMatch('{^(?:git://|git@|https?://)(gitlab.com|bitbucket.org)[:/]([^/]+)/(.+?)(?:\.git|/)?$}i', $version->getPackage()->getRepositoryUrl(), $match)) {
                                $version->setReadme($this->prepareReadme($readme, $match[1], $match[2], $match[3]));
                            } else {
                                $version->setReadme($this->prepareReadme($readme));
                            }
                        }
                    }

                    break;
            }
        } catch (\Exception $e) {
            throw $e; // todo handle politely
        }
    }

    private function prepareReadme(string $readme): string
    {
        return $readme;
    }
}
