<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationPayments extends Model
{
    protected $table = 'reservation_payments';

    protected $fillable = [
        'reservation_id', 'type', 'amount', 'method',
        'reference', 'paid_at', 'received_by', 'note',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount'  => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'dp'         => 'DP',
            'settlement' => 'Pelunasan',
            'refund'     => 'Refund',
            default      => ucfirst((string) $this->type),
        };
    }
}