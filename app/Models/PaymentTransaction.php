<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id', 'gateway', 'gateway_order_id', 'transaction_id', 'amount',
        'status', 'result_code', 'message', 'request_payload', 'response_payload', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            'pending' => 'Chờ thanh toán', 'initiated' => 'Đang thanh toán',
            'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'cancelled' => 'Đã hủy',
        ][$this->status] ?? $this->status;
    }
}
