<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Repository\Mysql\BaseRepository;

/**
 * Base service class providing CRUD operations for Eloquent models.
 *
 * Follows Domain-Driven Design principles and Laravel conventions.
 * Automatically creates ModelManager for write operations and Repository for read operations.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract readonly class BaseModelService
{

    /**
     * Get the model class this service manages.
     *
     * @return class-string<TModel>
     */
    abstract protected function getModelClass(): string;

    /**
     * Create a model manager instance.
     *
     * @return \Pekral\Arch\ModelManager\Mysql\BaseModelManager<TModel>
     */
    abstract protected function getModelManager(): BaseModelManager;

    /**
     * Create a repository instance.
     *
     * @return \Pekral\Arch\Repository\Mysql\BaseRepository<TModel>
     */
    abstract protected function getRepository(): BaseRepository;

    /**
     * @param array<string, mixed> $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        return $this->getModelManager()->create($data);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param TModel $model
     * @param array<TKey, TValue> $data
     * @return TModel
     */
    public function updateModel(Model $model, array $data): Model
    {
        $model->update($data);

        return $model;
    }

    /**
     * @param TModel $model
     */
    public function deleteModel(Model $model): bool
    {
        $result = $model->delete();

        if ($result === null) {
            return false;
        }

        return $result;
    }

    /**
     * Find a model by given criteria.
     *
     * @param array<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string> $orderBy
     * @return TModel|null
     */
    public function findOneByParams(array $parameters, array $with = [], array $orderBy = []): ?Model
    {
        return $this->getRepository()->findOneByParams($parameters, $with, $orderBy);
    }

    /**
     * Find a model by given criteria or fail.
     *
     * @param array<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string> $orderBy
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(array $parameters, array $with = [], array $orderBy = []): Model
    {
        return $this->getRepository()->getOneByParams($parameters, $with, $orderBy);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string> $orderBy
     * @param array<string> $groupBy
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel>
     * @phpstan-return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel>
     */
    public function paginateByParams(
        array $parameters = [],
        array $with = [],
        ?int $perPage = null,
        array $orderBy = [],
        array $groupBy = [],
    ): LengthAwarePaginator {
        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, TModel> $paginator */
        $paginator = $this->getRepository()->paginateByParams($parameters, $with, $perPage, $orderBy, $groupBy);

        return $paginator;
    }

    /**
     * Count models by given criteria.
     *
     * @param array<int, array<int, mixed>> $parameters
     * @param array<string> $groupBy
     */
    public function countByParams(array $parameters, array $groupBy = []): int
    {
        return $this->getRepository()->countByParams($parameters, $groupBy);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function deleteByParams(array $parameters): bool
    {
        return $this->getModelManager()->deleteByParams($parameters);
    }

}
