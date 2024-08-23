<?php

namespace CodedMonkey\Conductor\Message;

use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Package\PackageProviderManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DumpPackageProviderHandler
{
    public function __construct(
        private PackageRepository $packageRepository,
        private PackageProviderManager $providerManager,
    ) {
    }

    public function __invoke(DumpPackageProvider $message): void
    {
        $package = $this->packageRepository->find($message->packageId);

        $this->providerManager->dump($package);
    }
}
