<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Package\DistributionRemovalScheduler;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::preRemove, entity: Distribution::class)]
readonly class DistributionListener
{
    public function __construct(
        private DistributionRemovalScheduler $removalScheduler,
    ) {
    }

    public function preRemove(Distribution $distribution): void
    {
        $this->removalScheduler->schedule($distribution);
    }
}
