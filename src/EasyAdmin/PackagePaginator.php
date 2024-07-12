<?php

namespace CodedMonkey\Conductor\EasyAdmin;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;

class PackagePaginator implements EntityPaginatorInterface
{
    private ?int $currentPage = null;
    private ?int $pageSize = null;
    private ?int $rangeSize = null;
    private ?int $rangeEdgeSize = null;
    private $results;
    private $numResults;
    private ?int $rangeFirstResultNumber = null;
    private ?int $rangeLastResultNumber = null;

    public function __construct(
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
    ) {
    }

    public function paginate(PaginatorDto $paginatorDto, QueryBuilder $queryBuilder): EntityPaginatorInterface
    {
        $this->pageSize = $paginatorDto->getPageSize();
        $this->rangeSize = $paginatorDto->getRangeSize();
        $this->rangeEdgeSize = $paginatorDto->getRangeEdgeSize();
        $this->currentPage = max(1, $paginatorDto->getPageNumber());
        $firstResult = ($this->currentPage - 1) * $this->pageSize;
        $this->rangeFirstResultNumber = $this->pageSize * ($this->currentPage - 1) + 1;
        $this->rangeLastResultNumber = $this->rangeFirstResultNumber + $this->pageSize - 1;

        $query = $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($this->pageSize)
            ->getQuery();

        $paginator = new Paginator($query, $paginatorDto->fetchJoinCollection());

        $this->results = $paginator->getIterator();
        $this->numResults = $paginator->count();
        if ($this->rangeLastResultNumber > $this->numResults) {
            $this->rangeLastResultNumber = $this->numResults;
        }

        return $this;
    }

    public function generateUrlForPage(int $page): string
    {
        return $this->adminUrlGenerator->set(EA::PAGE, $page)->generateUrl();
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return (int) ceil($this->numResults / $this->pageSize);
    }

    public function getPageRange(?int $pagesOnEachSide = null, ?int $pagesOnEdges = null): iterable
    {
        $pagesOnEachSide = $pagesOnEachSide ?? $this->rangeSize;
        $pagesOnEdges = $pagesOnEdges ?? $this->rangeEdgeSize;

        if (0 === $pagesOnEachSide) {
            return null;
        }

        if ($this->getLastPage() <= ($pagesOnEachSide + $pagesOnEdges) * 2) {
            return yield from range(1, $this->getLastPage());
        }

        if ($this->getCurrentPage() > ($pagesOnEachSide + $pagesOnEdges + 1)) {
            yield from range(1, $pagesOnEdges);
            yield null;
            yield from range($this->getCurrentPage() - $pagesOnEachSide, $this->getCurrentPage());
        } else {
            yield from range(1, $this->getCurrentPage());
        }

        if ($this->getCurrentPage() < ($this->getLastPage() - $pagesOnEachSide - $pagesOnEdges - 1)) {
            yield from range($this->getCurrentPage() + 1, $this->getCurrentPage() + $pagesOnEachSide);
            yield null;
            yield from range($this->getLastPage() - $pagesOnEdges + 1, $this->getLastPage());
        } elseif ($this->getCurrentPage() + 1 <= $this->getLastPage()) {
            yield from range($this->getCurrentPage() + 1, $this->getLastPage());
        }
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getLastPage();
    }

    public function getNextPage(): int
    {
        return min($this->getLastPage(), $this->currentPage + 1);
    }

    public function hasToPaginate(): bool
    {
        return $this->numResults > $this->pageSize;
    }

    public function isOutOfRange(): bool
    {
        if (1 === $this->currentPage) {
            return false;
        }

        return $this->currentPage < 1 || $this->currentPage > $this->getLastPage();
    }

    public function getNumResults(): int
    {
        return $this->numResults;
    }

    public function getResults(): ?iterable
    {
        return $this->results;
    }

    public function getResultsAsJson(): string
    {
        throw new \LogicException();
    }
}
