<?php

namespace App\Services;

use App\Models\ProductVariant;

class CartService
{
    public function refresh(array $cart): array
    {
        $variants = ProductVariant::with('product')->whereIn('id', array_column($cart, 'variant_id'))->get()->keyBy('id');
        $items = [];
        foreach ($cart as $item) {
            $variant = $variants->get($item['variant_id'] ?? null);
            if (!$variant || !$variant->product || $variant->product->status !== 'active') {
                continue;
            }
            $items['variant_' . $variant->id] = $this->snapshot($variant, max(1, (int) ($item['quantity'] ?? 1)));
        }
        return $items;
    }

    public function snapshot(ProductVariant $variant, int $quantity): array
    {
        return [
            'variant_id' => $variant->id,
            'product_id' => $variant->product->id,
            'product_name' => $variant->product->name,
            'product_slug' => $variant->product->slug,
            'product_image' => $variant->product->image,
            'variant_image' => $variant->image,
            'storage' => $variant->storage,
            'color' => $variant->color,
            'sku' => $variant->sku,
            'price' => (int) round($variant->price),
            'stock' => $variant->stock,
            'quantity' => $quantity,
        ];
    }
}
