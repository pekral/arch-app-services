<?php

declare(strict_types = 1);

namespace Pekral\Arch\ModelManager\Mysql;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\Exceptions\MassUpdateNotAvailable;
use Pekral\Arch\ModelManager\ModelManager;

use function class_uses_recursive;
use function count;
use function in_array;

/**
 * Base class for managing Eloquent model operations (CRUD).
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
        return $model->delete() ?? false;
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
        /** @var TModel $model */
        $model = $this->getModelClassName()::updateOrCreate($attributes, $values);

        return $model;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return TModel
     */
    public function getOrCreate(array $attributes, array $values = []): Model
    {
        /** @var TModel $model */
        $model = $this->getModelClassName()::firstOrCreate($attributes, $values);

        return $model;
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

        return $this->getModelClassName()::insert($dataArray) ? count($dataArray) : 0;
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

        $this->getModelClassName()::insertOrIgnore($dataArray);
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
            ->map(fn (array $data): array => $this->extractKeyAndData($data, $keyColumn))
            ->filter(fn (array $item): bool => $item['data'] !== [])
            ->sum(fn (array $item): int => $this->newModelQuery()
                ->where($keyColumn, $item['key'])
                ->update($item['data']));
    }

    /**
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

        $this->ensureMassUpdatableTraitUsed($this->getModelClassName());

        /** @var int $result @phpstan-ignore method.notFound (massUpdate provided by MassUpdatable trait) */
        $result = $this->newModelQuery()->massUpdate($values, $uniqueBy);

        return $result;
    }

    /**
     * @return TModel
     */
    public function createNewModelInstance(): Model
    {
        return new ($this->getModelClassName())([]);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $data
     * @return array{
     *     key: TValue,
     *     data: array<TKey, TValue>
     * }
     */
    private function extractKeyAndData(array $data, string $keyColumn): array
    {
        $keyValue = $data[$keyColumn];
        unset($data[$keyColumn]);

        return [
            'data' => $data,
            'key' => $keyValue,
        ];
    }

    /**
     * @param class-string<TModel> $modelClassName
     * @throws \Pekral\Arch\Exceptions\MassUpdateNotAvailable
     */
    private function ensureMassUpdatableTraitUsed(string $modelClassName): void
    {
        if (!in_array('Iksaku\Laravel\MassUpdate\MassUpdatable', class_uses_recursive($modelClassName), true)) {
            throw MassUpdateNotAvailable::traitNotUsed($modelClassName);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    private function newModelQuery(): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = $this->createNewModelInstance()->newQuery();

        return $query;
    }

}
