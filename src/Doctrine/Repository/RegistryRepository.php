<?php

namespace CodedMonkey\Conductor\Doctrine\Repository;

use CodedMonkey\Conductor\Doctrine\Entity\Registry;
use CodedMonkey\Conductor\Doctrine\Entity\RegistryPackageMirroring;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registry>
 *
 * @method Registry|null find($id, $lockMode = null, $lockVersion = null)
 * @method Registry[]    findAll()
 * @method Registry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Registry|null findOneBy(array $criteria, array $orderBy = null)
 */
class RegistryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registry::class);
    }

    public function createPackageMirroringQueryBuilder(RegistryPackageMirroring|string $packageMirroring): QueryBuilder
    {
        $builder = $this->createQueryBuilder('registry');

        if (is_string($packageMirroring)) {
            $packageMirroring = RegistryPackageMirroring::from($packageMirroring);
        }

        if ($packageMirroring === RegistryPackageMirroring::Manual) {
            $builder->andWhere($builder->expr()->orX(
                $builder->expr()->eq('registry.packageMirroring', $builder->expr()->literal('manual')),
                $builder->expr()->eq('registry.packageMirroring', $builder->expr()->literal('auto')),
            ));
        } elseif ($packageMirroring === RegistryPackageMirroring::Automatic) {
            $builder->andWhere($builder->expr()->orX(
                $builder->expr()->eq('registry.packageMirroring', $builder->expr()->literal('auto')),
            ));
        } else {
            throw new \LogicException();
        }

        return $builder;
    }

    public function findByPackageMirroring(RegistryPackageMirroring $packageMirroring): array
    {
        return $this->createPackageMirroringQueryBuilder($packageMirroring)->getQuery()->getResult();
    }

    public function save(Registry $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Registry $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
