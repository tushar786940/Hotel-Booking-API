<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * POST /api/v1/register
     * Body: { name, email, password, password_confirmation, phone }
     */
    public function register(Request $request): JsonResponse
    {
        /**
         * VALIDATION
         *
         * WHY validate? Never trust user input!
         * - Someone could send empty fields
         * - Someone could send malicious data
         * - Email might not be a real email format
         *
         * validate() automatically returns 422 error if validation fails.
         * You don't need try/catch - Laravel handles it!
         *
         * Common rules:
         *   'required'   = field must be present and not empty
         *   'string'     = must be text
         *   'email'      = must be valid email format
         *   'unique:users' = no other user with this email
         *   'confirmed'  = must have a matching 'password_confirmation' field
         *   'max:255'    = maximum 255 characters
         */
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone'    => ['nullable', 'string', 'max:20'],
        ]);

        // Create the user
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'], // Auto-hashed by 'hashed' cast
            'phone'    => $validated['phone'] ?? null,
        ]);

        // Assign default role
        $user->assignRole('guest');

        /**
         * Create API token
         *
         * Sanctum creates a token stored in personal_access_tokens table.
         * The plainTextToken is shown ONCE - it can't be retrieved later.
         * User must include this in future requests:
         *   Authorization: Bearer <token>
         */
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful!',
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'token' => $token,
            ],
        ], 201); // 201 = "Created" HTTP status code
    }

    /**
     * Login existing user
     *
     * POST /api/v1/login
     * Body: { email, password }
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find user by email
        $user = User::where('email', $validated['email'])->first();

        // Check if user exists AND password matches
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            /**
             * WHY throw ValidationException instead of returning error?
             * It produces a consistent 422 response format that
             * matches Laravel's validation errors.
             *
             * Also: we use the SAME message for wrong email AND wrong password.
             * WHY? Security! Don't tell hackers which one is wrong.
             */
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful!',
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->getRoleNames(),
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout (revoke token)
     *
     * POST /api/v1/logout
     * Header: Authorization: Bearer <token>
     */
    public function logout(Request $request): JsonResponse
    {
        // Delete the token that was used for this request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully!',
        ]);
    }

    /**
     * Get authenticated user's profile
     *
     * GET /api/v1/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->getRoleNames(),
                'notifications' => $user->unreadNotifications->count(),
                'created_at'    => $user->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update profile
     *
     * PUT /api/v1/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully!',
            'data'    => $request->user()->fresh(),
        ]);
    }
}