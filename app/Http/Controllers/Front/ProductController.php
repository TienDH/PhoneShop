<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'storage' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['new', 'price_asc', 'price_desc', 'best_selling'])],
        ]);
        $variantFilter = function ($query) use ($filters) {
            if (!empty($filters['storage'])) $query->where('storage', $filters['storage']);
            if (!empty($filters['color'])) $query->where('color', $filters['color']);
            if (isset($filters['min_price'])) $query->where('price', '>=', $filters['min_price']);
            if (isset($filters['max_price'])) $query->where('price', '<=', $filters['max_price']);
            if (!empty($filters['in_stock'])) $query->where('stock', '>', 0);
        };
        $query = Product::where('status', 'active')->with(['category', 'variants' => $variantFilter])
            ->whereHas('variants', $variantFilter)->withSales()
            ->withMin(['variants as min_price' => $variantFilter], 'price')->withCount('reviews')->withAvg('reviews', 'rating');
        if (!empty($filters['category'])) $query->where('category_id', $filters['category']);
        if (!empty($filters['q'])) {
            $keyword = '%' . $filters['q'] . '%';
            $query->where(function ($query) use ($keyword) {
                $query->where('name', 'like', $keyword)->orWhere('description', 'like', $keyword)
                    ->orWhereHas('variants', function ($query) use ($keyword) {
                        $query->where('sku', 'like', $keyword)->orWhere('storage', 'like', $keyword)->orWhere('color', 'like', $keyword);
                    });
            });
        }
        $sort = $filters['sort'] ?? 'new';
        if ($sort === 'price_asc') $query->orderBy('min_price');
        elseif ($sort === 'price_desc') $query->orderByDesc('min_price');
        elseif ($sort === 'best_selling') $query->orderByDesc('sold_count');
        $products = $query->latest('id')->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $available = ProductVariant::whereHas('product', function ($query) { $query->where('status', 'active'); });
        $storages = (clone $available)->select('storage')->distinct()->orderBy('storage')->pluck('storage');
        $colors = (clone $available)->select('color')->distinct()->orderBy('color')->pluck('color');
        return view('products.index', compact('products', 'categories', 'storages', 'colors', 'sort'));
    }
    /**
     * Show the product detail page.
     */
    public function show(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')->withCount('reviews')->withAvg('reviews', 'rating')
            ->with(['category', 'variants' => function ($q) {
                $q->orderBy('price', 'asc');
            }])
            ->firstOrFail();

        $reviews = $product->reviews()->with('user')->latest('id')->paginate(10, ['*'], 'reviews_page');
        $purchases = collect();
        if ($request->user()) {
            $purchases = OrderItem::with(['order', 'review'])->where('product_id', $product->id)
                ->whereHas('order', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)->where('status', 'done')->where('payment_status', 'paid');
                })->latest('id')->get();
        }
        $purchaseReviews = $purchases->mapWithKeys(function ($item) {
            return [$item->id => $item->review ? $item->review->only('rating', 'comment') : ['rating' => 5, 'comment' => '']];
        });
        return view('products.show', compact('product', 'reviews', 'purchases', 'purchaseReviews'));
    }
}
