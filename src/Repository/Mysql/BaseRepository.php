<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository\Mysql;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as ContractsLengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function config;
use function count;
use function is_array;

/**
 * Base class for querying Eloquent models with consistent interface.
 *
 * Provides standardized methods for finding, filtering, and paginating
 * model data with support for eager loading relationships.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class BaseRepository
{

    /**
     * @return class-string<TModel>
     */
    abstract protected function getModelClassName(): string;

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $params
     * @param array<string> $withRelations
     * @param array<string> $orderBy
     * @param array<string> $groupBy
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, TModel>
     */
    public function paginateByParams(
        array $params,
        array $withRelations = [],
        ?int $itemsPerPage = null,
        array $orderBy = [],
        array $groupBy = [],
    ): ContractsLengthAwarePaginator {
        $queryBuilder = $this->createQueryBuilder();

        $itemsPerPage = $this->resolveItemsPerPage($itemsPerPage);
        $queryBuilder = $this->applyQueryConditions($queryBuilder, $params, $withRelations, $orderBy, $groupBy);

        return $queryBuilder->paginate($itemsPerPage);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $params
     * @param array<string> $with
     * @param array<string> $orderBy
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(Collection|array $params, array $with = [], array $orderBy = []): Model
    {
        $queryBuilder = $this->createQueryBuilder()
            ->with($with)
            ->where(is_array($params) ? $params : $params->toArray());

        if (count($orderBy) > 0) {
            foreach ($orderBy as $column => $direction) {
                $queryBuilder = $queryBuilder->orderBy($column, $direction);
            }
        }

        return $queryBuilder->firstOrFail();
    }

    /**
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $with
     * @param array<string> $orderBy
     * @return TModel|null
     */
    public function findOneByParams(Collection|array $params, array $with = [], array $orderBy = []): ?Model
    {
        $queryBuilder = $this->createQueryBuilder()
            ->where(is_array($params) ? $params : $params->toArray())
            ->with($with);

        if (count($orderBy) > 0) {
            foreach ($orderBy as $column => $direction) {
                $queryBuilder = $queryBuilder->orderBy($column, $direction);
            }
        }

        return $queryBuilder->first();
    }

    /**
     * Find all models by given criteria.
     *
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $with
     * @param array<string> $orderBy
     * @param array<string> $groupBy
     * @return \Illuminate\Support\Collection<int, TModel>
     */
    public function findAllByParams(Collection|array $params, array $with = [], array $orderBy = [], array $groupBy = [], ?int $limit = null): Collection
    {
        $queryBuilder = $this->createQueryBuilder();

        $normalizedParams = is_array($params) ? $params : $params->toArray();

        if (count($normalizedParams) > 0) {
            $queryBuilder = $queryBuilder->where($normalizedParams);
        }

        if (count($with) > 0) {
            $queryBuilder = $queryBuilder->with($with);
        }

        if (count($groupBy) > 0) {
            $queryBuilder = $queryBuilder->groupBy($groupBy);
        }

        if (count($orderBy) > 0) {
            foreach ($orderBy as $column => $direction) {
                $queryBuilder = $queryBuilder->orderBy($column, $direction);
            }
        }

        if ($limit !== null) {
            $queryBuilder = $queryBuilder->limit($limit);
        }

        /** @var \Illuminate\Support\Collection<int, TModel> $result */
        $result = $queryBuilder->get();

        return $result;
    }

    /**
     * Count models by given criteria.
     *
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $groupBy
     */
    public function countByParams(Collection|array $params, array $groupBy = []): int
    {
        $queryBuilder = $this->createQueryBuilder();

        $normalizedParams = is_array($params) ? $params : $params->toArray();

        if (count($normalizedParams) > 0) {
            $queryBuilder = $queryBuilder->where($normalizedParams);
        }

        if (count($groupBy) > 0) {
            $queryBuilder = $queryBuilder->groupBy($groupBy);
        }

        return $queryBuilder->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    private function createQueryBuilder(): Builder
    {
        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();

        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = $model->newQuery();
        
        return $query;
    }

    private function resolveItemsPerPage(?int $itemsPerPage): int
    {
        if ($itemsPerPage !== null) {
            return $itemsPerPage;
        }

        $configValue = config('arch.default_items_per_page', 15);

        return is_int($configValue) ? $configValue : 15;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<TModel> $queryBuilder
     * @param array<string, mixed> $params
     * @param array<string> $withRelations
     * @param array<string> $orderBy
     * @param array<string> $groupBy
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    private function applyQueryConditions(Builder $queryBuilder, array $params, array $withRelations, array $orderBy, array $groupBy): Builder
    {
        if (count($params) > 0) {
            $queryBuilder = $queryBuilder->where($params);
        }

        if (count($withRelations) > 0) {
            $queryBuilder = $queryBuilder->with($withRelations);
        }

        if (count($groupBy) > 0) {
            $queryBuilder = $queryBuilder->groupBy($groupBy);
        }

        if (count($orderBy) > 0) {
            foreach ($orderBy as $column => $direction) {
                $queryBuilder = $queryBuilder->orderBy($column, $direction);
            }
        }

        return $queryBuilder;
    }

}
