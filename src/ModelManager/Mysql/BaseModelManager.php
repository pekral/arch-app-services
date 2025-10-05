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
     * @return TModel
     */
    private function createModel(): Model
    {
        $modelClassName = $this->getModelClassName();

        return new $modelClassName([]);
    }

}
