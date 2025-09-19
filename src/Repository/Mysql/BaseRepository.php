<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository\Mysql;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as ContractsLengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /** Default number of items per page for pagination. */
    private const int DEFAULT_ITEMS_PER_PAGE = 10;

    /**
     * @return class-string<TModel>
     */
    abstract protected function getModelClassName(): string;

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $params
     * @param array<string> $withRelations
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, TModel>
     */
    public function paginateByParams(
        array $params,
        array $withRelations = [],
        int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE,
    ): ContractsLengthAwarePaginator {
        $queryBuilder = $this->createQueryBuilder();

        if (count($params) > 0) {
            $queryBuilder = $queryBuilder->where($params);
        }

        if (count($withRelations) > 0) {
            $queryBuilder = $queryBuilder->with($withRelations);
        }

        return $queryBuilder->paginate($itemsPerPage);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $params
     * @param array<string> $with
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(Collection|array $params, array $with = []): Model
    {
        return $this->createQueryBuilder()
            ->with($with)
            ->where(is_array($params) ? $params : $params->toArray())
            ->firstOrFail();
    }

    /**
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $with
     * @return TModel|null
     */
    public function findOneByParams(Collection|array $params, array $with = []): ?Model
    {
        return $this->createQueryBuilder()
            ->where(is_array($params) ? $params : $params->toArray())
            ->with($with)
            ->first();
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

}
