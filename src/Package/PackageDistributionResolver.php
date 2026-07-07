<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Package;

use CodedMonkey\Dirigent\Composer\ComposerClient;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Repository\DistributionRepository;
use CodedMonkey\Dirigent\Message\ResolveDistribution;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
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
        #[Autowire(param: 'dirigent.distributions.dev_versions')]
        private bool $includeDevVersions,
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

    public function resolve(Metadata $metadata, string $type, bool $async): bool
    {
        $package = $metadata->getPackage();
        $packageName = $package->getName();
        $versionName = $metadata->getNormalizedVersionName();
        $reference = $metadata->getDistReference();

        if ($this->exists($packageName, $versionName, $reference, $type)) {
            return true;
        }

        if ($reference !== $metadata->getDistReference() || $type !== $metadata->getDistType()) {
            return false;
        }

        if ($metadata->getVersion()->isDevelopment() && !$this->includeDevVersions) {
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

        $distribution = $this->distributionRepository->findOneBy(['metadata' => $metadata, 'type' => $type]);
        if (null === $distribution) {
            $distribution = new Distribution($metadata);
            $distribution->setReference($reference);
            $distribution->setType($type);
            $distribution->setReleasedAt($metadata->getReleasedAt());
        }

        $distUrl = $metadata->getDistUrl();
        $path = $this->path($packageName, $versionName, $reference, $type);

        $this->filesystem->mkdir(dirname($path));

        $httpDownloader = $this->composer->createHttpDownloader();
        $httpDownloader->copy($distUrl, $path);

        $this->distributionRepository->save($distribution);

        return true;
    }
}
