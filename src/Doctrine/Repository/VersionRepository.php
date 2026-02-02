<?php

namespace CodedMonkey\Dirigent\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Version>
 *
 * @method Version|null find($id, $lockMode = null, $lockVersion = null)
 * @method Version[]    findAll()
 * @method Version[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Version|null findOneBy(array $criteria, array $orderBy = null)
 */
class VersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Version::class);
    }

    public function save(Version $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Version $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByNormalizedVersion(Package $package, string $version): ?Version
    {
        return $this->findOneBy(['package' => $package, 'normalizedVersion' => $version]);
    }

    /**
     * @return array<string, array{id: int, version: string, normalized_version: string, source: ?array}>
     */
    public function getVersionMetadataForUpdate(Package $package): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT id, version, normalized_version, source FROM version v WHERE v.package_id = :id',
            ['id' => $package->getId()]
        );

        $versions = [];
        foreach ($rows as $row) {
            if ($row['source']) {
                $row['source'] = json_decode((string) $row['source'], true);
            }

            $key = strtolower((string) $row['normalized_version']);
            $versions[$key] = $row;
        }

        return $versions;
    }
}
