<?php

declare(strict_types = 1);

namespace Pekral\Arch\ModelManager\DynamoDb;

use BaoPham\DynamoDb\DynamoDbModel;
use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\Exceptions\MassUpdateNotAvailable;
use Pekral\Arch\Exceptions\ShouldNotHappen;
use Pekral\Arch\ModelManager\ModelManager;

use function count;
use function mb_strlen;

/**
 * Base class for managing DynamoDB model operations (CRUD).
 *
 * Provides a consistent interface for create, update, delete operations
 * with support for batch processing. Note that DynamoDB has limitations:
 * - insert() and insertOrIgnore() are not available
 * - bulkUpdate() works differently (updates items one by one)
 * - rawMassUpdate() is not supported
 *
 * @template TModel of \BaoPham\DynamoDb\DynamoDbModel
 * @implements \Pekral\Arch\ModelManager\ModelManager<TModel>
 */
abstract class BaseModelManager implements ModelManager
{

    /**
     * @return class-string<TModel>
     */
    abstract protected function getModelClassName(): string;

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     */
    public function deleteByParams(array $parameters): bool
    {
        $queryBuilder = $this->newModelQuery();
        $queryBuilder = $this->applyWhereConditions($queryBuilder, $parameters);
        /** @var \BaoPham\DynamoDb\DynamoDbCollection<\BaoPham\DynamoDb\DynamoDbModel> $collection */
        $collection = $queryBuilder->get();

        foreach ($collection as $model) {
            $model->delete();
        }

        return true;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     */
    public function bulkDeleteByParams(array $parameters): void
    {
        if (count($parameters) > 0) {
            throw new ShouldNotHappen();
        }
    }

    /**
     * @param TModel $model
     */
    public function delete(Model $model): bool
    {
        $result = $model->delete();

        if ($result === null) {
            return false;
        }

        return $result;
    }

    /**
     * @template TKey as string
     * @template TValue
     * @param array<TKey, TValue> $data
     * @return TModel
     */
    public function create(array $data): DynamoDbModel
    {
        $model = $this->createNewModelInstance();
        $model->fill($data)->save();

        return $model;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Model $model, array $data): bool
    {
        return $model->fill($data)->save();
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): DynamoDbModel
    {
        $model = $this->findOneByAttributes($attributes);

        if ($model !== null) {
            $this->update($model, $values);

            return $model;
        }

        return $this->create([...$attributes, ...$values]);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return TModel
     */
    public function getOrCreate(array $attributes, array $values = []): DynamoDbModel
    {
        $model = $this->findOneByAttributes($attributes);

        if ($model !== null) {
            return $model;
        }

        return $this->create([...$attributes, ...$values]);
    }

    /**
     * Bulk create multiple records.
     * Note: DynamoDB doesn't have native bulk insert, so we create items one by one.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     */
    public function bulkCreate(array $dataArray): int
    {
        if (count($dataArray) > 0) {
            throw new ShouldNotHappen();
        }

        return 0;
    }

    /**
     * Bulk insert records, ignoring duplicates based on unique constraints.
     * Note: DynamoDB doesn't support insertOrIgnore, so we try to create and ignore errors.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     */
    public function insertOrIgnore(array $dataArray): void
    {
        if (count($dataArray) > 0) {
            throw new ShouldNotHappen();
        }
    }

    /**
     * Bulk update multiple records.
     * Note: DynamoDB doesn't have native bulk update, so we update items one by one.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     */
    public function bulkUpdate(array $dataArray, string $keyColumn = 'id'): int
    {
        if (count($dataArray) > 0 && mb_strlen($keyColumn) > 0) {
            throw new ShouldNotHappen();
        }

        return 0;
    }

    /**
     * Mass update records using CASE WHEN SQL statement.
     * Note: This is not supported in DynamoDB.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>|\Illuminate\Database\Eloquent\Model> $values
     * @param array<int, string>|string|null $uniqueBy
     * @throws \Pekral\Arch\Exceptions\MassUpdateNotAvailable
     */
    public function rawMassUpdate(array $values, null|array|string $uniqueBy = null): int
    {
        if (count($values) > 0 && $uniqueBy !== null) {
            throw MassUpdateNotAvailable::notSupportedForDynamoDb();
        }

        return 0;
    }

    /**
     * @return TModel
     */
    public function createNewModelInstance(): DynamoDbModel
    {
        $modelClassName = $this->getModelClassName();

        return new $modelClassName([]);
    }

    private function newModelQuery(): DynamoDbQueryBuilder
    {
        $modelClassName = $this->getModelClassName();

        /** @var TModel $model */
        $model = new $modelClassName();

        /** @var \BaoPham\DynamoDb\DynamoDbQueryBuilder $query */
        $query = $model->newQuery();

        return $query;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function applyWhereConditions(DynamoDbQueryBuilder $queryBuilder, array $parameters): DynamoDbQueryBuilder
    {
        foreach ($parameters as $key => $value) {
            /** @var \BaoPham\DynamoDb\DynamoDbQueryBuilder $queryBuilder */
            $queryBuilder = $queryBuilder->where($key, $value);
        }

        return $queryBuilder;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return TModel|null
     */
    private function findOneByAttributes(array $attributes): ?DynamoDbModel
    {
        $queryBuilder = $this->newModelQuery();
        $queryBuilder = $this->applyWhereConditions($queryBuilder, $attributes);

        /** @var TModel|null $result */
        $result = $queryBuilder->first();

        return $result;
    }

}
