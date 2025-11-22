<?php

declare(strict_types = 1);

namespace Pekral\Arch\ModelManager;

use Illuminate\Database\Eloquent\Model;

/**
 * Base interface for model manager implementations.
 *
 * Defines the contract for managing model operations (CRUD)
 * with support for batch processing and advanced database features.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface ModelManager
{

    /**
     * Delete records by given parameters.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters Search criteria as key-value pairs
     * @return bool True if records were deleted, false otherwise
     */
    public function deleteByParams(array $parameters): bool;

    /**
     * Batch delete records by parameters.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $parameters
     */
    public function bulkDeleteByParams(array $parameters): void;

    /**
     * Delete a model instance.
     *
     * @param TModel $model
     */
    public function delete(Model $model): bool;

    /**
     * Create a new model record.
     *
     * @template TKey as string
     * @template TValue
     * @param array<TKey, TValue> $data
     * @return TModel
     */
    public function create(array $data): Model;

    /**
     * Update an existing model record.
     *
     * @param TModel $model Model instance to update
     * @param array<string, mixed> $data Data to update
     * @return bool True if update was successful, false otherwise
     */
    public function update(Model $model, array $data): bool;

    /**
     * Update existing record or create a new one if it doesn't exist.
     *
     * @param array<string, mixed> $attributes Attributes to search for
     * @param array<string, mixed> $values Values to update/create with
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Get existing record or create a new one if it doesn't exist.
     *
     * @param array<string, mixed> $attributes Attributes to search for
     * @param array<string, mixed> $values Values to use when creating
     * @return TModel
     */
    public function getOrCreate(array $attributes, array $values = []): Model;

    /**
     * Bulk create multiple records.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     * @return int Number of created records
     */
    public function bulkCreate(array $dataArray): int;

    /**
     * Bulk insert records, ignoring duplicates based on unique constraints.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray Array of records to insert
     */
    public function insertOrIgnore(array $dataArray): void;

    /**
     * Bulk update multiple records.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $dataArray
     * @param string $keyColumn Column to match records (usually 'id')
     * @return int Number of updated records
     */
    public function bulkUpdate(array $dataArray, string $keyColumn = 'id'): int;

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
    public function rawMassUpdate(array $values, null|array|string $uniqueBy = null): int;

    /**
     * Create a new model instance.
     *
     * @return TModel
     */
    public function createNewModelInstance(): Model;

}
