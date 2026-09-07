<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::preRemove, entity: Version::class)]
readonly class VersionListener
{
    public function __construct(
        private PackageDistributionResolver $distributionResolver,
    ) {
    }

    public function preRemove(Version $version): void
    {
        $this->distributionResolver->removeVersion($version);
    }
}
