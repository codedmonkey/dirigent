<?php

namespace CodedMonkey\Conductor\Message;

use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Doctrine\Repository\VersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class TrackDownloadsHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PackageRepository $packageRepository,
        private VersionRepository $versionRepository,
    ) {
    }

    public function __invoke(TrackDownloads $message): void
    {
        $dataKey = (new \DateTime())->format('Ymd');

        foreach ($message->downloads as $download) {
            $package = $this->packageRepository->findOneByName($download['name']);

            if (!$package) {
                continue;
            }

            $version = $this->versionRepository->findOneBy([
                'package' => $package,
                'normalizedVersion' => $download['version']]
            );

            if (!$version) {
                continue;
            }

            $package->getDownloads()->increase($dataKey);
            $version->getDownloads()->increase($dataKey);

            $this->entityManager->persist($package);
            $this->entityManager->persist($version);
        }

        $this->entityManager->flush();
    }
}
