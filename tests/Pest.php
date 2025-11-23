<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\DynamoDbTestCase;
use Pekral\Arch\Tests\TestCase;

uses(DynamoDbTestCase::class)
    ->in('Unit/Actions/User/DynamoDb')
    ->in('Unit/ModelManager/DynamoDb')
    ->in('Unit/Repository/DynamoDb')
    ->in('Unit/Services/User/DynamoDb');

uses(TestCase::class)
    ->in('Unit/Actions')
    ->in('Unit/ArchServiceProviderTest.php')
    ->in('Unit/DataBuilder')
    ->in('Unit/Exceptions')
    ->in('Unit/ModelManager/Mysql')
    ->in('Unit/Repository/Mysql')
    ->in('Unit/Service')
    ->in('Unit/Services/User/UserModelServiceTest.php');
