<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Distribution;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

readonly class DistributionFilesystem
{
    private Filesystem $filesystem;
    private string $storagePath;

    public function __construct(
        private LockFactory $lockFactory,
        #[Autowire(param: 'dirigent.storage.path')]
        string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
        $this->storagePath = Path::canonicalize("$storagePath/distribution");
    }

    /**
     * Create the directory for the given distribution path.
     */
    public function createDirectory(string $path): void
    {
        $this->filesystem->mkdir(dirname($path));
    }

    public function createDistributionLock(string $path): SharedLockInterface
    {
        $lock = $this->lockFactory->createLock('distribution.' . hash('sha256', $path), ttl: null);
        $lock->acquire(blocking: true);

        return $lock;
    }

    /**
     * @phpstan-impure Method can return a different result after lock expired.
     */
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

    /**
     * Resolve the path to the file from the distribution storage path root.
     */
    public function relativePath(Distribution $distribution): string
    {
        return Path::makeRelative(
            $this->path($distribution->getMetadata(), $distribution->getType()),
            $this->storagePath,
        );
    }

    public function removeFile(string $relativePath): void
    {
        $path = Path::canonicalize("{$this->storagePath}/$relativePath");
        if (!Path::isBasePath($this->storagePath, $path) || $this->storagePath === $path) {
            throw new \RuntimeException('Distribution path is outside the configured storage directory.');
        }

        $lock = $this->createDistributionLock($path);

        try {
            $this->removePath($path);
        } finally {
            $lock->release();
        }
    }

    /**
     * Only use if you acquired a lock for the distribution.
     */
    public function removePath(string $path): void
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

    private function fileExists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }
}
