<?php

use App\Models\Hotel;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
    Role::create(['name' => 'guest', 'guard_name' => 'web']);
});

// ─── LIST HOTELS ───

test('guest can list active hotels', function () {
    // Create 3 active hotels and 1 inactive
    Hotel::factory()->count(3)->create(['is_active' => true]);
    Hotel::factory()->inactive()->create();

    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        // Only 3 active hotels should be returned
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'city', 'star_rating', 'average_rating'],
            ],
            'meta' => ['current_page', 'last_page', 'total'],
        ]);
});

test('hotels can be filtered by city', function () {
    Hotel::factory()->create(['city' => 'New York']);
    Hotel::factory()->create(['city' => 'Miami']);
    Hotel::factory()->create(['city' => 'Denver']);

    $response = $this->getJson('/api/v1/hotels?city=New York');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.city', 'New York');
});

test('hotels can be filtered by star rating', function () {
    Hotel::factory()->create(['star_rating' => 3]);
    Hotel::factory()->create(['star_rating' => 5]);

    $response = $this->getJson('/api/v1/hotels?star_rating=4');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data'); // Only the 5-star hotel
});

test('hotels can be sorted by price', function () {
    $cheap = Hotel::factory()->create();
    RoomType::factory()->create(['hotel_id' => $cheap->id, 'price_per_night' => 50]);

    $expensive = Hotel::factory()->create();
    RoomType::factory()->create(['hotel_id' => $expensive->id, 'price_per_night' => 500]);

    $response = $this->getJson('/api/v1/hotels?sort_by=price&sort_order=asc');

    $response->assertStatus(200);

    // First hotel should be the cheaper one
    $data = $response->json('data');
    expect($data[0]['starting_price'])->toBeLessThan($data[1]['starting_price']);
});

test('hotels list is paginated', function () {
    Hotel::factory()->count(20)->create();

    $response = $this->getJson('/api/v1/hotels?per_page=5');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.last_page', 4);
});

test('filament hotel image paths are exposed and served publicly', function () {
    Storage::fake('public');

    $path = 'hotels/admin-upload.jpg';
    Storage::disk('public')->put($path, 'hotel-image');

    Hotel::factory()->create([
        'images' => [$path],
    ]);

    $imageUrl = asset("storage/{$path}");

    $this->getJson('/api/v1/hotels')
        ->assertOk()
        ->assertJsonPath('data.0.cover_image', $imageUrl)
        ->assertJsonPath('data.0.images.0.url', $imageUrl)
        ->assertJsonPath('data.0.images.0.thumbnail_url', $imageUrl)
        ->assertJsonPath('data.0.image_urls.0', $imageUrl);

    $this->get('/storage/hotels/admin-upload.jpg')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('absolute hotel image urls are preserved', function () {
    $imageUrl = 'https://cdn.example.com/hotels/external.jpg';

    Hotel::factory()->create([
        'images' => [$imageUrl],
    ]);

    $this->getJson('/api/v1/hotels')
        ->assertOk()
        ->assertJsonPath('data.0.cover_image', $imageUrl)
        ->assertJsonPath('data.0.images.0.url', $imageUrl)
        ->assertJsonPath('data.0.image_urls.0', $imageUrl);
});

// ─── SHOW HOTEL ───

test('guest can view hotel details by slug', function () {
    $hotel = Hotel::factory()->create();
    RoomType::factory()->count(3)->create(['hotel_id' => $hotel->id]);

    $response = $this->getJson("/api/v1/hotels/{$hotel->slug}");

    $response->assertStatus(200)
        ->assertJsonPath('data.name', $hotel->name)
        ->assertJsonStructure([
            'data' => [
                'id', 'name', 'slug', 'description',
                'room_types', 'reviews', 'amenities',
            ],
        ]);
});

test('inactive hotel returns 404', function () {
    $hotel = Hotel::factory()->inactive()->create();

    $response = $this->getJson("/api/v1/hotels/{$hotel->slug}");

    $response->assertStatus(404);
});

test('non-existent hotel returns 404', function () {
    $response = $this->getJson('/api/v1/hotels/does-not-exist');

    $response->assertStatus(404);
});

// ─── REVIEWS ───

test('guest can view hotel reviews', function () {
    $hotel  = Hotel::factory()->create();
    $users  = User::factory()->count(3)->create();

    foreach ($users as $user) {
        Review::factory()->create([
            'hotel_id' => $hotel->id,
            'user_id'  => $user->id,
            'rating'   => rand(3, 5),
        ]);
    }

    $response = $this->getJson("/api/v1/hotels/{$hotel->id}/reviews");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'meta' => ['average_rating', 'total_reviews'],
        ]);
});