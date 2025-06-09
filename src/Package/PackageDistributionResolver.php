<?php

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Composer\ConfigFactory;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\PackageFetchStrategy;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Message\ResolveDistribution;
use Composer\IO\NullIO;
use Composer\Pcre\Preg;
use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Git as GitUtility;
use Composer\Util\ProcessExecutor;
use Composer\Util\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

readonly class PackageDistributionResolver
{
    private Filesystem $filesystem;
    private string $distributionStoragePath;

    public function __construct(
        private MessageBusInterface $messenger,
        private ComposerClient $composer,
        private DistributionRepository $distributionRepository,
        #[Autowire(param: 'dirigent.distributions.build')]
        private bool $buildDistributions,
        #[Autowire(param: 'dirigent.distributions.mirror')]
        private bool $mirrorDistributions,
        #[Autowire(param: 'dirigent.distributions.dev_versions')]
        private bool $resolveDevVersions,
        #[Autowire(param: 'dirigent.storage.path')]
        string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
        $this->distributionStoragePath = "$storagePath/distribution";
    }

    public function exists(string $packageName, string $versionName, ?string $reference, ?string $type): bool
    {
        return null !== $reference && null !== $type && $this->filesystem->exists($this->path($packageName, $versionName, $reference, $type));
    }

    public function path(string $packageName, string $versionName, string $reference, string $type): string
    {
        return "$this->distributionStoragePath/$packageName/$versionName-$reference.$type";
    }

    public function schedule(Version $version, bool $onlyAutomatic = true): void
    {
        $package = $version->getPackage();

        if (
            ($onlyAutomatic && !$package->getDistributionStrategy()->isAutomatic())
            || null === $this->getFetchStrategy($version)
        ) {
            return;
        }

        $this->messenger->dispatch(new ResolveDistribution($version->getId()), [
            new TransportNamesStamp('async'),
        ]);
    }

    public function resolve(Version $version, ?string $reference, ?string $type, bool $async): bool
    {
        $package = $version->getPackage();
        $packageName = $package->getName();
        $versionName = $version->getNormalizedVersion();

        if ($this->exists($packageName, $versionName, $reference, $type)) {
            return true;
        }

        $strategy = $this->getFetchStrategy($version);

        if (null === $strategy) {
            return false;
        }

        $currentReference = $strategy->isMirror() ? $version->getDistReference() : $version->getSourceReference();
        $currentType = $strategy->isMirror() ? $version->getDistType() : 'zip';

        if (null === $reference || null === $type) {
            // Fall back to the current reference and type if not provided
            $reference = $currentReference;
            $type = $currentType;

            if (null === $reference || null === $type) {
                return false;
            }
        }

        // Only support the current references for now
        if ($reference !== $currentReference || $type !== $currentType) {
            return false;
        }

        if ($async) {
            // Resolve the distribution asynchronously so it's available in the future now that we know it was requested
            $this->schedule($version, onlyAutomatic: false);

            // Still return false so the service resolving the distribution doesn't try to fetch it anyway
            return false;
        }

        $distribution = $this->distributionRepository->findOneBy(['version' => $version, 'reference' => $reference, 'type' => $type]);
        if (null === $distribution) {
            $distribution = new Distribution();
            $distribution->setVersion($version);
            $distribution->setReference($reference);
            $distribution->setType($type);
            $distribution->setReleasedAt($version->getReleasedAt());
        }

        $result = false;

        // Build the distribution from source
        if ($strategy->isVcs()) {
            $result = $this->build($distribution);

            if (
                !$result
                && $this->mirrorDistributions
                && null !== $version->getDist()
                && $version->getDistReference() === $reference
                && $version->getDistType() === $type
            ) {
                // Mirror the distribution if it failed to build from source
                // todo log fallback
                $strategy = PackageFetchStrategy::Mirror;
            }
        }

        if ($strategy->isMirror()) {
            $result = $this->mirror($distribution);
        }

        if ($result) {
            $distribution->setResolvedAt();
            $this->distributionRepository->save($distribution, true);
        }

        return $result;
    }

    private function getFetchStrategy(Version $version): ?PackageFetchStrategy
    {
        $package = $version->getPackage();

        if ($version->isDevelopment() && !$this->resolveDevVersions) {
            return null;
        } elseif ($package->getFetchStrategy()->isVcs() && $this->buildDistributions) {
            return PackageFetchStrategy::Vcs;
        } elseif (
            $this->mirrorDistributions
            && (
                $package->getFetchStrategy()->isMirror()
                || null !== $version->getDist()
            )
        ) {
            return PackageFetchStrategy::Mirror;
        }

        return null;
    }

    private function build(Distribution $distribution): bool
    {
        $version = $distribution->getVersion();
        $reference = $distribution->getReference();

        $package = $version->getPackage();
        $repositoryUrl = $package->getRepositoryUrl();
        $distributionPath = $this->path($package->getName(), $version->getNormalizedVersion(), $reference, 'zip');

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

    private function mirror(Distribution $distribution): bool
    {
        $version = $distribution->getVersion();
        $package = $version->getPackage();

        $distributionUrl = $version->getDistUrl();
        $distributionPath = $this->path(
            $package->getName(),
            $version->getNormalizedVersion(),
            $distribution->getReference(),
            $distribution->getType(),
        );

        $this->filesystem->mkdir(dirname($distributionPath));

        $httpDownloader = $this->composer->createHttpDownloader();
        $httpDownloader->copy($distributionUrl, $distributionPath);

        $distribution->setSource($distributionUrl);

        return true;
    }
}
