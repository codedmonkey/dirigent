<?php

namespace CodedMonkey\Dirigent\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Entity\Metadata;
use CodedMonkey\Dirigent\Entity\MetadataLinkType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
