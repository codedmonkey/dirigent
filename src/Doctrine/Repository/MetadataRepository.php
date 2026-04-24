<?php

namespace CodedMonkey\Dirigent\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Doctrine\Entity\Package;
use CodedMonkey\Dirigent\Doctrine\Entity\Version;
use CodedMonkey\Dirigent\Entity\MetadataLinkType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Metadata>
 *
 * @method Metadata|null find($id, $lockMode = null, $lockVersion = null)
 * @method Metadata[]    findAll()
 * @method Metadata[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Metadata|null findOneBy(array $criteria, array $orderBy = null)
 */
class MetadataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Metadata::class);
    }

    public function save(Metadata $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Metadata $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getMetadataCountForVersion(Version $version): int
    {
        return $this->count(['version' => $version]);
    }

    public function getMetadataCollectionForVersion(Version $version): array
    {
        return $this->findBy(
            ['version' => $version],
            ['revision' => Order::Descending->value],
        );
    }

    /**
     * Returns a map of version ID => metadata count for all versions of the given package.
     *
     * @return array<int, int>
     */
    public function getMetadataCountsForPackage(Package $package): array
    {
        $rows = $this->createQueryBuilder('metadata')
            ->select('IDENTITY(metadata.version) as version_id, COUNT(metadata.id) as revision_count')
            ->join('metadata.version', 'version')
            ->where('version.package = :package')
            ->groupBy('metadata.version')
            ->setParameter('package', $package)
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['version_id']] = (int) $row['revision_count'];
        }

        return $counts;
    }

    /**
     * Initializes all link and keyword collections for the given metadata.
     */
    public function fetchMetadataCollections(Metadata ...$metadata): void
    {
        if ([] === $metadata) {
            return;
        }

        $metadataCollection = $metadata;

        foreach (MetadataLinkType::cases() as $linkType) {
            $association = match ($linkType) {
                MetadataLinkType::Require => 'requireLinks',
                MetadataLinkType::DevRequire => 'devRequireLinks',
                MetadataLinkType::Conflict => 'conflictLinks',
                MetadataLinkType::Provide => 'provideLinks',
                MetadataLinkType::Replace => 'replaceLinks',
                MetadataLinkType::Suggest => 'suggestLinks',
            };

            $this->getEntityManager()->createQueryBuilder()
                ->select('metadata', $association)
                ->from(Metadata::class, 'metadata')
                ->leftJoin("metadata.$association", $association)
                ->where('metadata IN (:metadata)')
                ->setParameter('metadata', $metadataCollection)
                ->getQuery()
                ->getResult();
        }

        $this->getEntityManager()->createQueryBuilder()
            ->select('metadata', 'keywords')
            ->from(Metadata::class, 'metadata')
            ->leftJoin('metadata.keywords', 'keywords')
            ->leftJoin('keywords.keyword', 'keyword')
            ->addSelect('keyword')
            ->where('metadata IN (:metadata)')
            ->setParameter('metadata', $metadataCollection)
            ->getQuery()
            ->getResult();
    }

    public function getNextRevision(Metadata $metadata): int
    {
        $version = $metadata->getVersion();

        if (null === $version->getId()) {
            return 1;
        }

        $lastRevision = $this->createQueryBuilder('metadata')
            ->select('MAX(metadata.revision)')
            ->where('metadata.version = :version')
            ->setParameter('version', $metadata->getVersion())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $lastRevision + 1;
    }
}
