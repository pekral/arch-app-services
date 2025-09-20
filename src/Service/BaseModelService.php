<?php

declare(strict_types = 1);

namespace Pekral\Arch\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Validation\ValidatesData;

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

    /** @use \Pekral\Arch\Validation\ValidatesData<TModel> */
    use ValidatesData;

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
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $data
     * @param array<string, mixed>|null $rules
     * @return TModel
     */
    public function create(array|Collection $data, ?array $rules = null): Model
    {
        $normalizedData = $this->normalizeData($data);
        
        if ($rules !== null) {
            $this->validateData(
                $normalizedData,
                $rules,
                $this->getValidationMessages(),
                $this->getValidationAttributes(),
            );
        }

        return $this->getModelManager()->create($normalizedData);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param \Illuminate\Support\Collection<TKey, TValue>|array<TKey, TValue> $data
     * @param array<string, string|int> $conditions
     * @param array<string, mixed>|null $rules
     */
    public function updateByParams(array|Collection $data, array $conditions, ?array $rules = null): int
    {
        $normalizedData = $this->normalizeData($data);
        
        if ($rules !== null) {
            $normalizedData = $this->validateData($normalizedData, $rules);
        } elseif ($this->getUpdateRules() !== []) {
            $normalizedData = $this->validateData(
                $normalizedData,
                $this->getUpdateRules(),
                $this->getValidationMessages(),
                $this->getValidationAttributes(),
            );
        }
        
        return $this->getModelManager()->updateByParams($normalizedData, $conditions);
    }

    /**
     * Find a model by given criteria.
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string> $orderBy
     * @return TModel|null
     */
    public function findOneByParams(array|Collection $parameters, array $with = [], array $orderBy = []): ?Model
    {
        return $this->getRepository()->findOneByParams($this->normalizeData($parameters), $with, $orderBy);
    }

    /**
     * Find a model by given criteria or fail.
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string> $orderBy
     * @return TModel
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOneByParams(array|Collection $parameters, array $with = [], array $orderBy = []): Model
    {
        return $this->getRepository()->getOneByParams($this->normalizeData($parameters), $with, $orderBy);
    }

    /**
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string> $orderBy
     * @param array<string> $groupBy
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel>
     * @phpstan-return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel>
     */
    public function paginateByParams(
        array|Collection $parameters = [],
        array $with = [],
        ?int $perPage = null,
        array $orderBy = [],
        array $groupBy = [],
    ): LengthAwarePaginator {
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TModel> $paginator */
        $paginator = $this->getRepository()->paginateByParams(
            $this->normalizeData($parameters),
            $with,
            $perPage,
            $orderBy,
            $groupBy,
        );

        return $paginator;
    }

    /**
     * Find all models by given criteria.
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @param array<string> $with
     * @param array<string> $orderBy
     * @param array<string> $groupBy
     * @return \Illuminate\Support\Collection<int, TModel>
     */
    public function findAllByParams(
        array|Collection $parameters,
        array $with = [],
        array $orderBy = [],
        array $groupBy = [],
        ?int $limit = null,
    ): Collection {
        return $this->getRepository()->findAllByParams(
            $this->normalizeData($parameters),
            $with,
            $orderBy,
            $groupBy,
            $limit,
        );
    }

    /**
     * Count models by given criteria.
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @param array<string> $groupBy
     */
    public function countByParams(array|Collection $parameters, array $groupBy = []): int
    {
        return $this->getRepository()->countByParams($this->normalizeData($parameters), $groupBy);
    }

    /**
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     */
    public function deleteByParams(array|Collection $parameters): bool
    {
        return $this->getModelManager()->deleteByParams($this->normalizeData($parameters));
    }

    /**
     * Soft delete a model by ID.
     */
    public function softDelete(int|string $id): bool
    {
        return $this->getModelManager()->softDelete($id);
    }

    /**
     * Soft delete models by parameters.
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @return int Number of soft deleted records
     */
    public function softDeleteByParams(array|Collection $parameters): int
    {
        return $this->getModelManager()->softDeleteByParams($this->normalizeData($parameters));
    }

    /**
     * Restore a soft deleted model by ID.
     */
    public function restore(int|string $id): bool
    {
        return $this->getModelManager()->restore($id);
    }

    /**
     * Restore soft deleted models by parameters.
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @return int Number of restored records
     */
    public function restoreByParams(array|Collection $parameters): int
    {
        return $this->getModelManager()->restoreByParams($this->normalizeData($parameters));
    }

    /**
     * Force delete a model by ID (permanent deletion).
     */
    public function forceDelete(int|string $id): bool
    {
        return $this->getModelManager()->forceDelete($id);
    }

    /**
     * Force delete models by parameters (permanent deletion).
     *
     * @param array<string, mixed>|\Illuminate\Support\Collection<string, mixed> $parameters
     * @return int Number of force deleted records
     */
    public function forceDeleteByParams(array|Collection $parameters): int
    {
        return $this->getModelManager()->forceDeleteByParams($this->normalizeData($parameters));
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
