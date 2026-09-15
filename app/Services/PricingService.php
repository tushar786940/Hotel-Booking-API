<?php

namespace App\Services;

use App\Models\RoomType;

/**
 * Handles all price calculations
 *
 * WHY a separate service?
 * Pricing logic might change (seasonal rates, discounts, etc.)
 * Having it in ONE place means we update it ONCE.
 */
class PricingService
{
    /**
     * Tax rate (12%)
     * In production, this would come from config or database
     */
    const TAX_RATE = 0.12;

    /**
     * Extra guest charge (20% of room rate per extra guest)
     */
    const EXTRA_GUEST_RATE = 0.20;

    /**
     * Base capacity included in room price
     */
    const BASE_CAPACITY = 2;

    /**
     * Calculate total price for a booking
     *
     * @param RoomType $roomType  - Which type of room
     * @param int      $nights    - How many nights
     * @param int      $guests    - How many guests
     * @return float              - Total price including tax
     *
     * Example:
     *   Room: $100/night, 3 nights, 4 guests
     *   Base: $100 × 3 = $300
     *   Extra guests: 2 extra × ($100 × 0.20) × 3 nights = $120
     *   Subtotal: $420
     *   Tax (12%): $50.40
     *   Total: $470.40
     */
    public function calculateTotal(
        RoomType $roomType,
        int $nights,
        int $guests = 1
    ): float {
        $breakdown = $this->getBreakdown($roomType, $nights, $guests);

        return $breakdown['total'];
    }

    /**
     * Get detailed price breakdown
     *
     * Returns array with each component of the price.
     * Useful for showing the user HOW their total was calculated.
     */
    public function getBreakdown(
        RoomType $roomType,
        int $nights,
        int $guests = 1
    ): array {
        // Base price = price per night × number of nights
        $basePrice = $roomType->price_per_night * $nights;

        // Extra guest charges
        $extraGuestCharge = 0;

        if ($guests > self::BASE_CAPACITY) {
            $extraGuests = $guests - self::BASE_CAPACITY;

            // Each extra guest pays 20% of the nightly rate per night
            $extraGuestCharge = $extraGuests
                * ($roomType->price_per_night * self::EXTRA_GUEST_RATE)
                * $nights;
        }

        $subtotal = $basePrice + $extraGuestCharge;

        // Tax
        $tax = $subtotal * self::TAX_RATE;

        $total = $subtotal + $tax;

        return [
            'price_per_night'    => (float) $roomType->price_per_night,
            'nights'             => $nights,
            'base_price'         => round($basePrice, 2),
            'extra_guests'       => max(0, $guests - self::BASE_CAPACITY),
            'extra_guest_charge' => round($extraGuestCharge, 2),
            'subtotal'           => round($subtotal, 2),
            'tax_rate'           => self::TAX_RATE * 100 . '%',
            'tax'                => round($tax, 2),
            'total'              => round($total, 2),
        ];
    }
}
