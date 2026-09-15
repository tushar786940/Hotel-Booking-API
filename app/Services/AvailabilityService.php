<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Support\Collection;

/**
 * Handles all room availability logic
 *
 * This is the MOST IMPORTANT service in a booking system.
 * It prevents double-bookings (two guests booking the same room
 * for the same dates).
 */
class AvailabilityService
{
    /**
     * Search for hotels with available rooms
     *
     * This is what powers the main search page:
     * "Show me hotels in New York with available rooms from Jan 15-18"
     *
     * @param array $filters - Search criteria
     * @return Collection    - Hotels with available rooms
     */
    public function search(array $filters): Collection
    {
        // Start building the query
        // query() gives us a query builder that we can chain conditions onto
        $query = Hotel::query()
            ->active()                              // Only active hotels
            ->with(['roomTypes.rooms', 'reviews']); // Eager load relationships

        /*
         * WHY eager loading?
         *
         * WITHOUT eager loading (N+1 problem):
         *   1 query: Get 50 hotels
         *   50 queries: Get room types for each hotel
         *   200 queries: Get rooms for each room type
         *   = 251 total queries! SLOW!
         *
         * WITH eager loading:
         *   1 query: Get 50 hotels
         *   1 query: Get ALL room types for those hotels
         *   1 query: Get ALL rooms for those room types
         *   = 3 total queries! FAST!
         */

        // Apply filters using when()
        // when($condition, $callback) = only apply if condition is truthy
        if (!empty($filters['city'])) {
            $query->inCity($filters['city']);
        }

        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (!empty($filters['star_rating'])) {
            $query->where('star_rating', '>=', $filters['star_rating']);
        }

        // Price filter - only show hotels that have rooms in budget
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $query->whereHas('roomTypes', function ($q) use ($filters) {
                if (!empty($filters['min_price'])) {
                    $q->where('price_per_night', '>=', $filters['min_price']);
                }
                if (!empty($filters['max_price'])) {
                    $q->where('price_per_night', '<=', $filters['max_price']);
                }
            });
        }

        // Guest capacity filter
        if (!empty($filters['guests'])) {
            $query->whereHas('roomTypes', function ($q) use ($filters) {
                $q->where('capacity', '>=', $filters['guests']);
            });
        }

        // Execute the query
        $hotels = $query->get();

        // Filter by date availability (done in PHP, not SQL)
        // WHY in PHP? Because the availability check requires
        // complex per-room logic that's easier in PHP
        if (!empty($filters['check_in']) && !empty($filters['check_out'])) {
            $hotels = $hotels->filter(function ($hotel) use ($filters) {
                return $this->hasAvailableRooms(
                    $hotel,
                    $filters['check_in'],
                    $filters['check_out']
                );
            });
        }

        return $hotels->values(); // Re-index the collection
    }

    /**
     * Check if a hotel has ANY available rooms for dates
     *
     * Quick yes/no check used for search results
     */
    public function hasAvailableRooms(
        Hotel $hotel,
        string $checkIn,
        string $checkOut
    ): bool {
        return $this->getAvailableRoomTypes($hotel, $checkIn, $checkOut)
                    ->isNotEmpty();
    }

    /**
     * Get available room types with counts for a hotel
     *
     * Used on the hotel detail page to show:
     * "Standard - 3 rooms available - $99/night"
     * "Deluxe - 1 room available - $179/night"
     * "Suite - 0 rooms available" (filtered out)
     */
    public function getAvailableRoomTypes(
        Hotel $hotel,
        string $checkIn,
        string $checkOut
    ): Collection {
        return $hotel->roomTypes
            ->map(function ($roomType) use ($checkIn, $checkOut) {

                // Check each room of this type
                $availableRooms = $roomType->rooms
                    ->filter(function ($room) use ($checkIn, $checkOut) {
                        return $room->is_available
                            && $room->status === 'available'
                            && $room->isAvailableForDates($checkIn, $checkOut);
                    });

                return [
                    'room_type'       => $roomType,
                    'available_count' => $availableRooms->count(),
                    'available_rooms' => $availableRooms->pluck('id'),
                ];
            })
            // Only return room types that have available rooms
            ->filter(fn ($item) => $item['available_count'] > 0)
            ->values();
    }

    /**
     * Find ONE available room of a specific type
     *
     * Used during the booking process:
     * "Give me any available Deluxe room for Jan 15-18"
     *
     * Returns the first available room, or null if none found.
     */
    public function findAvailableRoom(
        int $roomTypeId,
        string $checkIn,
        string $checkOut
    ): ?Room {
        $roomType = RoomType::with('rooms.bookings')->findOrFail($roomTypeId);

        return $roomType->rooms
            ->filter(function ($room) use ($checkIn, $checkOut) {
                return $room->is_available
                    && $room->status === 'available'
                    && $room->isAvailableForDates($checkIn, $checkOut);
            })
            ->first(); // Return the first available room (or null)
    }
}