<?php

use App\Models\Booking;
use App\Models\Review;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
    Role::create(['name' => 'guest', 'guard_name' => 'web']);

    $this->setup = createHotelSetup();
    $this->auth  = createAuthUser('guest');
    $this->headers = ['Authorization' => "Bearer {$this->auth['token']}"];
});

test('guest can submit review after checkout', function () {
    $booking = Booking::factory()->checkedOut()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
        'rating'  => 5,
        'comment' => 'Amazing stay! Will come back.',
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonPath('data.rating', 5);

    $this->assertDatabaseHas('reviews', [
        'booking_id' => $booking->id,
        'rating'     => 5,
    ]);
});

test('guest cannot review before checkout', function () {
    $booking = Booking::factory()->confirmed()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
        'rating'  => 4,
        'comment' => 'Trying to review early',
    ], $this->headers);

    $response->assertStatus(422);
});

test('guest cannot submit duplicate review', function () {
    $booking = Booking::factory()->checkedOut()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    // First review
    Review::create([
        'user_id'    => $this->auth['user']->id,
        'hotel_id'   => $this->setup['hotel']->id,
        'booking_id' => $booking->id,
        'rating'     => 5,
        'comment'    => 'First review',
    ]);

    // Try to review again
    $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
        'rating'  => 3,
        'comment' => 'Changed my mind',
    ], $this->headers);

    $response->assertStatus(422);
});

test('rating must be between 1 and 5', function () {
    $booking = Booking::factory()->checkedOut()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    // Rating too high
    $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
        'rating' => 10,
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('guest cannot review another users booking', function () {
    $otherUser = \App\Models\User::factory()->create();
    $booking   = Booking::factory()->checkedOut()->create([
        'user_id'  => $otherUser->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/review", [
        'rating' => 5,
    ], $this->headers);

    $response->assertStatus(403);
});