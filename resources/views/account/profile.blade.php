@extends('layouts.app')
@section('title', 'Hồ sơ cá nhân | TDH Phone')
@section('content')
<div class="container commerce-page">
    <div class="commerce-toolbar"><h1>Tài khoản của tôi</h1></div>
    <div class="account-layout">
        @include('account.nav')
        <div class="account-content">
            @include('partials.alerts')
            <h2 class="mb-3">Thông tin cá nhân</h2>
            <form action="{{ route('profile.update') }}" method="POST" class="mb-4">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6"><label for="profile-name" class="form-label">Họ tên</label><input id="profile-name" name="name" class="form-control" value="{{ old('name', $user->name) }}" maxlength="100" autocomplete="name" required></div>
                    <div class="col-md-6"><label for="profile-email" class="form-label">Email</label><input type="email" id="profile-email" name="email" class="form-control" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required></div>
                    <div class="col-md-6"><label for="profile-phone" class="form-label">Số điện thoại</label><input type="tel" id="profile-phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" pattern="0[0-9]{9}" autocomplete="tel"></div>
                    <div class="col-md-6"><label class="form-label">Xác thực email</label><div class="pt-2"><span class="badge bg-{{ $user->hasVerifiedEmail() ? 'success' : 'warning text-dark' }}">{{ $user->hasVerifiedEmail() ? 'Đã xác thực' : 'Chưa xác thực' }}</span></div></div>
                    <div class="col-12"><label for="profile-address" class="form-label">Địa chỉ giao hàng</label><input id="profile-address" name="address" class="form-control" value="{{ old('address', $user->address) }}" maxlength="500" autocomplete="street-address"></div>
                </div>
                <button class="btn btn-danger mt-3" type="submit"><i class="bi bi-check-lg me-1"></i>Lưu hồ sơ</button>
            </form>
            <section class="commerce-section">
                <h2 class="mb-3">Đổi mật khẩu</h2>
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12"><label for="current-password" class="form-label">Mật khẩu hiện tại</label><input type="password" name="current_password" id="current-password" class="form-control" autocomplete="current-password" required></div>
                        <div class="col-md-6"><label for="new-password" class="form-label">Mật khẩu mới</label><input type="password" name="password" id="new-password" class="form-control" minlength="8" autocomplete="new-password" required></div>
                        <div class="col-md-6"><label for="confirm-password" class="form-label">Nhập lại mật khẩu mới</label><input type="password" name="password_confirmation" id="confirm-password" class="form-control" minlength="8" autocomplete="new-password" required></div>
                    </div>
                    <button class="btn btn-outline-danger mt-3" type="submit"><i class="bi bi-lock me-1"></i>Đổi mật khẩu</button>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection
