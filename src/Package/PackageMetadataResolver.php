<?php

namespace CodedMonkey\Dirigent\Package;

use cebe\markdown\GithubMarkdown;
use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Composer\ConfigFactory;
use CodedMonkey\Dirigent\Composer\InMemoryJsonFile;
use CodedMonkey\Dirigent\Doctrine\Entity\AbstractVersionLink;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Entity\VersionConflictLink;
use CodedMonkey\Dirigent\Doctrine\Entity\VersionDevRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\VersionProvideLink;
use CodedMonkey\Dirigent\Doctrine\Entity\VersionReplaceLink;
use CodedMonkey\Dirigent\Doctrine\Entity\VersionRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\VersionSuggestLink;
use CodedMonkey\Dirigent\Doctrine\Repository\KeywordRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use CodedMonkey\Dirigent\Message\DumpPackageProvider;
use CodedMonkey\Dirigent\Message\UpdatePackageLinks;
use Composer\Installer\InstallationManager;
use Composer\IO\NullIO;
use Composer\Package\AliasPackage;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Locker;
use Composer\Pcre\Preg;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Util\Loop;
use Composer\Util\ProcessExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

readonly class PackageMetadataResolver
{
    /**
     * Available link types and their associated methods and entities.
     *
     * Each link type maps to an array that specifies:
     * - the `method` to be used for handling the link type.
     * - the `entity` class associated with the link type.
     *
     * Does not include `suggest` as it's not defined as a Link object in the Composer package interface.
     */
    private const array SUPPORTED_LINK_TYPES = [
        'conflict' => [
            'method' => 'getConflicts',
            'entity' => VersionConflictLink::class,
        ],
        'devRequire' => [
            'method' => 'getDevRequires',
            'entity' => VersionDevRequireLink::class,
        ],
        'provide' => [
            'method' => 'getProvides',
            'entity' => VersionProvideLink::class,
        ],
        'replace' => [
            'method' => 'getReplaces',
            'entity' => VersionReplaceLink::class,
        ],
        'require' => [
            'method' => 'getRequires',
            'entity' => VersionRequireLink::class,
        ],
    ];

    public function __construct(
        private ComposerClient $composer,
        private MessageBusInterface $messenger,
        private EntityManagerInterface $entityManager,
        private KeywordRepository $keywordRepository,
        private RegistryRepository $registryRepository,
        private VersionRepository $versionRepository,
    ) {
    }

    public function resolve(Package $package): void
    {
        match ($package->getFetchStrategy()) {
            PackageFetchStrategy::Mirror => $this->resolveRegistryPackage($package),
            PackageFetchStrategy::Vcs => $this->resolveVcsPackage($package),
            default => throw new \LogicException(),
        };

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
        $repository = $this->composer->createComposerRepository($registry);
        $composerPackages = $repository->findPackages($packageName);

        return count($composerPackages) > 0;
    }

    private function resolveRegistryPackage(Package $package, ?Registry $registry = null): void
    {
        $packageName = $package->getName();
        $registry ??= $package->getMirrorRegistry();

        if (!$registry) {
            throw new \LogicException("No registry provided for $packageName.");
        }

        $repository = $this->composer->createComposerRepository($registry);
        /** @var CompletePackageInterface[] $composerPackages */
        $composerPackages = $repository->findPackages($packageName);

        dd($composerPackages);

        $this->updatePackage($package, $composerPackages);
    }

    private function resolveVcsPackage(Package $package): void
    {
        if ($package->getMirrorRegistry()) {
            $this->resolveVcsRepository($package);
        }

        if (!$package->getRepositoryUrl()) {
            if ($package->getMirrorRegistry()) {
                // todo log fallback to mirror registry

                $this->resolveRegistryPackage($package);

                return;
            }

            throw new \LogicException("No repository URL provided for {$package->getName()}.");
        }

        $repository = $this->composer->createVcsRepository($package);

        $driver = $repository->getDriver();
        if (!$driver) {
            throw new \LogicException("Unable to resolve VCS driver for repository: {$package->getRepositoryUrl()}");
        }
        $information = $driver->getComposerInformation($driver->getRootIdentifier());
        if (!isset($information['name']) || !is_string($information['name'])) {
            throw new \LogicException();
        }
        $packageName = trim($information['name']);

        /** @var CompletePackageInterface[] $composerPackages */
        $composerPackages = $repository->findPackages($packageName);

        dump($repository);
        dump($composerPackages[0]);
        //dd($composerPackages);

        $this->updatePackage($package, $composerPackages, $driver);
    }

    private function resolveVcsRepository(Package $package): void
    {
        $repository = $this->composer->createComposerRepository($package->getMirrorRegistry());
        $composerPackages = $repository->findPackages($package->getName());

        foreach ($composerPackages as $composerPackage) {
            if ($composerPackage->isDefaultBranch()) {
                $package->setRepositoryUrl($composerPackage->getSourceUrl());

                return;
            }
        }
    }

    /**
     * @param CompletePackageInterface[] $composerPackages
     */
    private function updatePackage(Package $package, array $composerPackages, ?VcsDriverInterface $driver = null): void
    {
        $existingVersions = $this->versionRepository->getVersionMetadataForUpdate($package);
        /** @var ?CompletePackageInterface $primaryVersion Version to use as the package info source */
        $primaryVersion = null;

        foreach ($composerPackages as $composerPackage) {
            if ($composerPackage instanceof AliasPackage) {
                continue;
            }

            $version = $this->versionRepository->findOneByNormalizedVersion($package, $composerPackage->getVersion()) ?: new Version();

            if (!$package->getVersions()->contains($version)) {
                $package->getVersions()->add($version);
                $this->entityManager->persist($version);
            }

            $this->updateVersion($package, $version, $composerPackage, $driver);
            $versionName = $version->getNormalizedVersion();

            // Use the first version which should be the highest stable version by default
            $primaryVersion ??= $version;
            // If default branch is present however we prefer that as the canonical package link source
            if ($version->isDefaultBranch()) {
                $primaryVersion = $version;
            }

            unset($existingVersions[$versionName]);
        }

        if ($primaryVersion) {
            // Only update the repository URL if the package is mirrored
            if ($package->getMirrorRegistry()) {
                $package->setRepositoryUrl($primaryVersion->getSourceUrl());
            }

            $message = Envelope::wrap(new UpdatePackageLinks($package->getId(), $primaryVersion->getNormalizedVersion()))
                ->with(new DispatchAfterCurrentBusStamp())
                ->with(new TransportNamesStamp('async'));
            $this->messenger->dispatch($message);
        }

        // Remove outdated versions
        foreach ($existingVersions as $version) {
            $versionEntity = $this->versionRepository->find($version['id']);

            $this->entityManager->remove($versionEntity);
        }

        $package->setUpdatedAt(new \DateTimeImmutable());
    }

    private function updateVersion(Package $package, Version $version, CompletePackageInterface $data, ?VcsDriverInterface $driver = null): void
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
        $version->setUpdatedAt(new \DateTimeImmutable());
        $version->setReleasedAt(\DateTimeImmutable::createFromInterface($data->getReleaseDate()));

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
            // force public URLs even if the package somehow got downgraded to a GitDriver
            $source['url'] = static::optimizeRepositoryUrl($data->getSourceUrl());
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
            $package->setRepositoryUrl($data->getSourceUrl());
            $package->setDescription($description);
            $package->setType($this->sanitize($data->getType()));
            if ($data->isAbandoned() && !$package->isAbandoned()) {
                // $io->write('Marking package abandoned as per composer metadata from '.$version->getVersion());
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
                if (str_contains($constraint, ',') && str_contains($constraint, '@')) {
                    $constraint = Preg::replaceCallback('{([><]=?\s*[^@]+?)@([a-z]+)}i', static function ($matches) {
                        if ('stable' === $matches[2]) {
                            return $matches[1];
                        }

                        return $matches[1] . '-' . $matches[2];
                    }, $constraint);
                }

                $links[$link->getTarget()] = $constraint;
            }

            /** @var AbstractVersionLink $link */
            foreach ($version->{'get' . $linkType}() as $link) {
                $linkPackageName = $link->getLinkedPackageName();

                // Clear links that have changed/disappeared (for updates)
                if (!isset($links[$linkPackageName]) || $links[$linkPackageName] !== $link->getLinkedVersionConstraint()) {
                    $version->{'get' . $linkType}()->removeElement($link);
                    $em->remove($link);
                } else {
                    // Clear those that are already set
                    unset($links[$linkPackageName]);
                }
            }

            foreach ($links as $linkPackageName => $linkPackageConstraint) {
                /** @var AbstractVersionLink $link */
                $link = new $opts['entity']();
                $link->setLinkedPackageName($linkPackageName);
                $link->setLinkedVersionConstraint($linkPackageConstraint);
                $version->{'add' . $linkType . 'Link'}($link);
                $link->setVersion($version);
                $em->persist($link);
            }
        }

        // handle suggests
        if ($suggests = $data->getSuggests()) {
            foreach ($version->getSuggest() as $link) {
                $linkPackageName = $link->getLinkedPackageName();
                // clear links that have changed/disappeared (for updates)
                if (!isset($suggests[$linkPackageName]) || $suggests[$linkPackageName] !== $link->getLinkedVersionConstraint()) {
                    $version->getSuggest()->removeElement($link);
                    $em->remove($link);
                } else {
                    // clear those that are already set
                    unset($suggests[$linkPackageName]);
                }
            }

            foreach ($suggests as $linkPackageName => $linkPackageConstraint) {
                $link = new VersionSuggestLink();
                $link->setLinkedPackageName($linkPackageName);
                $link->setLinkedVersionConstraint($linkPackageConstraint);
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

        // Handle keywords
        if ($keywordsData = $data->getKeywords()) {
            foreach ($version->getKeywords() as $keyword) {
                $keywordName = $keyword->getName();
                // Clear keywords that have disappeared (for updates)
                if (!in_array($keywordName, $keywordsData, true)) {
                    $version->getKeywords()->removeElement($keyword);
                    $em->remove($keyword);
                } else {
                    // Clear those that are already set
                    $index = array_search($keywordName, $keywordsData, true);
                    unset($keywordsData[$index]);
                }
            }

            foreach ($keywordsData as $keywordName) {
                $keyword = $this->keywordRepository->getByName($keywordName);
                $version->addKeyword($keyword);
            }
        } elseif (count($version->getKeywords())) {
            // Clear existing keywords if present
            $version->getKeywords()->clear();
        }

        if ($driver) {
            $this->updateReadme($version, $driver);
        } else {
            $version->setReadme(null);
        }
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

    private function updateLockedPackages(Version $version): void
    {
        $locker = $this->composer->createLocker($version);

        dump($locker->getLockedRepository());
        dd();
    }

    private function updateReadme(Version $version, VcsDriverInterface $driver): void
    {
        try {
            $composerInfo = $driver->getComposerInformation($version->getSource()['reference']);
            $readmeFile = is_string($composerInfo['readme'] ?? null) ? $composerInfo['readme'] : 'README.md';

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
                            $version->setReadme($this->prepareReadme($readme));
                        }
                    }

                    break;
            }
        } catch (\Exception $exception) {
            throw $exception; // todo handle politely
        }
    }

    private function prepareReadme(string $readme): string
    {
        return $readme;
    }

    public static function optimizeRepositoryUrl(?string $url): ?string
    {
        if (null === $url) {
            return null;
        }

        // Force GitHub repos to use standardized format
        $url = Preg::replace('{^git@github.com:}i', 'https://github.com/', $url);
        $url = Preg::replace('{^git://github.com/}i', 'https://github.com/', $url);
        $url = Preg::replace('{^(https://github.com/.*?)\.git$}i', '$1', $url);
        $url = Preg::replace('{^(https://github.com/.*?)/$}i', '$1', $url);

        // Force GitLab repos to use standardized format
        $url = Preg::replace('{^git@gitlab.com:}i', 'https://gitlab.com/', $url);
        $url = Preg::replace('{^https?://(?:www\.)?gitlab\.com/(.*?)\.git$}i', 'https://gitlab.com/$1', $url);

        // Force Bitbucket repos to use standardized format
        $url = Preg::replace('{^git@+bitbucket.org:}i', 'https://bitbucket.org/', $url);
        $url = Preg::replace('{^bitbucket.org:}i', 'https://bitbucket.org/', $url);
        $url = Preg::replace('{^https://[a-z0-9_-]*@bitbucket.org/}i', 'https://bitbucket.org/', $url);
        $url = Preg::replace('{^(https://bitbucket.org/[^/]+/[^/]+)/src/[^.]+}i', '$1.git', $url);

        // Normalize protocol case
        $url = Preg::replaceCallbackStrictGroups('{^(https?|git|svn)://}i', static fn ($match) => strtolower($match[1]) . '://', $url);

        return $url;
    }
}
