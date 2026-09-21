<?php

use App\Models\Hotel;
use App\Models\RoomType;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
    Role::create(['name' => 'guest', 'guard_name' => 'web']);

    $this->ownerAuth = createAuthUser('hotelOwner');
    $this->guestAuth = createAuthUser('guest');
    $this->ownerHeaders = ['Authorization' => "Bearer {$this->ownerAuth['token']}"];
    $this->guestHeaders = ['Authorization' => "Bearer {$this->guestAuth['token']}"];
});

test('hotel owner can create a hotel', function () {
    $response = $this->postJson('/api/v1/manage/hotels', [
        'name'        => 'My New Hotel',
        'description' => 'A beautiful hotel',
        'address'     => '123 Main St',
        'city'        => 'Chicago',
        'country'     => 'USA',
        'star_rating' => 4,
        'amenities'   => ['wifi', 'pool'],
    ], $this->ownerHeaders);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'My New Hotel');

    $this->assertDatabaseHas('hotels', [
        'name'    => 'My New Hotel',
        'user_id' => $this->ownerAuth['user']->id,
    ]);
});

test('guest cannot create a hotel', function () {
    $response = $this->postJson('/api/v1/manage/hotels', [
        'name'        => 'Unauthorized Hotel',
        'address'     => '123 Main St',
        'city'        => 'Chicago',
        'country'     => 'USA',
        'star_rating' => 3,
    ], $this->guestHeaders);

    // 403 = Forbidden (guest role doesn't have access)
    $response->assertStatus(403);
});

test('hotel owner can create room type with auto-generated rooms', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    $response = $this->postJson("/api/v1/manage/hotels/{$hotel->id}/room-types", [
        'name'            => 'Deluxe',
        'price_per_night' => 199.99,
        'capacity'        => 3,
        'total_rooms'     => 5,
        'amenities'       => ['wifi', 'tv', 'balcony'],
    ], $this->ownerHeaders);

    $response->assertStatus(201);

    // Verify 5 rooms were auto-created
    $this->assertDatabaseCount('rooms', 5);
    $this->assertDatabaseHas('room_types', [
        'hotel_id' => $hotel->id,
        'name'     => 'Deluxe',
    ]);
});

test('hotel owner cannot manage another owners hotel', function () {
    $otherOwner = createAuthUser('hotelOwner');
    $hotel      = Hotel::factory()->create([
        'user_id' => $otherOwner['user']->id,
    ]);

    $response = $this->putJson("/api/v1/manage/hotels/{$hotel->id}", [
        'name' => 'Hacked Hotel Name',
    ], $this->ownerHeaders);

    $response->assertStatus(403);
});

test('hotel owner can view their hotel bookings', function () {
    $setup = createHotelSetup([
        'hotel' => ['user_id' => $this->ownerAuth['user']->id],
    ]);

    \App\Models\Booking::factory()->count(3)->create([
        'hotel_id' => $setup['hotel']->id,
        'room_id'  => $setup['rooms']->first()->id,
    ]);

    $response = $this->getJson(
        "/api/v1/manage/hotels/{$setup['hotel']->id}/bookings",
        $this->ownerHeaders
    );

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('hotel owner can update booking status to checked_in', function () {
    $setup = createHotelSetup([
        'hotel' => ['user_id' => $this->ownerAuth['user']->id],
    ]);

    $booking = \App\Models\Booking::factory()->confirmed()->create([
        'hotel_id' => $setup['hotel']->id,
        'room_id'  => $setup['rooms']->first()->id,
    ]);

    $response = $this->putJson(
        "/api/v1/manage/bookings/{$booking->id}/status",
        ['status' => 'checked_in'],
        $this->ownerHeaders
    );

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'checked_in');

    // Room status should also update
    $this->assertDatabaseHas('rooms', [
        'id'     => $setup['rooms']->first()->id,
        'status' => 'occupied',
    ]);
});

test('booking status transition validation works', function () {
    $setup = createHotelSetup([
        'hotel' => ['user_id' => $this->ownerAuth['user']->id],
    ]);

    $booking = \App\Models\Booking::factory()->checkedOut()->create([
        'hotel_id' => $setup['hotel']->id,
        'room_id'  => $setup['rooms']->first()->id,
    ]);

    // Cannot change checked_out to checked_in
    $response = $this->putJson(
        "/api/v1/manage/bookings/{$booking->id}/status",
        ['status' => 'checked_in'],
        $this->ownerHeaders
    );

    $response->assertStatus(422);
});