<?php

declare(strict_types = 1);

namespace Pekral\Arch\ModelManager\Mysql;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for managing Eloquent model operations (CRUD).
 *
 * Provides a consistent interface for create, update, delete operations
 * with support for batch processing and advanced database features.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class BaseModelManager
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
        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();

        return (bool) $model->newQuery()
            ->where($parameters)
            ->delete();
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $data
     * @param array<string, string|int> $conditions
     */
    public function updateByParams(array $data, array $conditions): int
    {
        return $this->createModel()->newQuery()
            ->where($conditions)
            ->update($data);
    }

    /**
     * @template TKey as string
     * @template TValue
     * @param array<TKey, TValue> $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        $model = $this->createModel();
        $model->fill($data)
            ->save();

        return $model;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     * @return int Number of created records
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
     * @param string $keyColumn Column to match records (usually 'id')
     * @return int Number of updated records
     */
    public function bulkUpdate(array $dataArray, string $keyColumn = 'id'): int
    {
        if ($dataArray === []) {
            return 0;
        }

        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();
        
        $updatedCount = 0;
        
        foreach ($dataArray as $data) {
            if (!isset($data[$keyColumn])) {
                continue;
            }
            
            $keyValue = $data[$keyColumn];
            unset($data[$keyColumn]);
            
            if ($data === []) {
                continue;
            }
            
            $updatedCount += $model->newQuery()
                ->where($keyColumn, $keyValue)
                ->update($data);
        }
        
        return $updatedCount;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     * @return int Number of soft deleted records
     */
    public function softDeleteByParams(array $parameters): int
    {
        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();

        $query = $model->newQuery();
        
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->update(['deleted_at' => now()]);
    }

    /**
     * Soft delete a single model by ID.
     */
    public function softDelete(int|string $id): bool
    {
        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();

        $affected = $model->newQuery()
            ->where('id', $id)
            ->update(['deleted_at' => now()]);

        return $affected > 0;
    }

    /**
     * Restore a soft deleted model by ID.
     */
    public function restore(int|string $id): bool
    {
        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();

        $query = $model->newQuery();
        
        // @phpstan-ignore-next-line - withTrashed() is available when model uses SoftDeletes trait
        $affected = $query
            ->withTrashed()
            ->where('id', $id)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        return $affected > 0;
    }

    /**
     * Restore soft deleted models by parameters.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     * @return int Number of restored records
     */
    public function restoreByParams(array $parameters): int
    {
        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();

        // @phpstan-ignore-next-line - withTrashed() is available when model uses SoftDeletes trait
        $query = $model->newQuery()
            ->withTrashed()
            ->whereNotNull('deleted_at');
        
        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = $query;
        
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->update(['deleted_at' => null]);
    }

    /**
     * Force delete a model by ID (permanent deletion).
     */
    public function forceDelete(int|string $id): bool
    {
        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();
        
        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = $model->newQuery();
        
        // @phpstan-ignore-next-line - withTrashed() is available when model uses SoftDeletes trait
        $query = $query->withTrashed();
        
        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = $query;
        
        $query = $query->where('id', $id);

        $affected = $query->forceDelete();

        return $affected > 0;
    }

    /**
     * Force delete models by parameters (permanent deletion).
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     * @return int Number of force deleted records
     */
    public function forceDeleteByParams(array $parameters): int
    {
        $modelClassName = $this->getModelClassName();
        $model = new $modelClassName();

        // @phpstan-ignore-next-line - withTrashed() is available when model uses SoftDeletes trait
        $query = $model->newQuery()->withTrashed();
        
        /** @var \Illuminate\Database\Eloquent\Builder<TModel> $query */
        $query = $query;
        
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        $affected = $query->forceDelete();
        \assert(\is_int($affected));
        
        return $affected;
    }

    /**
     * @return TModel
     */
    private function createModel(): Model
    {
        $modelClassName = $this->getModelClassName();

        return new $modelClassName([]);
    }

}
