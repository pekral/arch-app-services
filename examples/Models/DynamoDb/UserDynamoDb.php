<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Models\DynamoDb;

use BaoPham\DynamoDb\DynamoDbModel;

/**
 * Example DynamoDB model for User.
 *
 * This model extends DynamoDbModel and demonstrates how to use
 * DynamoDB with the arch-app-services package.
 */
final class UserDynamoDb extends DynamoDbModel
{

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

}
