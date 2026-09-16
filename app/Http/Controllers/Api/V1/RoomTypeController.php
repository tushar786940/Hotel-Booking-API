<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoomTypeResource;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    /**
     * List room types for a hotel
     */
    public function index(Request $request, Hotel $hotel): JsonResponse
    {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $roomTypes = $hotel->roomTypes()->with('rooms')->get();

        return response()->json([
            'data' => RoomTypeResource::collection($roomTypes),
        ]);
    }

    /**
     * Create room type + auto-generate rooms
     *
     * POST /api/v1/manage/hotels/1/room-types
     * Body: {
     *   "name": "Deluxe",
     *   "price_per_night": 179.99,
     *   "capacity": 3,
     *   "total_rooms": 5,
     *   "amenities": ["tv", "minibar", "balcony"]
     * }
     */
    public function store(Request $request, Hotel $hotel): JsonResponse
    {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'price_per_night' => ['required', 'numeric', 'min:1'],
            'capacity'        => ['required', 'integer', 'min:1', 'max:20'],
            'total_rooms'     => ['required', 'integer', 'min:1', 'max:100'],
            'amenities'       => ['nullable', 'array'],
            'amenities.*'     => ['string'],
        ]);

        $roomType = $hotel->roomTypes()->create($validated);

        // Auto-generate room records
        // If room type is "Deluxe" with 5 rooms → D001, D002, D003, D004, D005
        $prefix = strtoupper(substr($roomType->name, 0, 1));

        for ($i = 1; $i <= $validated['total_rooms']; $i++) {
            Room::create([
                'room_type_id' => $roomType->id,
                'room_number'  => $prefix . str_pad($i, 3, '0', STR_PAD_LEFT),
                'floor'        => (int) ceil($i / 4), // 4 rooms per floor
                'status'       => 'available',
                'is_available' => true,
            ]);
        }

        $roomType->load('rooms');

        return response()->json([
            'message' => "Room type '{$roomType->name}' created with {$validated['total_rooms']} rooms!",
            'data'    => new RoomTypeResource($roomType),
        ], 201);
    }

    /**
     * Update room type
     */
    public function update(Request $request, Hotel $hotel, RoomType $roomType): JsonResponse
    {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'            => ['sometimes', 'string', 'max:255'],
            'description'     => ['sometimes', 'nullable', 'string'],
            'price_per_night' => ['sometimes', 'numeric', 'min:1'],
            'capacity'        => ['sometimes', 'integer', 'min:1'],
            'amenities'       => ['sometimes', 'array'],
        ]);

        $roomType->update($validated);

        return response()->json([
            'message' => 'Room type updated!',
            'data'    => new RoomTypeResource($roomType->fresh()),
        ]);
    }

    /**
     * Delete room type
     */
    public function destroy(Request $request, Hotel $hotel, RoomType $roomType): JsonResponse
    {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $roomType->delete(); // Cascade deletes rooms too

        return response()->json([
            'message' => 'Room type and all its rooms deleted.',
        ]);
    }
}