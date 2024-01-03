<?php

namespace CodedMonkey\Conductor\MessageHandler;

use CodedMonkey\Conductor\Conductor;
use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Message\SavePackage;
use Composer\Package\Loader\ArrayLoader;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SavePackageHandler
{
    public function __construct(
        private readonly Conductor $conductor,
        private readonly PackageRepository $packageRepository,
    ) {
    }

    public function __invoke(SavePackage $message): void
    {
        if (null === $package = $this->packageRepository->findOneBy(['name' => $message->packageName])) {
            $package = new Package();
        }

        $metadata = $this->conductor->getPackageMetadata($message->packageName);

        $composerPackages = (new ArrayLoader())->loadPackages($metadata['versions']);

        $package->name = $message->packageName;
        $package->description = $composerPackages[0]->getDescription() ?? null;

        $this->packageRepository->save($package, true);
    }
}
