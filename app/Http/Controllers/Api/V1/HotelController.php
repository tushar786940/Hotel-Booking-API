<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Http\Resources\HotelDetailResource;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    /**
     * List all active hotels with search & filters
     *
     * GET /api/v1/hotels
     * GET /api/v1/hotels?city=New York&star_rating=4&sort_by=price
     */
    public function index(Request $request): JsonResponse
    {
        $hotels = Hotel::query()
            ->active() // Our custom scope - only active hotels

            /**
             * with() = Eager Loading
             * Loads related data in a SINGLE query instead of N+1 queries
             *
             * 'roomTypes' loads room types
             * 'reviews' loads reviews
             */
            ->with(['roomTypes', 'reviews'])

            /**
             * when() = Conditional queries
             * "When city parameter exists, add city filter"
             *
             * This is cleaner than:
             *   if ($request->city) {
             *       $query->inCity($request->city);
             *   }
             */
            ->when(
                $request->city,
                fn ($query, $city) => $query->inCity($city)
            )
            ->when(
                $request->country,
                fn ($query, $country) => $query->where('country', $country)
            )
            ->when(
                $request->star_rating,
                fn ($query, $rating) => $query->where('star_rating', '>=', $rating)
            )
            ->when(
                $request->min_price,
                fn ($query, $minPrice) => $query->whereHas('roomTypes', function ($q) use ($minPrice) {
                    $q->where('price_per_night', '>=', $minPrice);
                })
            )
            ->when(
                $request->max_price,
                fn ($query, $maxPrice) => $query->whereHas('roomTypes', function ($q) use ($maxPrice) {
                    $q->where('price_per_night', '<=', $maxPrice);
                })
            )

            // Sorting
            ->when($request->sort_by === 'price', function ($query) use ($request) {
                /**
                 * withMin() adds a virtual column with the minimum value
                 * from a related table.
                 *
                 * This lets us sort hotels by their cheapest room price.
                 */
                $query->withMin('roomTypes', 'price_per_night')
                      ->orderBy(
                          'room_types_min_price_per_night',
                          $request->sort_order ?? 'asc'
                      );
            })
            ->when($request->sort_by === 'rating', function ($query) {
                $query->withAvg('reviews', 'rating')
                      ->orderByDesc('reviews_avg_rating');
            })
            ->when(!$request->sort_by, function ($query) {
                $query->latest(); // Default: newest first
            })

            /**
             * paginate() splits results into pages
             * Instead of returning ALL 10,000 hotels at once,
             * return 15 at a time.
             *
             * Response includes: data, current_page, last_page, total, etc.
             */
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => HotelResource::collection($hotels),
            'meta' => [
                'current_page' => $hotels->currentPage(),
                'last_page'    => $hotels->lastPage(),
                'per_page'     => $hotels->perPage(),
                'total'        => $hotels->total(),
            ],
        ]);
    }

    /**
     * Show single hotel with full details
     *
     * GET /api/v1/hotels/grand-palace-hotel-x8k9m
     *
     * Note: {hotel:slug} means Laravel finds the hotel by slug, not by id
     * This is called "Route Model Binding"
     */
    public function show(Hotel $hotel): JsonResponse
    {
        // Make sure the hotel is active
        if (!$hotel->is_active) {
            abort(404, 'Hotel not found.');
        }

        // Load all related data for the detail page
        $hotel->load([
            'roomTypes.rooms',  // Room types with their rooms
            'reviews.user',     // Reviews with reviewer names
            'owner',            // Hotel owner info
        ]);

        return response()->json([
            'data' => new HotelDetailResource($hotel),
        ]);
    }
}