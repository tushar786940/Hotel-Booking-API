<?php

namespace App\Models;

use App\Models\Concerns\HasPublicImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Hotel extends Model
{
    use HasFactory;
    use HasPublicImages;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'latitude',
        'longitude',
        'star_rating',
        'check_in_time',
        'check_out_time',
        'images',
        'amenities',
        'is_active',
    ];

    /**
     * Cast these columns to specific PHP types
     *
     * 'array' cast: JSON string in DB ↔ PHP array in code
     * This means you can do: $hotel->amenities = ['wifi', 'pool'];
     * And Laravel automatically converts to/from JSON for the database
     */
    protected $casts = [
        'images'    => 'array',
        'amenities' => 'array',
        'is_active' => 'boolean',
        'latitude'  => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Model Boot Method - runs when the model is initialized
     *
     * We use it to auto-generate a slug when creating a hotel.
     * "Grand Palace Hotel" → "grand-palace-hotel-x8k9m"
     *
     * WHY slugs? For clean URLs:
     *   BAD:  /hotels/42
     *   GOOD: /hotels/grand-palace-hotel-x8k9m
     */
    protected static function boot()
    {
        parent::boot();

        // This runs BEFORE a new hotel is saved to the database
        static::creating(function ($hotel) {
            $hotel->slug = Str::slug($hotel->name) . '-' . Str::random(5);
        });
    }

    // =============================================
    // RELATIONSHIPS
    // =============================================

    /**
     * Hotel belongs to an owner (User)
     * Inverse of User::hasMany(Hotel)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Hotel has many room types (Standard, Deluxe, Suite)
     */
    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    /**
     * Hotel has many rooms THROUGH room types
     *
     * Hotel → RoomType → Room
     *
     * hasManyThrough = skip the middle table
     * Instead of $hotel->roomTypes->each->rooms
     * We can do $hotel->rooms directly!
     */
    public function rooms()
    {
        return $this->hasManyThrough(Room::class, RoomType::class);
    }

    /**
     * Hotel has many bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Hotel has many reviews
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // =============================================
    // ACCESSORS
    // =============================================
    // Accessors create "virtual" properties that don't exist in the database
    // but are calculated on the fly.

    /**
     * Get the average rating from all reviews
     *
     * Usage: $hotel->average_rating  →  4.5
     *
     * The naming convention 'getXxxAttribute' creates a property 'xxx'
     * So 'getAverageRatingAttribute' → $hotel->average_rating
     */
    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    /**
     * Get the cheapest room price
     *
     * Usage: $hotel->starting_price  →  99.99
     */
    public function getStartingPriceAttribute(): float
    {
        return $this->roomTypes()->min('price_per_night') ?? 0;
    }

    // =============================================
    // SCOPES
    // =============================================
    // Scopes are reusable query filters.
    // Instead of writing the same WHERE clause everywhere,
    // define it once as a scope.

    /**
     * Only get active hotels
     *
     * Usage: Hotel::active()->get()
     * Same as: Hotel::where('is_active', true)->get()
     *
     * Prefix with 'scope' + PascalCase name
     * 'scopeActive' is called as '->active()'
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter by city (partial match)
     *
     * Usage: Hotel::inCity('New York')->get()
     * Same as: Hotel::where('city', 'LIKE', '%New York%')->get()
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', 'LIKE', "%{$city}%");
    }

}
