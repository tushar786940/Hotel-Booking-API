<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get reviews for a hotel
     *
     * GET /api/v1/hotels/5/reviews
     */
    public function index(Hotel $hotel): JsonResponse
    {
        $reviews = $hotel->reviews()
            ->with('user:id,name')  // Only load user's id and name
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'data' => $reviews->map(fn ($review) => [
                'id'         => $review->id,
                'user'       => $review->user->name,
                'rating'     => $review->rating,
                'comment'    => $review->comment,
                'created_at' => $review->created_at->diffForHumans(),
            ]),
            'meta' => [
                'average_rating' => $hotel->average_rating,
                'total_reviews'  => $hotel->reviews()->count(),
                'current_page'   => $reviews->currentPage(),
                'last_page'      => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * Submit a review for a booking
     *
     * POST /api/v1/bookings/5/review
     * Body: { "rating": 5, "comment": "Amazing hotel!" }
     *
     * Rules:
     * - User must own the booking
     * - Booking must be 'checked_out'
     * - One review per booking
     */
    public function store(Request $request, Booking $booking): JsonResponse
    {
        // Authorization
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'You can only review your own bookings.');
        }

        // Must have checked out
        if ($booking->status !== 'checked_out') {
            return response()->json([
                'message' => 'You can only review after checking out.',
            ], 422);
        }

        // Already reviewed?
        if ($booking->review()->exists()) {
            return response()->json([
                'message' => 'You have already reviewed this booking.',
            ], 422);
        }

        $validated = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = Review::create([
            'user_id'    => $request->user()->id,
            'hotel_id'   => $booking->hotel_id,
            'booking_id' => $booking->id,
            'rating'     => $validated['rating'],
            'comment'    => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Review submitted successfully! Thank you.',
            'data'    => [
                'id'      => $review->id,
                'rating'  => $review->rating,
                'comment' => $review->comment,
            ],
        ], 201);
    }
}