<?php

namespace CodedMonkey\Dirigent\EasyAdmin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PackagePaginator implements EntityPaginatorInterface
{
    private int $currentPage;
    private int $pageSize;
    private int $rangeSize;
    private int $rangeEdgeSize;
    private array $results;
    private int $numResults;

    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly string $routeName,
        private readonly array $routeParameters = [],
    ) {
    }

    public function paginate(PaginatorDto $paginatorDto, QueryBuilder $queryBuilder): EntityPaginatorInterface
    {
        $this->pageSize = $paginatorDto->getPageSize();
        $this->rangeSize = $paginatorDto->getRangeSize();
        $this->rangeEdgeSize = $paginatorDto->getRangeEdgeSize();
        $this->currentPage = max(1, $paginatorDto->getPageNumber());
        $firstResult = ($this->currentPage - 1) * $this->pageSize;
        $rangeFirstResultNumber = $this->pageSize * ($this->currentPage - 1) + 1;

        $countQueryBuilder = clone $queryBuilder;
        $this->numResults = $countQueryBuilder
            ->select('COUNT(package.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($rangeFirstResultNumber > $this->numResults) {
            $this->results = [];

            return $this;
        }

        $results = $queryBuilder
            ->addOrderBy('package.name', 'ASC')
            ->setFirstResult($firstResult)
            ->setMaxResults($this->pageSize)
            ->getQuery()
            ->getResult();

        $this->results = $results;

        return $this;
    }

    public static function fromRequest(Request $request, QueryBuilder $queryBuilder, UrlGeneratorInterface $router): EntityPaginatorInterface
    {
        $paginator = new self(
            $router,
            $request->attributes->get('_route'),
            $request->attributes->get('_route_params'),
        );

        $paginatorDto = new PaginatorDto(20, 3, 1, true, null);
        $paginatorDto->setPageNumber($request->query->getInt('page', 1));

        return $paginator->paginate($paginatorDto, $queryBuilder);
    }

    public function generateUrlForPage(int $page): string
    {
        return $this->router->generate($this->routeName, [...$this->routeParameters, 'page' => $page]);
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
