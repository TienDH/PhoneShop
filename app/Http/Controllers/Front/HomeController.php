<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->hasAny(['q', 'category', 'storage', 'color', 'min_price', 'max_price', 'sort', 'in_stock'])) {
            return redirect()->route('products.index', $request->query());
        }
        // Top information bar data - static.
        $categories = Category::orderBy('name')->get();

        // Best selling products – using latest active products with at least one variant.
        $products = Product::with(['variants' => function ($q) {
                $q->orderBy('price', 'asc');
            }])
            ->where('status', 'active')
            ->has('variants')
            ->latest()
            ->take(8)
            ->get();

        $bestSellers = Product::with('variants')->where('status', 'active')->has('variants')->withSales()
            ->whereHas('orderItems.order', function ($query) { $query->where('status', 'done')->where('payment_status', 'paid'); })
            ->orderByDesc('sold_count')->limit(5)->get();
        return view('home', compact('categories', 'products', 'bestSellers'));
    }
}
