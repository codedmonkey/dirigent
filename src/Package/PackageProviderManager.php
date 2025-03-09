<?php

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use Composer\MetadataMinifier\MetadataMinifier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class PackageProviderManager
{
    private Filesystem $filesystem;
    private string $storagePath;

    public function __construct(
        private RouterInterface $router,
        #[Autowire(param: 'dirigent.dist_builder.enabled')]
        private bool $buildDistributions,
        #[Autowire(param: 'dirigent.dist_builder.dev_packages')]
        private bool $buildDevDistributions,
        #[Autowire(param: 'dirigent.storage.path')]
        string $storagePath,
    ) {
        $this->filesystem = new Filesystem();
        $this->storagePath = "$storagePath/provider";
    }

    public function dump(Package $package): void
    {
        $releasePackages = [];
        $devPackages = [];

        $versions = $package->getVersions()->toArray();
        usort($versions, [Package::class, 'sortVersions']);

        foreach ($versions as $version) {
            $versionData = $version->toComposerArray();

            if (
                null === $version->getDist()
                && $this->buildDistributions
                && (!$version->isDevelopment() || $this->buildDevDistributions)
            ) {
                $distributionUrl = $this->router->generate('api_package_distribution', [
                    'packageName' => $package->getName(),
                    'packageVersion' => $version->getNormalizedVersion(),
                    'reference' => $version->getSourceReference(),
                    'type' => 'zip',
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $versionData['dist'] = [
                    'type' => 'zip',
                    'url' => $distributionUrl,
                    'reference' => $version->getSourceReference(),
                    'shasum' => '',
                ];
            }

            if (!$version->isDevelopment()) {
                $releasePackages[] = $versionData;
            } else {
                $devPackages[] = $versionData;
            }
        }

        $this->write($package->getName(), $releasePackages);
        $this->write($package->getName(), $devPackages, true);

        $package->setDumpedAt(new \DateTimeImmutable());
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
        return [
            'minified' => 'composer/2.0',
            'packages' => [
                $packageName => MetadataMinifier::minify($composerPackages),
            ],
        ];
    }
}
