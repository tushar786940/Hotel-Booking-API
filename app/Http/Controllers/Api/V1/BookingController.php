<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected PricingService $pricingService,
    ) {}

    /**
     * List user's bookings
     *
     * GET /api/v1/bookings
     * GET /api/v1/bookings?status=confirmed
     */
    public function index(Request $request): JsonResponse
    {
        /**
         * $request->user() returns the authenticated user
         * (determined by the Sanctum token in the Authorization header)
         *
         * We chain ->bookings() to only get THIS user's bookings.
         * A user should NEVER see other people's bookings!
         */
        $bookings = $request->user()
            ->bookings()
            ->with(['hotel', 'room.roomType', 'payment']) // Eager load
            ->when(
                $request->status,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->orderByDesc('created_at') // Newest first
            ->paginate(10);

        return response()->json([
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    /**
     * Create a new booking
     *
     * POST /api/v1/bookings
     * Body: {
     *   "room_type_id": 1,
     *   "check_in": "2025-02-01",
     *   "check_out": "2025-02-05",
     *   "guests_count": 2,
     *   "special_requests": "Late check-in please"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'check_in'     => ['required', 'date', 'after:today'],
            'check_out'    => ['required', 'date', 'after:check_in'],
            'guests_count' => ['required', 'integer', 'min:1', 'max:10'],
            'special_requests' => ['nullable', 'string', 'max:500'],
        ]);

        /**
         * Delegate to service
         * Controller doesn't know HOW to create a booking.
         * It just passes data to the service and returns the result.
         */
        $booking = $this->bookingService->createBooking(
            $request->user(),
            $validated
        );

        return response()->json([
            'message' => 'Booking created successfully! Please complete payment.',
            'data'    => new BookingResource($booking),
        ], 201);
    }

    /**
     * Show single booking details
     *
     * GET /api/v1/bookings/5
     */
    public function show(Request $request, Booking $booking): JsonResponse
    {
        /**
         * AUTHORIZATION CHECK
         *
         * Make sure the user can only see THEIR OWN bookings.
         * Without this, user A could see user B's booking by
         * guessing the ID: GET /api/v1/bookings/123
         */
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'You are not authorized to view this booking.');
        }

        $booking->load(['hotel', 'room.roomType', 'payment', 'review']);

        // Get price breakdown for display
        $priceBreakdown = $this->pricingService->getBreakdown(
            $booking->room->roomType,
            $booking->nights,
            $booking->guests_count
        );

        return response()->json([
            'data'            => new BookingResource($booking),
            'price_breakdown' => $priceBreakdown,
        ]);
    }

    /**
     * Cancel a booking
     *
     * POST /api/v1/bookings/5/cancel
     * Body: { "reason": "Change of plans" }
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        // Authorization
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'You are not authorized to cancel this booking.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = $this->bookingService->cancelBooking(
            $booking,
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'data'    => new BookingResource($booking),
        ]);
    }
}