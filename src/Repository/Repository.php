<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface Repository
{

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
    ): LengthAwarePaginator;

    /**
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $with
     * @param array<string, string> $orderBy
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(Collection|array $params, array $with = [], array $orderBy = []): Model;

    /**
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @param array<string> $with
     * @param array<string, string> $orderBy
     * @return TModel|null
     */
    public function findOneByParams(Collection|array $params, array $with = [], array $orderBy = []): ?Model;

    /**
     * @param \Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params
     * @param array<string> $groupBy
     */
    public function countByParams(Collection|array $params, array $groupBy = []): int;

}
