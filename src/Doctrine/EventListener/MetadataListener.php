<?php

namespace CodedMonkey\Dirigent\Doctrine\EventListener;

use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Repository\MetadataRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::prePersist, entity: Metadata::class)]
class MetadataListener
{
    public function prePersist(Metadata $metadata, PrePersistEventArgs $event): void
    {
        /** @var MetadataRepository $repository */
        $repository = $event->getObjectManager()->getRepository(Metadata::class);

        $revision = $repository->getNextRevision($metadata);
        $metadata->setRevision($revision);
    }
}
