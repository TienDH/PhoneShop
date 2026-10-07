@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">TDH Phone Admin Dashboard</h1>
        <p class="text-muted mb-0">Name: {{ Auth::user()->name }} | Role: Admin</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Categories</div>
                <div class="display-6">{{ $totalCategories }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Products</div>
                <div class="display-6">{{ $totalProducts }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Product Variants</div>
                <div class="display-6">{{ $totalVariants }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5">Quick Links</h2>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Add Category</a>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Add Product</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary">Đơn hàng</a>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">Báo cáo doanh thu</a>
    </div>
</div>
<div class="commerce-page pt-4">
    <div class="commerce-metrics" style="grid-template-columns: repeat(2, minmax(0, 1fr))">
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="commerce-metric text-decoration-none text-dark"><span>Đơn chờ xác nhận</span><strong>{{ $pendingOrders }}</strong></a>
        <a href="{{ route('admin.reports.index') }}" class="commerce-metric text-decoration-none text-dark"><span>Doanh thu đơn đã giao</span><strong>{{ number_format($revenue, 0, ',', '.') }}₫</strong></a>
    </div>
    <h2 class="mb-3">Đơn hàng gần đây</h2>
    <div class="table-responsive"><table class="table management-table"><thead><tr><th>Đơn hàng</th><th>Khách hàng</th><th>Trạng thái</th><th class="text-end">Tổng tiền</th></tr></thead><tbody>@forelse($recentOrders as $order)<tr><td><a class="order-code-text" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_code }}</a></td><td>{{ $order->receiver_name }}</td><td>{{ $order->status_label }}</td><td class="text-end text-nowrap">{{ number_format($order->total, 0, ',', '.') }}₫</td></tr>@empty<tr><td colspan="4" class="text-muted text-center py-4">Chưa có đơn hàng.</td></tr>@endforelse</tbody></table></div>
</div>
@endsection
