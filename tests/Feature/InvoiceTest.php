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
    $this->guestAuth = createAuthUser('guest');
    $this->ownerAuth = createAuthUser('hotelOwner');
    $this->adminAuth = createAuthUser('admin');
});

// ─── DOWNLOAD TESTS ───

test('guest can download their own invoice', function () {
    $booking = Booking::factory()->confirmed()->create([
        'user_id'  => $this->guestAuth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    Payment::factory()->completed()->create([
        'booking_id' => $booking->id,
    ]);

    $response = $this->get(
        "/api/v1/bookings/{$booking->id}/invoice/download",
        ['Authorization' => "Bearer {$this->guestAuth['token']}"]
    );

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader(
            'Content-Disposition',
            'attachment; filename="Invoice-' . $booking->booking_reference . '.pdf"'
        );
});

test('guest cannot download another users invoice', function () {
    $otherUser = User::factory()->guest()->create();
    $booking   = Booking::factory()->confirmed()->create([
        'user_id'  => $otherUser->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->get(
        "/api/v1/bookings/{$booking->id}/invoice/download",
        ['Authorization' => "Bearer {$this->guestAuth['token']}"]
    );

    $response->assertStatus(403);
});

test('hotel owner can download invoice for their hotel', function () {
    $setup = createHotelSetup([
        'hotel' => ['user_id' => $this->ownerAuth['user']->id],
    ]);

    $booking = Booking::factory()->confirmed()->create([
        'user_id'  => $this->guestAuth['user']->id,
        'hotel_id' => $setup['hotel']->id,
        'room_id'  => $setup['rooms']->first()->id,
    ]);

    Payment::factory()->completed()->create([
        'booking_id' => $booking->id,
    ]);

    $response = $this->get(
        "/api/v1/bookings/{$booking->id}/invoice/download",
        ['Authorization' => "Bearer {$this->ownerAuth['token']}"]
    );

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');
});

test('admin can download any invoice', function () {
    $booking = Booking::factory()->confirmed()->create([
        'user_id'  => $this->guestAuth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    Payment::factory()->completed()->create([
        'booking_id' => $booking->id,
    ]);

    $response = $this->get(
        "/api/v1/bookings/{$booking->id}/invoice/download",
        ['Authorization' => "Bearer {$this->adminAuth['token']}"]
    );

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');
});

// ─── VIEW (STREAM) TESTS ───

test('guest can view invoice inline in browser', function () {
    $booking = Booking::factory()->confirmed()->create([
        'user_id'  => $this->guestAuth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    Payment::factory()->completed()->create([
        'booking_id' => $booking->id,
    ]);

    $response = $this->get(
        "/api/v1/bookings/{$booking->id}/invoice",
        ['Authorization' => "Bearer {$this->guestAuth['token']}"]
    );

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');

    // Stream uses 'inline' instead of 'attachment'
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('inline');
});

// ─── EMAIL TESTS ───

test('guest can request invoice via email', function () {
    $booking = Booking::factory()->confirmed()->create([
        'user_id'  => $this->guestAuth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    Payment::factory()->completed()->create([
        'booking_id' => $booking->id,
    ]);

    $response = $this->postJson(
        "/api/v1/bookings/{$booking->id}/invoice/email",
        [],
        ['Authorization' => "Bearer {$this->guestAuth['token']}"]
    );

    $response->assertStatus(200)
        ->assertJsonPath('message', "Invoice sent to {$booking->user->email}");
});

test('cannot email invoice for unpaid booking', function () {
    $booking = Booking::factory()->create([
        'user_id'  => $this->guestAuth['user']->id,
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
        'status'   => 'pending',
    ]);

    // No payment record!

    $response = $this->postJson(
        "/api/v1/bookings/{$booking->id}/invoice/email",
        [],
        ['Authorization' => "Bearer {$this->guestAuth['token']}"]
    );

    $response->assertStatus(422);
});

// ─── UNAUTHENTICATED TESTS ───

test('unauthenticated user cannot access invoices', function () {
    $booking = Booking::factory()->create([
        'hotel_id' => $this->setup['hotel']->id,
        'room_id'  => $this->setup['rooms']->first()->id,
    ]);

    $response = $this->get("/api/v1/bookings/{$booking->id}/invoice/download");

    $response->assertStatus(401);
});