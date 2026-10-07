@extends('layouts.app')

@section('content')
<div class="container d-flex justify-content-center align-items-center" style="min-height:70vh;">
    <div class="card text-center p-4" style="max-width:500px;">
        <div class="mb-3">
            <i class="bi bi-check-circle" style="font-size:3rem;color:#28a745;"></i>
        </div>
        <h3 class="mb-3">Xác thực email thành công!</h3>
        <p class="mb-4">Tài khoản của bạn đã được xác thực. Bạn có muốn đăng nhập ngay bây giờ không?</p>
        <a href="{{ route('login') }}" class="btn btn-primary me-2">Đăng nhập ngay</a>
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">Để sau</a>
    </div>
</div>
@endsection