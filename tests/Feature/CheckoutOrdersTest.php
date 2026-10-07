<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\StoreTestCase;

class CheckoutOrdersTest extends StoreTestCase
{
    public function test_price_changes_after_shipping_quote_abort_without_deducting_stock()
    {
        $item = app(\App\Services\CartService::class)->snapshot($this->variant, 2);
        $this->variant->update(['price' => 300000]);
        try {
            app(\App\Services\OrderService::class)->place($this->customer, [$item], ['checkout_token' => (string) Str::uuid()], 30000);
            $this->fail('A stale price must not be used to create an order.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }
        $this->assertSame(0, Order::count());
        $this->assertSame(5, $this->variant->fresh()->stock);
    }

    public function test_ghn_address_is_required_even_for_free_shipping_orders()
    {
        config(['ghn.token' => 'TEST', 'ghn.shop_id' => 1, 'ghn.from_district_id' => 1]);
        $this->variant->update(['price' => 500000]);
        $this->checkout()->assertSessionHasErrors(['ghn_district_id', 'ghn_ward_code', 'district_name', 'ward_name']);
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_uses_current_prices_and_server_shipping_and_decrements_stock()
    {
        $this->checkout(['shipping_fee' => 0], ['price' => 1, 'product_name' => 'Forged name'])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertEquals(200000, $order->subtotal);
        $this->assertEquals(30000, $order->shipping_fee);
        $this->assertEquals(230000, $order->total);
        $this->assertTrue($order->stock_deducted);
        $this->assertSame(3, $this->variant->fresh()->stock);
        $this->assertSame('iPhone Test', $order->items->first()->product_name);
        $this->assertSame([], session('cart'));
        $this->assertSame('pending', $order->transactions->first()->status);
        $this->assertSame(1, $order->histories()->count());
        $this->get(route('orders.show', $order))->assertOk();
    }

    public function test_selected_checkout_leaves_other_cart_items()
    {
        $second = $this->product->variants()->create(['storage' => '256GB', 'color' => 'White', 'price' => 200000, 'stock' => 8, 'sku' => 'SECOND']);
        $token = (string) Str::uuid();
        $this->withSession(['checkout_token' => $token, 'cart' => [
            'variant_' . $this->variant->id => ['variant_id' => $this->variant->id, 'quantity' => 1],
            'variant_' . $second->id => ['variant_id' => $second->id, 'quantity' => 1],
        ]])->post(route('checkout.store'), [
            'receiver_name' => 'Customer', 'receiver_phone' => '0901234567', 'receiver_address' => 'Test',
            'payment_method' => 'cod', 'selected_keys' => 'variant_' . $this->variant->id, 'checkout_token' => $token,
        ])->assertRedirect();
        $this->assertArrayHasKey('variant_' . $second->id, session('cart'));
        $this->assertSame(8, $second->fresh()->stock);
    }

    public function test_insufficient_inventory_rolls_back_the_order()
    {
        $this->checkout([], ['quantity' => 6])->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
        $this->assertSame(5, $this->variant->fresh()->stock);
        $this->assertCount(1, session('cart'));
    }

    public function test_duplicate_submission_does_not_create_or_deduct_twice()
    {
        $this->checkout()->assertRedirect();
        $order = Order::firstOrFail();
        $this->post(route('checkout.store'), [
            'receiver_name' => $order->receiver_name, 'receiver_phone' => $order->receiver_phone,
            'receiver_address' => $order->receiver_address, 'payment_method' => 'cod',
            'selected_keys' => 'variant_' . $this->variant->id, 'checkout_token' => $order->checkout_token,
        ])->assertRedirect(route('orders.show', $order));
        $this->assertSame(1, Order::count());
        $this->assertSame(3, $this->variant->fresh()->stock);
    }

    public function test_cancellation_restock_is_idempotent_and_paid_orders_require_refund()
    {
        $this->checkout()->assertRedirect();
        $order = Order::firstOrFail();
        $order->update(['payment_status' => 'paid']);
        $this->post(route('orders.cancel', $order))->assertRedirect();
        $this->post(route('orders.cancel', $order))->assertRedirect();
        $this->assertSame(5, $this->variant->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('refund_pending', $order->fresh()->payment_status);
        $this->assertFalse($order->fresh()->stock_deducted);
    }

    public function test_admin_order_transitions_and_cod_payment_on_delivery()
    {
        $this->checkout()->assertRedirect();
        $order = Order::firstOrFail();
        $this->actingAs($this->admin);
        $this->putJson(route('admin.orders.update', $order), ['status' => 'done'])->assertUnprocessable();
        foreach (['confirmed', 'packed', 'shipping', 'done'] as $status) {
            $this->put(route('admin.orders.update', $order), ['status' => $status])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($status, $order->fresh()->status);
        }
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertSame('paid', $order->transactions()->first()->status);
        $this->assertSame(3, $this->variant->fresh()->stock);
        $this->putJson(route('admin.orders.update', $order), ['status' => 'cancelled'])->assertUnprocessable();
    }

    public function test_customer_order_access_is_private_and_admin_routes_are_protected()
    {
        $order = $this->order();
        $this->actingAs(User::factory()->create());
        $this->get(route('orders.show', $order))->assertNotFound();
        $this->get(route('checkout.success', $order->order_code))->assertNotFound();
        $this->post(route('orders.cancel', $order))->assertNotFound();
        $this->post(route('orders.momo.pay', $order))->assertNotFound();
        foreach (['admin.orders.index', 'admin.users.index', 'admin.reports.index'] as $route) {
            $this->get(route($route))->assertRedirect('/');
        }
        $this->put(route('admin.orders.update', $order), ['status' => 'confirmed'])->assertRedirect('/');
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_checkout_requires_email_verification()
    {
        $this->customer->forceFill(['email_verified_at' => null])->save();
        $this->get(route('checkout.index'))->assertRedirect(route('verification.notice'));
        $this->get(route('orders.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_cart_updates_validate_current_inventory_and_reject_inactive_products()
    {
        $key = 'variant_' . $this->variant->id;
        $this->withSession(['cart' => [$key => ['variant_id' => $this->variant->id, 'quantity' => 2, 'stock' => 100]]]);
        $this->variant->update(['stock' => 1]);
        $this->postJson(route('cart.update'), ['key' => $key, 'quantity' => 3])->assertUnprocessable();
        $this->postJson(route('cart.update'), ['key' => $key, 'quantity' => 1])->assertOk()->assertJsonPath('quantity', 1);
        $this->product->update(['status' => 'inactive']);
        $this->post(route('cart.add'), ['variant_id' => $this->variant->id, 'quantity' => 1])->assertNotFound();
        $this->get(route('product.show', $this->product->slug))->assertNotFound();
    }

    public function test_bank_payment_can_be_confirmed_only_by_admin_for_bank_orders()
    {
        $this->checkout(['payment_method' => 'bank'])->assertRedirect();
        $order = Order::firstOrFail();
        $this->actingAs($this->admin);
        $this->putJson(route('admin.orders.update', $order), ['status' => 'confirmed'])->assertUnprocessable();
        $this->post(route('admin.orders.payment', $order))->assertRedirect();
        $this->post(route('admin.orders.payment', $order))->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, $order->transactions()->where('status', 'paid')->count());
        $other = $this->order(['payment_method' => 'momo']);
        $this->postJson(route('admin.orders.payment', $other))->assertUnprocessable();
        $this->assertSame('pending', $other->fresh()->payment_status);
    }
}
