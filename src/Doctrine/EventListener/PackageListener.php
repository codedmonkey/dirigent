<?php

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::preRemove, entity: Package::class)]
class PackageListener
{
    public function preRemove(Package $package, PreRemoveEventArgs $event): void
    {
        /** @var PackageRepository $repository */
        $repository = $event->getObjectManager()->getRepository(Package::class);

        // Delete existing package links
        $repository->deletePackageLinks($package);
    }
}
