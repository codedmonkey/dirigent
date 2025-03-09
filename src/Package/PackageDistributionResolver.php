<?php

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Composer\ConfigFactory;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Composer\IO\NullIO;
use Composer\Pcre\Preg;
use Composer\Util\Filesystem as ComposerFilesystem;
use Composer\Util\Git as GitUtility;
use Composer\Util\ProcessExecutor;
use Composer\Util\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

readonly class PackageDistributionResolver
{
    private Filesystem $filesystem;
    private string $distributionStoragePath;

    public function __construct(
        private ComposerClient $composer,
        #[Autowire(param: 'dirigent.storage.path')]
        string $storagePath,
        #[Autowire(param: 'dirigent.dist_builder.enabled')]
        private bool $buildDistributions,
        #[Autowire(param: 'dirigent.dist_builder.dev_packages')]
        private bool $buildDevDistributions,
    ) {
        $this->filesystem = new Filesystem();
        $this->distributionStoragePath = "$storagePath/distribution";
    }

    public function exists(string $packageName, string $packageVersion, string $reference, string $type): bool
    {
        return $this->filesystem->exists($this->path($packageName, $packageVersion, $reference, $type));
    }

    public function path(string $packageName, string $packageVersion, string $reference, string $type): string
    {
        return "$this->distributionStoragePath/$packageName/$packageVersion-$reference.$type";
    }

    public function resolve(Version $version, string $reference, string $type): bool
    {
        $package = $version->getPackage();
        $packageName = $package->getName();
        $packageVersion = $version->getNormalizedVersion();

        if ($this->exists($packageName, $packageVersion, $reference, $type)) {
            return true;
        }

        if (
            null === $version->getDist()
            && $this->buildDistributions
            && (!$version->isDevelopment() || $this->buildDevDistributions)
        ) {
            return $this->build($version, $reference, $type);
        } elseif (null !== $version->getDist()) {
            return $this->mirror($version, $reference, $type);
        }

        return false;
    }

    private function build(Version $version, string $reference, string $type): bool
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

        $io = new NullIO();
        $config = ConfigFactory::createForVcsRepository($repositoryUrl, $package->getRepositoryCredentials());

        $gitUtility = new GitUtility(
            $io,
            $config,
            $process = new ProcessExecutor($io),
            new ComposerFilesystem($process),
        );

        $cacheRepositoryName = Preg::replace('{[^a-z0-9.]}i', '-', Url::sanitize($repositoryUrl));
        $cachePath = $config->get('cache-vcs-dir') . '/' . $cacheRepositoryName . '/';

        $this->filesystem->mkdir(dirname($distributionPath));

        $gitUtility->runCommands([
            ['git', 'archive', '--format=zip', "--output=$distributionPath", $reference],
        ], $repositoryUrl, $cachePath);

        return true;
    }

    private function mirror(Version $version, string $reference, string $type): bool
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
