<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

    /** @var \Pekral\Arch\ModelManager\Mysql\BaseModelManager<TModel> */
    private BaseModelManager $baseModelManager;
    
    /** @var \Pekral\Arch\Repository\Mysql\BaseRepository<TModel> */
    private BaseRepository $baseRepository;

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
    abstract protected function createModelManager(): BaseModelManager;

    /**
     * Create a repository instance.
     *
     * @return \Pekral\Arch\Repository\Mysql\BaseRepository<TModel>
     */
    abstract protected function createRepository(): BaseRepository;

    public function __construct()
    {
        $this->baseModelManager = $this->createModelManager();
        $this->baseRepository = $this->createRepository();
    }

    /**
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $attributes
     *                                                                                       return TModel
     */
    public function create(array|Collection $attributes): Model
    {
        return $this->baseModelManager->create($this->normalizeData($attributes));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $data
     * @param array<string, string|int> $conditions
     */
    public function updateByParams(array|Collection $data, array $conditions): int
    {
        return $this->baseModelManager->updateByParams($this->normalizeData($data), $conditions);
    }

    /**
     * Find a model by given criteria.
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @param array<string> $with
     * @return TModel|null
     */
    public function findOneByParams(array|Collection $parameters, array $with = []): ?Model
    {
        return $this->baseRepository->findOneByParams($this->normalizeData($parameters), $with);
    }

    /**
     * Find a model by given criteria or fail.
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @param array<string> $with
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(array|Collection $parameters, array $with = []): Model
    {
        return $this->baseRepository->getOneByParams($this->normalizeData($parameters), $with);
    }

    /**
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @param array<string> $with
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel>
     * @phpstan-return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel>
     */
    public function paginateByParams(array|Collection $parameters = [], array $with = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel> $paginator */
        $paginator = $this->baseRepository->paginateByParams(
            $this->normalizeData($parameters),
            $with,
            $perPage,
        );

        return $paginator;
    }

    /**
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     */
    public function deleteByParams(array|Collection $parameters): bool
    {
        return $this->baseModelManager->deleteByParams($this->normalizeData($parameters));
    }

    /**
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeData(array|Collection $data): array
    {
        /** @var array<string, mixed> $result */
        $result = $data instanceof Collection ? $data->toArray() : $data;

        return $result;
    }

}
