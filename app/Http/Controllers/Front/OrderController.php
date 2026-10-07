<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(array_keys(Order::STATUSES))]]);
        $orders = $request->user()->orders()->with('items')->when($data['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->latest('id')->paginate(10)->withQueryString();
        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id == $request->user()->id, 404);
        $order->load(['items.product', 'items.review', 'transactions' => function ($query) { $query->latest('id'); }, 'histories.user']);
        return view('orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order, OrderService $service)
    {
        abort_unless($order->user_id == $request->user()->id, 404);
        $service->changeStatus($order, 'cancelled', $request->user(), 'Khách hàng hủy đơn.');
        return back()->with('success', 'Đã hủy đơn hàng.');
    }
}
