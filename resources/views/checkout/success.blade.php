@extends('layouts.app')

@section('content')

<style>
    body { background: #f5f5f5; }
    .success-card {
        max-width: 760px; margin: 40px auto;
        background: #fff; border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,.09);
        overflow: hidden;
    }
    .success-header {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: #fff; text-align: center;
        padding: 40px 24px;
    }
    .success-icon {
        width: 80px; height: 80px;
        background: rgba(255,255,255,.25);
        border-radius: 50%; margin: 0 auto 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.5rem;
        animation: popIn .5s cubic-bezier(.175,.885,.32,1.275) both;
    }
    @keyframes popIn {
        from { transform: scale(0); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
    }
    .order-code {
        display: inline-block;
        background: rgba(255,255,255,.2);
        border-radius: 8px;
        padding: 6px 20px;
        font-size: 1.1rem;
        font-weight: 700;
        letter-spacing: 1px;
        margin-top: 8px;
    }
    .info-body { padding: 28px 32px; }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    @media(max-width:540px) { .info-grid { grid-template-columns: 1fr; } }
    .info-item {
        background: #f8f9fa; border-radius: 10px;
        padding: 14px 18px;
    }
    .info-label { font-size: 0.78rem; color: #888; text-transform: uppercase; letter-spacing: .5px; }
    .info-value { font-weight: 600; font-size: 0.95rem; color: #222; margin-top: 4px; }
    .section-title { font-weight: 700; font-size: 1rem; border-left: 4px solid #dc3545; padding-left: 10px; margin-bottom: 16px; }
    .item-row { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .item-row:last-child { border-bottom: none; }
    .item-thumb { width: 56px; height: 56px; object-fit: contain; border-radius: 8px; border: 1px solid #eee; background: #f8f8f8; padding: 4px; }
    .item-name { font-weight: 600; font-size: 0.9rem; }
    .item-meta { font-size: 0.78rem; color: #888; }
    .price-red { color: #dc3545; font-weight: 700; }
    .total-box {
        background: #fff5f5; border-radius: 10px;
        padding: 16px 20px; margin-top: 16px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .pay-method-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: #f0f7ff; border-radius: 8px;
        padding: 8px 14px; font-size: 0.88rem; font-weight: 600;
    }
    .timeline { list-style: none; padding: 0; margin: 0; }
    .timeline li {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 10px 0; border-left: 2px solid #eee;
        padding-left: 20px; margin-left: 10px;
        position: relative;
    }
    .timeline li::before {
        content: '';
        position: absolute; left: -6px; top: 16px;
        width: 10px; height: 10px; border-radius: 50%;
        background: #ddd; border: 2px solid #fff;
    }
    .timeline li.active::before { background: #dc3545; }
    .timeline li.done::before   { background: #28a745; }
</style>

<div class="success-card">

    {{-- HEADER --}}
    <div class="success-header">
        <div class="success-icon">✓</div>
        <h2 class="fw-bold mb-1">Đặt hàng thành công!</h2>
        <p class="mb-2" style="opacity:.9;">Cảm ơn bạn đã mua sắm tại TDH Phone 🎉</p>
        <div class="order-code"># {{ $order->order_code }}</div>
    </div>

    <div class="info-body">

        {{-- THÔNG TIN ĐƠN HÀNG --}}
        <div class="info-grid mb-4">
            <div class="info-item">
                <div class="info-label">Người nhận</div>
                <div class="info-value">{{ $order->receiver_name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Số điện thoại</div>
                <div class="info-value">{{ $order->receiver_phone }}</div>
            </div>
            <div class="info-item" style="grid-column: span 2;">
                <div class="info-label">Địa chỉ giao hàng</div>
                <div class="info-value">
                    {{ $order->receiver_address }}
                    @if($order->city), {{ $order->city }}@endif
                </div>
            </div>
            @if($order->note)
            <div class="info-item" style="grid-column: span 2;">
                <div class="info-label">Ghi chú</div>
                <div class="info-value">{{ $order->note }}</div>
            </div>
            @endif
        </div>

        {{-- PHƯƠNG THỨC THANH TOÁN --}}
        <div class="mb-4">
            <div class="section-title">Phương thức thanh toán</div>
            <div class="pay-method-badge">
                @if($order->payment_method === 'cod')   🚚
                @elseif($order->payment_method === 'momo') 💜
                @elseif($order->payment_method === 'bank') 🏦
                @endif
                {{ $order->payment_method_label }}
            </div>
            @if($order->payment_method !== 'cod')
                <div class="mt-2 p-3 rounded" style="background:#fff9e6; border:1px solid #ffe08a;">
                    <i class="bi bi-info-circle text-warning me-2"></i>
                    <strong>Demo:</strong> Hệ thống sẽ xác nhận đơn hàng sau khi nhận được thanh toán.
                </div>
            @endif
        </div>

        {{-- SẢN PHẨM ĐÃ ĐẶT --}}
        <div class="mb-4">
            <div class="section-title">Sản phẩm đã đặt</div>
            @foreach($order->items as $item)
            <div class="item-row">
                <img src="{{ $item->image ? asset('storage/'.$item->image) : 'https://via.placeholder.com/56?text=No+Img' }}"
                     class="item-thumb" alt="{{ $item->product_name }}">
                <div class="flex-grow-1">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="item-meta">
                        @if($item->storage) {{ $item->storage }} @endif
                        @if($item->color) · {{ $item->color }} @endif
                        @if($item->sku) · SKU: {{ $item->sku }} @endif
                    </div>
                </div>
                <div class="text-end">
                    <div class="price-red">{{ number_format($item->subtotal, 0, ',', '.') }}₫</div>
                    <div class="text-muted small">x{{ $item->quantity }}</div>
                </div>
            </div>
            @endforeach

            <div class="total-box">
                <span class="fw-bold fs-5">Tổng cộng</span>
                <span class="price-red fs-4 fw-bold">{{ number_format($order->total, 0, ',', '.') }}₫</span>
            </div>
        </div>

        {{-- TRẠNG THÁI ĐƠN HÀNG --}}
        <div class="mb-4">
            <div class="section-title">Trạng thái đơn hàng</div>
            <ul class="timeline">
                <li class="done">
                    <div>
                        <strong>Đã đặt hàng</strong>
                        <div class="text-muted small">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </li>
                <li class="active">
                    <div>
                        <strong>Chờ xác nhận</strong>
                        <div class="text-muted small">TDH Phone đang xử lý đơn hàng của bạn</div>
                    </div>
                </li>
                <li>
                    <div class="text-muted">
                        <strong>Đang giao hàng</strong>
                        <div class="small">Dự kiến 1–3 ngày làm việc</div>
                    </div>
                </li>
                <li>
                    <div class="text-muted">
                        <strong>Giao thành công</strong>
                    </div>
                </li>
            </ul>
        </div>

        {{-- ACTIONS --}}
        <div class="d-flex gap-3 flex-wrap">
            <a href="{{ route('front.home') }}" class="btn btn-danger px-4 py-2 fw-bold">
                <i class="bi bi-house me-2"></i>Về trang chủ
            </a>
            <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary px-4 py-2">
                <i class="bi bi-cart3 me-2"></i>Giỏ hàng
            </a>
        </div>

        <div class="mt-3 text-muted small">
            <i class="bi bi-envelope me-1"></i>Xác nhận đơn hàng sẽ được gửi đến email của bạn.
            Mọi thắc mắc liên hệ <strong>1800 1234</strong>.
        </div>

    </div>
</div>

@endsection
