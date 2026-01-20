<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Doctrine\Repository\VersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class TrackInstallationsHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PackageRepository $packageRepository,
        private VersionRepository $versionRepository,
    ) {
    }

    public function __invoke(TrackInstallations $message): void
    {
        foreach ($message->installations as $install) {
            if (!$package = $this->packageRepository->findOneByName($install['name'])) {
                continue;
            }

            if (!$version = $this->versionRepository->findOneByNormalizedVersionName($package, $install['version'])) {
                continue;
            }

            $package->getInstallations()->increase($message->installedAt);
            $version->getInstallations()->increase($message->installedAt);

            $this->entityManager->persist($package);
            $this->entityManager->persist($version);
        }

        $this->entityManager->flush();
    }
}
