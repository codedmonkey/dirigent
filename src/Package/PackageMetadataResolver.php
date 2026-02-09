<?php

namespace CodedMonkey\Dirigent\Package;

use cebe\markdown\GithubMarkdown;
use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataConflictLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataDevRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataProvideLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataReplaceLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataRequireLink;
use CodedMonkey\Dirigent\Doctrine\Entity\MetadataSuggestLink;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Doctrine\Entity\Registry;
use CodedMonkey\Dirigent\Doctrine\Entity\RegistryPackageMirroring;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\KeywordRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\RegistryRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use CodedMonkey\Dirigent\Message\DumpPackageProvider;
use CodedMonkey\Dirigent\Message\UpdatePackageLinks;
use Composer\Package\AliasPackage;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Link as ComposerPackageLink;
use Composer\Pcre\Preg;
use Composer\Repository\Vcs\VcsDriverInterface;
use Doctrine\ORM\EntityManagerInterface;
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
            'entity' => MetadataConflictLink::class,
        ],
        'devRequire' => [
            'method' => 'getDevRequires',
            'entity' => MetadataDevRequireLink::class,
        ],
        'provide' => [
            'method' => 'getProvides',
            'entity' => MetadataProvideLink::class,
        ],
        'replace' => [
            'method' => 'getReplaces',
            'entity' => MetadataReplaceLink::class,
        ],
        'require' => [
            'method' => 'getRequires',
            'entity' => MetadataRequireLink::class,
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
        $existingVersionMetadata = $this->versionRepository->getVersionMetadataForUpdate($package);

        /** @var ?Version $primaryVersion Version to use as the package info source */
        $primaryVersion = null;
        /** @var ?CompletePackageInterface $primaryVersionData */
        $primaryVersionData = null;

        // Every Composer package is a separate package version
        foreach ($composerPackages as $composerPackage) {
            if ($composerPackage instanceof AliasPackage) {
                continue;
            }

            $key = strtolower($composerPackage->getVersion());
            if ($versionId = $existingVersionMetadata[$key] ?? null) {
                $version = $this->entityManager->getReference(Version::class, $versionId);
            } else {
                $version = new Version($package);
                $version->setName($composerPackage->getPrettyVersion());
                $version->setNormalizedName($composerPackage->getVersion());
                $version->setDevelopment($composerPackage->isDev());

                $package->getVersions()->add($version);
            }

            $this->updateVersion($version, $composerPackage, $driver);

            // Use the first version which should be the highest stable version by default
            $primaryVersion ??= $version;
            $primaryVersionData ??= $composerPackage;
            // If default branch is present however we prefer that as the canonical package link source
            if ($version->isDefaultBranch()) {
                $primaryVersion = $version;
                $primaryVersionData = $composerPackage;
            }

            unset($existingVersionMetadata[$key]);
        }

        if ($primaryVersion) {
            // Update package fields from metadata
            $package->setDescription($this->sanitize($primaryVersionData->getDescription()));
            $package->setType($this->sanitize($primaryVersionData->getType()));

            // Update abandoned data at the package level
            $package->setAbandoned($primaryVersionData->isAbandoned());
            $package->setReplacementPackage($primaryVersionData->getReplacementPackage());

            // Only update the repository URL if the package is mirrored
            if ($package->getMirrorRegistry()) {
                $package->setRepositoryUrl($primaryVersion->getCurrentMetadata()->getSourceUrl());
            }

            $this->messenger->dispatch(new UpdatePackageLinks($package->getId(), $primaryVersion->getNormalizedName()), [
                new DispatchAfterCurrentBusStamp(),
                new TransportNamesStamp('async'),
            ]);
        }

        // Remove outdated versions
        foreach ($existingVersionMetadata as $versionId) {
            $version = $this->entityManager->getReference(Version::class, $versionId);
            $this->entityManager->remove($version);
        }

        $package->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($package);
    }

    private function updateVersion(Version $version, CompletePackageInterface $data, ?VcsDriverInterface $driver = null): void
    {
        $metadata = $this->createMetadata($version, $data, $driver);

        if ($this->hasMetadataChanged($version->getCurrentMetadata(), $metadata)) {
            $version->setCurrentMetadata($metadata);

            $this->entityManager->persist($metadata);

            $version->setDefaultBranch($data->isDefaultBranch());
        }

        $version->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($version);
    }

    private function createMetadata(Version $version, CompletePackageInterface $data, ?VcsDriverInterface $driver): Metadata
    {
        $metadata = new Metadata($version);
        $metadata->setPackageName($data->getName());
        $metadata->setVersionName($data->getPrettyVersion());
        $metadata->setNormalizedVersionName($data->getVersion());
        $metadata->setDescription($this->sanitize($data->getDescription()));
        $metadata->setPhpExt($data->getPhpExt());
        $metadata->setTargetDir($data->getTargetDir());
        $metadata->setAutoload($data->getAutoload());
        $metadata->setExtra($data->getExtra());
        $metadata->setBinaries($data->getBinaries());
        $metadata->setIncludePaths($data->getIncludePaths());
        $metadata->setSupport($data->getSupport());
        $metadata->setFunding($data->getFunding());
        $metadata->setHomepage($data->getHomepage());
        $metadata->setLicense($data->getLicense() ?: []);
        $metadata->setType($this->sanitize($data->getType()));
        $metadata->setReleasedAt(\DateTimeImmutable::createFromInterface($data->getReleaseDate()));

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

                // Skip authors with no information
                if (!isset($authorData['email']) && !isset($authorData['name'])) {
                    continue;
                }

                $authors[] = $author;
            }

            $metadata->setAuthors($authors);
        }

        if ($data->getSourceType()) {
            $metadata->setSource([
                'type' => $data->getSourceType(),
                'url' => static::optimizeRepositoryUrl($data->getSourceUrl()),
                'reference' => $data->getSourceReference(),
            ]);
        }

        if ($data->getDistType()) {
            $metadata->setDist([
                'type' => $data->getDistType(),
                'url' => $data->getDistUrl(),
                'reference' => $data->getDistReference(),
                'shasum' => $data->getDistSha1Checksum(),
            ]);
        }

        // Handle links
        foreach (self::SUPPORTED_LINK_TYPES as $linkType => $linkOptions) {
            $links = [];
            /** @var ComposerPackageLink $link */
            foreach ($data->{$linkOptions['method']}() as $link) {
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

            $index = 0;
            foreach ($links as $linkPackageName => $linkPackageConstraint) {
                new $linkOptions['entity']($metadata, $linkPackageName, $linkPackageConstraint, $index++);
            }
        }

        // Handle suggests
        if ($suggests = $data->getSuggests()) {
            $index = 0;
            foreach ($suggests as $linkPackageName => $linkPackageConstraint) {
                new MetadataSuggestLink($metadata, $linkPackageName, $linkPackageConstraint, $index++);
            }
        }

        // Handle keywords
        if ($keywordsData = $data->getKeywords()) {
            foreach ($keywordsData as $keywordName) {
                $keyword = $this->keywordRepository->getByName($keywordName);

                $metadata->getKeywords()->add($keyword);
            }
        }

        if ($driver) {
            $metadata->setReadme($this->getReadmeContents($metadata, $driver));
        }

        return $metadata;
    }

    private function sanitize(?string $string): ?string
    {
        if (null === $string || '' === $string) {
            return null;
        }

        // Remove escape chars
        $string = Preg::replace("{\x1B(?:\[.)?}u", '', $string);
        $string = Preg::replace("{[\x01-\x1A]}u", '', $string);

        return $string;
    }

    private function hasMetadataChanged(?Metadata $currentMetadata, Metadata $metadata): bool
    {
        $currentData = $currentMetadata?->toComposerArray();
        $data = $metadata->toComposerArray();

        if (null === $currentData) {
            return true;
        }

        // Fields that shouldn't trigger a new revision
        $excludeFields = ['abandoned', 'default-branch'];

        foreach ($excludeFields as $field) {
            unset($currentData[$field], $data[$field]);
        }

        // Normalize both arrays for comparison
        ksort($currentData);
        ksort($data);

        return $currentData !== $data;
    }

    private function getReadmeContents(Metadata $metadata, VcsDriverInterface $driver): ?string
    {
        try {
            $composerInfo = $driver->getComposerInformation($metadata->getSourceReference());
            $readmeFile = is_string($composerInfo['readme'] ?? null) ? $composerInfo['readme'] : 'README.md';

            $ext = substr($readmeFile, (int) strrpos($readmeFile, '.'));
            if ($ext === $readmeFile) {
                $ext = '.txt';
            }

            switch ($ext) {
                case '.txt':
                    $source = $driver->getFileContent($readmeFile, $metadata->getSourceReference());

                    if (!empty($source)) {
                        return '<pre>' . htmlspecialchars($source) . '</pre>';
                    }

                    break;

                case '.markdown':
                case '.md':
                    $source = $driver->getFileContent($readmeFile, $metadata->getSourceReference());

                    if (!empty($source)) {
                        $parser = new GithubMarkdown();
                        $readme = $parser->parse($source);

                        if (!empty($readme)) {
                            return $this->prepareReadmeContents($readme);
                        }
                    }

                    break;
            }
        } catch (\Exception $exception) {
            throw $exception; // todo handle politely
        }

        return null;
    }

    private function prepareReadmeContents(string $readme): string
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
