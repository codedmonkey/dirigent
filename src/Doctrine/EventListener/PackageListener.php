<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Repository\PackageRepository;
use CodedMonkey\Dirigent\Message\RemovePackageProvider;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(Events::preRemove, entity: Package::class)]
readonly class PackageListener
{
    public function __construct(
        private MessageBusInterface $messenger,
    ) {
    }

    public function preRemove(Package $package, PreRemoveEventArgs $event): void
    {
        /** @var PackageRepository $repository */
        $repository = $event->getObjectManager()->getRepository(Package::class);

        // Delete existing package links
        $repository->deletePackageLinks($package);

        // Remove package provider
        $this->messenger->dispatch(new RemovePackageProvider($package->getId()));
    }
}
