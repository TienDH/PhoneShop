<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\MomoService;
use App\Services\OrderService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(Request $request, CartService $cartService)
    {
        $request->validate(['selected' => ['nullable', 'array'], 'selected.*' => ['string', 'regex:/^variant_[0-9]+$/']]);
        $cart = $cartService->refresh(session('cart', []));
        session()->put('cart', $cart);
        $selectedKeys = array_values(array_intersect($request->input('selected', array_keys($cart)), array_keys($cart)));
        $selectedItems = array_intersect_key($cart, array_flip($selectedKeys));
        if (!$selectedItems) {
            return redirect()->route('cart.index')->with('error', 'Vui lòng chọn sản phẩm còn bán để thanh toán.');
        }
        $subtotal = array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $selectedItems));
        $user = $request->user();
        $ghnConfigured = app(GHNService::class)->isConfigured();
        $momoConfigured = app(MomoService::class)->isConfigured();
        $bankConfigured = (bool) config('services.bank.account');
        $initialShippingFee = $subtotal >= config('shop.shipping_free_from') ? 0 : ($ghnConfigured ? null : (int) config('shop.shipping_flat_fee'));
        $checkoutToken = session('checkout_token');
        if (!$checkoutToken) {
            $checkoutToken = (string) Str::uuid();
            session()->put('checkout_token', $checkoutToken);
        }
        return view('checkout.index', compact('selectedItems', 'subtotal', 'selectedKeys', 'user', 'checkoutToken', 'ghnConfigured', 'momoConfigured', 'bankConfigured', 'initialShippingFee'));
    }

    public function store(Request $request, CartService $cartService, OrderService $orders, ShippingService $shipping, MomoService $momo)
    {
        $methods = ['cod'];
        if ($momo->isConfigured()) $methods = array_merge($methods, ['momo', 'momo_atm', 'momo_card']);
        if (config('services.bank.account')) $methods[] = 'bank';
        $requiresShippingAddress = app(GHNService::class)->isConfigured();
        $data = $request->validate([
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'regex:/^0[0-9]{9}$/'],
            'receiver_address' => ['required', 'string', 'max:500'],
            'city' => [Rule::requiredIf($requiresShippingAddress), 'nullable', 'string', 'max:150'],
            'district_name' => [Rule::requiredIf($requiresShippingAddress), 'nullable', 'string', 'max:150'],
            'ward_name' => [Rule::requiredIf($requiresShippingAddress), 'nullable', 'string', 'max:150'],
            'ghn_district_id' => [Rule::requiredIf($requiresShippingAddress), 'nullable', 'integer', 'min:1'],
            'ghn_ward_code' => [Rule::requiredIf($requiresShippingAddress), 'nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in($methods)],
            'selected_keys' => ['required', 'string', 'max:5000'],
            'checkout_token' => ['required', 'uuid'],
        ], [
            'receiver_phone.regex' => 'Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.',
            'payment_method.in' => 'Phương thức thanh toán này chưa sẵn sàng.',
        ]);
        $existing = Order::where('checkout_token', $data['checkout_token'])->where('user_id', $request->user()->id)->first();
        if ($existing) return redirect()->route('orders.show', $existing);
        if (!hash_equals((string) session('checkout_token'), $data['checkout_token'])) {
            throw ValidationException::withMessages(['cart' => 'Phiên đặt hàng đã thay đổi. Vui lòng mở lại trang thanh toán.']);
        }

        $cart = $cartService->refresh(session('cart', []));
        $keys = array_values(array_unique(array_filter(explode(',', $data['selected_keys']))));
        $items = array_intersect_key($cart, array_flip($keys));
        if (!$items || count($keys) !== count($items)) {
            return redirect()->route('cart.index')->with('error', 'Một số sản phẩm đã ngừng bán. Vui lòng kiểm tra lại giỏ hàng.');
        }
        $subtotal = array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $items));
        $fee = $shipping->quote($items, $subtotal, $data['ghn_district_id'] ?? null, $data['ghn_ward_code'] ?? null);
        if (str_starts_with($data['payment_method'], 'momo') && ($subtotal + $fee < 1000 || $subtotal + $fee > 50000000)) {
            throw ValidationException::withMessages(['payment_method' => 'MoMo hỗ trợ giao dịch từ 1.000 đến 50.000.000 đồng.']);
        }
        unset($data['selected_keys']);
        try {
            $order = $orders->place($request->user(), $items, $data, $fee);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'Chưa thể tạo đơn hàng. Vui lòng thử lại.');
        }
        foreach ($keys as $key) unset($cart[$key]);
        session()->put('cart', $cart);
        session()->forget('checkout_token');

        if ($order->uses_momo) {
            try {
                return redirect()->away($momo->paymentUrl($order));
            } catch (ValidationException $exception) {
                return redirect()->route('orders.show', $order)->withErrors($exception->errors());
            }
        }
        if ($order->payment_method === 'cod') app(GHNOrderService::class)->create($order);
        return redirect()->route('checkout.success', $order->order_code);
    }

    public function success(Request $request, string $orderCode)
    {
        $order = Order::where('order_code', $orderCode)->where('user_id', $request->user()->id)->firstOrFail();
        return redirect()->route('orders.show', $order)->with('success', 'Đặt hàng thành công. TDH Phone đã nhận đơn hàng của bạn.');
    }
}
