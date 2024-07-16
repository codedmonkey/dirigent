<?php

namespace CodedMonkey\Conductor\Doctrine\Repository;

use CodedMonkey\Conductor\Doctrine\Entity\Package;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Package>
 *
 * @method Package|null find($id, $lockMode = null, $lockVersion = null)
 * @method Package[]    findAll()
 * @method Package[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Package|null findOneBy(array $criteria, array $orderBy = null)
 * @method Package|null findOneByName(string $name)
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    public function save(Package $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Package $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return list<array{id: int}>
     */
    public function getStalePackages(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        return $connection->fetchAllAssociative(
            'SELECT p.id FROM package p
            WHERE p.abandoned = false
                AND (p.crawled_at IS NULL OR p.crawled_at < :crawled)
            ORDER BY p.id',
            [
                // crawl packages every 2 weeks
                'crawled' => date('Y-m-d H:i:s', strtotime('-2week')),
            ]
        );
    }

    /**
     * @return list<array{id: int}>
     */
    public function getAllPackageIds(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        return $connection->fetchAllAssociative('SELECT id FROM package ORDER BY id');
    }
}
