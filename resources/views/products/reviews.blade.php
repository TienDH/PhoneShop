<section class="container commerce-page" id="reviews">
    <div class="commerce-section">
        <div class="commerce-toolbar">
            <h2 class="mb-0">Đánh giá sản phẩm</h2>
            <div class="small"><span class="rating-stars"><i class="bi bi-star-fill"></i></span> {{ $product->reviews_count ? number_format($product->reviews_avg_rating, 1).'/5' : 'Chưa có đánh giá' }} <span class="text-muted">({{ $product->reviews_count }})</span></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-7">
                @forelse($reviews as $review)
                    <article class="review-entry">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap"><strong class="small">{{ $review->user->name ?? 'Khách hàng' }}</strong><time class="small text-muted">{{ $review->updated_at->format('d/m/Y') }}</time></div>
                        <div class="rating-stars mt-2" aria-label="{{ $review->rating }} trên 5 sao">@for($star = 1; $star <= 5; $star++)<i class="bi bi-star{{ $star <= $review->rating ? '-fill' : '' }}"></i>@endfor<span class="text-success small ms-2"><i class="bi bi-patch-check"></i> Đã mua hàng</span></div>
                        <p class="review-comment small mb-0">{{ $review->comment }}</p>
                    </article>
                @empty<div class="text-muted small py-4">Sản phẩm chưa có đánh giá.</div>@endforelse
                <div class="mt-3">{{ $reviews->withQueryString()->fragment('reviews')->links() }}</div>
            </div>
            <div class="col-lg-5">
                @if($purchases->isNotEmpty())
                    @php $purchase = $purchases->firstWhere('id', old('order_item_id')) ?? $purchases->first(); @endphp
                    <h2 class="mb-3">Đánh giá của bạn</h2>
                    <form action="{{ route('reviews.store', $product) }}" method="POST" id="review-form">
                        @csrf
                        <label for="review-purchase" class="form-label">Đơn hàng</label><select name="order_item_id" id="review-purchase" class="form-select mb-3">@foreach($purchases as $item)<option value="{{ $item->id }}" {{ $purchase->id === $item->id ? 'selected' : '' }}>{{ $item->order->order_code }} · {{ $item->storage }} / {{ $item->color }}</option>@endforeach</select>
                        <label for="review-rating" class="form-label">Điểm đánh giá</label><select id="review-rating" name="rating" class="form-select mb-3">@foreach([5 => '5 - Rất hài lòng', 4 => '4 - Hài lòng', 3 => '3 - Bình thường', 2 => '2 - Chưa hài lòng', 1 => '1 - Không hài lòng'] as $value => $label)<option value="{{ $value }}" {{ old('rating', optional($purchase->review)->rating ?? 5) == $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                        <label for="review-comment" class="form-label">Nhận xét</label><textarea name="comment" id="review-comment" class="form-control mb-3" rows="4" maxlength="2000" required>{{ old('comment', optional($purchase->review)->comment) }}</textarea>
                        <button class="btn btn-danger" type="submit"><i class="bi bi-send me-1"></i>Lưu đánh giá</button>
                    </form>
                @elseif(auth()->check())<p class="small text-muted">Bạn chưa có đơn hàng đã giao của sản phẩm này.</p>
                @else<a href="{{ route('login') }}" class="btn btn-outline-secondary"><i class="bi bi-person me-1"></i>Đăng nhập</a>@endif
            </div>
        </div>
    </div>
</section>
@if($purchases->isNotEmpty())
@push('scripts')
<script>
    const purchaseReviews = @json($purchaseReviews);
    document.getElementById('review-purchase').addEventListener('change', function () {
        const review = purchaseReviews[this.value];
        document.getElementById('review-rating').value = review.rating;
        document.getElementById('review-comment').value = review.comment;
    });
</script>
@endpush
@endif
