<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function index(Product $product)
    {
        $variants = $product->variants()->latest('id')->get();

        return response()->json([
            'variants' => $variants->map(function ($variant) {
                return $this->variantData($variant);
            }),
            'count' => $variants->count(),
        ]);
    }

    public function store(Request $request, Product $product)
    {
        $data = $this->validatedData($request, $product);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('variants', 'public');
        }

        $variant = $product->variants()->create($data);

        return response()->json([
            'message' => 'Đã thêm variant thành công.',
            'variant' => $this->variantData($variant),
            'count' => $product->variants()->count(),
        ], 201);
    }

    public function update(Request $request, Product $product, ProductVariant $variant)
    {
        $data = $this->validatedData($request, $product, $variant);

        if ($request->hasFile('image')) {
            $this->deleteImage($variant->image);
            $data['image'] = $request->file('image')->store('variants', 'public');
        }

        $variant->update($data);

        return response()->json([
            'message' => 'Đã cập nhật variant thành công.',
            'variant' => $this->variantData($variant->fresh()),
            'count' => $product->variants()->count(),
        ]);
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        $this->deleteImage($variant->image);
        $variant->delete();

        return response()->json([
            'message' => 'Đã xóa variant thành công.',
            'count' => $product->variants()->count(),
        ]);
    }

    private function validatedData(Request $request, Product $product, ProductVariant $variant = null)
    {
        return $request->validate([
            'storage' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants')
                    ->where('product_id', $product->id)
                    ->where('color', $request->input('color'))
                    ->ignore(optional($variant)->id),
            ],
            'color' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants')->ignore(optional($variant)->id),
            ],
            'image' => ['nullable', 'image', 'max:2048'],
        ], [
            'storage.unique' => 'Sản phẩm đã có variant cùng dung lượng và màu sắc.',
            'sku.unique' => 'Mã SKU này đã được sử dụng.',
        ], [
            'storage' => 'dung lượng',
            'color' => 'màu sắc',
            'price' => 'giá',
            'stock' => 'tồn kho',
            'sku' => 'SKU',
            'image' => 'ảnh',
        ]);
    }

    private function variantData(ProductVariant $variant)
    {
        return array_merge($variant->toArray(), [
            'image_url' => $variant->image ? asset('storage/' . $variant->image) : null,
            'url' => route('admin.products.variants.update', [$variant->product_id, $variant]),
        ]);
    }

    private function deleteImage($path)
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
