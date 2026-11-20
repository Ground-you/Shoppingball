<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'payment_id',
        'order_claim_id',
        'refund_amount',
        'refund_status',
        'reason',
        'refunded_at',
        'failure_message',
        'pg_refund_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'refunded_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
