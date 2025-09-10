<?php

namespace CodedMonkey\Dirigent\Message;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Package\PackageMetadataResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdatePackageHandler
{
    private \DateInterval $updateDelay;

    public function __construct(
        private PackageRepository $packageRepository,
        private PackageMetadataResolver $metadataResolver,
        #[Autowire(param: 'dirigent.packages.dynamic_updates')]
        private bool $dynamicUpdatesEnabled,
        #[Autowire(param: 'dirigent.packages.dynamic_update_delay')]
        string $updateDelay,
    ) {
        $this->updateDelay = new \DateInterval($updateDelay);
    }

    public function __invoke(UpdatePackage $message): void
    {
        if ($message->source->isDynamic() && !$this->dynamicUpdatesEnabled) {
            // Dynamic updates are disabled
            return;
        }

        $package = $this->packageRepository->find($message->packageId);

        if ($message->scheduled && null === $package->getUpdateScheduledAt()) {
            // Package was already updated between being scheduled and now,
            // so stop the update to prevent excessive requests
            return;
        }

        if (!$message->source->isManual() && $this->isFresh($package)) {
            // Package was recently updated
            return;
        }

        $this->metadataResolver->resolve($package);

        $package->setUpdateScheduledAt(null);

        $this->packageRepository->save($package, true);
    }

    private function isFresh(Package $package): bool
    {
        if (null === $lastUpdatedAt = $package->getUpdatedAt()) {
            return false;
        }

        $updateDelay = $package->getMirrorRegistry()?->getDynamicUpdateDelay() ?? $this->updateDelay;

        $now = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
        $before = $now->sub($updateDelay);

        return $before->getTimestamp() < $lastUpdatedAt->getTimestamp();
    }
}
