@extends('layouts.admin')
@section('title', 'Chi tiết đơn hàng | TDH Phone')
@section('content')
<div class="commerce-page pt-0">
    <div class="commerce-toolbar">
        <div><a href="{{ route('admin.orders.index') }}" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Đơn hàng</a><h1 class="mt-2">Chi tiết đơn hàng</h1><div class="order-code-text text-muted mt-2">#{{ $order->order_code }}</div></div>
        <div class="d-flex gap-2 flex-wrap">
            @if($order->payment_method === 'bank' && $order->payment_status !== 'paid' && $order->status !== 'cancelled')<form action="{{ route('admin.orders.payment', $order) }}" method="POST" onsubmit="return confirm('Xác nhận đã nhận đủ tiền chuyển khoản?')">@csrf<button class="btn btn-outline-success" type="submit"><i class="bi bi-cash-coin me-1"></i>Đã nhận chuyển khoản</button></form>@endif
            @if(!$order->ghn_order_code && !in_array($order->status, ['cancelled', 'done']) && app(\App\Services\GHNService::class)->isConfigured())<form action="{{ route('admin.orders.shipment', $order) }}" method="POST">@csrf<button class="btn btn-outline-secondary" type="submit"><i class="bi bi-truck me-1"></i>Tạo vận đơn GHN</button></form>@endif
        </div>
    </div>
    @if($order->next_statuses)
        <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="row g-2 align-items-end mb-4">
            @csrf @method('PUT')
            <div class="col-md-4"><label for="order-status" class="form-label">Cập nhật trạng thái</label><select id="order-status" name="status" class="form-select" required>@foreach($order->next_statuses as $status)<option value="{{ $status }}">{{ \App\Models\Order::STATUSES[$status] }}</option>@endforeach</select></div>
            <div class="col-md-5"><label for="order-note" class="form-label">Ghi chú xử lý</label><input id="order-note" name="note" class="form-control" maxlength="500" value="{{ old('note') }}"></div>
            <div class="col-md-3"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Cập nhật</button></div>
        </form>
    @endif
    @include('partials.order-detail', ['admin' => true])
</div>
@endsection
