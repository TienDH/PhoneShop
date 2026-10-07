@extends('layouts.admin')
@section('title', 'Quản lý người dùng | TDH Phone')
@section('content')
<div class="commerce-page pt-0">
    <div class="commerce-toolbar"><h1>Quản lý người dùng</h1><span class="small text-muted">{{ $users->total() }} tài khoản</span></div>
    <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 mb-4">
        <div class="col-md-5"><input name="q" class="form-control" value="{{ request('q') }}" maxlength="100" placeholder="Tên hoặc email" aria-label="Tìm người dùng"></div>
        <div class="col-md-3"><select name="role" class="form-select" aria-label="Vai trò"><option value="">Tất cả vai trò</option><option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Khách hàng</option><option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Quản trị viên</option></select></div>
        <div class="col-md-4"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Tìm kiếm</button></div>
    </form>
    <div class="table-responsive"><table class="table management-table align-middle"><thead><tr><th>Người dùng</th><th>Liên hệ</th><th>Xác thực</th><th class="text-end">Đơn hàng</th><th>Vai trò</th></tr></thead><tbody>
        @forelse($users as $user)<tr><td class="fw-semibold">{{ $user->name }}<div class="text-muted small">#{{ $user->id }}</div></td><td>{{ $user->email }}<div class="small text-muted">{{ $user->phone }}</div></td><td><span class="badge bg-{{ $user->hasVerifiedEmail() ? 'success' : 'secondary' }}">{{ $user->hasVerifiedEmail() ? 'Đã xác thực' : 'Chưa xác thực' }}</span></td><td class="text-end">{{ $user->orders_count }}</td><td>@if($user->id === auth()->id())<span class="badge bg-primary">Quản trị viên · Bạn</span>@else<form action="{{ route('admin.users.update', $user) }}" method="POST" class="d-flex gap-2">@csrf @method('PUT')<select name="role" class="form-select form-select-sm" aria-label="Vai trò của {{ $user->name }}"><option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>Khách hàng</option><option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Quản trị viên</option></select><button class="btn btn-sm btn-outline-primary" type="submit" title="Lưu vai trò" aria-label="Lưu vai trò"><i class="bi bi-check-lg"></i></button></form>@endif</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Không tìm thấy người dùng.</td></tr>@endforelse
    </tbody></table></div>
    <div class="mt-3">{{ $users->links() }}</div>
</div>
@endsection
