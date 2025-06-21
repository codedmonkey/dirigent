<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdatePackageLinksHandler
{
    public function __construct(
        private PackageRepository $packageRepository,
        private VersionRepository $versionRepository,
    ) {
    }

    public function __invoke(UpdatePackageLinks $message): void
    {
        $package = $this->packageRepository->find($message->packageId);
        $version = $this->versionRepository->findOneByNormalizedVersion($package, $message->versionName);

        $this->packageRepository->updatePackageLinks($package, $version);
    }
}
