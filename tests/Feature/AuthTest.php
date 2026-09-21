<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| WHY TEST AUTHENTICATION?
|--------------------------------------------------------------------------
| Auth is the gatekeeper of your app. If auth is broken:
| - Strangers can access private data
| - Users can't log in
| - Tokens don't expire properly
|
| These tests ensure the gate works correctly.
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    // Create roles before each test
    // WHY? RefreshDatabase wipes everything, including roles
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'hotel-owner', 'guard_name' => 'web']);
    Role::create(['name' => 'guest', 'guard_name' => 'web']);
});

// ─── REGISTRATION TESTS ───

test('user can register with valid data', function () {
    $response = $this->postJson('/api/v1/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'phone'                 => '+1555000000',
    ]);

    // assertStatus(201) = "Created" HTTP code
    $response->assertStatus(201)
        // assertJsonStructure checks the SHAPE of the response
        // without caring about exact values
        ->assertJsonStructure([
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'phone'],
                'token',
            ],
        ]);

    // Verify user was actually saved to database
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'name'  => 'John Doe',
    ]);
});

test('user cannot register with existing email', function () {
    // Create a user first
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name'                  => 'Jane Doe',
        'email'                 => 'taken@example.com', // Already taken!
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // 422 = Validation Error
    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('user cannot register with weak password', function () {
    $response = $this->postJson('/api/v1/register', [
        'name'                  => 'Jane Doe',
        'email'                 => 'jane@example.com',
        'password'              => '123', // Too short!
        'password_confirmation' => '123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

test('user cannot register without password confirmation', function () {
    $response = $this->postJson('/api/v1/register', [
        'name'     => 'Jane Doe',
        'email'    => 'jane@example.com',
        'password' => 'password123',
        // Missing password_confirmation!
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

test('registered user gets guest role', function () {
    $this->postJson('/api/v1/register', [
        'name'                  => 'New User',
        'email'                 => 'new@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'new@example.com')->first();

    expect($user->hasRole('guest'))->toBeTrue();
});

// ─── LOGIN TESTS ───

test('user can login with correct credentials', function () {
    $user = User::factory()->guest()->create([
        'email'    => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email'    => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'user'  => ['id', 'name', 'email', 'roles'],
                'token',
            ],
        ]);
});

test('user cannot login with wrong password', function () {
    User::factory()->create([
        'email'    => 'test@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email'    => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

test('user cannot login with non-existent email', function () {
    $response = $this->postJson('/api/v1/login', [
        'email'    => 'nobody@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
});

// ─── LOGOUT TESTS ───

test('user can logout and token is revoked', function () {
    $user  = User::factory()->guest()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->postJson('/api/v1/logout', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(200);

    // Verify token was deleted from database
    expect($user->tokens()->count())->toBe(0);
});

// ─── PROFILE TESTS ───

test('authenticated user can view profile', function () {
    $auth = createAuthUser('guest');

    $response = $this->getJson('/api/v1/profile', [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.email', $auth['user']->email);
});

test('unauthenticated user cannot view profile', function () {
    $response = $this->getJson('/api/v1/profile');

    // 401 = Unauthorized (no token provided)
    $response->assertStatus(401);
});

test('user can update profile', function () {
    $auth = createAuthUser('guest');

    $response = $this->putJson('/api/v1/profile', [
        'name'  => 'Updated Name',
        'phone' => '+1999999999',
    ], [
        'Authorization' => "Bearer {$auth['token']}",
    ]);

    $response->assertStatus(200);

    // Verify database was updated
    $this->assertDatabaseHas('users', [
        'id'    => $auth['user']->id,
        'name'  => 'Updated Name',
        'phone' => '+1999999999',
    ]);
});