@extends('layouts.app')

@section('content')

<style>
    body { background: #f5f5f5; }
    .cart-wrapper { max-width: 1100px; margin: 0 auto; }
    .cart-table-wrap {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        overflow-x: auto;
    }
    .cart-table th {
        background: #fafafa;
        border-bottom: 2px solid #eee;
        font-weight: 600;
        font-size: 0.9rem;
        color: #555;
        padding: 14px 16px;
    }
    .cart-table td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
    .cart-table tr:last-child td { border-bottom: none; }
    .product-thumb {
        width: 72px; height: 72px;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid #eee;
        background: #f8f8f8;
        padding: 4px;
    }
    .product-name { font-weight: 600; font-size: 0.95rem; color: #222; }
    .product-variant { font-size: 0.82rem; color: #888; }
    .qty-wrap {
        display: flex; align-items: center; gap: 0;
        border: 1px solid #ddd; border-radius: 8px;
        overflow: hidden; width: fit-content;
    }
    .qty-wrap button {
        width: 34px; height: 34px;
        border: none; background: #f5f5f5;
        font-size: 1rem; cursor: pointer;
        transition: background .2s;
    }
    .qty-wrap button:hover { background: #e0e0e0; }
    .qty-wrap input {
        width: 48px; height: 34px;
        border: none;
        border-left: 1px solid #ddd; border-right: 1px solid #ddd;
        text-align: center; font-size: 0.9rem; outline: none;
    }
    .price-col { color: #dc3545; font-weight: 700; font-size: 1rem; }
    .remove-btn { color: #aaa; background: none; border: none; font-size: 1.2rem; cursor: pointer; transition: color .2s; }
    .remove-btn:hover { color: #dc3545; }
    .summary-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        padding: 24px;
        position: sticky; top: 80px;
    }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; }
    .summary-total { font-size: 1.3rem; font-weight: 700; color: #dc3545; }
    .btn-checkout {
        background: #dc3545; color: #fff;
        border: none; border-radius: 8px;
        padding: 14px;
        font-size: 1rem; font-weight: 600;
        width: 100%; cursor: pointer;
        transition: background .2s, transform .1s;
    }
    .btn-checkout:hover { background: #b02a37; transform: translateY(-1px); }
    .btn-checkout:disabled { background: #aaa; cursor: not-allowed; transform: none; }
    .empty-cart { text-align: center; padding: 60px 20px; }
    .empty-cart i { font-size: 5rem; color: #ddd; }
    .select-all-row { background: #fff9f9; }
    .check-item, .check-all { width: 18px; height: 18px; accent-color: #dc3545; cursor: pointer; }
    .badge-variant { background: #f0f0f0; color: #555; border-radius: 6px; padding: 2px 8px; font-size: 0.78rem; }
</style>

<div class="container py-4 cart-wrapper">

    {{-- THÔNG BÁO --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <h2 class="fw-bold mb-4"><i class="bi bi-cart3 me-2 text-danger"></i>Giỏ hàng của bạn</h2>
    <div id="cartError" class="alert alert-danger" role="alert" hidden></div>

    @if(empty($cart))
        {{-- GIỎ HÀNG TRỐNG --}}
        <div class="cart-table-wrap empty-cart">
            <i class="bi bi-cart-x"></i>
            <h4 class="mt-3 fw-bold">Giỏ hàng trống</h4>
            <p class="text-muted">Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
            <a href="{{ route('front.home') }}" class="btn btn-danger px-5 mt-2">
                <i class="bi bi-arrow-left me-2"></i>Tiếp tục mua sắm
            </a>
        </div>
    @else
        <div class="row g-4">

            {{-- BẢNG GIỎ HÀNG --}}
            <div class="col-12 col-lg-8">
                <div class="cart-table-wrap">

                    {{-- HEADER BẢNG --}}
                    <table class="table cart-table mb-0" id="cartTable">
                        <thead>
                            <tr>
                                <th style="width:44px;">
                                    <input type="checkbox" id="checkAll" class="check-all" title="Chọn tất cả">
                                </th>
                                <th>Sản phẩm</th>
                                <th style="width:130px;">Đơn giá</th>
                                <th style="width:140px;">Số lượng</th>
                                <th style="width:130px;">Thành tiền</th>
                                <th style="width:44px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart as $key => $item)
                            <tr class="cart-row" data-key="{{ $key }}" data-price="{{ $item['price'] }}">
                                <td>
                                    <input type="checkbox" class="check-item" data-key="{{ $key }}"
                                           data-price="{{ $item['price'] }}" data-qty="{{ $item['quantity'] }}" checked>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <a href="{{ route('product.show', $item['product_slug']) }}">
                                            @php
                                                $thumb = $item['variant_image'] ?? $item['product_image'];
                                            @endphp
                                            <img src="{{ $thumb ? asset('storage/'.$thumb) : 'https://via.placeholder.com/72?text=No+Image' }}"
                                                 class="product-thumb" alt="{{ $item['product_name'] }}">
                                        </a>
                                        <div>
                                            <a href="{{ route('product.show', $item['product_slug']) }}" class="product-name text-decoration-none">
                                                {{ $item['product_name'] }}
                                            </a>
                                            <div class="mt-1">
                                                @if($item['storage'])
                                                    <span class="badge-variant">{{ $item['storage'] }}</span>
                                                @endif
                                                @if($item['color'])
                                                    <span class="badge-variant">{{ $item['color'] }}</span>
                                                @endif
                                            </div>
                                            @if($item['sku'])
                                                <div class="product-variant mt-1">SKU: {{ $item['sku'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="price-col unit-price">
                                    {{ number_format($item['price'], 0, ',', '.') }}₫
                                </td>
                                <td>
                                    <div class="qty-wrap">
                                        <button type="button" class="qty-minus" data-key="{{ $key }}">−</button>
                                        <input type="number"
                                               class="qty-input"
                                               data-key="{{ $key }}"
                                               value="{{ $item['quantity'] }}"
                                               min="1"
                                               max="{{ $item['stock'] }}">
                                        <button type="button" class="qty-plus" data-key="{{ $key }}">+</button>
                                    </div>
                                    <div class="text-muted" style="font-size:0.75rem; margin-top:4px;">
                                        Còn: {{ $item['stock'] }}
                                    </div>
                                </td>
                                <td class="price-col subtotal-cell"
                                    data-key="{{ $key }}">
                                    {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}₫
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('cart.remove') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $key }}">
                                        <button class="remove-btn" title="Xóa" onclick="return confirm('Xóa sản phẩm này?')">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- FOOTER BẢNG --}}
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="d-flex gap-2">
                        <a href="{{ route('front.home') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Tiếp tục mua sắm
                        </a>
                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Xóa toàn bộ giỏ hàng?')">
                                <i class="bi bi-trash3 me-1"></i>Xóa tất cả
                            </button>
                        </form>
                    </div>
                    <div class="text-muted small">
                        <span id="selectedCount">{{ count($cart) }}</span> sản phẩm được chọn
                    </div>
                </div>
            </div>

            {{-- TÓM TẮT ĐƠN HÀNG --}}
            <div class="col-12 col-lg-4">
                <div class="summary-card">
                    <h5 class="fw-bold mb-4">Tóm tắt đơn hàng</h5>

                    <div class="summary-row">
                        <span class="text-muted">Tạm tính (<span id="summaryQty">0</span> sản phẩm)</span>
                        <span id="summarySubtotal" class="fw-semibold">0₫</span>
                    </div>
                    <div class="summary-row">
                        <span class="text-muted">Phí vận chuyển</span>
                        <span class="text-muted small">Tính khi đặt hàng</span>
                    </div>
                    <hr>
                    <div class="summary-row">
                        <span class="fw-bold fs-5">Tổng cộng</span>
                        <span class="summary-total" id="summaryTotal">0₫</span>
                    </div>

                    <form method="GET" action="{{ route('checkout.index') }}" id="checkoutForm">
                        <input type="hidden" name="selected" id="selectedKeysInput" value="">
                        <button type="submit" class="btn-checkout mt-3" id="btnCheckout" disabled>
                            <i class="bi bi-credit-card me-2"></i>Tiến hành thanh toán
                        </button>
                    </form>

                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-shield-check text-success me-1"></i>Thanh toán an toàn & bảo mật
                        </small>
                    </div>

                    <div class="mt-3 border-top pt-3">
                        <div class="d-flex gap-2 flex-wrap justify-content-center">
                            <small class="text-muted"><i class="bi bi-truck text-danger me-1"></i>Giao hàng toàn quốc</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
(function() {
    let pendingUpdates = 0;
    if (!document.getElementById('cartTable')) return;

    // ============================
    // CẬP NHẬT SỐ LƯỢNG QUA AJAX
    // ============================
    async function updateQtyAjax(key, qty) {
        const row = document.querySelector(`tr[data-key="${key}"]`);
        const input = row.querySelector('.qty-input');
        const cb = row.querySelector('.check-item');
        const previousQty = cb.dataset.qty;
        const feedback = document.getElementById('cartError');
        feedback.hidden = true;
        pendingUpdates++;
        row.querySelectorAll('.qty-wrap button, .qty-input').forEach(el => { el.disabled = true; });
        updateSummary();
        try {
            const response = await fetch('{{ route("cart.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ key, quantity: qty })
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Không thể cập nhật giỏ hàng. Vui lòng tải lại trang.');
            input.value = data.quantity;
            input.max = data.stock;
            row.dataset.price = data.price;
            cb.dataset.price = data.price;
            row.querySelector('.unit-price').textContent = formatVND(data.price);
        } catch (error) {
            input.value = previousQty;
            feedback.textContent = error.message;
            feedback.hidden = false;
        } finally {
            pendingUpdates--;
            row.querySelectorAll('.qty-wrap button, .qty-input').forEach(el => { el.disabled = false; });
            updateSubtotal(key);
        }
    }

    function updateSubtotal(key) {
        const row = document.querySelector(`tr[data-key="${key}"]`);
        if (!row) return;
        const price = parseFloat(row.dataset.price);
        const input = row.querySelector('.qty-input');
        const qty   = parseInt(input.value) || 1;
        const cell  = row.querySelector('.subtotal-cell');
        cell.textContent = formatVND(price * qty);

        // Cập nhật data-qty trên checkbox
        const cb = row.querySelector('.check-item');
        if (cb) { cb.dataset.qty = qty; }

        updateSummary();
    }

    // ============================
    // NÚT + / -
    // ============================
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const key = this.dataset.key;
            const input = document.querySelector(`.qty-input[data-key="${key}"]`);
            let val = parseInt(input.value) || 1;
            if (val > 1) {
                val--;
                input.value = val;
                updateQtyAjax(key, val);
            }
        });
    });

    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const key = this.dataset.key;
            const input = document.querySelector(`.qty-input[data-key="${key}"]`);
            const max = parseInt(input.max) || 99;
            let val = parseInt(input.value) || 1;
            if (val < max) {
                val++;
                input.value = val;
                updateQtyAjax(key, val);
            }
        });
    });

    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const key = this.dataset.key;
            const max = parseInt(this.max) || 99;
            let val = parseInt(this.value) || 1;
            val = Math.max(1, Math.min(val, max));
            this.value = val;
            updateQtyAjax(key, val);
        });
    });

    // ============================
    // CHECKBOX CHỌN SẢN PHẨM
    // ============================
    const checkAll = document.getElementById('checkAll');
    const checkItems = document.querySelectorAll('.check-item');

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkItems.forEach(cb => { cb.checked = this.checked; });
            updateSummary();
        });
    }

    checkItems.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = [...checkItems].every(c => c.checked);
            const noneChecked = [...checkItems].every(c => !c.checked);
            if (checkAll) {
                checkAll.checked = allChecked;
                checkAll.indeterminate = !allChecked && !noneChecked;
            }
            updateSummary();
        });
    });

    // ============================
    // TÍNH TỔNG
    // ============================
    function updateSummary() {
        let total = 0;
        let qty   = 0;
        let count = 0;

        checkItems.forEach(cb => {
            if (cb.checked) {
                const price = parseFloat(cb.dataset.price) || 0;
                const q     = parseInt(cb.dataset.qty)    || 1;
                total += price * q;
                qty   += q;
                count++;
            }
        });

        document.getElementById('summarySubtotal').textContent = formatVND(total);
        document.getElementById('summaryTotal').textContent    = formatVND(total);
        document.getElementById('summaryQty').textContent      = qty;
        document.getElementById('selectedCount').textContent   = count;

        const btnCheckout = document.getElementById('btnCheckout');
        if (btnCheckout) {
            btnCheckout.disabled = (count === 0 || pendingUpdates > 0);
        }
    }

    function formatVND(amount) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
    }

    // ============================
    // NÚT THANH TOÁN
    // ============================
    const btnCheckout = document.getElementById('btnCheckout');
    const checkoutForm = document.getElementById('checkoutForm');
    const selectedKeysInput = document.getElementById('selectedKeysInput');

    if (btnCheckout && checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            if (pendingUpdates > 0) { e.preventDefault(); return; }
            const selected = [...checkItems].filter(c => c.checked).map(c => c.dataset.key);
            if (selected.length === 0) {
                e.preventDefault();
                alert('Vui lòng chọn ít nhất 1 sản phẩm!');
                return;
            }
            // Điền các key được chọn: dùng multiple hidden inputs với name="selected[]"
            // Xóa input cũ nếu có
            checkoutForm.querySelectorAll('input[name="selected[]"]').forEach(el => el.remove());
            selected.forEach(key => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'selected[]';
                inp.value = key;
                checkoutForm.appendChild(inp);
            });
        });
    }

    // Khởi tạo
    updateSummary();
})();
</script>
@endpush
