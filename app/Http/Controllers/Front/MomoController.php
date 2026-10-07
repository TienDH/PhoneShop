<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\GHNOrderService;
use App\Services\MomoService;
use Illuminate\Http\Request;

class MomoController extends Controller
{
    public function start(Request $request, Order $order, MomoService $momo)
    {
        abort_unless($order->user_id == $request->user()->id, 404);
        return redirect()->away($momo->paymentUrl($order));
    }

    public function callback(Request $request, MomoService $momo)
    {
        $transaction = $momo->handleNotification($request->all());
        if ($transaction->status === 'paid') {
            app(GHNOrderService::class)->create($transaction->order);
        }
        return redirect()->route('orders.show', $transaction->order_id)
            ->with($transaction->status === 'paid' ? 'success' : 'error', $transaction->status === 'paid'
                ? 'MoMo đã ghi nhận thanh toán của bạn.' : 'Thanh toán chưa thành công. Bạn có thể thanh toán lại đơn hàng này.');
    }

    public function ipn(Request $request, MomoService $momo)
    {
        $transaction = $momo->handleNotification($request->all());
        if ($transaction->status === 'paid') {
            app(GHNOrderService::class)->create($transaction->order);
        }
        return response()->noContent();
    }
}
