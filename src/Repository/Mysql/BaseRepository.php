<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository\Mysql;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Pekral\Arch\Repository\Repository;

use function assert;
use function count;
use function is_array;

/**
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
        $queryBuilder = $this->applyQueryConditions(
            $this->createQueryBuilder(),
            $params,
            $withRelations,
            $orderBy,
            $groupBy,
        );

        return $queryBuilder->paginate($this->resolveItemsPerPage($itemsPerPage));
    }

    /**
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $with
     * @param array<string, string> $orderBy
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(Collection|array $params, array $with = [], array $orderBy = []): Model
    {
        $queryBuilder = $this->applyOrderBy(
            $this->createQueryBuilder()
                ->with($with)
                ->where($this->normalizeParams($params)),
            $orderBy,
        );

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
        return $this->applyOrderBy(
            $this->createQueryBuilder()
                ->where($this->normalizeParams($params))
                ->with($with),
            $orderBy,
        )->first();
    }

    /**
     * @param \Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params
     * @param array<string> $groupBy
     */
    public function countByParams(Collection|array $params, array $groupBy = []): int
    {
        $queryBuilder = $this->createQueryBuilder();
        $normalizedParams = $this->normalizeParams($params);

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

        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = new $modelClassName()->newQuery();

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

    /**
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $params
     * @return array<TKey, TValue>
     */
    private function normalizeParams(Collection|array $params): array
    {
        return is_array($params) ? $params : $params->toArray();
    }

}
