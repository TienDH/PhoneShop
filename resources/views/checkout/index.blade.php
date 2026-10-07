@extends('layouts.app')

@section('content')

<style>
    body { background: #f5f5f5; }
    .checkout-wrap { max-width: 1100px; margin: 0 auto; }

    /* Stepper */
    .stepper { display: flex; align-items: center; justify-content: center; gap: 0; margin: 24px 0 32px; }
    .step { display: flex; align-items: center; gap: 8px; font-size: 0.88rem; }
    .step-num {
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.8rem;
        background: #ddd; color: #888;
    }
    .step.active .step-num { background: #dc3545; color: #fff; }
    .step.done   .step-num { background: #28a745; color: #fff; }
    .step-label { color: #888; }
    .step.active .step-label { color: #dc3545; font-weight: 600; }
    .step-line { width: 60px; height: 2px; background: #ddd; margin: 0 4px; }
    .step-line.done { background: #28a745; }

    /* Cards */
    .card-section {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        padding: 24px;
        margin-bottom: 20px;
    }
    .section-head {
        font-weight: 700; font-size: 1rem;
        border-left: 4px solid #dc3545;
        padding-left: 12px;
        margin-bottom: 20px;
        color: #222;
    }

    /* Payment methods */
    .pay-option { display: none; }
    .pay-label {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 18px;
        border: 2px solid #eee;
        border-radius: 10px;
        cursor: pointer;
        transition: border-color .2s, background .2s;
        margin-bottom: 10px;
    }
    .pay-label:hover { border-color: #dc3545; background: #fff9f9; }
    .pay-option:checked + .pay-label { border-color: #dc3545; background: #fff0f0; }
    .pay-icon { font-size: 1.6rem; width: 40px; text-align: center; }
    .pay-title { font-weight: 600; font-size: 0.95rem; }
    .pay-desc  { font-size: 0.8rem; color: #888; }
    .pay-badge { margin-left: auto; font-size: 0.72rem; }

    /* Payment info boxes */
    .pay-info-box {
        border-radius: 10px;
        padding: 16px 20px;
        margin-top: 12px;
        display: none;
        animation: fadeIn .3s ease;
    }
    .pay-info-box.show { display: block; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

    /* Order summary */
    .summary-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        padding: 24px;
        position: sticky; top: 80px;
    }
    .item-thumb {
        width: 56px; height: 56px;
        object-fit: contain; background: #f8f8f8;
        border-radius: 8px; border: 1px solid #eee;
        padding: 4px;
    }
    .item-name { font-size: 0.88rem; font-weight: 600; }
    .item-variant { font-size: 0.76rem; color: #888; }
    .price-red { color: #dc3545; font-weight: 700; }
    .total-line { font-size: 1.2rem; font-weight: 700; color: #dc3545; }
    .btn-place-order {
        background: #dc3545; color: #fff; border: none;
        border-radius: 10px; padding: 15px;
        font-size: 1rem; font-weight: 700;
        width: 100%; cursor: pointer;
        transition: background .2s, transform .1s;
        letter-spacing: .3px;
    }
    .btn-place-order:hover { background: #b02a37; transform: translateY(-1px); }
    .form-control:focus, .form-select:focus {
        border-color: #dc3545; box-shadow: 0 0 0 .2rem rgba(220,53,69,.15);
    }
</style>

<div class="container py-4 checkout-wrap">

    {{-- STEPPER --}}
    <div class="stepper">
        <div class="step done">
            <div class="step-num"><i class="bi bi-check"></i></div>
            <span class="step-label">Giỏ hàng</span>
        </div>
        <div class="step-line done"></div>
        <div class="step active">
            <div class="step-num">2</div>
            <span class="step-label">Thanh toán</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-num">3</div>
            <span class="step-label">Hoàn thành</span>
        </div>
    </div>

    {{-- THÔNG BÁO LỖI --}}
    @if(session('error'))
        <div class="alert alert-danger mb-3"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" id="checkoutForm">
        @csrf
        {{-- Truyền danh sách key sản phẩm được chọn --}}
        <input type="hidden" name="selected_keys" value="{{ implode(',', $selectedKeys) }}">
        <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

        <div class="row g-4">

            {{-- ===== CỘT TRÁI ===== --}}
            <div class="col-12 col-lg-7">

                {{-- 1. THÔNG TIN NGƯỜI NHẬN --}}
                <div class="card-section">
                    <div class="section-head"><i class="bi bi-person-fill me-2 text-danger"></i>Thông tin người nhận</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="receiver_name" class="form-control @error('receiver_name') is-invalid @enderror"
                                   placeholder="Nguyễn Văn A"
                                   value="{{ old('receiver_name', $user->name ?? '') }}" required>
                            @error('receiver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" name="receiver_phone" class="form-control @error('receiver_phone') is-invalid @enderror"
                                   placeholder="0901 234 567"
                                   value="{{ old('receiver_phone', $user->phone) }}" pattern="0[0-9]{9}" required>
                            @error('receiver_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @if($ghnConfigured)
                        {{-- GHN: Tỉnh/Thành phố --}}
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold" for="ghn_province">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                            <select id="ghn_province" class="form-select" required>
                                <option value="">-- Chọn tỉnh/thành --</option>
                            </select>
                            <input type="hidden" name="city" id="city_name">
                            <input type="hidden" name="ghn_province_id" id="ghn_province_id">
                        </div>

                        {{-- GHN: Quận/Huyện --}}
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold" for="ghn_district">Quận / Huyện <span class="text-danger">*</span></label>
                            <select id="ghn_district" class="form-select" disabled required>
                                <option value="">-- Chọn quận/huyện --</option>
                            </select>
                            <input type="hidden" name="district_name" id="district_name">
                            <input type="hidden" name="ghn_district_id" id="ghn_district_id">
                        </div>

                        {{-- GHN: Phường/Xã --}}
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold" for="ghn_ward">Phường / Xã <span class="text-danger">*</span></label>
                            <select id="ghn_ward" class="form-select" disabled required>
                                <option value="">-- Chọn phường/xã --</option>
                            </select>
                            <input type="hidden" name="ward_name" id="ward_name">
                            <input type="hidden" name="ghn_ward_code" id="ghn_ward_code">
                        </div>

                        {{-- Hidden: phí vận chuyển --}}
                        <input type="hidden" id="hidden_shipping_fee" value="{{ $initialShippingFee ?? 0 }}">
                        @else
                        <div class="col-12 col-sm-6"><label for="checkout-city" class="form-label fw-semibold">Tỉnh / Thành phố</label><input name="city" id="checkout-city" class="form-control" maxlength="150" value="{{ old('city') }}" required></div>
                        @endif
                        <div class="col-12">
                            <label class="form-label fw-semibold">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                            <input type="text" name="receiver_address" class="form-control @error('receiver_address') is-invalid @enderror"
                                   placeholder="Số nhà, tên đường, phường/xã, quận/huyện..."
                                   value="{{ old('receiver_address', $user->address) }}" required>
                            @error('receiver_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Ghi chú đơn hàng</label>
                            <textarea name="note" class="form-control" rows="2"
                                      placeholder="Ghi chú về đơn hàng (không bắt buộc)...">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- 2. PHƯƠNG THỨC THANH TOÁN --}}
                <div class="card-section">
                    <div class="section-head"><i class="bi bi-credit-card-fill me-2 text-danger"></i>Phương thức thanh toán</div>

                    {{-- COD --}}
                    <input type="radio" name="payment_method" id="pay_cod" value="cod" class="pay-option" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}>
                    <label for="pay_cod" class="pay-label">
                        <span class="pay-icon"><i class="bi bi-truck"></i></span>
                        <div>
                            <div class="pay-title">Thanh toán khi nhận hàng (COD)</div>
                            <div class="pay-desc">Kiểm tra hàng, thanh toán tiền mặt khi nhận</div>
                        </div>
                        <span class="pay-badge badge bg-success">Phổ biến</span>
                    </label>

                    {{-- MOMO --}}
                    @if($momoConfigured)
                    <input type="radio" name="payment_method" id="pay_momo" value="momo" class="pay-option" {{ old('payment_method') === 'momo' ? 'checked' : '' }}>
                    <label for="pay_momo" class="pay-label">
                        <span class="pay-icon" style="color:#ae2070;">
                            <i class="bi bi-wallet2"></i>
                        </span>
                        <div>
                            <div class="pay-title">Ví MoMo</div>
                            <div class="pay-desc">Thanh toán qua ví MoMo</div>
                        </div>
                    </label>
                    <input type="radio" name="payment_method" id="pay_momo_atm" value="momo_atm" class="pay-option" {{ old('payment_method') === 'momo_atm' ? 'checked' : '' }}>
                    <label for="pay_momo_atm" class="pay-label"><span class="pay-icon"><i class="bi bi-bank"></i></span><div><div class="pay-title">Thẻ ATM nội địa</div><div class="pay-desc">Cổng thanh toán MoMo</div></div></label>
                    <input type="radio" name="payment_method" id="pay_momo_card" value="momo_card" class="pay-option" {{ old('payment_method') === 'momo_card' ? 'checked' : '' }}>
                    <label for="pay_momo_card" class="pay-label"><span class="pay-icon"><i class="bi bi-credit-card"></i></span><div><div class="pay-title">Thẻ tín dụng / ghi nợ</div><div class="pay-desc">Visa, Mastercard qua MoMo</div></div></label>
                    @endif

                    {{-- BANK --}}
                    @if($bankConfigured)
                    <input type="radio" name="payment_method" id="pay_bank" value="bank" class="pay-option" {{ old('payment_method') === 'bank' ? 'checked' : '' }}>
                    <label for="pay_bank" class="pay-label">
                        <span class="pay-icon"><i class="bi bi-bank"></i></span>
                        <div>
                            <div class="pay-title">Chuyển khoản ngân hàng</div>
                            <div class="pay-desc">Chuyển khoản qua internet banking hoặc ATM</div>
                        </div>
                    </label>
                    {{-- Bank info --}}
                    <div id="info_bank" class="pay-info-box" style="background:#f0f7ff; border:1px solid #b8d8ff;">
                        <div class="fw-bold mb-2">Thông tin chuyển khoản</div>
                        <table class="table table-sm table-borderless mb-1">
                            <tr><td class="text-muted" style="width:140px;">Ngân hàng</td><td><strong>{{ config('services.bank.name') }}</strong></td></tr>
                            <tr><td class="text-muted">Số tài khoản</td><td><strong>{{ config('services.bank.account') }}</strong></td></tr>
                            <tr><td class="text-muted">Chủ tài khoản</td><td><strong>{{ config('services.bank.holder') }}</strong></td></tr>
                            <tr><td class="text-muted">Nội dung CK</td><td><strong>THANHTOAN [Mã đơn hàng]</strong></td></tr>
                        </table>
                    </div>
                    @endif
                </div>

            </div>

            {{-- ===== CỘT PHẢI — TÓM TẮT ===== --}}
            <div class="col-12 col-lg-5">
                <div class="summary-card">
                    <div class="section-head"><i class="bi bi-receipt me-2 text-danger"></i>Đơn hàng của bạn</div>

                    {{-- Danh sách sản phẩm --}}
                    <div class="mb-3" style="max-height:320px; overflow-y:auto;">
                        @foreach($selectedItems as $item)
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <img src="{{ ($item['variant_image'] ?? $item['product_image']) ? asset('storage/'.($item['variant_image'] ?? $item['product_image'])) : 'https://via.placeholder.com/56?text=No+Img' }}"
                                 class="item-thumb" alt="{{ $item['product_name'] }}">
                            <div class="flex-grow-1">
                                <div class="item-name">{{ $item['product_name'] }}</div>
                                <div class="item-variant">
                                    @if($item['storage']) {{ $item['storage'] }} @endif
                                    @if($item['color']) · {{ $item['color'] }} @endif
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted small">x{{ $item['quantity'] }}</span>
                                    <span class="price-red">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}₫</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <hr>

                    {{-- Tổng tiền --}}
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tiền hàng</span>
                        <span class="fw-semibold" id="display_subtotal">{{ number_format($subtotal, 0, ',', '.') }}₫</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Phí vận chuyển</span>
                        <span id="display_shipping_fee" class="fw-semibold">{{ $initialShippingFee !== null ? number_format($initialShippingFee, 0, ',', '.').'₫' : 'Chưa tính' }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold fs-5">Tổng thanh toán</span>
                        <span class="total-line" id="display_total">{{ number_format($subtotal + ($initialShippingFee ?? 0), 0, ',', '.') }}₫</span>
                    </div>

                    <button type="submit" class="btn-place-order" id="btnPlaceOrder">
                        <i class="bi bi-bag-check-fill me-2"></i>Đặt hàng ngay
                    </button>

                    <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-arrow-left me-1"></i>Quay lại giỏ hàng
                    </a>

                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-shield-check text-success me-1"></i>Đơn hàng được bảo vệ bởi TDH Phone
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
(function() {
    // -------------------------------------------------------------------
    // 1. Hiện/ẩn thông tin phương thức thanh toán (giữ nguyên)
    // -------------------------------------------------------------------
    const payOptions = document.querySelectorAll('.pay-option');
    const infoBoxes  = { momo: document.getElementById('info_momo'), bank: document.getElementById('info_bank') };

    function updatePayInfo() {
        const selected = document.querySelector('.pay-option:checked')?.value;
        Object.entries(infoBoxes).forEach(([key, box]) => {
            if (box) box.classList.toggle('show', key === selected);
        });
    }
    payOptions.forEach(opt => opt.addEventListener('change', updatePayInfo));
    updatePayInfo();

    // -------------------------------------------------------------------
    // 2. Prevent double submit (giữ nguyên)
    // -------------------------------------------------------------------
    document.getElementById('checkoutForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnPlaceOrder');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
    });
    if (!@json($ghnConfigured)) return;

    // -------------------------------------------------------------------
    // 3. GHN — Địa chỉ & Phí vận chuyển
    // -------------------------------------------------------------------
    const SUBTOTAL     = {{ $subtotal }};
    const SELECTED_KEYS = '{{ implode(',', $selectedKeys) }}';

    const selProvince  = document.getElementById('ghn_province');
    const selDistrict  = document.getElementById('ghn_district');
    const selWard      = document.getElementById('ghn_ward');

    const hidProvinceId  = document.getElementById('ghn_province_id');
    const hidDistrictId  = document.getElementById('ghn_district_id');
    const hidWardCode    = document.getElementById('ghn_ward_code');
    const hidCityName    = document.getElementById('city_name');
    const hidDistrictName= document.getElementById('district_name');
    const hidWardName    = document.getElementById('ward_name');
    const hidShippingFee = document.getElementById('hidden_shipping_fee');

    const dispShipping   = document.getElementById('display_shipping_fee');
    const dispTotal      = document.getElementById('display_total');

    function formatVND(amount) {
        return amount.toLocaleString('vi-VN') + '₫';
    }

    function setShippingDisplay(text, cls) {
        dispShipping.textContent = text;
        dispShipping.className   = 'fw-semibold ' + (cls || 'text-muted fst-italic');
    }

    function updateTotal(fee) {
        hidShippingFee.value   = fee;
        const total = SUBTOTAL + fee;
        dispTotal.textContent  = formatVND(total);
    }

    function resetShipping() {
        hidShippingFee.value = 0;
        setShippingDisplay('Chưa tính', 'text-muted fst-italic');
        dispTotal.textContent = formatVND(SUBTOTAL);
    }

    // --- Tải tỉnh ---
    async function loadProvinces() {
        try {
            const res  = await fetch('{{ route('locations.provinces') }}');
            const json = await res.json();
            if (json.success && json.data.length) {
                json.data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value        = p.ProvinceID;
                    opt.textContent  = p.ProvinceName;
                    selProvince.appendChild(opt);
                });
            } else {
                console.warn('[GHN] Không tải được danh sách tỉnh.');
            }
        } catch (e) {
            console.error('[GHN] loadProvinces error:', e);
        }
    }

    // --- Tải quận ---
    async function loadDistricts(provinceId) {
        selDistrict.innerHTML = '<option value="">Đang tải...</option>';
        selDistrict.disabled  = true;
        selWard.innerHTML     = '<option value="">-- Chọn phường/xã --</option>';
        selWard.disabled      = true;
        resetShipping();

        try {
            const res  = await fetch(`{{ url('locations/districts') }}/${provinceId}`);
            const json = await res.json();
            selDistrict.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
            if (json.success && json.data.length) {
                json.data.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value       = d.DistrictID;
                    opt.textContent = d.DistrictName;
                    selDistrict.appendChild(opt);
                });
                selDistrict.disabled = false;
            }
        } catch (e) {
            console.error('[GHN] loadDistricts error:', e);
            selDistrict.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        }
    }

    // --- Tải phường ---
    async function loadWards(districtId) {
        selWard.innerHTML = '<option value="">Đang tải...</option>';
        selWard.disabled  = true;
        resetShipping();

        try {
            const res  = await fetch(`{{ url('locations/wards') }}/${districtId}`);
            const json = await res.json();
            selWard.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
            if (json.success && json.data.length) {
                json.data.forEach(w => {
                    const opt = document.createElement('option');
                    opt.value       = w.WardCode;
                    opt.textContent = w.WardName;
                    selWard.appendChild(opt);
                });
                selWard.disabled = false;
            }
        } catch (e) {
            console.error('[GHN] loadWards error:', e);
            selWard.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        }
    }

    // --- Tính phí vận chuyển ---
    async function calculateFee(districtId, wardCode) {
        setShippingDisplay('Đang tính phí...', 'text-warning');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                           || document.querySelector('input[name="_token"]')?.value
                           || '';

            const res = await fetch('{{ route('locations.fee') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    district_id:   districtId,
                    ward_code:     wardCode,
                    subtotal:      SUBTOTAL,
                    selected_keys: SELECTED_KEYS,
                }),
            });

            const json = await res.json();

            if (json.success) {
                setShippingDisplay(formatVND(json.fee), 'text-danger');
                updateTotal(json.fee);
            } else {
                setShippingDisplay('Không thể tính phí', 'text-warning');
                console.warn('[GHN] calculateFee:', json.message);
                hidShippingFee.value = 0;
                dispTotal.textContent = formatVND(SUBTOTAL);
            }
        } catch (e) {
            setShippingDisplay('Lỗi kết nối GHN', 'text-danger');
            console.error('[GHN] calculateFee error:', e);
            hidShippingFee.value = 0;
            dispTotal.textContent = formatVND(SUBTOTAL);
        }
    }

    // --- Event: chọn tỉnh ---
    selProvince.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        hidProvinceId.value = this.value;
        hidCityName.value   = opt.textContent || '';

        hidDistrictId.value = '';
        hidDistrictName.value = '';
        hidWardCode.value   = '';
        hidWardName.value   = '';

        if (this.value) {
            loadDistricts(this.value);
        } else {
            selDistrict.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
            selDistrict.disabled  = true;
            selWard.innerHTML     = '<option value="">-- Chọn phường/xã --</option>';
            selWard.disabled      = true;
            resetShipping();
        }
    });

    // --- Event: chọn quận ---
    selDistrict.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        hidDistrictId.value   = this.value;
        hidDistrictName.value = opt.textContent || '';

        hidWardCode.value = '';
        hidWardName.value = '';

        if (this.value) {
            loadWards(this.value);
        } else {
            selWard.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
            selWard.disabled  = true;
            resetShipping();
        }
    });

    // --- Event: chọn phường ---
    selWard.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        hidWardCode.value = this.value;
        hidWardName.value = opt.textContent || '';

        if (this.value && hidDistrictId.value) {
            calculateFee(parseInt(hidDistrictId.value), this.value);
        } else {
            resetShipping();
        }
    });

    // --- Khởi tạo: tải tỉnh ---
    loadProvinces();

})();
</script>
@endpush
