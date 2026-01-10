<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Pekral\Arch\ModelManager\ModelManager;
use Pekral\Arch\Repository\Repository;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract readonly class BaseModelService
{

    /**
     * @return \Pekral\Arch\ModelManager\ModelManager<TModel>
     */
    abstract public function getModelManager(): ModelManager;

    /**
     * @return \Pekral\Arch\Repository\Repository<TModel>
     */
    abstract public function getRepository(): Repository;

    /**
     * @return class-string<TModel>
     */
    abstract protected function getModelClass(): string;

    /**
     * @param array<string, mixed> $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        return $this->getModelManager()->create($data);
    }

    /**
     * @param TModel $model
     * @param array<string, mixed> $data
     */
    public function updateModel(Model $model, array $data): bool
    {
        return $this->getModelManager()->update($model, $data);
    }

    /**
     * @param TModel $model
     */
    public function deleteModel(Model $model): bool
    {
        return $this->getModelManager()->delete($model);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string, string> $orderBy
     * @return TModel|null
     */
    public function findOneByParams(array $parameters, array $with = [], array $orderBy = []): ?Model
    {
        return $this->getRepository()->findOneByParams($parameters, $with, $orderBy);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string, string> $orderBy
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
     * @param array<string, string> $orderBy
     * @param array<string> $groupBy
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel>
     */
    public function paginateByParams(
        array $parameters = [],
        array $with = [],
        ?int $perPage = null,
        array $orderBy = [],
        array $groupBy = [],
    ): LengthAwarePaginator {
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel> $paginator */
        $paginator = $this->getRepository()->paginateByParams($parameters, $with, $perPage, $orderBy, $groupBy);

        return $paginator;
    }

    /**
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

    /**
     * @param array<string, mixed> $parameters
     */
    public function bulkDeleteByParams(array $parameters): void
    {
        $this->getModelManager()->bulkDeleteByParams($parameters);
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function bulkCreate(array $data): int
    {
        return $this->getModelManager()->bulkCreate($data);
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function bulkUpdate(array $data, string $keyColumn = 'id'): int
    {
        return $this->getModelManager()->bulkUpdate($data, $keyColumn);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->getModelManager()->updateOrCreate($attributes, $values);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return TModel
     */
    public function getOrCreate(array $attributes, array $values = []): Model
    {
        return $this->getModelManager()->getOrCreate($attributes, $values);
    }

}
