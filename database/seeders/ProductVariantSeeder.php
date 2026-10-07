<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductVariantSeeder extends Seeder
{
    public function run()
    {
        $products = DB::table('products')
            ->pluck('id', 'slug');

        DB::table('product_variants')->insert([
            [
                'product_id' => $products['iphone-15-pro-max'],
                'storage' => '256GB',
                'color' => 'Titan Tự Nhiên',
                'price' => 29990000,
                'stock' => 10,
                'sku' => 'IP15PM-256-NATURAL',
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $products['iphone-15-pro-max'],
                'storage' => '512GB',
                'color' => 'Titan Đen',
                'price' => 34990000,
                'stock' => 5,
                'sku' => 'IP15PM-512-BLACK',
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $products['iphone-15'],
                'storage' => '128GB',
                'color' => 'Đen',
                'price' => 18990000,
                'stock' => 15,
                'sku' => 'IP15-128-BLACK',
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $products['samsung-galaxy-s24-ultra'],
                'storage' => '256GB',
                'color' => 'Đen Titan',
                'price' => 27990000,
                'stock' => 8,
                'sku' => 'S24U-256-BLACK',
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $products['xiaomi-14'],
                'storage' => '256GB',
                'color' => 'Đen',
                'price' => 19990000,
                'stock' => 12,
                'sku' => 'XM14-256-BLACK',
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $products['oppo-reno-12'],
                'storage' => '256GB',
                'color' => 'Xám',
                'price' => 12990000,
                'stock' => 20,
                'sku' => 'OPR12-256-GRAY',
                'image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
