<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\StoreTestCase;

class StoreManagementTest extends StoreTestCase
{
    public function test_catalog_search_filters_and_inactive_products()
    {
        $this->get(route('products.index', ['q' => 'TEST-128-BLACK', 'storage' => '128GB', 'color' => 'Black', 'in_stock' => 1]))
            ->assertOk()->assertViewHas('products', function ($products) { return $products->total() === 1; });
        $this->get(route('products.index', ['min_price' => 200000]))->assertOk()
            ->assertViewHas('products', function ($products) { return $products->total() === 0; });
        $this->product->variants()->create(['storage' => '256GB', 'color' => 'White', 'price' => 500000, 'stock' => 2, 'sku' => 'WHITE']);
        $this->get(route('products.index', ['storage' => '128GB', 'color' => 'White']))->assertOk()
            ->assertViewHas('products', function ($products) { return $products->total() === 0; });
        $this->product->update(['status' => 'inactive']);
        $this->get(route('products.index'))->assertOk()->assertViewHas('products', function ($products) { return $products->total() === 0; });
    }

    public function test_best_sellers_count_only_delivered_paid_orders()
    {
        $this->order(['status' => 'done', 'payment_status' => 'paid', 'completed_at' => now()]);
        $this->order(['status' => 'cancelled', 'payment_status' => 'paid']);
        $this->order(['status' => 'done', 'payment_status' => 'pending']);
        $product = Product::withSales()->find($this->product->id);
        $this->assertEquals(2, $product->sold_count);
        $this->get('/')->assertOk()->assertViewHas('bestSellers', function ($products) { return $products->first()->sold_count == 2; });
    }

    public function test_profile_edit_preserves_role_and_email_changes_require_verification()
    {
        $this->put(route('profile.update'), ['name' => 'Updated Name', 'email' => $this->customer->email, 'phone' => '0901234567', 'address' => 'New Address', 'role' => 'admin'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('user', $this->customer->fresh()->role);
        $this->assertSame('New Address', $this->customer->fresh()->address);
        $this->put(route('profile.update'), ['name' => 'Updated Name', 'email' => 'new-email@tdh.test'])
            ->assertRedirect(route('verification.notice'));
        $this->assertNull($this->customer->fresh()->email_verified_at);
        Notification::assertSentTo($this->customer, VerifyEmail::class);
    }

    public function test_password_change_checks_the_current_password()
    {
        $this->putJson(route('profile.password'), ['current_password' => 'wrong', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])
            ->assertUnprocessable();
        $this->customer->update(['password' => Hash::make('old-password-123')]);
        $this->put(route('profile.password'), ['current_password' => 'old-password-123', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password-123', $this->customer->fresh()->password));
    }

    public function test_only_completed_owned_purchases_can_be_reviewed_and_edited()
    {
        $order = $this->order();
        $data = ['order_item_id' => $order->items()->first()->id, 'rating' => 5, 'comment' => 'Great phone'];
        $url = route('reviews.store', $this->product);
        $this->post($url, $data)->assertNotFound();
        $order->update(['status' => 'done', 'payment_status' => 'paid', 'completed_at' => now()]);
        $this->post($url, $data)->assertRedirect();
        $this->post($url, array_merge($data, ['rating' => 4, 'comment' => 'Updated review']))->assertRedirect();
        $this->assertSame(1, Review::count());
        $this->assertEquals(4, Review::first()->rating);
        $this->get(route('product.show', $this->product->slug))->assertOk()->assertSee('Updated review');
        $this->actingAs(User::factory()->create());
        $this->post($url, $data)->assertNotFound();
        $this->assertSame(1, Review::count());
    }

    public function test_reports_use_completion_date_and_exclude_cancelled_unpaid_or_undelivered_orders()
    {
        $this->order(['status' => 'done', 'payment_status' => 'paid', 'completed_at' => '2026-10-07 12:00:00']);
        $this->order(['status' => 'done', 'payment_status' => 'paid', 'completed_at' => '2026-09-20 12:00:00']);
        $this->order(['status' => 'cancelled', 'payment_status' => 'paid']);
        $this->order(['status' => 'done', 'payment_status' => 'pending', 'completed_at' => '2026-10-07 12:00:00']);
        $this->order(['status' => 'shipping', 'payment_status' => 'paid']);
        $this->actingAs($this->admin)->get(route('admin.reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()->assertViewHas('revenue', 200000)->assertViewHas('orderCount', 1)->assertViewHas('units', 2)->assertViewHas('shipping', 30000);
    }

    public function test_admin_role_management_rejects_self_demotion_and_customer_access()
    {
        $this->put(route('admin.users.update', $this->customer), ['role' => 'admin'])->assertRedirect('/');
        $this->assertSame('user', $this->customer->fresh()->role);
        $this->actingAs($this->admin)->putJson(route('admin.users.update', $this->admin), ['role' => 'user'])->assertUnprocessable();
        $this->put(route('admin.users.update', $this->customer), ['role' => 'admin'])->assertRedirect();
        $this->assertSame('admin', $this->customer->fresh()->role);
    }

    public function test_new_pages_render_and_checkout_prefills_profile()
    {
        $order = $this->order();
        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('orders.index'))->assertOk();
        $this->get(route('orders.show', $order))->assertOk();
        $this->customer->update(['phone' => '0901234567', 'address' => 'Saved Address']);
        $this->withSession(['cart' => ['variant_' . $this->variant->id => ['variant_id' => $this->variant->id, 'quantity' => 1]]])
            ->get(route('checkout.index'))->assertOk()->assertSee('Saved Address')->assertSee('checkout_token');
        $this->actingAs($this->admin);
        foreach (['admin.dashboard', 'admin.orders.index', 'admin.users.index', 'admin.reports.index', 'admin.products.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('admin.orders.show', $order))->assertOk();
    }
}
