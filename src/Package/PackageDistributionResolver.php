<?php

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

readonly class PackageDistributionResolver
{
    private Filesystem $filesystem;
    private string $storagePath;

    public function __construct(
        private ComposerClient $composer,
        #[Autowire(param: 'dirigent.storage.path')]
        string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
        $this->storagePath = "$storagePath/distribution";
    }

    public function exists(string $packageName, string $versionName, string $reference, string $type): bool
    {
        return $this->filesystem->exists($this->path($packageName, $versionName, $reference, $type));
    }

    public function path(string $packageName, string $versionName, string $reference, string $type): string
    {
        return "{$this->storagePath}/{$packageName}/{$versionName}-{$reference}.{$type}";
    }

    public function resolve(Version $version, string $reference, string $type): bool
    {
        $package = $version->getPackage();
        $packageName = $package->getName();
        $versionName = $version->getNormalizedName();

        if ($this->exists($packageName, $versionName, $reference, $type)) {
            return true;
        }

        $metadata = $version->getCurrentMetadata();

        if ($reference !== $metadata->getDistReference() || $type !== $metadata->getDistType()) {
            return false;
        }

        $distUrl = $metadata->getDistUrl();
        $path = $this->path($packageName, $versionName, $reference, $type);

        $this->filesystem->mkdir(dirname($path));

        $httpDownloader = $this->composer->createHttpDownloader();
        $httpDownloader->copy($distUrl, $path);

        return true;
    }
}
