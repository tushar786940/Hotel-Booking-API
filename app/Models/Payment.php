<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'amount', 'currency', 'method',
        'transaction_id', 'status', 'paid_at', 'gateway_response',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'paid_at'          => 'datetime',
        'gateway_response' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Check if payment is successful
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}