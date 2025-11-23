<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository\DynamoDb;

use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Pekral\Arch\Exceptions\DynamoDbNotSupported;
use Pekral\Arch\Repository\Repository;

use function assert;
use function config;
use function count;
use function is_array;
use function request;

/**
 * Base class for querying DynamoDb models with consistent interface.
 *
 * Provides standardized methods for finding, filtering, and paginating
 * model data with support for eager loading relationships.
 *
 * @template TModel of \BaoPham\DynamoDb\DynamoDbModel
 * @implements \Pekral\Arch\Repository\Repository<TModel>
 */
abstract class BaseRepository implements Repository
{

    /**
     * @return class-string<TModel>
     */
    abstract protected function getModelClassName(): string;

    /**
     * @param array<string, mixed> $params
     * @param array<string> $withRelations
     * @param array<string, string> $orderBy
     * @param array<string> $groupBy
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, TModel>
     */
    public function paginateByParams(
        array $params,
        array $withRelations = [],
        ?int $itemsPerPage = null,
        array $orderBy = [],
        array $groupBy = [],
    ): LengthAwarePaginator {
        if (count($withRelations) > 0) {
            throw DynamoDbNotSupported::eagerLoadingNotSupported();
        }

        if (count($groupBy) > 0) {
            throw DynamoDbNotSupported::groupByNotSupported();
        }

        $itemsPerPage = $this->resolveItemsPerPage($itemsPerPage);
        $currentPage = $this->getCurrentPage();

        $total = $this->getTotalCount($params);
        $items = $this->getPaginatedItems($params, $orderBy, $currentPage, $itemsPerPage);

        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, TModel> $paginator */
        $paginator = new Paginator(
            $items,
            $total,
            $itemsPerPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );

        return $paginator;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $params
     * @param array<string> $with
     * @param array<string, string> $orderBy
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(Collection|array $params, array $with = [], array $orderBy = []): Model
    {
        if (count($with) > 0) {
            throw DynamoDbNotSupported::eagerLoadingNotSupported();
        }

        if (count($orderBy) > 0) {
            throw DynamoDbNotSupported::orderByNotSupported();
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder = $queryBuilder->where(is_array($params) ? $params : $params->toArray());
        assert($queryBuilder instanceof DynamoDbQueryBuilder);

        /** @var TModel $result */
        $result = $queryBuilder->firstOrFail();
        assert($result instanceof Model);

        return $result;
    }

    /**
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $with
     * @param array<string, string> $orderBy
     * @return TModel|null
     */
    public function findOneByParams(Collection|array $params, array $with = [], array $orderBy = []): ?Model
    {
        if (count($with) > 0) {
            throw DynamoDbNotSupported::eagerLoadingNotSupported();
        }

        if (count($orderBy) > 0) {
            throw DynamoDbNotSupported::orderByNotSupported();
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder = $queryBuilder->where(is_array($params) ? $params : $params->toArray());
        assert($queryBuilder instanceof DynamoDbQueryBuilder);
        /** @var TModel|null $result */
        $result = $queryBuilder->first();

        return $result;
    }

    /**
     * Count models by given criteria.
     *
     * @param \Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params
     * @param array<string> $groupBy
     */
    public function countByParams(Collection|array $params, array $groupBy = []): int
    {
        if (count($groupBy) > 0) {
            throw DynamoDbNotSupported::groupByNotSupported();
        }

        $queryBuilder = $this->createQueryBuilder();

        $normalizedParams = is_array($params) ? $params : $params->toArray();

        if (count($normalizedParams) > 0) {
            $queryBuilder = $queryBuilder->where($normalizedParams);
            assert($queryBuilder instanceof DynamoDbQueryBuilder);
        }

        $count = $queryBuilder->count();
        assert(is_int($count));

        return $count;
    }

    private function createQueryBuilder(): DynamoDbQueryBuilder
    {
        $modelClassName = $this->getModelClassName();

        $query = new $modelClassName()->newQuery();
        assert($query instanceof DynamoDbQueryBuilder);

        return $query;
    }

    /**
     * @param \Illuminate\Support\Collection<int, TModel> $items
     * @param array<string, string> $orderBy
     * @return \Illuminate\Support\Collection<int, TModel>
     */
    private function sortItems(Collection $items, array $orderBy): Collection
    {
        if (count($orderBy) === 0) {
            return $items;
        }

        $sorted = $items;

        foreach ($orderBy as $column => $direction) {
            $sorted = $sorted->sortBy($column, SORT_REGULAR, $direction === 'desc');
        }

        return $sorted->values();
    }

    /**
     * @param array<string, mixed> $params
     */
    private function getTotalCount(array $params): int
    {
        $queryBuilder = $this->createQueryBuilder();

        if (count($params) > 0) {
            $queryBuilder = $queryBuilder->where($params);
            assert($queryBuilder instanceof DynamoDbQueryBuilder);
        }

        $total = $queryBuilder->count();
        assert(is_int($total));

        return $total;
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, string> $orderBy
     * @return \Illuminate\Support\Collection<int, TModel>
     */
    private function getPaginatedItems(array $params, array $orderBy, int $currentPage, int $itemsPerPage): Collection
    {
        $queryBuilder = $this->createQueryBuilder();

        if (count($params) > 0) {
            $queryBuilder = $queryBuilder->where($params);
            assert($queryBuilder instanceof DynamoDbQueryBuilder);
        }

        $allItems = $queryBuilder->get();
        assert($allItems instanceof Collection);

        $sortedItems = $this->sortItems($allItems, $orderBy);

        $offset = ($currentPage - 1) * $itemsPerPage;

        return $sortedItems->slice($offset, $itemsPerPage)->values();
    }

    private function resolveItemsPerPage(?int $itemsPerPage): int
    {
        if ($itemsPerPage !== null) {
            return $itemsPerPage;
        }

        $configValue = config('arch.default_items_per_page', 15);

        return is_int($configValue) ? $configValue : 15;
    }

    private function getCurrentPage(): int
    {
        $page = request()->input('page', 1);

        return is_numeric($page) && $page > 0 ? (int) $page : 1;
    }

}
