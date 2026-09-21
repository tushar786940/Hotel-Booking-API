<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        $checkIn  = fake()->dateTimeBetween('+1 week', '+1 month');
        $checkOut = (clone $checkIn)->modify('+' . fake()->numberBetween(1, 7) . ' days');

        return [
            'booking_reference' => 'BK-' . strtoupper(Str::random(8)),
            'user_id'           => User::factory(),
            'room_id'           => Room::factory(),
            'hotel_id'          => Hotel::factory(),
            'check_in'          => $checkIn,
            'check_out'         => $checkOut,
            'guests_count'      => fake()->numberBetween(1, 4),
            'total_price'       => fake()->randomFloat(2, 100, 2000),
            'status'            => 'pending',
            'special_requests'  => fake()->optional()->sentence(),
        ];
    }

    /**
     * State: Confirmed booking
     */
    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }

    /**
     * State: Checked in
     */
    public function checkedIn(): static
    {
        return $this->state(fn () => ['status' => 'checked_in']);
    }

    /**
     * State: Checked out (completed stay)
     */
    public function checkedOut(): static
    {
        return $this->state(fn () => ['status' => 'checked_out']);
    }

    /**
     * State: Cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => 'Change of plans',
        ]);
    }

    /**
     * State: Past dates (for testing reviews)
     */
    public function past(): static
    {
        return $this->state(fn () => [
            'check_in'  => now()->subDays(10),
            'check_out' => now()->subDays(7),
            'status'    => 'checked_out',
        ]);
    }
}