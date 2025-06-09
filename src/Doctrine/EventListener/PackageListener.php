<?php

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::postUpdate, entity: Package::class)]
#[AsEntityListener(Events::preRemove, entity: Package::class)]
readonly class PackageListener
{
    public function __construct(
        private PackageDistributionResolver $distributionResolver,
    ) {
    }

    public function postUpdate(Package $package, PostUpdateEventArgs $event): void
    {
        $changedFields = $event->getObjectManager()->getUnitOfWork()->getEntityChangeSet($package);

        // Only resolve the distributions if distribution strategy has changed to automatic
        if (isset($changedFields['distributionStrategy']) && $package->getDistributionStrategy()->isAutomatic()) {
            foreach ($package->getVersions() as $version) {
                $this->distributionResolver->schedule($version);
            }
        }
    }

    public function preRemove(Package $package, PreRemoveEventArgs $event): void
    {
        /** @var PackageRepository $repository */
        $repository = $event->getObjectManager()->getRepository(Package::class);

        // Delete existing package links
        $repository->deletePackageLinks($package);
    }
}
