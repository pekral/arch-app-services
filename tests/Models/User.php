<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Models;

use Iksaku\Laravel\MassUpdate\MassUpdatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<\Pekral\Arch\Tests\Models\User> whereName(string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\Pekral\Arch\Tests\Models\User> whereEmail(string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<\Pekral\Arch\Tests\Models\User> active()
 */
final class User extends Model
{

    /**
     * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Pekral\Arch\Tests\Models\UserFactory>
     */
    use HasFactory;
    use MassUpdatable;
    use SoftDeletes;

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
        'deleted_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Scope to filter only active (email-verified) users.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Returns true when the user has a verified email address.
     * This is a pure model helper that reads already-loaded state — no query is issued.
     */
    public function isActive(): bool
    {
        return $this->email_verified_at !== null;
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

}
