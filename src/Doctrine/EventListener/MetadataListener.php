<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Package\DistributionRemovalScheduler;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::preRemove, entity: Metadata::class)]
readonly class MetadataListener
{
    public function __construct(
        private DistributionRemovalScheduler $removalScheduler,
    ) {
    }

    public function preRemove(Metadata $metadata): void
    {
        $this->removalScheduler->scheduleMetadata($metadata);
    }
}
