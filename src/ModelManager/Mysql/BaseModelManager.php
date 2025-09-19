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
     * @return TModel
     */
    private function createModel(): Model
    {
        $modelClassName = $this->getModelClassName();

        return new $modelClassName([]);
    }

}
