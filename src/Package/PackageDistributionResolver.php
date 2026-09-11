<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Composer\ConfigFactory;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Message\ResolveDistribution;
use Composer\IO\NullIO;
use Composer\Pcre\Preg;
use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Git as GitUtility;
use Composer\Util\ProcessExecutor;
use Composer\Util\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

readonly class PackageDistributionResolver
{
    private Filesystem $filesystem;
    private string $storagePath;

    public function __construct(
        private MessageBusInterface $messenger,
        private ComposerClient $composer,
        private DistributionRepository $distributionRepository,
        private LockFactory $lockFactory,
        #[Autowire(param: 'dirigent.distributions.build')]
        private bool $buildDistributions,
        #[Autowire(param: 'dirigent.distributions.mirror')]
        private bool $mirrorDistributions,
        #[Autowire(param: 'dirigent.distributions.dev_versions')]
        private bool $includeDevVersions,
        #[Autowire(param: 'dirigent.storage.path')]
        string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
        $this->storagePath = "$storagePath/distribution";
    }

    public function exists(Metadata|string $metadataOrPath, ?string $reference = null, ?string $type = null): bool
    {
        if ($metadataOrPath instanceof Metadata) {
            return $this->filesystem->exists($this->path($metadataOrPath, $reference, $type));
        }

        return $this->filesystem->exists($metadataOrPath);
    }

    public function path(Metadata $metadata, string $reference, string $type): string
    {
        $packageName = $metadata->getPackage()->getName();
        $versionName = $metadata->getNormalizedVersionName();
        $revision = $metadata->getRevision();

        return "{$this->storagePath}/{$packageName}/{$versionName}-r{$revision}-{$reference}.{$type}";
    }

    public function remove(Distribution $distribution): void
    {
        $path = $this->path($distribution->getMetadata(), $distribution->getReference(), $distribution->getType());
        $lock = $this->createDistributionLock($path);

        try {
            $this->filesystem->remove($path);

            // Remove the package directory if it's empty
            $packageDirectory = dirname($path);
            if (is_dir($packageDirectory) && !new \FilesystemIterator($packageDirectory)->valid()) {
                $this->filesystem->remove($packageDirectory);
            }

            // Remove the vendor directory if it's empty
            $vendorDirectory = dirname($packageDirectory);
            if (is_dir($vendorDirectory) && !new \FilesystemIterator($vendorDirectory)->valid()) {
                $this->filesystem->remove($vendorDirectory);
            }
        } finally {
            $lock->release();
        }
    }

    public function removeMetadata(Metadata $metadata): void
    {
        foreach ($this->distributionRepository->findByMetadata($metadata) as $distribution) {
            $this->remove($distribution);
        }
    }

    public function removePackage(Package $package): void
    {
        foreach ($this->distributionRepository->findByPackage($package) as $distribution) {
            $this->remove($distribution);
        }
    }

    public function removeVersion(Version $version): void
    {
        foreach ($this->distributionRepository->findByVersion($version) as $distribution) {
            $this->remove($distribution);
        }
    }

    public function resolve(Metadata $metadata, string $reference, string $type, bool $async): bool
    {
        $path = $this->path($metadata, $reference, $type);

        if ($this->exists($path)) {
            return true;
        }

        if (null === $strategy = $this->getFetchStrategy($metadata)) {
            return false;
        }

        $currentReference = $strategy->isMirror() ? $metadata->getDistributionReference() : $metadata->getSourceReference();
        $currentType = $strategy->isMirror() ? $metadata->getDistributionType() : 'zip';

        if ($reference !== $currentReference || $type !== $currentType) {
            return false;
        }

        if ($async) {
            // Resolve the distribution asynchronously so it's available in the future now that we know it was requested
            $this->messenger->dispatch(new ResolveDistribution($metadata->getId(), $reference, $type), [
                new TransportNamesStamp('async'),
            ]);

            // Still return false so the service resolving the distribution doesn't try to fetch it anyway
            return false;
        }

        $lock = $this->createDistributionLock($path);

        try {
            if (null === $distribution = $this->distributionRepository->findOneByReferenceAndType($metadata, $reference, $type)) {
                $distribution = new Distribution($metadata, $reference, $type);
            }

            $result = false;

            // Build the distribution from VCS source
            if ($strategy->isVcs()) {
                $result = $this->build($distribution);

                if (
                    !$result
                    && $this->mirrorDistributions
                    && $metadata->hasDistribution()
                    && $reference === $metadata->getDistributionReference()
                    && $type === $metadata->getDistributionType()
                ) {
                    // Mirror the distribution if it failed to build from source
                    // todo log fallback
                    $strategy = PackageFetchStrategy::Mirror;
                }
            }

            if ($strategy->isMirror()) {
                $result = $this->mirror($distribution, $path);
            }

            if ($result) {
                $distribution->setResolvedAt();
                $this->distributionRepository->save($distribution, true);
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function build(Distribution $distribution): bool
    {
        $metadata = $distribution->getMetadata();
        $reference = $distribution->getReference();

        $package = $metadata->getPackage();
        $repositoryUrl = $package->getRepositoryUrl();
        $distributionPath = $this->path($metadata, $reference, 'zip');

        $composerConfig = ConfigFactory::createForVcsRepository($repositoryUrl, $package->getRepositoryCredentials());

        $gitUtility = new GitUtility(
            $io = new NullIO(),
            $composerConfig,
            $process = new ProcessExecutor($io),
            new ComposerFilesystem($process),
        );

        $cacheRepositoryName = Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize($repositoryUrl));
        $cachePath = $composerConfig->get('cache-vcs-dir') . '/' . $cacheRepositoryName . '/';

        $this->filesystem->mkdir(dirname($distributionPath));

        $gitUtility->runCommands([
            ['git', 'archive', '--format=zip', "--output=$distributionPath", $reference],
        ], $repositoryUrl, $cachePath);

        $distribution->setSource(null);

        return true;
    }

    private function mirror(Distribution $distribution, string $path): bool
    {
        $url = $distribution->getMetadata()->getDistributionUrl();

        $this->filesystem->mkdir(dirname($path));

        $httpDownloader = $this->composer->createHttpDownloader();
        $httpDownloader->copy($url, $path);

        $distribution->setSource($url);

        return true;
    }

    private function createDistributionLock(string $path): SharedLockInterface
    {
        $lock = $this->lockFactory->createLock('distribution.' . hash('sha256', $path), ttl: null);
        $lock->acquire(blocking: true);

        return $lock;
    }

    private function getFetchStrategy(Metadata $metadata): ?PackageFetchStrategy
    {
        $fetchStrategy = $metadata->getPackage()->getFetchStrategy();

        if (!$this->includeDevVersions && $metadata->getVersion()->isDevelopment()) {
            // Development versions are not supported by the configuration
            return null;
        } elseif ($this->buildDistributions && $fetchStrategy->isVcs()) {
            // Only build distributions if the fetch strategy is VCS (not source because it might not contain VCS data)
            return PackageFetchStrategy::Vcs;
        } elseif (
            $this->mirrorDistributions
            && ($fetchStrategy->isMirror() || $metadata->hasDistribution())
        ) {
            // Always mirror distributions if building from source is not supported and a distribution is available
            return PackageFetchStrategy::Mirror;
        }

        return null;
    }
}
