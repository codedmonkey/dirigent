<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
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

    public function resolve(Metadata $metadata, string $type): bool
    {
        $package = $metadata->getPackage();
        $packageName = $package->getName();
        $versionName = $metadata->getNormalizedVersionName();
        $reference = $metadata->getDistributionReference();

        if ($this->exists($packageName, $versionName, $reference, $type)) {
            return true;
        }

        if ($reference !== $metadata->getDistributionReference() || $type !== $metadata->getDistributionType()) {
            return false;
        }

        $distributionUrl = $metadata->getDistributionUrl();
        $path = $this->path($packageName, $versionName, $reference, $type);

        $this->filesystem->mkdir(dirname($path));

        $httpDownloader = $this->composer->createHttpDownloader();
        $httpDownloader->copy($distributionUrl, $path);

        return true;
    }
}
