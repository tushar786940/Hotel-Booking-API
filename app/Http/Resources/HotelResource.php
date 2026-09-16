<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms Hotel model for LIST view (search results)
 * Shows summary info - not full details
 */
class HotelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * $this refers to the Hotel model being transformed.
     * We pick exactly which fields to include.
     */
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
            'images'         => $this->images,
            'amenities'      => $this->amenities,
            'check_in_time'  => $this->check_in_time,
            'check_out_time' => $this->check_out_time,

            // Show cheapest room price (great for search results)
            'starting_price' => $this->whenLoaded('roomTypes', function () {
                return $this->roomTypes->min('price_per_night');
            }),
        ];
    }
}
