<?php

namespace CodedMonkey\Dirigent\Doctrine\Repository;

use CodedMonkey\Dirigent\Doctrine\Entity\Keyword;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Keyword>
 *
 * @method Keyword|null find($id, $lockMode = null, $lockVersion = null)
 * @method Keyword[]    findAll()
 * @method Keyword[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Keyword|null findOneBy(array $criteria, array $orderBy = null)
 */
class KeywordRepository extends ServiceEntityRepository
{
    /**
     * @var array<string, Keyword>
     */
    private array $cachedKeywords = [];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Keyword::class);
    }

    public function save(Keyword $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Keyword $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getByName(string $name): Keyword
    {
        if (isset($this->cachedKeywords[$name])) {
            return $this->cachedKeywords[$name];
        }

        if (null === $keyword = $this->findOneBy(['name' => $name])) {
            $keyword = new Keyword($name);
            $this->save($keyword);
        }

        return $this->cachedKeywords[$name] = $keyword;
    }
}
