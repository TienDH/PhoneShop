<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d']]);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay();
        if ($to->lt($from) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['from' => 'Chọn khoảng ngày hợp lệ, tối đa 366 ngày.']);
        }
        $completed = Order::where('status', 'done')->where('payment_status', 'paid')
            ->whereRaw('COALESCE(completed_at, updated_at) BETWEEN ? AND ?', [$from, $to]);
        $revenue = (clone $completed)->sum('subtotal');
        $shipping = (clone $completed)->sum('shipping_fee');
        $orderCount = (clone $completed)->count();
        $days = (clone $completed)->selectRaw('DATE(COALESCE(completed_at, updated_at)) as day, COUNT(*) as orders, SUM(subtotal) as revenue')
            ->groupBy('day')->orderByDesc('day')->get();
        $bestSellers = OrderItem::whereIn('order_id', (clone $completed)->select('id'))
            ->selectRaw('product_id, MAX(product_name) as name, SUM(quantity) as quantity, SUM(subtotal) as revenue')
            ->groupBy('product_id')->orderByDesc('quantity')->limit(10)->get();
        $units = OrderItem::whereIn('order_id', (clone $completed)->select('id'))->sum('quantity');
        return view('admin.reports.index', compact('from', 'to', 'revenue', 'shipping', 'orderCount', 'units', 'days', 'bestSellers'));
    }
}
