<?php

declare(strict_types=1);

namespace CodedMonkey\Dirigent\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Entity\Distribution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Distribution>
 *
 * @method Distribution|null find($id, $lockMode = null, $lockVersion = null)
 * @method Distribution[]    findAll()
 * @method Distribution[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Distribution|null findOneBy(array $criteria, array $orderBy = null)
 */
class DistributionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Distribution::class);
    }

    public function save(Distribution $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Distribution $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
