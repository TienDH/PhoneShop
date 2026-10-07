<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GHNOrderService
{
    private function http()
    {
        return Http::withHeaders(['Token' => config('ghn.token'), 'ShopId' => (string) config('ghn.shop_id')])
            ->withOptions(['verify' => (bool) config('ghn.verify_ssl', true), 'connect_timeout' => 5])->timeout(10);
    }

    public function create(Order $order): bool
    {
        if (!app(GHNService::class)->isConfigured() || !$order->ghn_district_id || !$order->ghn_ward_code) {
            return false;
        }
        // The same client order code makes a retried create request identifiable at GHN.
        return DB::transaction(function () use ($order) {
            $order = Order::with('items')->lockForUpdate()->findOrFail($order->id);
            if ($order->ghn_order_code) return true;
            if ($order->status === 'cancelled' || ($order->payment_method !== 'cod' && $order->payment_status !== 'paid')) return false;
            $weight = max(1, $order->items->sum('quantity') * (int) config('ghn.default_weight', 200));
            try {
                $response = $this->http()->post(rtrim(config('ghn.base_url'), '/') . '/v2/shipping-order/create', [
                    'payment_type_id' => 1, 'required_note' => 'CHOXEMHANGKHONGTHU',
                    'client_order_code' => $order->order_code,
                    'to_name' => $order->receiver_name, 'to_phone' => $order->receiver_phone,
                    'to_address' => $order->receiver_address, 'to_district_id' => $order->ghn_district_id,
                    'to_ward_code' => $order->ghn_ward_code, 'to_ward_name' => $order->ward_name,
                    'to_district_name' => $order->district_name, 'to_province_name' => $order->city,
                    'cod_amount' => $order->payment_status === 'paid' ? 0 : (int) $order->total,
                    'insurance_value' => min((int) $order->subtotal, 5000000),
                    'service_type_id' => 2, 'content' => $order->order_code, 'note' => $order->note,
                    'weight' => $weight, 'length' => 20, 'width' => 15, 'height' => 10,
                    'items' => $order->items->map(function ($item) {
                        return ['name' => $item->product_name, 'code' => $item->sku, 'quantity' => $item->quantity, 'price' => (int) $item->price, 'weight' => (int) config('ghn.default_weight', 200)];
                    })->all(),
                ]);
                $code = $response->json('data.order_code');
                if ($response->successful() && $response->json('code') == 200 && $code) {
                    $order->update(['ghn_order_code' => $code, 'shipping_status' => 'ready_to_pick']);
                    return true;
                }
                Log::warning('GHN create failed', ['order_id' => $order->id, 'code' => $response->json('code')]);
            } catch (\Throwable $exception) {
                report($exception);
            }
            return false;
        });
    }

    public function cancel(Order $order): void
    {
        if (!$order->ghn_order_code) return;
        try {
            $response = $this->http()->post(rtrim(config('ghn.base_url'), '/') . '/v2/switch-status/cancel', ['order_codes' => [$order->ghn_order_code]]);
            $result = collect($response->json('data') ?: [])->firstWhere('order_code', $order->ghn_order_code);
            if (!$response->successful() || !($result['result'] ?? false)) {
                throw new \RuntimeException('GHN cancellation rejected.');
            }
            $order->shipping_status = 'cancel';
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['status' => 'Chưa thể hủy vận đơn GHN. Vui lòng thử lại trước khi hủy đơn hàng.']);
        }
    }
}
