<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms Hotel model for DETAIL view (single hotel page)
 * Shows everything including room types and reviews
 */
class HotelDetailResource extends JsonResource
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
            'state'          => $this->state,
            'country'        => $this->country,
            'zip_code'       => $this->zip_code,
            'latitude'       => $this->latitude,
            'longitude'      => $this->longitude,
            'star_rating'    => $this->star_rating,
            'average_rating' => $this->average_rating,
            'check_in_time'  => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'images'         => $this->images,
            'amenities'      => $this->amenities,

            // Nested resources - room types with their details
            'room_types'     => RoomTypeResource::collection(
                $this->whenLoaded('roomTypes')
            ),

            // Recent reviews
            'reviews'        => $this->whenLoaded('reviews', function () {
                return $this->reviews->take(10)->map(fn ($review) => [
                    'id'         => $review->id,
                    'user'       => $review->user->name,
                    'rating'     => $review->rating,
                    'comment'    => $review->comment,
                    'created_at' => $review->created_at->diffForHumans(), // "2 days ago"
                ]);
            }),

            'reviews_count'  => $this->reviews->count(),

            // Owner info
            'owner'          => $this->whenLoaded('owner', function () {
                return [
                    'name'  => $this->owner->name,
                    'email' => $this->owner->email,
                ];
            }),
        ];
    }
}