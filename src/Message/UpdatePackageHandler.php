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
    use PackageHandlerTrait;

    private ?\DateInterval $dynamicUpdateDelay;

    public function __construct(
        private PackageRepository $packageRepository,
        private PackageMetadataResolver $metadataResolver,
        #[Autowire(param: 'dirigent.packages.dynamic_update_delay')]
        ?string $dynamicUpdateDelay,
    ) {
        $this->dynamicUpdateDelay = $dynamicUpdateDelay ? new \DateInterval($dynamicUpdateDelay) : null;
    }

    public function __invoke(UpdatePackage $message): void
    {
        $package = $this->getPackage($this->packageRepository, $message->packageId);

        if ($message->scheduled && !$package->isUpdateScheduled()) {
            // Package was already updated between being scheduled and now,
            // so stop the update to prevent excessive requests
            return;
        }

        if ($message->source->isDynamic() && $this->isFreshDynamicUpdate($package)) {
            // Package was recently updated
            return;
        }

        $this->metadataResolver->resolve($package);

        $package->setUpdateScheduledAt(null);

        $this->packageRepository->save($package, true);
    }

    private function isFreshDynamicUpdate(Package $package): bool
    {
        // If the package was never updated, it's always stale
        if (null === $lastUpdatedAt = $package->getUpdatedAt()) {
            return false;
        }

        // Override update delay from registry
        $dynamicUpdateDelay = $package->getMirrorRegistry()?->getDynamicUpdateDelay() ?? $this->dynamicUpdateDelay;

        $freshFrom = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
        $freshFrom = $freshFrom->sub($dynamicUpdateDelay ?? new \DateInterval('PT0'));

        // Check if the package was updated recently, and therefore fresh
        return $freshFrom < $lastUpdatedAt;
    }
}
