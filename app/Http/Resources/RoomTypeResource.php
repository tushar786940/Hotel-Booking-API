<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'price_per_night' => (float) $this->price_per_night,
            'capacity'        => $this->capacity,
            'total_rooms'     => $this->total_rooms,
            'amenities'       => $this->amenities,
            'images'          => $this->images,

            // Show available rooms count (only if rooms are loaded)
            'available_rooms' => $this->whenLoaded('rooms', function () {
                return $this->rooms
                    ->where('is_available', true)
                    ->where('status', 'available')
                    ->count();
            }),
        ];
    }
}