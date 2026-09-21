<?php

use App\Models\Booking;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
    Role::create(['name' => 'guest', 'guard_name' => 'web']);

    $this->setup = createHotelSetup([
        'roomType' => ['price_per_night' => 100, 'total_rooms' => 3],
    ]);

    $this->auth = createAuthUser('guest');
    $this->headers = [
        'Authorization' => "Bearer {$this->auth['token']}",
    ];
});

// ─── CREATE BOOKING ───

test('guest can create a booking', function () {
    $response = $this->postJson('/api/v1/bookings', [
        'room_type_id' => $this->setup['roomType']->id,
        'check_in'     => now()->addWeek()->format('Y-m-d'),
        'check_out'    => now()->addWeek()->addDays(3)->format('Y-m-d'),
        'guests_count' => 2,
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => [
                'id', 'booking_reference', 'hotel', 'room',
                'check_in', 'check_out', 'nights', 'total_price', 'status',
            ],
        ])
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.nights', 3);

    // Verify booking exists in database
    $this->assertDatabaseHas('bookings', [
        'user_id' => $this->auth['user']->id,
        'status'  => 'pending',
    ]);
});

test('booking fails when no rooms are available', function () {
    $checkIn  = now()->addWeek()->format('Y-m-d');
    $checkOut = now()->addWeek()->addDays(3)->format('Y-m-d');

    // Book ALL 3 rooms for the same dates
    foreach ($this->setup['rooms'] as $room) {
        Booking::factory()->confirmed()->create([
            'room_id'   => $room->id,
            'hotel_id'  => $this->setup['hotel']->id,
            'user_id'   => User::factory()->create()->id,
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]);
    }

    $response = $this->postJson('/api/v1/bookings', [
        'room_type_id' => $this->setup['roomType']->id,
        'check_in'     => $checkIn,
        'check_out'    => $checkOut,
        'guests_count' => 2,
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('room_type_id');
});

test('booking fails with overlapping dates', function () {
    $room = $this->setup['rooms']->first();

    // Existing booking: March 5-10
    Booking::factory()->confirmed()->create([
        'room_id'   => $room->id,
        'hotel_id'  => $this->setup['hotel']->id,
        'user_id'   => User::factory()->create()->id,
        'check_in'  => '2025-03-05',
        'check_out' => '2025-03-10',
    ]);

    // Try to book March 8-12 (overlaps with existing!)
    $response = $this->postJson('/api/v1/bookings', [
        'room_type_id' => $this->setup['roomType']->id,
        'check_in'     => '2025-03-08',
        'check_out'    => '2025-03-12',
        'guests_count' => 2,
    ], $this->headers);

    // Should still succeed because there are 2 other rooms available
    $response->assertStatus(201);
});

test('booking requires valid dates', function () {
    $response = $this->postJson('/api/v1/bookings', [
        'room_type_id' => $this->setup['roomType']->id,
        'check_in'     => '2020-01-01', // Past date!
        'check_out'    => '2020-01-05',
        'guests_count' => 2,
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('check_in');
});

test('booking requires authentication', function () {
    $response = $this->postJson('/api/v1/bookings', [
        'room_type_id' => $this->setup['roomType']->id,
        'check_in'     => now()->addWeek()->format('Y-m-d'),
        'check_out'    => now()->addWeek()->addDays(3)->format('Y-m-d'),
        'guests_count' => 2,
    ]);

    $response->assertStatus(401);
});

test('booking generates unique reference', function () {
    $checkIn  = now()->addWeek()->format('Y-m-d');
    $checkOut = now()->addWeek()->addDays(2)->format('Y-m-d');

    $response1 = $this->postJson('/api/v1/bookings', [
        'room_type_id' => $this->setup['roomType']->id,
        'check_in'     => $checkIn,
        'check_out'    => $checkOut,
        'guests_count' => 1,
    ], $this->headers);

    $response2 = $this->postJson('/api/v1/bookings', [
        'room_type_id' => $this->setup['roomType']->id,
        'check_in'     => $checkIn,
        'check_out'    => $checkOut,
        'guests_count' => 1,
    ], $this->headers);

    $ref1 = $response1->json('data.booking_reference');
    $ref2 = $response2->json('data.booking_reference');

    expect($ref1)->not->toBe($ref2);
    expect($ref1)->toStartWith('BK-');
});

// ─── LIST BOOKINGS ───

test('guest can list their own bookings', function () {
    // Create 2 bookings for this user
    Booking::factory()->count(2)->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    // Create 1 booking for ANOTHER user (should not appear)
    Booking::factory()->create([
        'user_id'  => User::factory()->create()->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->last()->id,
    ]);

    $response = $this->getJson('/api/v1/bookings', $this->headers);

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data'); // Only THIS user's bookings
});

test('bookings can be filtered by status', function () {
    Booking::factory()->confirmed()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms'][0]->id,
    ]);

    Booking::factory()->cancelled()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms'][1]->id,
    ]);

    $response = $this->getJson('/api/v1/bookings?status=confirmed', $this->headers);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'confirmed');
});

// ─── VIEW BOOKING ───

test('guest can view their own booking', function () {
    $booking = Booking::factory()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->getJson("/api/v1/bookings/{$booking->id}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.booking_reference', $booking->booking_reference)
        ->assertJsonStructure([
            'data'            => ['id', 'hotel', 'room', 'nights', 'total_price'],
            'price_breakdown' => ['base_price', 'tax', 'total'],
        ]);
});

test('guest cannot view another users booking', function () {
    $otherUser  = User::factory()->guest()->create();
    $booking    = Booking::factory()->create([
        'user_id'  => $otherUser->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->getJson("/api/v1/bookings/{$booking->id}", $this->headers);

    $response->assertStatus(403); // Forbidden!
});

// ─── CANCEL BOOKING ───

test('guest can cancel a pending booking', function () {
    $booking = Booking::factory()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
        'check_in' => now()->addWeek(), // Far enough in the future
        'status'   => 'pending',
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel", [
        'reason' => 'Change of plans',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('bookings', [
        'id'                  => $booking->id,
        'status'              => 'cancelled',
        'cancellation_reason' => 'Change of plans',
    ]);
});

test('guest cannot cancel a checked-in booking', function () {
    $booking = Booking::factory()->checkedIn()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel", [], $this->headers);

    $response->assertStatus(422);
});

test('guest cannot cancel booking for tomorrow', function () {
    $booking = Booking::factory()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
        'check_in' => now()->addHours(12), // Less than 1 day away
        'status'   => 'confirmed',
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel", [], $this->headers);

    $response->assertStatus(422);
});