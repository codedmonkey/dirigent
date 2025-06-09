<?php

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Composer\ConfigFactory;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Message\ResolveDistribution;
use Composer\IO\NullIO;
use Composer\Pcre\Preg;
use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Git as GitUtility;
use Composer\Util\ProcessExecutor;
use Composer\Util\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

readonly class PackageDistributionResolver
{
    private Filesystem $filesystem;
    private string $distributionStoragePath;

    public function __construct(
        private MessageBusInterface $messenger,
        private ComposerClient $composer,
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

    public function resolve(Version $version, ?string $reference, ?string $type, bool $async): bool
    {
        $package = $version->getPackage();
        $packageName = $package->getName();
        $versionName = $version->getNormalizedVersion();

        if ($this->exists($packageName, $versionName, $reference, $type)) {
            return true;
        }

        if ($version->isDevelopment() && !$this->includeDevVersions) {
            return false;
        }

        if ($async) {
            // Resolve the distribution asynchronously so it's available in the future now that we know it was requested
            $message = Envelope::wrap(new ResolveDistribution($version->getId(), $reference, $type))
                ->with(new TransportNamesStamp('async'));
            $this->messenger->dispatch($message);

            // Still return false so the service resolving the distribution doesn't try to fetch it anyway
            return false;
        }

        $result = false;

        // Build the distribution from source
        if (
            $this->buildDistributions
            && $version->getPackage()->getFetchStrategy()->isVcs()
        ) {
            $result = $this->build($version, $reference ?? $version->getSourceReference(), $type ?? $version->getSourceType());
        }

        // Mirror the distribution from a remote source if it can't be built from source
        $distributionAvailable = null !== $version->getDist();
        if (
            !$result
            && $this->mirrorDistributions
            && $distributionAvailable
        ) {
            $result = $this->mirror($version, $reference ?? $version->getDistReference(), $type ?? $version->getDistType());
        }

        return $result;
    }

    private function build(Version $version, ?string $reference, ?string $type): bool
    {
        // Skip building of outdated references for now
        if ($reference !== $version->getSourceReference()) {
            return false;
        }

        // Only provide .zip support for now
        if ('zip' !== $type) {
            return false;
        }

        $package = $version->getPackage();
        $repositoryUrl = $package->getRepositoryUrl();
        $distributionPath = $this->path($package->getName(), $version->getNormalizedVersion(), $reference, $type);

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

        return true;
    }

    private function mirror(Version $version, ?string $reference, ?string $type): bool
    {
        // Skip mirroring of outdated references for now
        if ($reference !== $version->getDistReference()) {
            return false;
        }

        // The distribution type must match the origin format
        if ($type !== $version->getDistType()) {
            return false;
        }

        $package = $version->getPackage();
        $distributionUrl = $version->getDistUrl();
        $distributionPath = $this->path($package->getName(), $version->getNormalizedVersion(), $reference, $type);

        $this->filesystem->mkdir(dirname($distributionPath));

        $httpDownloader = $this->composer->createHttpDownloader();
        $httpDownloader->copy($distributionUrl, $distributionPath);

        return true;
    }
}
