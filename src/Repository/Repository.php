<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Base interface for repository implementations.
 *
 * Defines the contract for querying and retrieving model data
 * with support for filtering, pagination, and eager loading.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface Repository
{

    /**
     * Paginate models by given criteria.
     *
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
    ): LengthAwarePaginator;

    /**
     * Get one model by given criteria or throw exception if not found.
     *
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $params
     * @param array<string> $with
     * @param array<string> $orderBy
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(Collection|array $params, array $with = [], array $orderBy = []): Model;

    /**
     * Find one model by given criteria or return null if not found.
     *
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $with
     * @param array<string> $orderBy
     * @return TModel|null
     */
    public function findOneByParams(Collection|array $params, array $with = [], array $orderBy = []): ?Model;

    /**
     * Count models by given criteria.
     *
     * @param \Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params
     * @param array<string> $groupBy
     */
    public function countByParams(Collection|array $params, array $groupBy = []): int;

    /**
     * Create a new query builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    public function createQueryBuilder(): Builder;

    /**
     * Start a fluent query builder interface.
     *
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    public function query(): Builder;

}
