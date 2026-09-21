<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'room_number'  => 'R' . fake()->unique()->numberBetween(100, 999),
            'floor'        => fake()->numberBetween(1, 10),
            'status'       => 'available',
            'is_available' => true,
        ];
    }

    /**
     * State: Occupied room
     */
    public function occupied(): static
    {
        return $this->state(fn () => [
            'status'       => 'occupied',
            'is_available' => false,
        ]);
    }

    /**
     * State: Under maintenance
     */
    public function maintenance(): static
    {
        return $this->state(fn () => [
            'status'       => 'maintenance',
            'is_available' => false,
        ]);
    }
}