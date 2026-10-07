<?php

namespace Tests;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class StoreTestCase extends TestCase
{
    protected $customer;
    protected $admin;
    protected $product;
    protected $variant;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'ghn.token' => '', 'ghn.shop_id' => 0, 'ghn.from_district_id' => 0,
            'services.momo.partner_code' => 'TEST_PARTNER', 'services.momo.access_key' => 'TEST_ACCESS',
            'services.momo.secret_key' => 'TEST_SECRET', 'services.momo.request_type' => 'captureWallet',
            'services.momo.redirect_url' => null, 'services.momo.ipn_url' => null,
            'services.bank.name' => 'Test Bank', 'services.bank.account' => 'TEST_ACCOUNT',
            'shop.shipping_flat_fee' => 30000, 'shop.shipping_free_from' => 500000,
        ]);
        DB::purge('sqlite');
        $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true]);
        Notification::fake();
        Storage::fake('public');
        Http::fake(function () { throw new \RuntimeException('Unexpected external HTTP request in test.'); });
        $this->customer = User::factory()->create(['name' => 'TDH Customer', 'role' => 'user']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Apple', 'slug' => 'apple']);
        $this->product = Product::create(['category_id' => $category->id, 'name' => 'iPhone Test', 'slug' => 'iphone-test', 'status' => 'active']);
        $this->variant = $this->product->variants()->create(['storage' => '128GB', 'color' => 'Black', 'price' => 100000, 'stock' => 5, 'sku' => 'TEST-128-BLACK']);
        $this->actingAs($this->customer);
    }

    protected function order(array $attributes = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $this->customer->id, 'order_code' => Order::generateCode(),
            'receiver_name' => 'Test Customer', 'receiver_phone' => '0901234567', 'receiver_address' => '12 Test Street',
            'payment_method' => 'cod', 'payment_status' => 'pending', 'status' => 'pending',
            'subtotal' => 200000, 'shipping_fee' => 30000, 'total' => 230000,
        ], $attributes));
        $order->items()->create([
            'product_id' => $this->product->id, 'variant_id' => $this->variant->id,
            'product_name' => $this->product->name, 'storage' => $this->variant->storage,
            'color' => $this->variant->color, 'sku' => $this->variant->sku,
            'price' => 100000, 'quantity' => 2, 'subtotal' => 200000,
        ]);
        return $order;
    }

    protected function checkout(array $overrides = [], array $cartOverrides = [])
    {
        $token = (string) Str::uuid();
        $cart = ['variant_' . $this->variant->id => array_merge(['variant_id' => $this->variant->id, 'quantity' => 2], $cartOverrides)];
        return $this->withSession(['cart' => $cart, 'checkout_token' => $token])->post(route('checkout.store'), array_merge([
            'receiver_name' => 'Test Customer', 'receiver_phone' => '0901234567',
            'receiver_address' => '12 Test Street', 'city' => 'Test City', 'payment_method' => 'cod',
            'selected_keys' => 'variant_' . $this->variant->id, 'checkout_token' => $token,
        ], $overrides));
    }
}
