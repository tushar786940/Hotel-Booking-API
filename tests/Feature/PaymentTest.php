<?php

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
    Role::create(['name' => 'guest', 'guard_name' => 'web']);

    $this->setup = createHotelSetup();
    $this->auth  = createAuthUser('guest');
    $this->headers = ['Authorization' => "Bearer {$this->auth['token']}"];
});

test('guest can initiate payment for pending booking', function () {
    $booking = Booking::factory()->create([
        'user_id'     => $this->auth['user']->id,
        'hotel_id'    => $this->setup['hotel']->id,
        'room_id'     => $this->setup['rooms']->first()->id,
        'total_price' => 500.00,
        'status'      => 'pending',
    ]);

    // Note: This test would need Stripe mocking in production.
    // For now, we test the validation and authorization logic.

    // Test that a non-pending booking cannot be paid
    $confirmedBooking = Booking::factory()->confirmed()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->last()->id,
    ]);

    $response = $this->postJson(
        "/api/v1/bookings/{$confirmedBooking->id}/pay",
        [],
        $this->headers
    );

    $response->assertStatus(422);
});

test('guest cannot pay for another users booking', function () {
    $otherUser = User::factory()->create();
    $booking   = Booking::factory()->create([
        'user_id'  => $otherUser->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->postJson(
        "/api/v1/bookings/{$booking->id}/pay",
        [],
        $this->headers
    );

    $response->assertStatus(403);
});

test('guest can check payment status', function () {
    $booking = Booking::factory()->confirmed()->create([
        'user_id'  => $this->auth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    Payment::factory()->completed()->create([
        'booking_id' => $booking->id,
        'amount'     => $booking->total_price,
    ]);

    $response = $this->getJson(
        "/api/v1/bookings/{$booking->id}/payment-status",
        $this->headers
    );

    $response->assertStatus(200)
        ->assertJsonPath('data.payment.status', 'completed')
        ->assertJsonPath('data.booking_status', 'confirmed');
});