<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::preRemove, entity: Distribution::class)]
readonly class DistributionListener
{
    public function __construct(
        private PackageDistributionResolver $distributionResolver,
    ) {
    }

    public function preRemove(Distribution $distribution): void
    {
        $this->distributionResolver->remove($distribution);
    }
}
