<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\ModelManager\DynamoDb;

use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use Pekral\Arch\ModelManager\DynamoDb\BaseModelManager;
use Pekral\Arch\Tests\Models\UserDynamoModel;

/**
 * @extends \Pekral\Arch\ModelManager\DynamoDb\BaseModelManager<\Pekral\Arch\Tests\Models\UserDynamoModel>
 */
final class TestableDynamoDbModelManager extends BaseModelManager
{

    private ?DynamoDbQueryBuilder $dynamoDbQueryBuilder = null;
    private string $modelClassName = UserDynamoModel::class;

    public function setModelClassName(string $className): void
    {
        $this->modelClassName = $className;
    }

    public function setNewModelQuery(DynamoDbQueryBuilder $query): void
    {
        $this->dynamoDbQueryBuilder = $query;
    }

    protected function getModelClassName(): string
    {
        return $this->modelClassName;
    }

    protected function newModelQuery(): DynamoDbQueryBuilder
    {
        if ($this->dynamoDbQueryBuilder !== null) {
            return $this->dynamoDbQueryBuilder;
        }

        $modelClassName = $this->getModelClassName();

        $query = new $modelClassName()->newQuery();
        assert($query instanceof DynamoDbQueryBuilder);

        return $query;
    }

}
