<?php

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
    Role::create(['name' => 'guest', 'guard_name' => 'web']);

    // Fake the storage disk
    // WHY? Tests shouldn't create real files on disk!
    // Storage::fake() creates a temporary fake storage.
    Storage::fake('public');

    $this->ownerAuth = createAuthUser('hotelOwner');
    $this->headers   = ['Authorization' => "Bearer {$this->ownerAuth['token']}"];
});

test('hotel owner can upload images', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    /*
     * UploadedFile::fake()->image() creates a fake image
     * for testing WITHOUT actually creating files on disk.
     *
     * Parameters:
     *   'name.jpg'  = filename
     *   800         = width
     *   600         = height
     */
    $image1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
    $image2 = UploadedFile::fake()->image('photo2.jpg', 1200, 800);

    $response = $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [$image1, $image2]],
        $this->headers
    );

    $response->assertStatus(201)
        ->assertJsonPath('uploaded_count', 2)
        ->assertJsonStructure([
            'data' => [
                'images' => [
                    '*' => ['url', 'thumbnail_url'],
                ],
            ],
        ]);

    // Verify database was updated
    $hotel->refresh();
    expect(count($hotel->images))->toBe(2);

    // Verify files exist in fake storage
    Storage::disk('public')->assertExists($hotel->images[0]['path']);
    Storage::disk('public')->assertExists($hotel->images[0]['thumbnail']);
});

test('image upload rejects non-image files', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    // Create a fake PDF (not an image!)
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [$file]],
        $this->headers
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('images.0');
});

test('image upload rejects files over 5MB', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    // Create a huge fake image (10MB)
    $largeImage = UploadedFile::fake()->image('huge.jpg')->size(10000); // 10MB

    $response = $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [$largeImage]],
        $this->headers
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('images.0');
});

test('image upload rejects images too small', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    // Too small (200x200 - below 400x300 minimum)
    $smallImage = UploadedFile::fake()->image('small.jpg', 200, 200);

    $response = $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [$smallImage]],
        $this->headers
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('images.0');
});

test('non-owner cannot upload hotel images', function () {
    // Create hotel owned by DIFFERENT owner
    $otherOwner = createAuthUser('hotelOwner');
    $hotel      = Hotel::factory()->create([
        'user_id' => $otherOwner['user']->id,
    ]);

    $image = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $response = $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [$image]],
        $this->headers
    );

    $response->assertStatus(403);
});

test('guest cannot upload hotel images', function () {
    $guestAuth = createAuthUser('guest');
    $hotel     = Hotel::factory()->create();

    $image = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $response = $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [$image]],
        ['Authorization' => "Bearer {$guestAuth['token']}"]
    );

    // 403 = role does not have permission
    $response->assertStatus(403);
});

test('images are appended not replaced', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    // Upload 2 images
    $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [
            UploadedFile::fake()->image('a.jpg', 800, 600),
            UploadedFile::fake()->image('b.jpg', 800, 600),
        ]],
        $this->headers
    );

    // Upload 2 more
    $response = $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [
            UploadedFile::fake()->image('c.jpg', 800, 600),
            UploadedFile::fake()->image('d.jpg', 800, 600),
        ]],
        $this->headers
    );

    $response->assertStatus(201)
        ->assertJsonPath('total_images', 4); // 2 + 2
});

test('hotel owner can delete an image', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    // Upload 3 images first
    $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [
            UploadedFile::fake()->image('a.jpg', 800, 600),
            UploadedFile::fake()->image('b.jpg', 800, 600),
            UploadedFile::fake()->image('c.jpg', 800, 600),
        ]],
        $this->headers
    );

    // Delete the middle one (index 1)
    $response = $this->deleteJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['image_index' => 1],
        $this->headers
    );

    $response->assertStatus(200)
        ->assertJsonPath('total_images', 2);

    $hotel->refresh();
    expect(count($hotel->images))->toBe(2);
});

test('hotel owner can reorder images', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    // Upload 3 images
    $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => [
            UploadedFile::fake()->image('first.jpg', 800, 600),
            UploadedFile::fake()->image('second.jpg', 800, 600),
            UploadedFile::fake()->image('third.jpg', 800, 600),
        ]],
        $this->headers
    );

    $hotel->refresh();
    $originalOrder = $hotel->images;

    // Reorder: third → first, first → second, second → third
    $response = $this->putJson(
        "/api/v1/manage/hotels/{$hotel->id}/images/reorder",
        ['order' => [2, 0, 1]],
        $this->headers
    );

    $response->assertStatus(200);

    $hotel->refresh();
    expect($hotel->images[0])->toBe($originalOrder[2]);
    expect($hotel->images[1])->toBe($originalOrder[0]);
    expect($hotel->images[2])->toBe($originalOrder[1]);
});

test('room type images can be uploaded', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    $roomType = RoomType::factory()->create(['hotel_id' => $hotel->id]);

    $response = $this->postJson(
        "/api/v1/manage/room-types/{$roomType->id}/images",
        ['images' => [
            UploadedFile::fake()->image('room1.jpg', 800, 600),
            UploadedFile::fake()->image('room2.jpg', 800, 600),
        ]],
        $this->headers
    );

    $response->assertStatus(201)
        ->assertJsonPath('uploaded_count', 2);

    $roomType->refresh();
    expect(count($roomType->images))->toBe(2);
});

test('max 10 hotel images per upload', function () {
    $hotel = Hotel::factory()->create([
        'user_id' => $this->ownerAuth['user']->id,
    ]);

    // Try to upload 11 images
    $images = [];
    for ($i = 0; $i < 11; $i++) {
        $images[] = UploadedFile::fake()->image("photo{$i}.jpg", 800, 600);
    }

    $response = $this->postJson(
        "/api/v1/manage/hotels/{$hotel->id}/images",
        ['images' => $images],
        $this->headers
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('images');
});