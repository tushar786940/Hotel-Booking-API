<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'floor',
        'status',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    /**
     * Room belongs to a room type
     */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Room has many bookings (over time)
     * Same room can be booked many times (just not overlapping dates)
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Check if this room is available for specific dates
     *
     * This is the CORE LOGIC of our booking system!
     *
     * We need to check: are there any EXISTING bookings
     * that OVERLAP with the requested dates?
     *
     * OVERLAP SCENARIOS:
     * ──────────────────────────────────────────
     * Existing:     [====CHECK_IN====CHECK_OUT====]
     *
     * Case 1:  [──new──]              (new check-in falls within existing)
     * Case 2:              [──new──]  (new check-out falls within existing)
     * Case 3:  [────────────new────────────]  (new wraps around existing)
     * Case 4:       [──new──]         (new is completely inside existing)
     * ──────────────────────────────────────────
     *
     * If ANY of these cases match → room is NOT available
     */
    public function isAvailableForDates(string $checkIn, string $checkOut): bool
    {
        $hasConflict = $this->bookings()
            // Ignore cancelled/refunded bookings
            ->whereNotIn('status', ['cancelled', 'refunded'])
            // Check for date overlaps
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query
                    // Case 1: Existing booking's check-in is within new dates
                    ->whereBetween('check_in', [$checkIn, $checkOut])
                    // Case 2: Existing booking's check-out is within new dates
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    // Case 3: Existing booking completely wraps new dates
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->exists(); // Returns true/false (faster than count())

        // If there IS a conflict, room is NOT available
        return !$hasConflict;
    }
}
