@php
    $minPrice = $product->min_price ?? $product->variants->min('price');
    $image = $product->image ?: optional($product->variants->firstWhere('image', '!=', null))->image;
@endphp
<article class="shop-product">
    <a href="{{ route('product.show', $product->slug) }}">
        <div class="shop-product-visual">
            @if($image)<img src="{{ asset('storage/'.$image) }}" alt="{{ $product->name }}" loading="lazy">@else<i class="bi bi-phone" aria-label="Chưa có ảnh"></i>@endif
        </div>
        <h2>{{ $product->name }}</h2>
        <div class="price">{{ $minPrice !== null ? number_format($minPrice, 0, ',', '.').'₫' : 'Liên hệ' }}</div>
    </a>
    <div class="shop-product-meta d-flex justify-content-between gap-2 flex-wrap">
        <span class="{{ $product->variants->sum('stock') > 0 ? 'text-success' : 'text-muted' }}">{{ $product->variants->sum('stock') > 0 ? 'Còn hàng' : 'Hết hàng' }}</span>
        @if($product->sold_count > 0)<span>Đã bán {{ $product->sold_count }}</span>@endif
    </div>
    @if($product->reviews_count > 0)<div class="shop-product-meta"><i class="bi bi-star-fill rating-stars"></i> {{ number_format($product->reviews_avg_rating, 1) }} ({{ $product->reviews_count }})</div>@endif
</article>
