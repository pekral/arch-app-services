<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository\Mysql;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Pekral\Arch\Repository\Repository;

use function assert;
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
 * @implements \Pekral\Arch\Repository\Repository<TModel>
 * @method \Pekral\Arch\Repository\CacheWrapper cache()
 */
abstract class BaseRepository implements Repository
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
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder = $this->applyQueryConditions($queryBuilder, $params, $withRelations, $orderBy, $groupBy);

        return $queryBuilder->paginate($this->resolveItemsPerPage($itemsPerPage));
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
        $queryBuilder = $this->createQueryBuilder()
            ->with($with)
            ->where(is_array($params) ? $params : $params->toArray());

        $queryBuilder = $this->applyOrderBy($queryBuilder, $orderBy);

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
        $queryBuilder = $this->createQueryBuilder()
            ->where(is_array($params) ? $params : $params->toArray())
            ->with($with);

        $queryBuilder = $this->applyOrderBy($queryBuilder, $orderBy);

        return $queryBuilder->first();
    }

    /**
     * Count models by given criteria.
     *
     * @param \Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params
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
    public function createQueryBuilder(): Builder
    {
        $modelClassName = $this->getModelClassName();

        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = new $modelClassName()->newQuery();

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    public function query(): Builder
    {
        return $this->createQueryBuilder();
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
     * @param array<string, string> $orderBy
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

        return $this->applyOrderBy($queryBuilder, $orderBy);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<TModel> $queryBuilder
     * @param array<string, string> $orderBy
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    private function applyOrderBy(Builder $queryBuilder, array $orderBy): Builder
    {
        if (count($orderBy) === 0) {
            return $queryBuilder;
        }

        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $result */
        $result = Collection::make($orderBy)->reduce(
            fn (Builder $builder, string $direction, string $column): Builder => $builder->orderBy($column, $direction),
            $queryBuilder,
        );

        return $result;
    }

}
