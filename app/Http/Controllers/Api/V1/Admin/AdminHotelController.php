<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelDetailResource;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Hotel management for hotel owners
 * All routes require 'hotel-owner' role
 */
class AdminHotelController extends Controller
{
    /**
     * List hotels owned by authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $hotels = $request->user()
            ->hotels()
            ->with(['roomTypes', 'reviews'])
            ->withCount('bookings')
            ->latest()
            ->get();

        return response()->json([
            'data' => HotelResource::collection($hotels),
        ]);
    }

    /**
     * Create a new hotel
     *
     * POST /api/v1/manage/hotels
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'address'        => ['required', 'string', 'max:255'],
            'city'           => ['required', 'string', 'max:100'],
            'state'          => ['nullable', 'string', 'max:100'],
            'country'        => ['required', 'string', 'max:100'],
            'zip_code'       => ['nullable', 'string', 'max:20'],
            'latitude'       => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'      => ['nullable', 'numeric', 'between:-180,180'],
            'star_rating'    => ['required', 'integer', 'min:1', 'max:5'],
            'check_in_time'  => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'amenities'      => ['nullable', 'array'],
            'amenities.*'    => ['string'],
        ]);

        $hotel = $request->user()->hotels()->create($validated);

        return response()->json([
            'message' => 'Hotel created successfully!',
            'data'    => new HotelResource($hotel),
        ], 201);
    }

    /**
     * Show hotel details (for owner)
     */
    public function show(Request $request, Hotel $hotel): JsonResponse
    {
        // Ensure user owns this hotel
        if ($hotel->user_id !== $request->user()->id) {
            abort(403, 'You do not own this hotel.');
        }

        $hotel->load(['roomTypes.rooms', 'reviews.user']);

        return response()->json([
            'data' => new HotelDetailResource($hotel),
        ]);
    }

    /**
     * Update hotel
     */
    public function update(Request $request, Hotel $hotel): JsonResponse
    {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403, 'You do not own this hotel.');
        }

        $validated = $request->validate([
            'name'           => ['sometimes', 'string', 'max:255'],
            'description'    => ['sometimes', 'nullable', 'string'],
            'address'        => ['sometimes', 'string', 'max:255'],
            'city'           => ['sometimes', 'string', 'max:100'],
            'country'        => ['sometimes', 'string', 'max:100'],
            'star_rating'    => ['sometimes', 'integer', 'min:1', 'max:5'],
            'check_in_time'  => ['sometimes', 'date_format:H:i'],
            'check_out_time' => ['sometimes', 'date_format:H:i'],
            'amenities'      => ['sometimes', 'array'],
            'is_active'      => ['sometimes', 'boolean'],
        ]);

        $hotel->update($validated);

        return response()->json([
            'message' => 'Hotel updated successfully!',
            'data'    => new HotelResource($hotel->fresh()),
        ]);
    }

    /**
     * Delete hotel
     */
    public function destroy(Request $request, Hotel $hotel): JsonResponse
    {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403, 'You do not own this hotel.');
        }

        // Check for active bookings
        $activeBookings = $hotel->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        if ($activeBookings > 0) {
            return response()->json([
                'message' => "Cannot delete hotel. There are {$activeBookings} active bookings.",
            ], 422);
        }

        $hotel->delete();

        return response()->json([
            'message' => 'Hotel deleted successfully.',
        ]);
    }
}