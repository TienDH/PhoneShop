<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_code', 'receiver_name', 'receiver_phone',
        'receiver_address', 'city', 'note',
        'payment_method', 'payment_status', 'status',
        'subtotal', 'shipping_fee', 'total',
        'checkout_token', 'stock_deducted', 'completed_at', 'ghn_district_id',
        'ghn_ward_code', 'district_name', 'ward_name', 'ghn_order_code', 'shipping_status',
    ];

    protected $casts = ['stock_deducted' => 'boolean', 'completed_at' => 'datetime'];

    public const STATUSES = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đang xử lý',
        'packed' => 'Đã đóng gói',
        'shipping' => 'Đang vận chuyển',
        'done' => 'Đã giao hàng',
        'cancelled' => 'Đã hủy',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateCode(): string
    {
        return 'TDH' . strtoupper(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function histories()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return [
            'pending' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán',
            'failed' => 'Thanh toán thất bại', 'refund_pending' => 'Chờ hoàn tiền',
        ][$this->payment_status] ?? $this->payment_status;
    }

    public function getNextStatusesAttribute(): array
    {
        $transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['packed', 'cancelled'],
            'packed' => ['shipping', 'cancelled'],
            'shipping' => ['done'],
        ];
        return $transitions[$this->status] ?? [];
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cod'   => 'Thanh toán khi nhận hàng (COD)',
            'momo'  => 'Ví MoMo',
            'momo_atm' => 'Thẻ ATM qua MoMo',
            'momo_card' => 'Thẻ tín dụng / ghi nợ qua MoMo',
            'bank'  => 'Chuyển khoản ngân hàng',
            default => $this->payment_method,
        };
    }

    public function getUsesMomoAttribute(): bool
    {
        return in_array($this->payment_method, ['momo', 'momo_atm', 'momo_card'], true);
    }
}
