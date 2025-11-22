<?php

declare(strict_types = 1);

namespace Pekral\Arch\ModelManager\DynamoDb;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\Exceptions\MassUpdateNotAvailable;
use Pekral\Arch\ModelManager\ModelManager;

use function collect;

/**
 * @template TModel of \BaoPham\DynamoDb\DynamoDbModel
 * @implements \Pekral\Arch\ModelManager\ModelManager<TModel>
 */
abstract class BaseDynamoDbModelManager implements ModelManager
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
        $models = $this->getModelsByParams($parameters);

        if ($models->isEmpty()) {
            return false;
        }

        $this->deleteModels($models);

        return true;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     */
    public function bulkDeleteByParams(array $parameters): void
    {
        $models = $this->getModelsByParams($parameters);
        $this->deleteModels($models);
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
        $existing = $this->findModelByAttributes($attributes);

        if ($existing !== null) {
            $this->update($existing, $values);

            return $existing;
        }

        return $this->create([...$attributes, ...$values]);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return TModel
     */
    public function getOrCreate(array $attributes, array $values = []): Model
    {
        $existing = $this->findModelByAttributes($attributes);

        if ($existing !== null) {
            return $existing;
        }

        return $this->create([...$attributes, ...$values]);
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

        return collect($dataArray)->each(fn (array $data): Model => $this->create($data))->count();
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

        collect($dataArray)->each(fn (array $data): Model => $this->getOrCreate($data, $data));
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
            ->sum(fn (array $item): int => $this->updateByKey($keyColumn, $item['key'], $item['data']));
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
        unset($values, $uniqueBy);

        throw MassUpdateNotAvailable::notSupportedForDynamoDb();
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
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    private function getModelsByParams(array $parameters): Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, TModel> $models */
        $models = $this->newModelQuery()->where($parameters)->get();

        return $models;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, TModel> $models
     */
    private function deleteModels(Collection $models): void
    {
        $models->each(fn (Model $model): mixed => $model->delete());
    }

    /**
     * @param array<string, mixed> $attributes
     * @return TModel|null
     */
    private function findModelByAttributes(array $attributes): ?Model
    {
        $modelClassName = $this->getModelClassName();

        /** @var TModel|null $existing */
        $existing = $modelClassName::where($attributes)->first();

        return $existing;
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

    /**
     * @param array<string, mixed> $data
     */
    private function updateByKey(string $keyColumn, mixed $keyValue, array $data): int
    {
        $model = $this->newModelQuery()
            ->where($keyColumn, $keyValue)
            ->first();

        if ($model === null) {
            return 0;
        }

        return $this->update($model, $data) ? 1 : 0;
    }

}
