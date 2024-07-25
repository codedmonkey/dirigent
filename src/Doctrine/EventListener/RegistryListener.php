<?php

namespace CodedMonkey\Conductor\Doctrine\EventListener;

use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(Events::prePersist, entity: Registry::class)]
#[AsEntityListener(Events::preRemove, entity: Registry::class)]
class RegistryListener
{
    public function prePersist(Registry $registry, PrePersistEventArgs $event): void
    {
        $repository = $event->getObjectManager()->getRepository(Registry::class);

        $registry->setMirroringPriority($repository->count([]) + 1);
    }

    public function preRemove(Registry $registry, PreRemoveEventArgs $event): void
    {
        $repository = $event->getObjectManager()->getRepository(Registry::class);
        $registries = $repository->findAll();

        foreach ($registries as $existingRegistry) {
            if ($existingRegistry->getMirroringPriority() > $registry->getMirroringPriority()) {
                $existingRegistry->setMirroringPriority($existingRegistry->getMirroringPriority() - 1);

                $repository->save($existingRegistry);
            }
        }
    }
}
