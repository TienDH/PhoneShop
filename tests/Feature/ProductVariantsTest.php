<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVariantsTest extends TestCase
{
    private $product;
    private $otherProduct;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true]);
        Storage::fake('public');

        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Phone A',
            'slug' => 'phone-a',
            'status' => 'active',
        ]);
        $this->otherProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Phone B',
            'slug' => 'phone-b',
            'status' => 'active',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_products_have_variant_buttons_and_no_separate_variant_navigation()
    {
        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('data-variants-url="' . $this->indexUrl() . '"', false)
            ->assertSee('id="product-variants-modal"', false)
            ->assertDontSee('/admin/product-variants', false);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->assertFalse(Route::has('admin.product-variants.index'));
        $this->assertFalse(Route::has('admin.product-variants.create'));
    }

    public function test_list_contains_only_variants_of_the_selected_product()
    {
        $variant = $this->product->variants()->create($this->variantData());
        $this->otherProduct->variants()->create($this->variantData(['sku' => 'OTHER-SKU']));

        $this->getJson($this->indexUrl())
            ->assertOk()
            ->assertJsonCount(1, 'variants')
            ->assertJsonPath('variants.0.id', $variant->id)
            ->assertJsonPath('count', 1)
            ->assertJsonMissing(['sku' => 'OTHER-SKU']);

        $this->product->variants()->delete();
        $this->getJson($this->indexUrl())->assertOk()->assertExactJson(['variants' => [], 'count' => 0]);
    }

    public function test_create_uses_the_selected_product_and_uploads_an_image()
    {
        $response = $this->postJson($this->indexUrl(), $this->variantData([
            'product_id' => $this->otherProduct->id,
            'price' => 0,
            'stock' => 0,
            'image' => $this->image(),
        ]));

        $response->assertCreated()->assertJsonPath('variant.product_id', $this->product->id)->assertJsonPath('count', 1);
        $this->assertDatabaseHas('product_variants', ['product_id' => $this->product->id, 'sku' => 'TDH-128-BLACK']);
        $this->assertSame(0, $this->otherProduct->variants()->count());
        Storage::disk('public')->assertExists($response->json('variant.image'));
        $this->assertNotNull($response->json('variant.image_url'));
    }

    public function test_multipart_update_preserves_the_product_and_existing_image()
    {
        Storage::disk('public')->put('variants/original.png', 'original');
        $variant = $this->product->variants()->create($this->variantData(['image' => 'variants/original.png']));

        $this->post(route('admin.products.variants.update', [$this->product, $variant]), $this->variantData([
            '_method' => 'PUT',
            'product_id' => $this->otherProduct->id,
            'price' => 123456.78,
            'stock' => 7,
        ]), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('variant.product_id', $this->product->id)
            ->assertJsonPath('variant.stock', 7)
            ->assertJsonPath('variant.image', 'variants/original.png');

        $this->assertEquals(123456.78, $variant->fresh()->price);
        Storage::disk('public')->assertExists('variants/original.png');
    }

    public function test_replacing_an_image_removes_the_old_file()
    {
        Storage::disk('public')->put('variants/old.png', 'old');
        $variant = $this->product->variants()->create($this->variantData(['image' => 'variants/old.png']));

        $response = $this->post(route('admin.products.variants.update', [$this->product, $variant]), $this->variantData([
            '_method' => 'PUT',
            'image' => $this->image(),
        ]), ['Accept' => 'application/json']);

        $response->assertOk();
        Storage::disk('public')->assertMissing('variants/old.png');
        Storage::disk('public')->assertExists($response->json('variant.image'));
    }

    public function test_delete_removes_the_variant_and_image_and_updates_the_count()
    {
        Storage::disk('public')->put('variants/delete.png', 'delete');
        $variant = $this->product->variants()->create($this->variantData(['image' => 'variants/delete.png']));
        $other = $this->product->variants()->create($this->variantData(['sku' => 'KEEP', 'storage' => '256GB']));

        $this->deleteJson(route('admin.products.variants.destroy', [$this->product, $variant]))
            ->assertOk()->assertJsonPath('count', 1);

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseHas('product_variants', ['id' => $other->id]);
        Storage::disk('public')->assertMissing('variants/delete.png');
    }

    public function test_storage_color_are_unique_per_product_and_sku_is_unique_globally()
    {
        $this->product->variants()->create($this->variantData());
        $this->postJson($this->indexUrl(), $this->variantData(['sku' => 'NEW-SKU']))
            ->assertUnprocessable()->assertJsonValidationErrors('storage');
        $this->postJson(route('admin.products.variants.store', $this->otherProduct), $this->variantData())
            ->assertUnprocessable()->assertJsonValidationErrors('sku');
        $this->postJson(route('admin.products.variants.store', $this->otherProduct), $this->variantData(['sku' => 'OTHER-SKU']))
            ->assertCreated();

        $this->assertSame(1, $this->product->variants()->count());
        $this->assertSame(1, $this->otherProduct->variants()->count());
    }

    public function test_invalid_input_does_not_create_a_variant()
    {
        $this->postJson($this->indexUrl(), $this->variantData([
            'storage' => '',
            'color' => str_repeat('x', 101),
            'price' => -1,
            'stock' => 1.5,
            'sku' => '',
            'image' => UploadedFile::fake()->createWithContent('not-image.txt', 'text'),
        ]))->assertUnprocessable()->assertJsonValidationErrors(['storage', 'color', 'price', 'stock', 'sku', 'image']);

        $this->assertSame(0, $this->product->variants()->count());
    }

    public function test_variants_cannot_be_updated_or_deleted_through_another_product()
    {
        $variant = $this->product->variants()->create($this->variantData());
        $url = route('admin.products.variants.update', [$this->otherProduct, $variant]);

        $this->putJson($url, $this->variantData(['price' => 1]))->assertNotFound();
        $this->deleteJson($url)->assertNotFound();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'price' => 10000000]);
    }

    public function test_guests_and_non_admins_cannot_manage_variants()
    {
        $variant = $this->product->variants()->create($this->variantData());
        $url = route('admin.products.variants.update', [$this->product, $variant]);
        $this->app['auth']->forgetGuards();

        $this->getJson($this->indexUrl())->assertUnauthorized();
        $this->postJson($this->indexUrl(), $this->variantData())->assertUnauthorized();
        $this->putJson($url, $this->variantData())->assertUnauthorized();
        $this->deleteJson($url)->assertUnauthorized();

        $this->actingAs(User::factory()->create(['role' => 'user']));
        $this->getJson($this->indexUrl())->assertRedirect('/');
        $this->postJson($this->indexUrl(), $this->variantData())->assertRedirect('/');
        $this->putJson($url, $this->variantData())->assertRedirect('/');
        $this->deleteJson($url)->assertRedirect('/');
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'price' => 10000000]);
    }

    private function indexUrl()
    {
        return route('admin.products.variants.index', $this->product);
    }

    private function variantData(array $overrides = [])
    {
        return array_merge([
            'storage' => '128GB',
            'color' => 'Black',
            'price' => 10000000,
            'stock' => 10,
            'sku' => 'TDH-128-BLACK',
        ], $overrides);
    }

    private function image()
    {
        return UploadedFile::fake()->createWithContent('phone.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
    }
}
