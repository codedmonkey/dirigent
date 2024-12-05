<?php

namespace CodedMonkey\Conductor\Message;

use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
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
            $package = $this->packageRepository->findOneByName($install['name']);

            if (!$package) {
                continue;
            }

            $version = $this->versionRepository->findOneBy([
                'package' => $package,
                'normalizedVersion' => $install['version'],
            ]);

            if (!$version) {
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
