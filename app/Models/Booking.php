<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_reference', 'user_id', 'room_id', 'hotel_id',
        'check_in', 'check_out', 'guests_count', 'total_price',
        'status', 'special_requests', 'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * Casts - note how dates are cast to Carbon objects
     * This lets us do things like:
     *   $booking->check_in->format('M d, Y')  →  "Jan 15, 2025"
     *   $booking->check_in->diffInDays($booking->check_out)  →  3
     */
    protected $casts = [
        'check_in'     => 'date',
        'check_out'    => 'date',
        'total_price'  => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Auto-generate booking reference on creation
     *
     * "BK-A8Kx9mPq" is easier for customers than "Booking #4582"
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            $booking->booking_reference = 'BK-' . strtoupper(Str::random(8));
        });
    }

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Booking has ONE payment
     * (simplified - in real apps, there might be multiple partial payments)
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Booking has ONE review (optional)
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // =============================================
    // ACCESSORS
    // =============================================

    /**
     * Calculate number of nights
     * Usage: $booking->nights  →  3
     */
    public function getNightsAttribute(): int
    {
        return $this->check_in->diffInDays($this->check_out);
    }

    // =============================================
    // HELPER METHODS
    // =============================================

    /**
     * Can this booking be cancelled?
     *
     * Rules:
     * 1. Status must be 'pending' or 'confirmed'
     * 2. Check-in must be at least 1 day away
     *
     * You can't cancel a booking after you've checked in!
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->check_in->isAfter(now()->addDay());
    }

    /**
     * Can the user leave a review?
     * Only after checking out!
     */
    public function isReviewable(): bool
    {
        return $this->status === 'checked_out'
            && !$this->review()->exists();
    }
}