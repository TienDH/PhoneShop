<nav class="account-nav" aria-label="Tài khoản">
    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="bi bi-person"></i>Hồ sơ cá nhân</a>
    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}"><i class="bi bi-bag-check"></i>Đơn hàng của tôi</a>
</nav>
