@extends('layouts.app')
@section('title', 'Chi tiết đơn hàng | TDH Phone')
@section('content')
<div class="container commerce-page">
    <div class="commerce-toolbar">
        <div><a href="{{ route('orders.index') }}" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Đơn hàng của tôi</a><h1 class="mt-2">Chi tiết đơn hàng</h1><div class="order-code-text text-muted mt-2">#{{ $order->order_code }} · {{ $order->created_at->format('d/m/Y H:i') }}</div></div>
        <div class="d-flex gap-2 flex-wrap">
            @if($order->uses_momo && !in_array($order->payment_status, ['paid', 'refund_pending']) && $order->status !== 'cancelled')
                <form action="{{ route('orders.momo.pay', $order) }}" method="POST">@csrf<button class="btn btn-danger" type="submit"><i class="bi bi-wallet2 me-1"></i>{{ $order->payment_status === 'failed' ? 'Thanh toán lại' : 'Thanh toán MoMo' }}</button></form>
            @endif
            @if($order->status === 'pending')<form action="{{ route('orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Hủy đơn hàng này?')">@csrf<button class="btn btn-outline-secondary" type="submit"><i class="bi bi-x-circle me-1"></i>Hủy đơn</button></form>@endif
        </div>
    </div>
    @include('partials.alerts')
    @include('partials.order-detail')
</div>
@endsection
