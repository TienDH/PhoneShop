<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function place(User $user, array $items, array $data, int $shippingFee): Order
    {
        return DB::transaction(function () use ($user, $items, $data, $shippingFee) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Order::where('checkout_token', $data['checkout_token'])->first();
            if ($existing) {
                abort_unless($existing->user_id == $user->id, 403);
                return $existing;
            }

            $variants = ProductVariant::with('product')
                ->whereIn('id', array_column($items, 'variant_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lines = [];
            $subtotal = 0;
            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);
                $quantity = (int) $item['quantity'];
                if (!$variant || !$variant->product || $variant->product->status !== 'active' || $quantity < 1 || $variant->stock < $quantity) {
                    throw ValidationException::withMessages(['cart' => 'Sản phẩm đã hết hàng hoặc số lượng vượt quá tồn kho. Vui lòng kiểm tra lại giỏ hàng.']);
                }
                $price = (int) round($variant->price);
                if (!isset($item['price']) || (int) $item['price'] !== $price) {
                    throw ValidationException::withMessages(['cart' => 'Giá sản phẩm vừa thay đổi. Vui lòng tải lại trang thanh toán.']);
                }
                $subtotal += $price * $quantity;
                $lines[] = [
                    'product_id' => $variant->product_id, 'variant_id' => $variant->id,
                    'product_name' => $variant->product->name, 'storage' => $variant->storage,
                    'color' => $variant->color, 'sku' => $variant->sku,
                    'image' => $variant->image ?: $variant->product->image,
                    'price' => $price, 'quantity' => $quantity, 'subtotal' => $price * $quantity,
                ];
                $variant->decrement('stock', $quantity);
            }
            if (!$lines) {
                throw ValidationException::withMessages(['cart' => 'Giỏ hàng không có sản phẩm.']);
            }

            $order = $user->orders()->create(array_merge($data, [
                'order_code' => Order::generateCode(), 'status' => 'pending', 'payment_status' => 'pending',
                'subtotal' => $subtotal, 'shipping_fee' => $shippingFee, 'total' => $subtotal + $shippingFee,
                'stock_deducted' => true,
            ]));
            $order->items()->createMany($lines);
            $order->histories()->create(['user_id' => $user->id, 'status' => 'pending', 'note' => 'Đã đặt hàng']);
            if (!$order->uses_momo) {
                $order->transactions()->create(['gateway' => $order->payment_method, 'amount' => $order->total, 'status' => 'pending']);
            }
            return $order;
        });
    }

    public function changeStatus(Order $order, string $status, User $actor, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $status, $actor, $note) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->status === $status) {
                return $order;
            }
            if (!in_array($status, $order->next_statuses, true)) {
                throw ValidationException::withMessages(['status' => 'Không thể chuyển đơn hàng sang trạng thái này.']);
            }
            if ($status !== 'cancelled' && $order->payment_method !== 'cod' && $order->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Đơn hàng cần được thanh toán trước khi xử lý.']);
            }
            if ($actor->role !== 'admin' && ($order->user_id != $actor->id || $order->status !== 'pending' || $status !== 'cancelled')) {
                abort(403);
            }

            if ($status === 'cancelled') {
                app(GHNOrderService::class)->cancel($order);
                if ($order->stock_deducted) {
                    $quantities = $order->items()->get()->groupBy('variant_id')->map->sum('quantity');
                    $variants = ProductVariant::whereIn('id', $quantities->keys())->orderBy('id')->lockForUpdate()->get();
                    foreach ($variants as $variant) {
                        $variant->increment('stock', $quantities[$variant->id]);
                    }
                    $order->stock_deducted = false;
                }
                if ($order->payment_status === 'paid') {
                    $order->payment_status = 'refund_pending';
                }
                $order->transactions()->whereIn('status', ['pending', 'initiated'])->update(['status' => 'cancelled']);
            }
            if ($status === 'done') {
                $order->completed_at = now();
                if ($order->payment_method === 'cod') {
                    $order->payment_status = 'paid';
                    $order->transactions()->where('gateway', 'cod')->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now()]);
                }
            }
            $order->status = $status;
            $order->save();
            $order->histories()->create(['user_id' => $actor->id, 'status' => $status, 'note' => $note]);
            return $order;
        });
    }

    public function confirmBankPayment(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->payment_method !== 'bank' || $order->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'Không thể xác nhận thanh toán cho đơn hàng này.']);
            }
            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
                $order->transactions()->where('gateway', 'bank')->where('status', 'pending')->update(['status' => 'paid', 'paid_at' => now()]);
            }
        });
    }
}
