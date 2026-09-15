<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'price_per_night',
        'capacity',
        'total_rooms',
        'amenities',
        'images',
    ];

    protected $casts = [
        'amenities'       => 'array',
        'images'          => 'array',
        'price_per_night' => 'decimal:2',
    ];

    /**
     * Room type belongs to a hotel
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Room type has many physical rooms
     *
     * Example: "Deluxe" room type has rooms D001, D002, D003
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get count of currently available rooms
     */
    public function getAvailableRoomsCountAttribute(): int
    {
        return $this->rooms()
            ->where('is_available', true)
            ->where('status', 'available')
            ->count();
    }
}
