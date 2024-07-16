<?php

namespace CodedMonkey\Conductor\Message;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use CodedMonkey\Conductor\Doctrine\Repository\PackageRepository;
use CodedMonkey\Conductor\Package\PackageMetadataResolver;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdatePackageHandler
{
    public function __construct(
        private PackageRepository $packageRepository,
        private PackageMetadataResolver $metadataResolver,
    ) {
    }

    public function __invoke(UpdatePackage $message): void
    {
        $package = $this->packageRepository->find($message->packageId);

        if ($message->scheduled && null === $package->getUpdateScheduledAt()) {
            // Package was already updated between being scheduled and now
            return;
        }

        if (!$message->forceRefresh && $this->isFresh($package)) {
            // Package was recently updated
            return;
        }

        $this->metadataResolver->resolve($package);

        $package->setUpdateScheduledAt(null);

        $this->packageRepository->save($package, true);
    }

    private function isFresh(Package $package): bool
    {
        $now = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));

        if (null !== $lastCrawledAt = $package->getCrawledAt()) {
            $interval = $now->getTimestamp() - $lastCrawledAt->getTimestamp();
            $delay = 3600;

            if ($interval < $delay) {
                return true;
            }
        }

        return false;
    }
}
