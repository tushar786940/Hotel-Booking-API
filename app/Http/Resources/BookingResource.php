<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'booking_reference' => $this->booking_reference,

            // Include related data in a clean structure
            'hotel' => [
                'id'   => $this->hotel->id,
                'name' => $this->hotel->name,
                'city' => $this->hotel->city,
            ],
            'room' => [
                'room_number' => $this->room->room_number,
                'room_type'   => $this->room->roomType->name,
                'floor'       => $this->room->floor,
            ],

            'check_in'         => $this->check_in->format('Y-m-d'),
            'check_out'        => $this->check_out->format('Y-m-d'),
            'nights'           => $this->nights, // Uses the accessor
            'guests_count'     => $this->guests_count,
            'total_price'      => (float) $this->total_price,
            'status'           => $this->status,
            'special_requests' => $this->special_requests,
            'is_cancellable'   => $this->isCancellable(),

            // Conditionally include payment if loaded
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'status'         => $this->payment->status,
                    'method'         => $this->payment->method,
                    'amount'         => (float) $this->payment->amount,
                    'transaction_id' => $this->payment->transaction_id,
                    'paid_at'        => $this->payment->paid_at?->toISOString(),
                ];
            }),

            'review' => $this->whenLoaded('review', function () {
                return [
                    'rating'  => $this->review->rating,
                    'comment' => $this->review->comment,
                ];
            }),

            'created_at' => $this->created_at->toISOString(),
        ];
    }
}