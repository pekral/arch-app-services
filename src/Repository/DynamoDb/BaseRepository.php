<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository\DynamoDb;

use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImplementation;
use Illuminate\Support\Collection;
use Pekral\Arch\Exceptions\FeatureNotSupportedForDynamoDb;
use Pekral\Arch\Repository\Repository;

use function config;
use function count;
use function is_array;

/**
 * Base class for querying DynamoDB models with consistent interface.
 *
 * Provides standardized methods for finding, filtering, and paginating
 * DynamoDB model data. Note that DynamoDB has limitations compared to SQL:
 * - Pagination uses lastEvaluatedKey instead of offset
 * - groupBy is not supported
 * - Relationships (eager loading) are not supported
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
     * Paginate models by given criteria.
     * Note: DynamoDB pagination works differently - it uses lastEvaluatedKey.
     * This implementation provides a simple pagination wrapper.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $params
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
            throw FeatureNotSupportedForDynamoDb::eagerLoading();
        }

        if (count($orderBy) > 0) {
            throw FeatureNotSupportedForDynamoDb::orderBy();
        }

        if (count($groupBy) > 0) {
            throw FeatureNotSupportedForDynamoDb::groupBy();
        }

        $itemsPerPage = $this->resolveItemsPerPage($itemsPerPage);

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder = $this->applyQueryConditions($queryBuilder, $params);

        /** @var \Illuminate\Support\Collection<int, TModel> $items */
        $items = $queryBuilder->limit($itemsPerPage)->get();

        return new LengthAwarePaginatorImplementation(
            $items,
            $items->count(),
            $itemsPerPage,
            1,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $params
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(Collection|array $params, array $with = [], array $orderBy = []): Model
    {
        if (count($with) > 0) {
            throw FeatureNotSupportedForDynamoDb::eagerLoading();
        }

        if (count($orderBy) > 0) {
            throw FeatureNotSupportedForDynamoDb::orderBy();
        }

        $queryBuilder = $this->createQueryBuilder();
        $normalizedParams = is_array($params) ? $params : $params->toArray();
        /** @var array<string, mixed> $normalizedParams */
        $queryBuilder = $this->applyWhereConditions($queryBuilder, $normalizedParams);
        $queryBuilder = $this->applyOrderBy($queryBuilder);

        /** @var TModel $result */
        $result = $queryBuilder->firstOrFail();

        return $result;
    }

    /**
     * @param \Illuminate\Support\Collection<string, mixed>|array<string, mixed> $params
     * @return TModel|null
     */
    public function findOneByParams(Collection|array $params, array $with = [], array $orderBy = []): ?Model
    {
        if (count($with) > 0) {
            throw FeatureNotSupportedForDynamoDb::eagerLoading();
        }

        if (count($orderBy) > 0) {
            throw FeatureNotSupportedForDynamoDb::orderBy();
        }

        $queryBuilder = $this->createQueryBuilder();
        $normalizedParams = is_array($params) ? $params : $params->toArray();
        /** @var array<string, mixed> $normalizedParams */
        $queryBuilder = $this->applyWhereConditions($queryBuilder, $normalizedParams);
        $queryBuilder = $this->applyOrderBy($queryBuilder);

        /** @var TModel|null $result */
        $result = $queryBuilder->first();

        return $result;
    }

    /**
     * Count models by given criteria.
     * Note: DynamoDB count operations scan the table, which can be expensive.
     *
     * @param \Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params
     */
    public function countByParams(Collection|array $params, array $groupBy = []): int
    {
        if (count($groupBy) > 0) {
            throw FeatureNotSupportedForDynamoDb::groupBy();
        }

        $queryBuilder = $this->createQueryBuilder();

        /** @var array<string, mixed> $normalizedParams */
        $normalizedParams = is_array($params) ? $params : $params->toArray();

        if (count($normalizedParams) > 0) {
            $queryBuilder = $this->applyWhereConditions($queryBuilder, $normalizedParams);
        }

        /** @var int $count */
        $count = $queryBuilder->count();

        return $count;
    }

    private function createQueryBuilder(): DynamoDbQueryBuilder
    {
        $modelClassName = $this->getModelClassName();

        /** @var TModel $model */
        $model = new $modelClassName();

        /** @var \BaoPham\DynamoDb\DynamoDbQueryBuilder $query */
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
     * @param array<string, mixed> $params
     */
    private function applyQueryConditions(DynamoDbQueryBuilder $queryBuilder, array $params): DynamoDbQueryBuilder
    {
        if (count($params) > 0) {
            $queryBuilder = $this->applyWhereConditions($queryBuilder, $params);
        }

        return $this->applyOrderBy($queryBuilder);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function applyWhereConditions(DynamoDbQueryBuilder $queryBuilder, array $params): DynamoDbQueryBuilder
    {
        foreach ($params as $key => $value) {
            /** @var \BaoPham\DynamoDb\DynamoDbQueryBuilder $queryBuilder */
            $queryBuilder = $queryBuilder->where($key, $value);
        }

        return $queryBuilder;
    }

    private function applyOrderBy(DynamoDbQueryBuilder $queryBuilder): DynamoDbQueryBuilder
    {
        // DynamoDB doesn't support general orderBy like SQL.
        // Sorting is only available for range keys in indexes using ScanIndexForward.
        // This method is kept for interface compatibility but doesn't apply ordering.
        return $queryBuilder;
    }

}
