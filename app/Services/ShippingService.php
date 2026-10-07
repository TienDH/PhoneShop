<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class ShippingService
{
    public function quote(array $items, int $subtotal, ?int $districtId = null, ?string $wardCode = null): int
    {
        if ($subtotal >= (int) config('shop.shipping_free_from')) {
            return 0;
        }

        $ghn = app(GHNService::class);
        if (!$ghn->isConfigured()) {
            return max(0, (int) config('shop.shipping_flat_fee'));
        }
        if (!$districtId || !$wardCode) {
            throw ValidationException::withMessages(['receiver_address' => 'Vui lòng chọn địa chỉ giao hàng đầy đủ.']);
        }

        $quantity = array_sum(array_column($items, 'quantity'));
        $fee = $ghn->calculateFee($districtId, $wardCode, max(1, $quantity * (int) config('ghn.default_weight', 200)), min($subtotal, 5000000));
        if (!($fee['success'] ?? false)) {
            throw ValidationException::withMessages(['receiver_address' => 'Chưa thể tính phí giao hàng. Vui lòng thử lại.']);
        }
        return (int) $fee['fee'];
    }
}
