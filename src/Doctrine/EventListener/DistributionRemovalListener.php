<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Distribution\DistributionRemovalScheduler;
use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsDoctrineListener(Events::onClear)]
#[AsDoctrineListener(Events::postFlush)]
#[AsEntityListener(Events::preRemove, method: 'preRemoveDistribution', entity: Distribution::class)]
#[AsEntityListener(Events::preRemove, method: 'preRemoveMetadata', entity: Metadata::class)]
#[AsEntityListener(Events::preRemove, method: 'preRemovePackage', entity: Package::class)]
#[AsEntityListener(Events::preRemove, method: 'preRemoveVersion', entity: Version::class)]
readonly class DistributionRemovalListener
{
    public function __construct(
        private DistributionRemovalScheduler $removalScheduler,
    ) {
    }

    public function onClear(): void
    {
        $this->removalScheduler->clear();
    }

    public function postFlush(): void
    {
        $this->removalScheduler->dispatch();
    }

    public function preRemoveDistribution(Distribution $distribution): void
    {
        $this->removalScheduler->schedule($distribution);
    }

    public function preRemoveMetadata(Metadata $metadata): void
    {
        $this->removalScheduler->scheduleMetadata($metadata);
    }

    public function preRemovePackage(Package $package): void
    {
        $this->removalScheduler->schedulePackage($package);
    }

    public function preRemoveVersion(Version $version): void
    {
        $this->removalScheduler->scheduleVersion($version);
    }
}
