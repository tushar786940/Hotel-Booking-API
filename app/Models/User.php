<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // Traits = reusable bundles of functionality
    use HasApiTokens;   // Adds token-based authentication methods
    use HasFactory;     // Allows creating test data with factories
    use Notifiable;     // Allows sending notifications (email, SMS, etc.)
    use HasRoles;       // Adds role/permission methods from Spatie

    /**
     * Fields that CAN be mass-assigned.
     *
     * WHY? Security! Without this, a hacker could send extra fields
     * like 'is_admin' => true and modify data they shouldn't.
     *
     * Only fields listed here can be set via Model::create([...])
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
    ];

    /**
     * Fields that are HIDDEN from JSON responses.
     *
     * WHY? When you return a user as JSON (API response),
     * you don't want to expose passwords or tokens!
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Type casting - automatically convert database values to PHP types.
     *
     * WHY? Database stores everything as strings.
     * Casting 'password' to 'hashed' automatically hashes it.
     * Casting dates lets you use Carbon methods like ->format(), ->diffInDays()
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // =============================================
    // RELATIONSHIPS
    // =============================================
    // These define HOW this model connects to others
    //
    // User HAS MANY hotels (a user can own multiple hotels)
    // User HAS MANY bookings (a user can make multiple bookings)
    // User HAS MANY reviews (a user can write multiple reviews)

    /**
     * A user can own multiple hotels (if they're a hotel owner)
     *
     * Database: hotels table has 'user_id' column
     * This means: SELECT * FROM hotels WHERE user_id = {this user's id}
     */
    public function hotels()
    {
        return $this->hasMany(Hotel::class);
    }

    /**
     * A user can have multiple bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * A user can write multiple reviews
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
