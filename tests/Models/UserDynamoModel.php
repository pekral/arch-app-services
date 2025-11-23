<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Models;

use BaoPham\DynamoDb\DynamoDbModel;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \BaoPham\DynamoDb\DynamoDbQueryBuilder whereEmail(string $value)
 * @method static \BaoPham\DynamoDb\DynamoDbQueryBuilder whereName(string $value)
 */
final class UserDynamoModel extends DynamoDbModel
{

    /**
     * @var string
     */
    protected $connection = 'test';

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @var array<string, array{hash: string}>
     */
    protected $dynamoDbIndexKeys = [
        'email-index' => [
            'hash' => 'email',
        ],
    ];

}
