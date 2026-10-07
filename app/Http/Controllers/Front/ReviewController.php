<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'order_item_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($request, $product, $data) {
            $item = OrderItem::whereKey($data['order_item_id'])->where('product_id', $product->id)
                ->whereHas('order', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)->where('status', 'done')->where('payment_status', 'paid');
                })->lockForUpdate()->firstOrFail();
            Review::updateOrCreate(['order_item_id' => $item->id], [
                'product_id' => $product->id, 'user_id' => $request->user()->id,
                'rating' => $data['rating'], 'comment' => $data['comment'],
            ]);
        });
        return redirect()->route('product.show', $product->slug)->with('success', 'Đã lưu đánh giá của bạn.')->withFragment('reviews');
    }
}
