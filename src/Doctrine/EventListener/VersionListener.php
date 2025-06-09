<?php

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Package\PackageDistributionResolver;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::postPersist, entity: Version::class)]
#[AsEntityListener(Events::postUpdate, entity: Version::class)]
readonly class VersionListener
{
    public function __construct(
        private PackageDistributionResolver $distributionResolver,
    ) {
    }

    public function postPersist(Version $version): void
    {
        $this->distributionResolver->schedule($version);
    }

    public function postUpdate(Version $version, PostUpdateEventArgs $event): void
    {
        // Only resolve the distribution if the source has changed
        $changedFields = $event->getObjectManager()->getUnitOfWork()->getEntityChangeSet($version);
        if (isset($changedFields['source'])) {
            $this->distributionResolver->schedule($version);
        }
    }
}
