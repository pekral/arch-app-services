<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Models;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\Pekral\Arch\Tests\Models\User> */
final class UserFactory extends Factory
{

    /** @var class-string<\Pekral\Arch\Tests\Models\User> */
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'password' => $this->faker->password(),
        ];
    }

}
