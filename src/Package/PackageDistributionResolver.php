<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Composer\ConfigFactory;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
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

    public function resolve(Metadata $metadata, string $type, bool $async): bool
    {
        $path = $this->path($metadata, $type);

        if ($this->fileExists($path)) {
            return true;
        }

        if (null === $strategy = $this->getFetchStrategy($metadata)) {
            return false;
        }

        $currentType = $strategy->isMirror() ? $metadata->getDistributionType() : 'zip';

        if ($type !== $currentType) {
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

        $lock = $this->createDistributionLock($path);

        try {
            if ($this->fileExists($path)) {
                return true;
            }

            if (null === $distribution = $this->distributionRepository->findOneByMetadataAndType($metadata, $type)) {
                $distribution = new Distribution($metadata, $type);
            }

            $result = false;

            // Build the distribution from VCS source
            if ($strategy->isVcs()) {
                $result = $this->build($distribution);

                if (
                    !$result
                    && $this->mirrorDistributions
                    && $metadata->hasDistribution()
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

                try {
                    $this->distributionRepository->save($distribution, true);
                } catch (\Throwable $exception) {
                    // Remove file immediately if saving the distribution to the database failed
                    $this->removePath($path);

                    throw $exception;
                }
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
        $distributionPath = $this->path($metadata, $reference);

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

    /**
     * @phpstan-impure
     */
    private function fileExists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }

    private function removePath(string $path): void
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
