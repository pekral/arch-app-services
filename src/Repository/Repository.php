<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * @param array<TKey, TValue> $params Search criteria as key-value pairs
     * @param array<string> $withRelations Eager load relationships
     * @param int|null $itemsPerPage Number of items per page (uses default from config if null)
     * @param array<string> $orderBy Order by columns (e.g., ['name' => 'asc', 'created_at' => 'desc'])
     * @param array<string> $groupBy Group by columns
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
     * @param \Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params Search criteria
     * @param array<string> $groupBy Group by columns
     * @return int Number of matching records
     */
    public function countByParams(Collection|array $params, array $groupBy = []): int;

}
