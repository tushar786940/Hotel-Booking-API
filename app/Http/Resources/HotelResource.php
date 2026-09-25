<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'address'        => $this->address,
            'city'           => $this->city,
            'country'        => $this->country,
            'star_rating'    => $this->star_rating,
            'average_rating' => $this->average_rating,
            'reviews_count'  => $this->reviews_count ?? $this->reviews->count(),

            // ─── Image URLs ───
            'cover_image'    => $this->cover_image,        // Single URL for cards
            'images'         => $this->images_with_urls,   // Full array for gallery
            'image_urls'     => $this->image_urls,         // Flat URL list for simple clients

            'amenities'      => $this->amenities,
            'check_in_time'  => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'starting_price' => $this->whenLoaded('roomTypes', function () {
                return $this->roomTypes->min('price_per_night');
            }),
        ];
    }
}
