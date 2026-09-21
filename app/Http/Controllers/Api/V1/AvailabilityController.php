<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * Constructor injection
     *
     * Laravel automatically creates instances of these services
     * and passes them to the controller.
     */
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected PricingService $pricingService,
    ) {}

    /**
     * Search hotels with availability check
     *
     * GET /api/v1/search?city=New York&check_in=2025-02-01&check_out=2025-02-05&guests=2
     *
     * This is the MAIN search endpoint. Users provide dates and location,
     * and we return only hotels with available rooms.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city'        => ['nullable', 'string'],
            'country'     => ['nullable', 'string'],
            'check_in'    => ['required', 'date', 'after:today'],
            'check_out'   => ['required', 'date', 'after:check_in'],
            'guests'      => ['nullable', 'integer', 'min:1'],
            'min_price'   => ['nullable', 'numeric', 'min:0'],
            'max_price'   => ['nullable', 'numeric', 'min:0'],
            'star_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $hotels = $this->availabilityService->search($validated);

        return response()->json([
            'data'    => HotelResource::collection($hotels),
            'count'   => $hotels->count(),
            'filters' => $validated, // Echo back the filters used
        ]);
    }

    /**
     * Check room availability for a specific hotel
     *
     * GET /api/v1/hotels/5/availability?check_in=2025-02-01&check_out=2025-02-05&guests=2
     *
     * Used on the hotel detail page. Shows which room types are available
     * and how much they cost for the selected dates.
     */
    public function checkHotel(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'check_in'  => ['required', 'date', 'after:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests'    => ['nullable', 'integer', 'min:1'],
        ]);

        // Get available room types
        $availableRoomTypes = $this->availabilityService->getAvailableRoomTypes(
            $hotel,
            $validated['check_in'],
            $validated['check_out']
        );

        $nights = Carbon::parse($validated['check_in'])->diffInDays(Carbon::parse($validated['check_out']));

        $guests = $validated['guests'] ?? 1;

        // Build response with pricing for each available room type
        $result = $availableRoomTypes->map(function ($item) use ($nights, $guests) {
            $roomType = $item['room_type'];

            return [
                'room_type' => [
                    'id'              => $roomType->id,
                    'name'            => $roomType->name,
                    'description'     => $roomType->description,
                    'capacity'        => $roomType->capacity,
                    'amenities'       => $roomType->amenities,
                    'images'          => $roomType->images,
                ],
                'available_rooms' => $item['available_count'],
                'pricing'         => $this->pricingService->getBreakdown(
                    $roomType, $nights, $guests
                ),
            ];
        });

        return response()->json([
            'data'  => $result,
            'hotel' => [
                'id'             => $hotel->id,
                'name'           => $hotel->name,
                'check_in_time'  => $hotel->check_in_time,
                'check_out_time' => $hotel->check_out_time,
            ],
            'search' => [
                'check_in'  => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'nights'    => $nights,
                'guests'    => $guests,
            ],
        ]);
    }
}