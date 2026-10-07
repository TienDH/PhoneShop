<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard.index', [
            'totalCategories' => Category::count(),
            'totalProducts' => Product::count(),
            'totalVariants' => ProductVariant::count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
            'revenue' => Order::where('status', 'done')->where('payment_status', 'paid')->sum('subtotal'),
            'recentOrders' => Order::latest('id')->limit(5)->get(),
        ]);
    }
}
