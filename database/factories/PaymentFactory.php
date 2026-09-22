<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id'     => Booking::factory(),
            'amount'         => fake()->randomFloat(2, 100, 2000),
            'currency'       => 'USD',
            'method'         => 'stripe',
            'transaction_id' => 'pi_' . Str::random(24),
            'status'         => 'pending',
            'paid_at'        => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status'  => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }
}