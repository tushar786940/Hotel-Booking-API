<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'), // Default test password
            'phone'             => fake()->phoneNumber(),
            'remember_token'    => Str::random(10),
        ];
    }

    /**
     * State: Unverified user
     * Usage: User::factory()->unverified()->create()
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * State: Guest role
     */
    public function guest(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('guest');
        });
    }

    /**
     * State: Hotel owner role
     */
    public function hotelOwner(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('hotel-owner');
        });
    }

    /**
     * State: Admin role
     */
    public function admin(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('admin');
        });
    }
}
