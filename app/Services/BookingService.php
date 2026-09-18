<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles booking creation and management
 *
 * This service orchestrates the booking process:
 * 1. Find available room
 * 2. Calculate price
 * 3. Create booking record
 * 4. Fire events (notifications, etc.)
 */
class BookingService
{
    /**
     * Constructor Injection
     *
     * WHY? Instead of creating dependencies inside the class,
     * Laravel automatically provides (injects) them.
     *
     * This is called "Dependency Injection" - a key design pattern.
     *
     * Benefits:
     * - Easy to test (can mock dependencies)
     * - Loose coupling (service doesn't create its own dependencies)
     * - Laravel's service container handles the wiring
     */
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected PricingService $pricingService,
    ) {}

    /**
     * Create a new booking
     *
     * @param User  $user - The user making the booking
     * @param array $data - Booking details (room_type_id, dates, etc.)
     * @return Booking
     * @throws ValidationException
     */
    public function createBooking(User $user, array $data): Booking
    {
        /**
         * DB::transaction() wraps everything in a database transaction.
         *
         * WHY? If step 3 (create booking) succeeds but step 4 fails,
         * we want to UNDO step 3. Transactions ensure either
         * EVERYTHING succeeds or NOTHING changes.
         *
         * Without transactions:
         *   Step 1: ✅ Room found
         *   Step 2: ✅ Price calculated
         *   Step 3: ✅ Booking created in DB
         *   Step 4: ❌ Something fails
         *   Result: Orphan booking in DB! BAD!
         *
         * With transactions:
         *   Step 1: ✅ Room found
         *   Step 2: ✅ Price calculated
         *   Step 3: ✅ Booking created (but not committed yet)
         *   Step 4: ❌ Something fails
         *   Result: Everything rolled back. Database unchanged. GOOD!
         */
        return DB::transaction(function () use ($user, $data) {

            // ─── STEP 1: Find an available room ───
            $room = $this->availabilityService->findAvailableRoom(
                $data['room_type_id'],
                $data['check_in'],
                $data['check_out']
            );

            if (!$room) {
                // This throws a 422 error with a clear message
                throw ValidationException::withMessages([
                    'room_type_id' => ['No rooms available for the selected dates.'],
                ]);
            }

            // ─── STEP 2: Calculate total price ───
            $checkIn  = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            $nights   = $checkIn->diffInDays($checkOut);

            $totalPrice = $this->pricingService->calculateTotal(
                $room->roomType,
                $nights,
                $data['guests_count'] ?? 1
            );

            // ─── STEP 3: Create the booking ───
            $booking = Booking::create([
                'user_id'          => $user->id,
                'room_id'          => $room->id,
                'hotel_id'         => $room->roomType->hotel_id,
                'check_in'         => $data['check_in'],
                'check_out'        => $data['check_out'],
                'guests_count'     => $data['guests_count'] ?? 1,
                'total_price'      => $totalPrice,
                'status'           => 'pending',
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            // ─── STEP 4: Fire event ───
            // Events notify other parts of the system
            // "Hey, a booking was just created!"
            // Listeners react: send email, update stats, etc.
            event(new BookingCreated($booking));

            // Load relationships so the response includes hotel/room details
            return $booking->load(['room.roomType', 'hotel', 'user']);
        });
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): Booking
    {
        // Business rule check
        if (!$booking->isCancellable()) {
            throw ValidationException::withMessages([
                'booking' => ['This booking cannot be cancelled. Either it\'s too late or it\'s already cancelled.'],
            ]);
        }

        $booking->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => $reason,
        ]);

        event(new BookingCancelled($booking));

        // fresh() reloads the model from database
        return $booking->fresh();
    }
}
