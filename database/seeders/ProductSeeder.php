<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $iphone = DB::table('categories')
            ->where('slug', 'iphone')
            ->first();

        $samsung = DB::table('categories')
            ->where('slug', 'samsung')
            ->first();

        $xiaomi = DB::table('categories')
            ->where('slug', 'xiaomi')
            ->first();

        $oppo = DB::table('categories')
            ->where('slug', 'oppo')
            ->first();

        DB::table('products')->insert([
            [
                'category_id' => $iphone->id,
                'name' => 'iPhone 15 Pro Max',
                'slug' => 'iphone-15-pro-max',
                'description' => 'iPhone 15 Pro Max chính hãng.',
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $iphone->id,
                'name' => 'iPhone 15',
                'slug' => 'iphone-15',
                'description' => 'iPhone 15 chính hãng.',
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $samsung->id,
                'name' => 'Samsung Galaxy S24 Ultra',
                'slug' => 'samsung-galaxy-s24-ultra',
                'description' => 'Samsung Galaxy S24 Ultra chính hãng.',
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $xiaomi->id,
                'name' => 'Xiaomi 14',
                'slug' => 'xiaomi-14',
                'description' => 'Xiaomi 14 chính hãng.',
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $oppo->id,
                'name' => 'OPPO Reno 12',
                'slug' => 'oppo-reno-12',
                'description' => 'OPPO Reno 12 chính hãng.',
                'image' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
