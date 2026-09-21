<?php

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;

/**
 * Helper function to create a complete hotel setup
 * (hotel + room type + rooms) for testing.
 *
 * WHY? Many tests need a hotel with rooms. Instead of
 * creating them manually in every test, this helper
 * does it in one call.
 *
 * Usage:
 *   $setup = createHotelSetup();
 *   $setup['hotel']      → Hotel model
 *   $setup['roomType']   → RoomType model
 *   $setup['rooms']      → Collection of Room models
 */
function createHotelSetup(array $overrides = []): array
{
    $owner = User::factory()->hotelOwner()->create();

    $hotel = Hotel::factory()->create(array_merge([
        'user_id' => $owner->id,
    ], $overrides['hotel'] ?? []));

    $roomType = RoomType::factory()->create(array_merge([
        'hotel_id'    => $hotel->id,
        'total_rooms' => 5,
    ], $overrides['roomType'] ?? []));

    $rooms = Room::factory()
        ->count($roomType->total_rooms)
        ->create(['room_type_id' => $roomType->id]);

    return compact('owner', 'hotel', 'roomType', 'rooms');
}

/**
 * Helper to create an authenticated user with a token
 *
 * Usage:
 *   $auth = actingAsGuest();
 *   $auth['user']  → User model
 *   $auth['token'] → API token string
 */
function createAuthUser(string $role = 'guest'): array
{
    $user = User::factory()->{$role}()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    return compact('user', 'token');
}