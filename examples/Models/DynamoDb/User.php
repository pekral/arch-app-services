<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Models\DynamoDb;

use BaoPham\DynamoDb\DynamoDbModel;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<\Pekral\Arch\Examples\Models\DynamoDb\User> whereName(string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\Pekral\Arch\Examples\Models\DynamoDb\User> whereEmail(string $value)
 */
final class User extends DynamoDbModel
{

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'email_verified_at',
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
     * @var array<string, array<string, string>>
     */
    protected $dynamoDbIndexKeys = [
        'email-index' => [
            'hash' => 'email',
        ],
    ];

}
