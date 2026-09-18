<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Message\ResolveDistribution;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
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
        #[Autowire(param: 'dirigent.distributions.mirror')]
        private bool $mirrorDistributions,
        #[Autowire(param: 'dirigent.distributions.dev_versions')]
        private bool $includeDevVersions,
        #[Autowire(param: 'dirigent.storage.path')]
        string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
        $this->storagePath = Path::canonicalize("$storagePath/distribution");
    }

    public function exists(Metadata $metadata, string $type): bool
    {
        return $this->fileExists($this->path($metadata, $type));
    }

    public function path(Metadata $metadata, string $type): string
    {
        $packageName = explode('/', $metadata->getPackage()->getName(), 2)
            |> (fn ($x) => array_map($this->encodePathComponent(...), $x))
            |> (static fn ($x) => implode('/', $x));
        $versionName = $this->encodePathComponent($metadata->getNormalizedVersionName());
        $revision = $metadata->getRevision();
        $reference = $this->encodePathComponent($metadata->getReference());
        $type = $this->encodePathComponent($type);

        $path = Path::canonicalize("{$this->storagePath}/{$packageName}/{$versionName}-r{$revision}-{$reference}.{$type}");
        if (!Path::isBasePath($this->storagePath, $path) || $this->storagePath === $path) {
            throw new \RuntimeException('Distribution path is outside the configured storage directory.');
        }

        return $path;
    }

    public function remove(Distribution $distribution): void
    {
        $path = $this->path($distribution->getMetadata(), $distribution->getType());
        $lock = $this->createDistributionLock($path);

        try {
            $this->removeFile($path);
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

    public function resolve(Metadata $metadata, string $type, bool $async): bool
    {
        if (!$this->mirrorDistributions) {
            return false;
        }

        $path = $this->path($metadata, $type);

        if ($this->fileExists($path)) {
            return true;
        }

        if ($type !== $metadata->getDistributionType()) {
            return false;
        }

        if ($metadata->getVersion()->isDevelopment() && !$this->includeDevVersions) {
            return false;
        }

        if ($async) {
            // Resolve the distribution asynchronously so it's available in the future now that we know it was requested
            $this->messenger->dispatch(new ResolveDistribution($metadata->getId(), $type), [
                new TransportNamesStamp('async'),
            ]);

            // Still return false so the service resolving the distribution doesn't try to fetch it anyway
            return false;
        }

        $distributionUrl = $metadata->getDistributionUrl();
        $path = $this->path($metadata, $type);

        $lock = $this->createDistributionLock($path);

        try {
            if ($this->fileExists($path)) {
                return true;
            }

            if (null === $distribution = $this->distributionRepository->findOneByMetadataAndType($metadata, $type)) {
                $distribution = new Distribution($metadata, $type);
            }

            $this->filesystem->mkdir(dirname($path));

            $httpDownloader = $this->composer->createHttpDownloader();
            $httpDownloader->copy($distributionUrl, $path);

            $distribution->setSource($distributionUrl);
            $distribution->setResolvedAt();

            try {
                $this->distributionRepository->save($distribution, true);
            } catch (\Throwable $exception) {
                // Remove file immediately if saving the distribution to the database failed
                $this->removeFile($path);

                throw $exception;
            }

            return true;
        } finally {
            $lock->release();
        }
    }

    private function createDistributionLock(string $path): SharedLockInterface
    {
        $lock = $this->lockFactory->createLock('distribution.' . hash('sha256', $path), ttl: null);
        $lock->acquire(blocking: true);

        return $lock;
    }

    private function fileExists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }

    private function removeFile(string $path): void
    {
        $this->filesystem->remove($path);

        // Remove parent directories that aren't empty
        $directory = dirname($path);
        while ($this->storagePath !== $directory && Path::isBasePath($this->storagePath, $directory) && is_dir($directory) && !new \FilesystemIterator($directory)->valid()) {
            $this->filesystem->remove($directory);
            $directory = dirname($directory);
        }
    }

    private function encodePathComponent(string $component): string
    {
        $encodedComponent = rawurlencode($component);

        return match ($encodedComponent) {
            '' => '%00',
            '.' => '%2E',
            '..' => '%2E%2E',
            default => $encodedComponent,
        };
    }
}
