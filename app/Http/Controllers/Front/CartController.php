<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Lấy giỏ hàng từ session
    private function getCart(): array
    {
        return session()->get('cart', []);
    }

    // Lưu giỏ hàng vào session
    private function saveCart(array $cart): void
    {
        session()->put('cart', $cart);
    }

    // Hiển thị trang giỏ hàng
    public function index()
    {
        $cart = app(CartService::class)->refresh($this->getCart());
        $this->saveCart($cart);
        return view('cart.index', compact('cart'));
    }

    // Thêm sản phẩm vào giỏ
    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1|max:1000',
        ]);

        $variantId = $request->input('variant_id');
        $qty       = (int) $request->input('quantity', 1);

        $variant = ProductVariant::with('product')->findOrFail($variantId);
        abort_unless($variant->product && $variant->product->status === 'active', 404);

        // Kiểm tra tồn kho
        if ($variant->stock < $qty) {
            return back()->with('error', 'Số lượng vượt quá tồn kho!');
        }

        $cart = app(CartService::class)->refresh($this->getCart());
        $key  = 'variant_' . $variantId;

        if (isset($cart[$key])) {
            $newQty = $cart[$key]['quantity'] + $qty;
            if ($newQty > $variant->stock) {
                $newQty = $variant->stock;
            }
            $cart[$key] = app(CartService::class)->snapshot($variant, $newQty);
        } else {
            $cart[$key] = [
                'variant_id'   => $variant->id,
                'product_id'   => $variant->product->id,
                'product_name' => $variant->product->name,
                'product_slug' => $variant->product->slug,
                'product_image'=> $variant->product->image,
                'variant_image'=> $variant->image,
                'storage'      => $variant->storage,
                'color'        => $variant->color,
                'sku'          => $variant->sku,
                'price'        => $variant->price,
                'stock'        => $variant->stock,
                'quantity'     => $qty,
            ];
        }

        $this->saveCart($cart);

        return redirect()->route('cart.index')
                         ->with('success', 'Đã thêm "' . $variant->product->name . '" vào giỏ hàng!');
    }

    // Cập nhật số lượng
    public function update(Request $request)
    {
        $request->validate([
            'key'      => 'required|string',
            'quantity' => 'required|integer|min:1|max:1000',
        ]);

        $cart = $this->getCart();
        $key  = $request->input('key');
        $qty  = (int) $request->input('quantity');

        if (isset($cart[$key])) {
            $variant = ProductVariant::with('product')->find($cart[$key]['variant_id']);
            if (!$variant || !$variant->product || $variant->product->status !== 'active' || $qty > $variant->stock) {
                return response()->json(['success' => false, 'message' => 'Sản phẩm đã hết hàng hoặc số lượng vượt quá tồn kho.', 'quantity' => $cart[$key]['quantity']], 422);
            }
            $cart[$key] = app(CartService::class)->snapshot($variant, $qty);
            $this->saveCart($cart);
        }

        return response()->json([
            'success' => isset($cart[$key]), 'quantity' => $cart[$key]['quantity'] ?? 0,
            'price' => $cart[$key]['price'] ?? null, 'stock' => $cart[$key]['stock'] ?? 0,
        ]);
    }

    // Xóa 1 sản phẩm
    public function remove(Request $request)
    {
        $key  = $request->input('key');
        $cart = $this->getCart();

        unset($cart[$key]);
        $this->saveCart($cart);

        return redirect()->route('cart.index')->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    // Xóa toàn bộ giỏ
    public function clear()
    {
        session()->forget('cart');
        return redirect()->route('cart.index')->with('success', 'Đã xóa toàn bộ giỏ hàng.');
    }
}
