<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;

trait EntityManagerTestTrait
{
    /**
     * Find a single entity by its ID or an array of criteria.
     *
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T|null
     */
    protected function findEntity(string $className, array|int $criteria): ?object
    {
        if (is_array($criteria)) {
            return $this->getService(EntityManagerInterface::class)->getRepository($className)->findOneBy($criteria);
        }

        return $this->getService(EntityManagerInterface::class)->find($className, $criteria);
    }

    /**
     * Persist and flush all given entities.
     *
     * @param object ...$entities
     */
    protected function persistEntities(...$entities): void
    {
        $entityManager = $this->getService(EntityManagerInterface::class);

        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();
    }

    protected function clearEntities(): void
    {
        $this->getService(EntityManagerInterface::class)->clear();
    }
}
