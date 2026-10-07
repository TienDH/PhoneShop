@extends('layouts.admin')
@section('title', 'Quản lý đơn hàng | TDH Phone')
@section('content')
<div class="commerce-page pt-0">
    <div class="commerce-toolbar"><h1>Quản lý đơn hàng</h1><span class="text-muted small">{{ $orders->total() }} đơn hàng</span></div>
    <form action="{{ route('admin.orders.index') }}" method="GET" class="row g-2 mb-4">
        <div class="col-lg-4"><input name="q" class="form-control" value="{{ request('q') }}" placeholder="Mã đơn, tên hoặc số điện thoại" aria-label="Tìm đơn hàng" maxlength="100"></div>
        <div class="col-lg-3"><select name="status" class="form-select" aria-label="Trạng thái đơn hàng"><option value="">Tất cả trạng thái</option>@foreach(\App\Models\Order::STATUSES as $value => $label)<option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-lg-3"><select name="payment_status" class="form-select" aria-label="Trạng thái thanh toán"><option value="">Tất cả thanh toán</option>@foreach(['pending' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'refund_pending' => 'Chờ hoàn tiền'] as $value => $label)<option value="{{ $value }}" {{ request('payment_status') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-lg-2"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Tìm kiếm</button></div>
    </form>
    <div class="table-responsive">
        <table class="table management-table align-middle"><thead><tr><th>Đơn hàng</th><th>Khách hàng</th><th>Trạng thái</th><th>Thanh toán</th><th class="text-end">Tổng tiền</th><th></th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr><td><a class="fw-semibold order-code-text" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_code }}</a><div class="text-muted small">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->items_count }} sản phẩm</div></td><td>{{ $order->receiver_name }}<div class="text-muted small">{{ $order->receiver_phone }}</div></td><td><span class="badge bg-{{ $order->status === 'done' ? 'success' : ($order->status === 'cancelled' ? 'secondary' : 'primary') }}">{{ $order->status_label }}</span></td><td>{{ $order->payment_status_label }}<div class="text-muted small">{{ $order->payment_method_label }}</div></td><td class="text-end fw-semibold text-nowrap">{{ number_format($order->total, 0, ',', '.') }}₫</td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.orders.show', $order) }}" title="Chi tiết đơn hàng" aria-label="Chi tiết đơn hàng"><i class="bi bi-arrow-right"></i></a></td></tr>
            @empty<tr><td colspan="6" class="text-muted text-center py-4">Không có đơn hàng phù hợp.</td></tr>@endforelse
        </tbody></table>
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
