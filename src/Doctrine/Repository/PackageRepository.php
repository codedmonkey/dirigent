<?php

namespace CodedMonkey\Dirigent\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @extends ServiceEntityRepository<Package>
 *
 * @method Package|null find($id, $lockMode = null, $lockVersion = null)
 * @method Package[]    findAll()
 * @method Package[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Package|null findOneBy(array $criteria, array $orderBy = null)
 */
class PackageRepository extends ServiceEntityRepository
{
    private \DateInterval $updateInterval;

    public function __construct(
        ManagerRegistry $registry,
        #[Autowire(param: 'dirigent.packages.periodic_update_interval')]
        string $updateInterval,
    ) {
        parent::__construct($registry, Package::class);

        $this->updateInterval = new \DateInterval($updateInterval);
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

    public function findOneByName(string $name): ?Package
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @return list<array{id: int}>
     */
    public function getAllPackageIds(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        return $connection->fetchAllAssociative('SELECT id FROM package ORDER BY id');
    }

    /**
     * @return list<array{id: int}>
     */
    public function getStalePackageIds(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $now = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
        $before = $now->sub($this->updateInterval);

        return $connection->fetchAllAssociative(
            'SELECT p.id FROM package p
            WHERE p.update_scheduled_at IS NULL
                AND (p.updated_at IS NULL OR p.updated_at < :crawled)
            ORDER BY p.id',
            [
                'crawled' => $before->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function updatePackageLinks(Package $package, Version $version): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->transactional(function (Connection $connection) use ($package, $version) {
            $this->deletePackageLinks($package);
            $queryParameters = ['id' => $package->getId(), 'version' => $version->getId()];

            $connection->executeStatement(
                <<<'SQL'
                INSERT INTO package_provide_link (linked_package_name, implementation, package_id)
                    SELECT linked_package_name, FALSE, :id
                    FROM version_provide_link
                    WHERE version_id = :version AND linked_package_name NOT LIKE '%-implementation'
                SQL,
                $queryParameters,
            );
            $connection->executeStatement(
                <<<'SQL'
                INSERT INTO package_provide_link (linked_package_name, implementation, package_id)
                    SELECT SUBSTRING(linked_package_name, 1, LENGTH(linked_package_name) - 15), TRUE, :id
                    FROM version_provide_link
                    WHERE version_id = :version AND linked_package_name LIKE '%-implementation'
                SQL,
                $queryParameters,
            );
            $connection->executeStatement(
                <<<'SQL'
                INSERT INTO package_require_link (linked_package_name, dev_dependency, package_id)
                    SELECT linked_package_name, FALSE, :id FROM version_require_link WHERE version_id = :version
                SQL,
                $queryParameters,
            );
            $connection->executeStatement(
                <<<'SQL'
                INSERT INTO package_require_link (linked_package_name, dev_dependency, package_id)
                    SELECT linked_package_name, TRUE, :id FROM version_dev_require_link WHERE version_id = :version
                SQL,
                $queryParameters,
            );
            $connection->executeStatement(
                <<<'SQL'
                INSERT INTO package_suggest_link (linked_package_name, package_id)
                    SELECT linked_package_name, :id FROM version_suggest_link WHERE version_id = :version
                SQL,
                $queryParameters,
            );
        });
    }

    public function deletePackageLinks(Package $package): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->transactional(function (Connection $connection) use ($package) {
            $queryParameters = ['id' => $package->getId()];

            $connection->executeStatement('DELETE FROM package_provide_link WHERE package_id = :id', $queryParameters);
            $connection->executeStatement('DELETE FROM package_require_link WHERE package_id = :id', $queryParameters);
            $connection->executeStatement('DELETE FROM package_suggest_link WHERE package_id = :id', $queryParameters);
        });
    }
}
