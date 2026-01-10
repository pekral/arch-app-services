<?php

declare(strict_types = 1);

namespace Pekral\Arch\ModelManager\DynamoDb;

use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\Exceptions\DynamoDbNotSupported;
use Pekral\Arch\ModelManager\ModelManager;

use function count;

/**
 * Base class for managing DynamoDb model operations (CRUD).
 *
 * Provides a consistent interface for create, update, delete operations
 * with support for batch processing and advanced database features.
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
        $query = $this->newModelQuery()->where($parameters);
        assert($query instanceof DynamoDbQueryBuilder);

        return (bool) $query->delete();
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     */
    public function bulkDeleteByParams(array $parameters): void
    {
        $query = $this->newModelQuery()->where($parameters);
        assert($query instanceof DynamoDbQueryBuilder);

        $query->delete();
    }

    public function delete(Model $model): bool
    {
        return $model->delete() ?? false;
    }

    /**
     * @template TKey as string
     * @template TValue
     * @param array<TKey, TValue> $data
     */
    public function create(array $data): Model
    {
        $model = $this->createNewModelInstance();
        $model->forceFill($data)->save();

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
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $modelClassName = $this->getModelClassName();

        /** @var TModel $result */
        $result = $modelClassName::updateOrCreate($attributes, $values);

        return $result;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     */
    public function getOrCreate(array $attributes, array $values = []): Model
    {
        $modelClassName = $this->getModelClassName();

        /** @var TModel $result */
        $result = $modelClassName::firstOrCreate($attributes, $values);

        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     */
    public function bulkCreate(array $dataArray): int
    {
        if ($dataArray === []) {
            return 0;
        }

        $modelClassName = $this->getModelClassName();

        return $modelClassName::insert($dataArray) ? count($dataArray) : 0;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     */
    public function insertOrIgnore(array $dataArray): void
    {
        if ($dataArray === []) {
            return;
        }

        $modelClassName = $this->getModelClassName();
        $modelClassName::insertOrIgnore($dataArray);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     */
    public function bulkUpdate(array $dataArray, string $keyColumn = 'id'): int
    {
        if ($dataArray === []) {
            return 0;
        }

        $updated = 0;

        foreach ($dataArray as $data) {
            if ($this->updateSingleRecord($data, $keyColumn)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Mass update records using CASE WHEN SQL statement.
     * Requires model to use MassUpdatable trait from iksaku/laravel-mass-update package.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $values
     * @param array<int, string>|string|null $uniqueBy
     * @throws \Pekral\Arch\Exceptions\DynamoDbNotSupported
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function rawMassUpdate(array $values, null|array|string $uniqueBy = null): int
    {
        throw DynamoDbNotSupported::rawMassUpdateNotSupported();
    }

    public function createNewModelInstance(): Model
    {
        $modelClassName = $this->getModelClassName();

        return new $modelClassName([]);
    }

    protected function newModelQuery(): DynamoDbQueryBuilder
    {
        $modelClassName = $this->getModelClassName();

        $query = new $modelClassName()->newQuery();
        assert($query instanceof DynamoDbQueryBuilder);

        return $query;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $data
     */
    private function updateSingleRecord(array $data, string $keyColumn): bool
    {
        if (!isset($data[$keyColumn])) {
            return false;
        }

        $keyValue = $data[$keyColumn];
        unset($data[$keyColumn]);

        if ($data === []) {
            return false;
        }

        $query = $this->newModelQuery()->where($keyColumn, $keyValue);
        assert($query instanceof DynamoDbQueryBuilder);

        /** @var TModel|null $model */
        $model = $query->first();

        if ($model === null) {
            return false;
        }

        $model->fill($data)->save();

        return true;
    }

}
