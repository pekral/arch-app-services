<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Test model without MassUpdatable trait for testing exception scenarios
 *
 * @property int $id
 * @property string $name
 * @property string $email
 */
final class UserWithoutMassUpdate extends Model
{

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

}
