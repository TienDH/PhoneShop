@extends('layouts.admin')
@section('title', 'Báo cáo doanh thu | TDH Phone')
@section('content')
<div class="commerce-page pt-0">
    <div class="commerce-toolbar"><h1>Báo cáo doanh thu</h1><span class="small text-muted">Đơn đã giao và đã thanh toán</span></div>
    <form action="{{ route('admin.reports.index') }}" method="GET" class="row g-3 align-items-end mb-3">
        <div class="col-sm-4 col-lg-3"><label for="report-from" class="form-label">Từ ngày</label><input type="date" id="report-from" name="from" class="form-control" value="{{ $from->toDateString() }}" required></div>
        <div class="col-sm-4 col-lg-3"><label for="report-to" class="form-label">Đến ngày</label><input type="date" id="report-to" name="to" class="form-control" value="{{ $to->toDateString() }}" required></div>
        <div class="col-sm-4"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Xem báo cáo</button></div>
    </form>
    <div class="commerce-metrics">
        <div class="commerce-metric"><span>Doanh thu bán hàng</span><strong>{{ number_format($revenue, 0, ',', '.') }}₫</strong></div>
        <div class="commerce-metric"><span>Đơn hoàn thành</span><strong>{{ number_format($orderCount) }}</strong></div>
        <div class="commerce-metric"><span>Sản phẩm đã bán</span><strong>{{ number_format($units) }}</strong></div>
        <div class="commerce-metric"><span>Phí giao hàng đã thu</span><strong>{{ number_format($shipping, 0, ',', '.') }}₫</strong></div>
    </div>
    <div class="report-columns">
        <section><h2 class="mb-3">Sản phẩm bán chạy</h2><div class="table-responsive"><table class="table management-table"><thead><tr><th>Sản phẩm</th><th class="text-end">Đã bán</th><th class="text-end">Doanh thu</th></tr></thead><tbody>@forelse($bestSellers as $product)<tr><td>{{ $product->name }}</td><td class="text-end">{{ number_format($product->quantity) }}</td><td class="text-end text-nowrap">{{ number_format($product->revenue, 0, ',', '.') }}₫</td></tr>@empty<tr><td colspan="3" class="text-muted py-4 text-center">Chưa có doanh số trong khoảng này.</td></tr>@endforelse</tbody></table></div></section>
        <section><h2 class="mb-3">Doanh thu theo ngày</h2><div class="table-responsive"><table class="table management-table"><thead><tr><th>Ngày</th><th class="text-end">Đơn hàng</th><th class="text-end">Doanh thu</th></tr></thead><tbody>@forelse($days as $day)<tr><td>{{ \Illuminate\Support\Carbon::parse($day->day)->format('d/m/Y') }}</td><td class="text-end">{{ number_format($day->orders) }}</td><td class="text-end text-nowrap">{{ number_format($day->revenue, 0, ',', '.') }}₫</td></tr>@empty<tr><td colspan="3" class="text-muted py-4 text-center">Chưa có doanh thu trong khoảng này.</td></tr>@endforelse</tbody></table></div></section>
    </div>
</div>
@endsection
