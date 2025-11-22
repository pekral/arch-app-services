<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository\DynamoDb;

use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorInstance;
use Illuminate\Support\Collection;
use Pekral\Arch\Exceptions\GroupByNotSupported;
use Pekral\Arch\Exceptions\OrderByNotSupported;
use Pekral\Arch\Exceptions\RelationsNotSupported;
use Pekral\Arch\Repository\Repository;

use function assert;
use function config;
use function count;
use function is_array;

/**
 * @template TModel of \BaoPham\DynamoDb\DynamoDbModel
 * @implements \Pekral\Arch\Repository\Repository<TModel>
 */
abstract class BaseDynamoDbRepository implements Repository
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
     * @throws \Pekral\Arch\Exceptions\RelationsNotSupported
     * @throws \Pekral\Arch\Exceptions\OrderByNotSupported
     * @throws \Pekral\Arch\Exceptions\GroupByNotSupported
     */
    public function paginateByParams(
        array $params,
        array $withRelations = [],
        ?int $itemsPerPage = null,
        array $orderBy = [],
        array $groupBy = [],
    ): LengthAwarePaginator {
        $this->validateUnsupportedParams($withRelations, $orderBy, $groupBy);

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder = $this->applyQueryConditions($queryBuilder, $params);

        $perPage = $this->resolveItemsPerPage($itemsPerPage);
        $items = $queryBuilder->take($perPage)->get();
        assert($items instanceof Collection);

        /** @var array<int, array<int, mixed>> $countParams */
        $countParams = $params;
        $total = $this->countByParams($countParams, []);

        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, TModel> $paginator */
        $paginator = new LengthAwarePaginatorInstance(
            $items,
            $total,
            $perPage,
            1,
            [
                'pageName' => 'page',
                'path' => request()->url(),
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
     * @throws \Pekral\Arch\Exceptions\RelationsNotSupported
     * @throws \Pekral\Arch\Exceptions\OrderByNotSupported
     */
    public function getOneByParams(Collection|array $params, array $with = [], array $orderBy = []): Model
    {
        $this->validateUnsupportedParams($with, $orderBy, []);

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder = $this->applyWhereConditions($queryBuilder, $this->normalizeParams($params));
        $queryBuilder = $this->applyOrderBy($queryBuilder);

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
     * @throws \Pekral\Arch\Exceptions\RelationsNotSupported
     * @throws \Pekral\Arch\Exceptions\OrderByNotSupported
     */
    public function findOneByParams(Collection|array $params, array $with = [], array $orderBy = []): ?Model
    {
        $this->validateUnsupportedParams($with, $orderBy, []);

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder = $this->applyWhereConditions($queryBuilder, $this->normalizeParams($params));
        $queryBuilder = $this->applyOrderBy($queryBuilder);

        /** @var TModel|null $result */
        $result = $queryBuilder->first();

        return $result;
    }

    /**
     * @param \Illuminate\Support\Collection<int, mixed>|array<int, array<int, mixed>> $params
     * @param array<string> $groupBy
     * @throws \Pekral\Arch\Exceptions\GroupByNotSupported
     */
    public function countByParams(Collection|array $params, array $groupBy = []): int
    {
        if (count($groupBy) > 0) {
            throw GroupByNotSupported::forDynamoDb();
        }

        $queryBuilder = $this->createQueryBuilder();
        $normalizedParams = $this->normalizeParams($params);

        if (count($normalizedParams) > 0) {
            $queryBuilder = $this->applyWhereConditions($queryBuilder, $normalizedParams);
        }

        $count = $queryBuilder->count();
        assert(is_int($count));

        return $count;
    }

    /**
     * @return \BaoPham\DynamoDb\DynamoDbQueryBuilder
     */
    public function createQueryBuilder(): mixed
    {
        $modelClassName = $this->getModelClassName();
        $query = new $modelClassName()->newQuery();
        assert($query instanceof DynamoDbQueryBuilder);

        return $query;
    }

    /**
     * @return \BaoPham\DynamoDb\DynamoDbQueryBuilder
     */
    public function query(): mixed
    {
        return $this->createQueryBuilder();
    }

    private function resolveItemsPerPage(?int $itemsPerPage): int
    {
        if ($itemsPerPage !== null && $itemsPerPage > 0) {
            return $itemsPerPage;
        }

        $configValue = config('arch.default_items_per_page', 15);

        return is_int($configValue) && $configValue > 0 ? $configValue : 15;
    }

    /**
     * @param array<string> $withRelations
     * @param array<string, string> $orderBy
     * @param array<string> $groupBy
     * @throws \Pekral\Arch\Exceptions\RelationsNotSupported
     * @throws \Pekral\Arch\Exceptions\OrderByNotSupported
     * @throws \Pekral\Arch\Exceptions\GroupByNotSupported
     */
    private function validateUnsupportedParams(array $withRelations, array $orderBy, array $groupBy): void
    {
        if (count($withRelations) > 0) {
            throw RelationsNotSupported::forDynamoDb();
        }

        if (count($orderBy) > 0) {
            throw OrderByNotSupported::forDynamoDb();
        }

        if (count($groupBy) > 0) {
            throw GroupByNotSupported::forDynamoDb();
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $params
     * @return array<string, mixed>
     */
    private function normalizeParams(Collection|array $params): array
    {
        return is_array($params) ? $params : $params->toArray();
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
        /** @var \BaoPham\DynamoDb\DynamoDbQueryBuilder $result */
        $result = Collection::make($params)->reduce(
            static function (DynamoDbQueryBuilder $builder, mixed $value, string $key): DynamoDbQueryBuilder {
                $whereResult = $builder->where($key, $value);
                assert($whereResult instanceof DynamoDbQueryBuilder);

                return $whereResult;
            },
            $queryBuilder,
        );

        return $result;
    }

    private function applyOrderBy(DynamoDbQueryBuilder $queryBuilder): DynamoDbQueryBuilder
    {
        return $queryBuilder;
    }

}
