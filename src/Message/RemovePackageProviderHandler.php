<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Package\PackageProviderManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class RemovePackageProviderHandler
{
    use PackageHandlerTrait;

    public function __construct(
        private PackageRepository $packageRepository,
        private PackageProviderManager $providerManager,
    ) {
    }

    public function __invoke(RemovePackageProvider $message): void
    {
        $package = $this->getPackage($this->packageRepository, $message->packageId);

        $this->providerManager->remove($package);
    }
}
