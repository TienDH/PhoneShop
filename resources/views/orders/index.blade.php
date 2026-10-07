@extends('layouts.app')
@section('title', 'Đơn hàng của tôi | TDH Phone')
@section('content')
<div class="container commerce-page">
    <div class="commerce-toolbar"><h1>Tài khoản của tôi</h1></div>
    <div class="account-layout">
        @include('account.nav')
        <div class="account-content">
            @include('partials.alerts')
            <div class="commerce-toolbar">
                <h2 class="mb-0">Đơn hàng của tôi</h2>
                <form action="{{ route('orders.index') }}" method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm" aria-label="Trạng thái đơn hàng"><option value="">Tất cả trạng thái</option>@foreach(\App\Models\Order::STATUSES as $value => $label)<option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                    <button class="btn btn-sm btn-outline-secondary" type="submit" title="Lọc đơn hàng" aria-label="Lọc đơn hàng"><i class="bi bi-funnel"></i></button>
                </form>
            </div>
            @forelse($orders as $order)
                <article class="order-summary">
                    <div class="d-flex justify-content-between gap-3 flex-wrap mb-3">
                        <div><a class="fw-bold order-code-text" href="{{ route('orders.show', $order) }}">#{{ $order->order_code }}</a><div class="text-muted small mt-1">{{ $order->created_at->format('d/m/Y H:i') }}</div></div>
                        <div><span class="badge bg-{{ $order->status === 'done' ? 'success' : ($order->status === 'cancelled' ? 'secondary' : 'primary') }}">{{ $order->status_label }}</span><div class="small text-muted mt-1">{{ $order->payment_status_label }}</div></div>
                    </div>
                    @foreach($order->items->take(2) as $item)
                        <div class="d-flex gap-2 justify-content-between small mb-2"><span>{{ $item->product_name }} · {{ $item->storage }} / {{ $item->color }}</span><span class="text-nowrap">×{{ $item->quantity }}</span></div>
                    @endforeach
                    @if($order->items->count() > 2)<div class="text-muted small">+{{ $order->items->count() - 2 }} sản phẩm khác</div>@endif
                    <div class="d-flex align-items-center justify-content-between gap-3 mt-3 flex-wrap"><strong>{{ number_format($order->total, 0, ',', '.') }}₫</strong><a class="btn btn-sm btn-outline-danger" href="{{ route('orders.show', $order) }}"><i class="bi bi-receipt me-1"></i>Chi tiết đơn hàng</a></div>
                </article>
            @empty
                <div class="commerce-empty"><i class="bi bi-bag"></i>Chưa có đơn hàng.<div class="mt-3"><a href="{{ route('products.index') }}" class="btn btn-danger">Xem sản phẩm</a></div></div>
            @endforelse
            <div class="mt-4">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
