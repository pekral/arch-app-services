<?php

declare(strict_types = 1);

namespace Pekral\Arch\ModelManager\Mysql;

use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\Exceptions\MassUpdateNotAvailable;

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
     * @template TKey as string
     * @template TValue
     * @param array<TKey, TValue> $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        $model = $this->createNewModelInstance();
        $model->fill($data)
            ->save();

        return $model;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Model $model, array $data): bool
    {
        return $model->fill($data)
            ->save();
    }

    /**
     * Update existing record or create a new one if it doesn't exist.
     *
     * @param array<string, mixed> $attributes Attributes to search for
     * @param array<string, mixed> $values Values to update/create with
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $modelClassName = $this->getModelClassName();

        /** @phpstan-ignore-next-line */
        return $modelClassName::updateOrCreate($attributes, $values);
    }

    /**
     * Get existing record or create a new one if it doesn't exist.
     *
     * @param array<string, mixed> $attributes Attributes to search for
     * @param array<string, mixed> $values Values to use when creating
     * @return TModel
     */
    public function getOrCreate(array $attributes, array $values = []): Model
    {
        $modelClassName = $this->getModelClassName();

        /** @phpstan-ignore-next-line */
        return $modelClassName::firstOrCreate($attributes, $values);
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
     * Bulk insert records, ignoring duplicates based on unique constraints.
     *
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
     * Mass update records using CASE WHEN SQL statement.
     * Requires model to use MassUpdatable trait from iksaku/laravel-mass-update package.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>|\Illuminate\Database\Eloquent\Model> $values Array of records to update or Model instances
     * @param array<int, string>|string|null $uniqueBy Column(s) to use as unique identifier (defaults to model's primary key)
     * @return int Number of updated records
     * @throws \Pekral\Arch\Exceptions\MassUpdateNotAvailable
     */
    public function rawMassUpdate(array $values, null|array|string $uniqueBy = null): int
    {
        if ($values === []) {
            return 0;
        }

        if (!trait_exists('Iksaku\Laravel\MassUpdate\MassUpdatable')) {
            throw MassUpdateNotAvailable::missingPackage();
        }

        $modelClassName = $this->getModelClassName();

        if (!in_array('Iksaku\Laravel\MassUpdate\MassUpdatable', class_uses_recursive($modelClassName), true)) {
            throw MassUpdateNotAvailable::traitNotUsed($modelClassName);
        }

        $model = new $modelClassName();

        /** @phpstan-ignore-next-line */
        return $model->newQuery()->massUpdate($values, $uniqueBy);
    }

    /**
     * @return TModel
     */
    public function createNewModelInstance(): Model
    {
        $modelClassName = $this->getModelClassName();

        return new $modelClassName([]);
    }

}
