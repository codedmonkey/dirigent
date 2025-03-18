<?php

namespace CodedMonkey\Dirigent\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $this->deletePackageLinks($entity->getId());

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
    public function getStalePackages(): array
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

    /**
     * @return list<array{id: int}>
     */
    public function getAllPackageIds(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        return $connection->fetchAllAssociative('SELECT id FROM package ORDER BY id');
    }

    public function updatePackageLinks(int $packageId, int $versionId): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            $this->deletePackageLinks($packageId);

            $connection->executeStatement(
                'INSERT INTO dependent (package_id, dependent_package_name, dev_dependency) SELECT :id, linked_package_name, :dev FROM require_link WHERE version_id = :version',
                ['id' => $packageId, 'version' => $versionId, 'dev' => 0],
            );
            $connection->executeStatement(
                'INSERT INTO dependent (package_id, dependent_package_name, dev_dependency) SELECT :id, linked_package_name, :dev FROM dev_require_link WHERE version_id = :version',
                ['id' => $packageId, 'version' => $versionId, 'dev' => 1],
            );
            $connection->executeStatement(
                'INSERT INTO suggester (package_id, suggested_package_name) SELECT :id, linked_package_name FROM suggest_link WHERE version_id = :version',
                ['id' => $packageId, 'version' => $versionId],
            );
            $connection->executeStatement(
                'INSERT INTO provider (package_id, provided_package_name) SELECT :id, linked_package_name FROM provide_link WHERE version_id = :version',
                ['id' => $packageId, 'version' => $versionId],
            );
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        $connection->commit();
    }

    public function deletePackageLinks(int $packageId): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement('DELETE FROM dependent WHERE package_id = :id', ['id' => $packageId]);
        $connection->executeStatement('DELETE FROM provider WHERE package_id = :id', ['id' => $packageId]);
        $connection->executeStatement('DELETE FROM suggester WHERE package_id = :id', ['id' => $packageId]);
    }
}
