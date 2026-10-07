<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\GHNOrderService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))],
            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'failed', 'refund_pending'])],
        ]);
        $orders = Order::with('user')->withCount('items')
            ->when($data['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('order_code', 'like', '%' . $keyword . '%')
                        ->orWhere('receiver_name', 'like', '%' . $keyword . '%')
                        ->orWhere('receiver_phone', 'like', '%' . $keyword . '%');
                });
            })->when($data['status'] ?? null, function ($query, $status) { $query->where('status', $status); })
            ->when($data['payment_status'] ?? null, function ($query, $status) { $query->where('payment_status', $status); })
            ->latest('id')->paginate(15)->withQueryString();
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'transactions' => function ($query) { $query->latest('id'); }, 'histories.user']);
        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order, OrderService $service)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Order::STATUSES))], 'note' => ['nullable', 'string', 'max:500']]);
        $service->changeStatus($order, $data['status'], $request->user(), $data['note'] ?? null);
        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    public function payment(Order $order, OrderService $service)
    {
        $service->confirmBankPayment($order);
        app(GHNOrderService::class)->create($order->fresh());
        return back()->with('success', 'Đã xác nhận nhận được chuyển khoản.');
    }

    public function shipment(Order $order, GHNOrderService $service)
    {
        return back()->with($service->create($order) ? 'success' : 'error', $order->fresh()->ghn_order_code
            ? 'Đã có vận đơn GHN.' : 'Chưa thể tạo vận đơn. Vui lòng kiểm tra địa chỉ và kết nối GHN.');
    }
}
