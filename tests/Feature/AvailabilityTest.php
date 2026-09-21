<?php

use App\Models\Booking;
use App\Models\Room;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
    Role::create(['name' => 'guest', 'guard_name' => 'web']);

    // Create a complete hotel setup for testing
    $this->setup = createHotelSetup([
        'roomType' => ['price_per_night' => 100, 'total_rooms' => 3],
    ]);
});

// ─── SEARCH TESTS ───

test('user can search hotels with availability', function () {
    $response = $this->getJson('/api/v1/search?' . http_build_query([
        'city'      => $this->setup['hotel']->city,
        'check_in'  => now()->addWeek()->format('Y-m-d'),
        'check_out' => now()->addWeek()->addDays(3)->format('Y-m-d'),
        'guests'    => 2,
    ]));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('search requires check_in and check_out dates', function () {
    $response = $this->getJson('/api/v1/search?city=New York');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['check_in', 'check_out']);
});

test('search rejects past dates', function () {
    $response = $this->getJson('/api/v1/search?' . http_build_query([
        'check_in'  => now()->subDay()->format('Y-m-d'), // Yesterday!
        'check_out' => now()->addDay()->format('Y-m-d'),
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors('check_in');
});

test('search rejects check_out before check_in', function () {
    $response = $this->getJson('/api/v1/search?' . http_build_query([
        'check_in'  => '2025-03-10',
        'check_out' => '2025-03-05', // Before check-in!
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors('check_out');
});

test('search filters by price range', function () {
    // Create a cheap and expensive hotel
    $cheapSetup = createHotelSetup([
        'roomType' => ['price_per_night' => 50],
    ]);
    $expensiveSetup = createHotelSetup([
        'roomType' => ['price_per_night' => 500],
    ]);

    $response = $this->getJson('/api/v1/search?' . http_build_query([
        'check_in'  => now()->addWeek()->format('Y-m-d'),
        'check_out' => now()->addWeek()->addDays(2)->format('Y-m-d'),
        'max_price' => 100,
    ]));

    $response->assertStatus(200);

    // Only the cheap hotel should appear
    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain($cheapSetup['hotel']->name);
    expect($names)->not->toContain($expensiveSetup['hotel']->name);
});

// ─── AVAILABILITY CHECK TESTS ───

test('available rooms are shown for open dates', function () {
    $hotel = $this->setup['hotel'];

    $response = $this->getJson("/api/v1/hotels/{$hotel->id}/availability?" . http_build_query([
        'check_in'  => now()->addWeek()->format('Y-m-d'),
        'check_out' => now()->addWeek()->addDays(3)->format('Y-m-d'),
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['room_type', 'available_rooms', 'pricing'],
            ],
        ]);

    // All 3 rooms should be available
    expect($response->json('data.0.available_rooms'))->toBe(3);
});

test('booked rooms are excluded from availability', function () {
    $hotel = $this->setup['hotel'];
    $room  = $this->setup['rooms']->first();
    $guest = \App\Models\User::factory()->guest()->create();

    $checkIn  = now()->addWeek()->format('Y-m-d');
    $checkOut = now()->addWeek()->addDays(3)->format('Y-m-d');

    // Book one room for the same dates
    Booking::factory()->create([
        'room_id'   => $room->id,
        'hotel_id'  => $hotel->id,
        'user_id'   => $guest->id,
        'check_in'  => $checkIn,
        'check_out' => $checkOut,
        'status'    => 'confirmed',
    ]);

    $response = $this->getJson("/api/v1/hotels/{$hotel->id}/availability?" . http_build_query([
        'check_in'  => $checkIn,
        'check_out' => $checkOut,
    ]));

    $response->assertStatus(200);

    // Only 2 rooms should be available (3 total - 1 booked)
    expect($response->json('data.0.available_rooms'))->toBe(2);
});

test('cancelled bookings do not block availability', function () {
    $hotel = $this->setup['hotel'];
    $room  = $this->setup['rooms']->first();
    $guest = \App\Models\User::factory()->guest()->create();

    $checkIn  = now()->addWeek()->format('Y-m-d');
    $checkOut = now()->addWeek()->addDays(3)->format('Y-m-d');

    // Create a CANCELLED booking
    Booking::factory()->cancelled()->create([
        'room_id'   => $room->id,
        'hotel_id'  => $hotel->id,
        'user_id'   => $guest->id,
        'check_in'  => $checkIn,
        'check_out' => $checkOut,
    ]);

    $response = $this->getJson("/api/v1/hotels/{$hotel->id}/availability?" . http_build_query([
        'check_in'  => $checkIn,
        'check_out' => $checkOut,
    ]));

    // All 3 rooms should still be available
    expect($response->json('data.0.available_rooms'))->toBe(3);
});

test('pricing breakdown is included in availability', function () {
    $hotel = $this->setup['hotel'];

    $checkIn  = now()->addWeek()->format('Y-m-d');
    $checkOut = now()->addWeek()->addDays(4)->format('Y-m-d'); // 4 nights

    $response = $this->getJson("/api/v1/hotels/{$hotel->id}/availability?" . http_build_query([
        'check_in'  => $checkIn,
        'check_out' => $checkOut,
        'guests'    => 2,
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'pricing' => [
                        'price_per_night',
                        'nights',
                        'base_price',
                        'tax',
                        'total',
                    ],
                ],
            ],
        ]);

    // Verify pricing math: $100 × 4 nights = $400 base
    $pricing = $response->json('data.0.pricing');
    expect($pricing['nights'])->toBe(4);
    expect($pricing['base_price'])->toBe(400.00);
});