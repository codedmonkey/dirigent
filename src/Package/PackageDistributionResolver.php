<?php

namespace CodedMonkey\Conductor\Package;

use CodedMonkey\Conductor\Composer\HttpDownloaderOptionsFactory;
use CodedMonkey\Conductor\Doctrine\Entity\Version;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Util\HttpDownloader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

readonly class PackageDistributionResolver
{
    private Filesystem $filesystem;
    private string $storagePath;

    public function __construct(
        #[Autowire(param: 'conductor.storage.path')]
        string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
        $this->storagePath = "$storagePath/distribution";
    }

    public function exists(string $packageName, string $packageVersion, string $reference, string $type): bool
    {
        return $this->filesystem->exists($this->path($packageName, $packageVersion, $reference, $type));
    }

    public function path(string $packageName, string $packageVersion, string $reference, string $type): string
    {
        return "{$this->storagePath}/{$packageName}/{$packageVersion}-{$reference}.{$type}";
    }

    public function resolve(Version $version, string $reference, string $type): bool
    {
        $package = $version->getPackage();
        $packageName = $package->getName();
        $packageVersion = $version->getNormalizedVersion();

        if ($this->exists($packageName, $packageVersion, $reference, $type)) {
            return true;
        }

        if ($reference !== $version->getDistReference() || $type !== $version->getDistType()) {
            return false;
        }

        $distUrl = $version->getDistUrl();
        $path = $this->path($packageName, $packageVersion, $reference, $type);

        $this->filesystem->mkdir(dirname($path));

        $io = new NullIO();
        $config = Factory::createConfig();
        $io->loadConfiguration($config);
        $httpDownloader = new HttpDownloader($io, $config, HttpDownloaderOptionsFactory::getOptions());

        $httpDownloader->copy($distUrl, $path);

        return true;
    }
}
