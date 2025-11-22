<?php

declare(strict_types = 1);

namespace Pekral\Arch\ModelManager\Mysql;

use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\Exceptions\MassUpdateNotAvailable;
use Pekral\Arch\ModelManager\ModelManager;

use function class_uses_recursive;
use function collect;
use function count;
use function in_array;

/**
 * Base class for managing Eloquent model operations (CRUD).
 *
 * Provides a consistent interface for create, update, delete operations
 * with support for batch processing and advanced database features.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
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
        return (bool) $this->newModelQuery()
            ->where($parameters)
            ->delete();
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     */
    public function bulkDeleteByParams(array $parameters): void
    {
        $this->newModelQuery()
            ->where($parameters)
            ->delete();
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
    public function create(array $data): Model
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
     * @return TModel
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
        $result = $modelClassName::insert($dataArray);

        return $result ? count($dataArray) : 0;
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

        return collect($dataArray)
            ->filter(fn (array $data): bool => isset($data[$keyColumn]))
            ->map(function (array $data) use ($keyColumn): array {
                $keyValue = $data[$keyColumn];
                unset($data[$keyColumn]);

                return [
                    'key' => $keyValue,
                    'data' => $data,
                ];
            })
            ->filter(fn (array $item): bool => $item['data'] !== [])
            ->sum(fn (array $item): int => $this->newModelQuery()
                ->where($keyColumn, $item['key'])
                ->update($item['data']));
    }

    /**
     * Mass update records using CASE WHEN SQL statement.
     * Requires model to use MassUpdatable trait from iksaku/laravel-mass-update package.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>|\Illuminate\Database\Eloquent\Model> $values
     * @param array<int, string>|string|null $uniqueBy
     * @throws \Pekral\Arch\Exceptions\MassUpdateNotAvailable
     */
    public function rawMassUpdate(array $values, null|array|string $uniqueBy = null): int
    {
        if ($values === []) {
            return 0;
        }

        $modelClassName = $this->getModelClassName();

        if (!in_array('Iksaku\Laravel\MassUpdate\MassUpdatable', class_uses_recursive($modelClassName), true)) {
            throw MassUpdateNotAvailable::traitNotUsed($modelClassName);
        }

        $query = $this->newModelQuery();

        $massUpdate = [$query, 'massUpdate'];

        /** @phpstan-var callable(array, array|string|null): int $massUpdate */
        /** @var int $result */
        $result = call_user_func($massUpdate, $values, $uniqueBy);

        return $result;
    }

    /**
     * @return TModel
     */
    public function createNewModelInstance(): Model
    {
        $modelClassName = $this->getModelClassName();

        return new $modelClassName([]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    private function newModelQuery(): mixed
    {
        $modelClassName = $this->getModelClassName();

        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = new $modelClassName()->newQuery();

        return $query;
    }

}
