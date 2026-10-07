@extends('layouts.app')

@section('content')

<style>
    .product-detail-img {
        width: 100%;
        border-radius: 12px;
        object-fit: contain;
        max-height: 420px;
        background: #f8f8f8;
        padding: 16px;
        border: 1px solid #eee;
        transition: all .3s;
    }
    .thumbnail-list {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
    }
    .thumbnail-list img {
        width: 72px;
        height: 72px;
        object-fit: contain;
        border: 2px solid #eee;
        border-radius: 8px;
        cursor: pointer;
        padding: 4px;
        background: #f8f8f8;
        transition: border-color .2s;
    }
    .thumbnail-list img:hover,
    .thumbnail-list img.active {
        border-color: #dc3545;
    }
    .variant-btn {
        display: inline-block;
        padding: 6px 16px;
        border: 2px solid #ddd;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        margin: 4px;
        transition: all .2s;
        background: #fff;
        user-select: none;
    }
    .variant-btn:hover {
        border-color: #dc3545;
        color: #dc3545;
    }
    .variant-btn.selected {
        border-color: #dc3545;
        background: #dc3545;
        color: #fff;
    }
    .variant-btn.disabled-variant {
        opacity: .4;
        cursor: not-allowed;
        text-decoration: line-through;
    }
    .price-display {
        font-size: 2rem;
        font-weight: 700;
        color: #dc3545;
    }
    .qty-control {
        display: flex;
        align-items: center;
        gap: 0;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        width: fit-content;
    }
    .qty-control button {
        width: 40px;
        height: 40px;
        border: none;
        background: #f5f5f5;
        font-size: 1.2rem;
        cursor: pointer;
        transition: background .2s;
    }
    .qty-control button:hover { background: #e0e0e0; }
    .qty-control input {
        width: 60px;
        height: 40px;
        border: none;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
        text-align: center;
        font-size: 1rem;
        outline: none;
    }
    .breadcrumb-item a { color: #dc3545; text-decoration: none; }
    .breadcrumb-item a:hover { text-decoration: underline; }
    .section-title {
        border-left: 4px solid #dc3545;
        padding-left: 12px;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 16px;
    }
    .spec-table td { padding: 8px 12px; }
    .spec-table tr:nth-child(even) { background: #fafafa; }
</style>

{{-- BREADCRUMB --}}
<div class="container mt-3">
    @include('partials.alerts')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('front.home') }}">Trang chủ</a></li>
            @if($product->category)
                <li class="breadcrumb-item"><a href="{{ route('products.index', ['category' => $product->category_id]) }}">{{ $product->category->name }}</a></li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>
</div>

{{-- PRODUCT DETAIL --}}
<section class="container my-3">
    <div class="row g-4">

        {{-- ẢNH SẢN PHẨM --}}
        <div class="col-12 col-md-5">
            <img id="mainProductImage"
                 src="{{ $product->image ? asset('storage/'.$product->image) : (optional($product->variants->first())->image ? asset('storage/'.$product->variants->first()->image) : 'https://via.placeholder.com/420?text=No+Image') }}"
                 class="product-detail-img"
                 alt="{{ $product->name }}">

            {{-- Thumbnails biến thể --}}
            @if($product->variants->where('image', '!=', null)->count() > 0)
            <div class="thumbnail-list">
                @if($product->image)
                    <img src="{{ asset('storage/'.$product->image) }}"
                         alt="{{ $product->name }}"
                         class="active"
                         onclick="setMainImage(this, '{{ asset('storage/'.$product->image) }}')">
                @endif
                @foreach($product->variants->whereNotNull('image')->unique('image') as $v)
                    <img src="{{ asset('storage/'.$v->image) }}"
                         alt="{{ $v->storage }} {{ $v->color }}"
                         onclick="setMainImage(this, '{{ asset('storage/'.$v->image) }}')">
                @endforeach
            </div>
            @endif
        </div>

        {{-- THÔNG TIN SẢN PHẨM --}}
        <div class="col-12 col-md-7">
            <h1 class="h3 fw-bold mb-1">{{ $product->name }}</h1>
            <p class="text-muted mb-3">
                Danh mục: <strong>{{ $product->category->name ?? 'Chưa phân loại' }}</strong>
            </p>

            {{-- GIÁ --}}
            <div class="mb-3">
                <span class="price-display" id="displayPrice">
                    @php $minPrice = $product->variants->min('price'); @endphp
                    @if($minPrice)
                        {{ number_format($minPrice, 0, ',', '.') }}₫
                    @else
                        Liên hệ
                    @endif
                </span>
            </div>

            @if($product->variants->isNotEmpty())

                {{-- CHỌN DUNG LƯỢNG --}}
                @php $storages = $product->variants->pluck('storage')->filter()->unique()->values(); @endphp
                @if($storages->count() > 0)
                <div class="mb-3">
                    <div class="section-title">Dung lượng</div>
                    <div id="storageOptions">
                        @foreach($storages as $storage)
                            <span class="variant-btn {{ $loop->first ? 'selected' : '' }}"
                                  data-type="storage"
                                  data-value="{{ $storage }}"
                                  onclick="selectVariantBtn(this, 'storage')">
                                {{ $storage }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- CHỌN MÀU SẮC --}}
                @php $colors = $product->variants->pluck('color')->filter()->unique()->values(); @endphp
                @if($colors->count() > 0)
                <div class="mb-3">
                    <div class="section-title">Màu sắc</div>
                    <div id="colorOptions">
                        @foreach($colors as $color)
                            <span class="variant-btn {{ $loop->first ? 'selected' : '' }}"
                                  data-type="color"
                                  data-value="{{ $color }}"
                                  onclick="selectVariantBtn(this, 'color')">
                                {{ $color }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- SKU & TỒN KHO --}}
                <div class="mb-3 d-flex gap-3 align-items-center flex-wrap">
                    <span id="displayStock" class="badge bg-success fs-6 px-3 py-2">Còn hàng</span>
                    <span id="displaySKU" class="text-muted small"></span>
                </div>

            @else
                <p class="text-muted">Chưa có biến thể sản phẩm.</p>
            @endif

            {{-- SỐ LƯỢNG --}}
            <div class="mb-4">
                <div class="section-title">Số lượng</div>
                <div class="qty-control">
                    <button type="button" id="qtyMinus" onclick="changeQty(-1)">−</button>
                    <input type="number" id="qtyInput" value="1" min="1" max="99">
                    <button type="button" id="qtyPlus" onclick="changeQty(1)">+</button>
                </div>
            </div>

            {{-- BUTTON --}}
            {{-- Form thêm vào giỏ hàng --}}
            <form id="formAddToCart" method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="variant_id" id="hiddenVariantId" value="">
                <input type="hidden" name="quantity"   id="hiddenQty"       value="1">
                <div class="d-flex gap-3 flex-wrap">
                    <button type="submit" class="btn btn-danger px-5 py-2 fw-bold" id="btnAddToCart" style="font-size:1.05rem;" disabled>
                        <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ hàng
                    </button>
                    <button type="button" class="btn btn-outline-danger px-4 py-2 fw-bold" style="font-size:1.05rem;" id="btnBuyNow" disabled>
                        <i class="bi bi-bolt me-1"></i>Mua ngay
                    </button>
                    <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary px-4 py-2">
                        <i class="bi bi-cart3 me-1"></i>Xem giỏ hàng
                        @if(count(session('cart', [])) > 0)
                            <span class="badge bg-danger ms-1">{{ array_sum(array_column(session('cart', []), 'quantity')) }}</span>
                        @endif
                    </a>
                </div>
            </form>

            {{-- CHÍNH SÁCH --}}
            <div class="row g-2 mt-4">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-shield-check text-danger fs-5"></i> Bảo hành chính hãng 12 tháng
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-truck text-danger fs-5"></i> Giao hàng toàn quốc
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-arrow-return-left text-danger fs-5"></i> Đổi trả trong 30 ngày
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-headset text-danger fs-5"></i> Hỗ trợ 24/7
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- MÔ TẢ SẢN PHẨM --}}
@if($product->description)
<section class="container my-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="section-title fs-5">Mô tả sản phẩm</h2>
            <p class="text-muted" style="line-height: 1.8;">{{ $product->description }}</p>
        </div>
    </div>
</section>
@endif
@include('products.reviews')
@endsection

@push('scripts')
<script>
(function() {
    const variants = @json($product->variants);
    let selectedVariantId = null;

    // Lấy giá trị đang chọn
    function getSelected(type) {
        const btn = document.querySelector(`.variant-btn.selected[data-type="${type}"]`);
        return btn ? btn.dataset.value : '';
    }

    // Tìm biến thể khớp
    function findVariant() {
        const storage = getSelected('storage');
        const color   = getSelected('color');
        return variants.find(v => {
            const matchStorage = !storage || v.storage === storage;
            const matchColor   = !color   || v.color === color;
            return matchStorage && matchColor;
        }) || null;
    }

    // Đồng bộ giá trị vào hidden inputs của form
    function syncForm(variantId, qty) {
        const hiddenId  = document.getElementById('hiddenVariantId');
        const hiddenQty = document.getElementById('hiddenQty');
        if (hiddenId)  hiddenId.value  = variantId || '';
        if (hiddenQty) hiddenQty.value = qty || 1;
    }

    // Cập nhật giá, tồn kho, SKU, ảnh, form
    function updateDisplay() {
        const v       = findVariant();
        const priceEl = document.getElementById('displayPrice');
        const stockEl = document.getElementById('displayStock');
        const skuEl   = document.getElementById('displaySKU');
        const qtyInput = document.getElementById('qtyInput');
        const btnCart  = document.getElementById('btnAddToCart');
        const btnBuy   = document.getElementById('btnBuyNow');
        if (!stockEl) {
            syncForm(null, 1);
            return;
        }

        if (v) {
            selectedVariantId = v.id;
            const price = parseFloat(v.price) || 0;
            priceEl.textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
            const stock = v.stock ?? 0;

            if (stock > 0) {
                stockEl.textContent = 'Còn hàng: ' + stock;
                stockEl.className   = 'badge bg-success fs-6 px-3 py-2';
                qtyInput.max        = stock;
                qtyInput.value      = Math.min(parseInt(qtyInput.value) || 1, stock);
                if (btnCart) btnCart.disabled = false;
                if (btnBuy)  btnBuy.disabled  = false;
            } else {
                stockEl.textContent = 'Hết hàng';
                stockEl.className   = 'badge bg-danger fs-6 px-3 py-2';
                qtyInput.max        = 0;
                if (btnCart) btnCart.disabled = true;
                if (btnBuy)  btnBuy.disabled  = true;
            }
            skuEl.textContent = v.sku ? 'SKU: ' + v.sku : '';

            if (v.image) {
                document.getElementById('mainProductImage').src = '/storage/' + v.image;
            }

            syncForm(v.id, parseInt(qtyInput.value) || 1);
        } else {
            selectedVariantId = null;
            priceEl.textContent = 'Liên hệ';
            stockEl.textContent = 'Không có sẵn';
            stockEl.className   = 'badge bg-secondary fs-6 px-3 py-2';
            skuEl.textContent   = '';
            if (btnCart) btnCart.disabled = true;
            if (btnBuy)  btnBuy.disabled  = true;
            syncForm(null, 1);
        }
    }

    // Chọn biến thể button
    window.selectVariantBtn = function(el, type) {
        document.querySelectorAll(`.variant-btn[data-type="${type}"]`)
            .forEach(b => b.classList.remove('selected'));
        el.classList.add('selected');
        updateDisplay();
    };

    // Đổi số lượng
    window.changeQty = function(delta) {
        const input = document.getElementById('qtyInput');
        let val = parseInt(input.value) || 1;
        const max = parseInt(input.max) || 99;
        val = Math.max(1, Math.min(val + delta, max));
        input.value = val;
        syncForm(selectedVariantId, val);
    };

    // Khi người dùng gõ số lượng thủ công
    const qtyInput = document.getElementById('qtyInput');
    if (qtyInput) {
        qtyInput.addEventListener('change', function() {
            syncForm(selectedVariantId, parseInt(this.value) || 1);
        });
    }

    // Đổi ảnh chính
    window.setMainImage = function(thumbEl, src) {
        document.getElementById('mainProductImage').src = src;
        document.querySelectorAll('.thumbnail-list img').forEach(img => img.classList.remove('active'));
        thumbEl.classList.add('active');
    };

    // Nút Mua ngay — thêm vào giỏ rồi chuyển sang trang giỏ hàng
    const btnBuyNow = document.getElementById('btnBuyNow');
    if (btnBuyNow) {
        btnBuyNow.addEventListener('click', function() {
            const form = document.getElementById('formAddToCart');
            if (!form) return;
            // Thêm hidden input để redirect sang cart sau khi add
            let redirectInput = form.querySelector('[name="redirect_to_cart"]');
            if (!redirectInput) {
                redirectInput = document.createElement('input');
                redirectInput.type  = 'hidden';
                redirectInput.name  = 'redirect_to_cart';
                form.appendChild(redirectInput);
            }
            redirectInput.value = '1';
            form.submit();
        });
    }

    // Khởi tạo
    updateDisplay();
})();
</script>
@endpush
