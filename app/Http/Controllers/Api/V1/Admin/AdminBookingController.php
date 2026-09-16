<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    /**
     * List bookings for a hotel (hotel owner view)
     */
    public function index(Request $request, Hotel $hotel): JsonResponse
    {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $bookings = $hotel->bookings()
            ->with(['user', 'room.roomType', 'payment'])
            ->when(
                $request->status,
                fn ($q, $s) => $q->where('status', $s)
            )
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    /**
     * Update booking status (check-in, check-out)
     *
     * PUT /api/v1/bookings/5/status
     * Body: { "status": "checked_in" }
     */
    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        // Verify hotel owner owns this booking's hotel
        if ($booking->hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,checked_in,checked_out,cancelled'],
        ]);

        // Define allowed transitions
        $allowedTransitions = [
            'pending'     => ['confirmed', 'cancelled'],
            'confirmed'   => ['checked_in', 'cancelled'],
            'checked_in'  => ['checked_out'],
            'checked_out' => [],
            'cancelled'   => [],
        ];

        $currentStatus = $booking->status;
        $newStatus     = $validated['status'];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            return response()->json([
                'message' => "Cannot change status from '{$currentStatus}' to '{$newStatus}'.",
                'allowed' => $allowedTransitions[$currentStatus] ?? [],
            ], 422);
        }

        $booking->update(['status' => $newStatus]);

        // Update room status when guest checks in/out
        if ($newStatus === 'checked_in') {
            $booking->room->update(['status' => 'occupied']);
        } elseif ($newStatus === 'checked_out') {
            $booking->room->update(['status' => 'available']);
        }

        return response()->json([
            'message' => "Booking status updated to '{$newStatus}'.",
            'data'    => new BookingResource($booking->fresh()->load('room.roomType', 'hotel')),
        ]);
    }
}