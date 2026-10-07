@php
    $progress = array_keys(\App\Models\Order::STATUSES);
    $currentStep = array_search($order->status, $progress);
@endphp
@if($order->status === 'cancelled')
    <div class="alert alert-secondary">Đơn hàng đã hủy.@if($order->payment_status === 'refund_pending') Khoản thanh toán đang chờ cửa hàng xử lý hoàn tiền.@endif</div>
@else
    <ol class="order-progress" aria-label="Tiến trình đơn hàng">
        @foreach(\App\Models\Order::STATUSES as $value => $label)
            @if($value !== 'cancelled')<li class="{{ $loop->index <= $currentStep ? 'is-complete' : '' }}" @if($order->status === $value) aria-current="step" @endif>{{ $label }}</li>@endif
        @endforeach
    </ol>
@endif
<div class="row g-4">
    <div class="col-lg-8">
        <section class="commerce-section">
            <h2>Sản phẩm</h2>
            @foreach($order->items as $item)
                <div class="order-item">
                    @if($item->image)<img src="{{ asset('storage/'.$item->image) }}" alt="{{ $item->product_name }}">@else<span class="order-image-placeholder"><i class="bi bi-phone"></i></span>@endif
                    <div class="order-item-info">
                        <div class="fw-semibold">{{ $item->product_name }}</div>
                        <div class="text-muted small">{{ $item->storage }} / {{ $item->color }} · {{ $item->sku }}</div>
                        <div class="small mt-1">{{ number_format($item->price, 0, ',', '.') }}₫ × {{ $item->quantity }}</div>
                        @if(!($admin ?? false) && $order->status === 'done' && $order->payment_status === 'paid' && $item->product && $item->product->status === 'active')
                            <a href="{{ route('product.show', $item->product->slug) }}#reviews" class="small text-danger">{{ $item->review ? 'Sửa đánh giá' : 'Đánh giá sản phẩm' }}</a>
                        @endif
                    </div>
                    <strong class="small text-nowrap">{{ number_format($item->subtotal, 0, ',', '.') }}₫</strong>
                </div>
            @endforeach
            <div class="order-total">
                <div class="d-flex justify-content-between small mb-2"><span>Tiền hàng</span><span>{{ number_format($order->subtotal, 0, ',', '.') }}₫</span></div>
                <div class="d-flex justify-content-between small mb-2"><span>Phí vận chuyển</span><span>{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</span></div>
                <div class="d-flex justify-content-between fw-bold mt-3"><span>Tổng thanh toán</span><span class="text-danger">{{ number_format($order->total, 0, ',', '.') }}₫</span></div>
            </div>
        </section>
        <section class="commerce-section">
            <h2 class="mb-3">Lịch sử thanh toán</h2>
            <div class="table-responsive">
                <table class="table management-table align-middle"><thead><tr><th>Thời gian</th><th>Phương thức</th><th class="text-end">Số tiền</th><th>Trạng thái</th></tr></thead><tbody>
                    @forelse($order->transactions as $transaction)
                        <tr><td class="text-nowrap">{{ $transaction->created_at->format('d/m/Y H:i') }}</td><td>{{ strtoupper($transaction->gateway) }}@if($transaction->transaction_id)<div class="text-muted small text-break">{{ $transaction->transaction_id }}</div>@endif</td><td class="text-end text-nowrap">{{ number_format($transaction->amount, 0, ',', '.') }}₫</td><td><span class="badge bg-{{ $transaction->status === 'paid' ? 'success' : ($transaction->status === 'failed' ? 'danger' : 'secondary') }}">{{ $transaction->status_label }}</span>@if(($admin ?? false) && $transaction->result_code !== null)<div class="small text-muted mt-1">Mã: {{ $transaction->result_code }}</div>@endif</td></tr>
                    @empty<tr><td colspan="4" class="text-muted">Chưa có giao dịch thanh toán.</td></tr>@endforelse
                </tbody></table>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="commerce-section">
            <h2 class="mb-3">Giao hàng</h2>
            <div class="fw-semibold">{{ $order->receiver_name }}</div><div class="small mt-1">{{ $order->receiver_phone }}</div>
            <p class="small text-muted mt-2 text-break">
                {{ $order->receiver_address }}
                @if($order->ward_name), {{ $order->ward_name }}@endif
                @if($order->district_name), {{ $order->district_name }}@endif
                @if($order->city), {{ $order->city }}@endif
            </p>
            @if($order->ghn_order_code)<div class="small mb-2">Vận đơn GHN: <strong>{{ $order->ghn_order_code }}</strong></div>@endif
            @if($order->note)<div class="small text-break">Ghi chú: {{ $order->note }}</div>@endif
            <div class="small mt-3">{{ $order->payment_method_label }}</div>
            <div class="fw-semibold small mt-1 {{ $order->payment_status === 'paid' ? 'text-success' : 'text-danger' }}">{{ $order->payment_status_label }}</div>
        </section>
        @if($order->payment_method === 'bank' && !in_array($order->payment_status, ['paid', 'refund_pending']) && $order->status !== 'cancelled')
            <section class="commerce-section"><h2>Chuyển khoản</h2>
                @if(config('services.bank.account'))<div class="small">{{ config('services.bank.name') }}</div><strong class="d-block mt-2 text-break">{{ config('services.bank.account') }}</strong><div class="small mt-1">{{ config('services.bank.holder') }}</div><div class="small mt-2">Nội dung: <strong class="text-break">{{ $order->order_code }}</strong></div>@else<p class="text-muted small">Liên hệ cửa hàng để nhận thông tin chuyển khoản.</p>@endif
            </section>
        @endif
        <section class="commerce-section">
            <h2 class="mb-3">Lịch sử trạng thái</h2>
            <ul class="order-history">
                @forelse($order->histories as $history)<li><div class="fw-semibold small">{{ \App\Models\Order::STATUSES[$history->status] ?? $history->status }}</div><time>{{ $history->created_at->format('d/m/Y H:i') }}</time>@if($history->note)<div class="text-muted small mt-1 text-break">{{ $history->note }}</div>@endif</li>@empty<li><div class="small">{{ $order->status_label }}</div><time>{{ $order->updated_at->format('d/m/Y H:i') }}</time></li>@endforelse
            </ul>
        </section>
    </div>
</div>
