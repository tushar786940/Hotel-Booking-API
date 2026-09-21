<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hotel_id'        => Hotel::factory(),
            'name'            => fake()->randomElement(['Standard', 'Deluxe', 'Suite', 'Penthouse']),
            'description'     => fake()->sentence(),
            'price_per_night' => fake()->randomFloat(2, 50, 500),
            'capacity'        => fake()->numberBetween(1, 6),
            'total_rooms'     => fake()->numberBetween(1, 20),
            'amenities'       => ['wifi', 'tv', 'ac'],
            'images'          => ['https://example.com/room.jpg'],
        ];
    }

    /**
     * State: Budget room
     */
    public function budget(): static
    {
        return $this->state(fn() => [
            'name'            => 'Standard',
            'price_per_night' => 79.99,
            'capacity'        => 2,
        ]);
    }

    /**
     * State: Luxury suite
     */
    public function luxury(): static
    {
        return $this->state(fn() => [
            'name'            => 'Penthouse',
            'price_per_night' => 999.99,
            'capacity'        => 6,
            'amenities'       => ['wifi', 'tv', 'ac', 'jacuzzi', 'butler', 'terrace'],
        ]);
    }
}
