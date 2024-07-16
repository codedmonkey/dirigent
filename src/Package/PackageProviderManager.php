<?php

namespace CodedMonkey\Conductor\Package;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Package\Dumper\ArrayDumper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

readonly class PackageProviderManager
{
    private Filesystem $filesystem;
    private string $storagePath;

    public function __construct(
        #[Autowire(param: 'conductor.storage.path')]
        string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
        $this->storagePath = "$storagePath/provider";
    }

    public function dump(Package $package, array $composerPackages): void
    {
        $releasePackages = [];
        $devPackages = [];

        foreach ($composerPackages as $composerPackage) {
            if (!$composerPackage->isDev()) {
                $releasePackages[] = $composerPackage;
            } else {
                $devPackages[] = $composerPackage;
            }
        }

        $this->write($package->getName(), $releasePackages);
        $this->write($package->getName(), $devPackages, true);

        $package->setDumpedAt(new \DateTime());
    }

    public function exists(string $packageName): bool
    {
        return $this->filesystem->exists($this->path($packageName));
    }

    public function path(string $packageName): string
    {
        return "{$this->storagePath}/{$packageName}.json";
    }

    private function write(string $packageName, array $composerPackages, bool $development = false): void
    {

        $path = $this->path(!$development ? $packageName : "{$packageName}~dev");
        $data = $this->compile($packageName, $composerPackages);

        $this->filesystem->mkdir(dirname($path));

        $contents = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->filesystem->dumpFile($path, $contents);
    }

    private function compile(string $packageName, array $composerPackages): array
    {
        $data = array_map([new ArrayDumper(), 'dump'], $composerPackages);

        return [
            'minified' => 'composer/2.0',
            'packages' => [
                $packageName => MetadataMinifier::minify($data),
            ],
        ];
    }
}
