<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager\DynamoDb;

use BaoPham\DynamoDb\DynamoDbModel;
use Illuminate\Database\Eloquent\Model;

final class TestableDynamoDbModelForStaticMethods extends DynamoDbModel
{

    private static ?Model $mockUpdateOrCreateResult = null;
    private static ?Model $mockFirstOrCreateResult = null;

    public static function setMockUpdateOrCreateResult(?Model $model): void
    {
        self::$mockUpdateOrCreateResult = $model;
    }

    public static function setMockFirstOrCreateResult(?Model $model): void
    {
        self::$mockFirstOrCreateResult = $model;
    }

    public static function updateOrCreate(array $attributes, array $values = []): Model
    {
        if (self::$mockUpdateOrCreateResult !== null) {
            return self::$mockUpdateOrCreateResult;
        }

        return parent::updateOrCreate($attributes, $values);
    }

    public static function firstOrCreate(array $attributes, array $values = []): Model
    {
        if (self::$mockFirstOrCreateResult !== null) {
            return self::$mockFirstOrCreateResult;
        }

        return parent::firstOrCreate($attributes, $values);
    }

}
