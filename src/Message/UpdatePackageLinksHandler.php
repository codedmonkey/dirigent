<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdatePackageLinksHandler
{
    use PackageHandlerTrait;

    public function __construct(
        private PackageRepository $packageRepository,
        private VersionRepository $versionRepository,
    ) {
    }

    public function __invoke(UpdatePackageLinks $message): void
    {
        $package = $this->getPackage($this->packageRepository, $message->packageId);
        $version = $this->versionRepository->findOneByNormalizedVersion($package, $message->versionName);

        $this->packageRepository->updatePackageLinks($package, $version);
    }
}
