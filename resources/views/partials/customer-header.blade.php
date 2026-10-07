@php
    $cartCount = array_sum(array_column(session('cart', []), 'quantity'));
@endphp

<header class="tdh-header">
    <div class="tdh-topbar">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span><i class="bi bi-telephone-fill me-1"></i>Hotline: <strong>1800 1234</strong> (miễn phí)</span>
            <div class="tdh-topbar-points d-flex align-items-center gap-3">
                <span><i class="bi bi-truck me-1"></i>Freeship đơn từ 500K</span>
                <span><i class="bi bi-shield-check me-1"></i>Bảo hành chính hãng</span>
                <span><i class="bi bi-lock-fill me-1"></i>Thanh toán an toàn</span>
            </div>
        </div>
    </div>

    <div class="tdh-header-main">
        <div class="container d-flex align-items-center gap-3 flex-wrap">
            @include('partials.tdh-logo')

            <form class="tdh-search" action="{{ route('products.index') }}" method="GET">
                <input type="text" name="q" placeholder="Tìm điện thoại, phụ kiện, ưu đãi..." value="{{ request('q') }}" aria-label="Tìm kiếm sản phẩm">
                <button type="submit" aria-label="Tìm kiếm"><i class="bi bi-search"></i></button>
            </form>

            <div class="tdh-action-row d-flex align-items-center ms-auto">
                <a href="{{ route('cart.index') }}" class="tdh-action {{ request()->routeIs('cart.*') ? 'active' : '' }}">
                    <i class="bi bi-cart3"></i>
                    <span>Giỏ hàng</span>
                    @if($cartCount > 0)
                        <span class="tdh-cart-badge">{{ $cartCount }}</span>
                    @endif
                </a>

                @guest
                    <a href="{{ route('login') }}" class="tdh-action {{ request()->routeIs('login') ? 'active' : '' }}">
                        <i class="bi bi-person"></i>
                        <span>Đăng nhập</span>
                    </a>
                    <a href="{{ route('register') }}" class="tdh-action {{ request()->routeIs('register') ? 'active' : '' }}">
                        <i class="bi bi-person-plus"></i>
                        <span>Đăng ký</span>
                    </a>
                @else
                    <div class="dropdown">
                        <button class="tdh-action border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span>{{ Auth::user()->name }}</span>
                            <i class="bi bi-chevron-down small"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-bag-check me-2"></i>Đơn hàng của tôi</a></li>
                            @if(Auth::user()->role === 'admin')
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Quản trị</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('tdh-logout-form').submit();">
                                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                    <form id="tdh-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                @endguest
            </div>
        </div>
    </div>

    <nav class="tdh-nav">
        <div class="container">
            <div class="nav py-1">
                <a class="tdh-nav-link {{ request()->routeIs('front.home') ? 'active' : '' }}" href="{{ route('front.home') }}">
                    <i class="bi bi-house-fill"></i>Trang chủ
                </a>
                <a class="tdh-nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <i class="bi bi-phone-fill"></i>Sản phẩm
                </a>
                <a class="tdh-nav-link" href="{{ route('products.index', ['q' => 'iPhone']) }}">
                    <i class="bi bi-apple"></i>iPhone
                </a>
                <a class="tdh-nav-link" href="{{ route('products.index', ['q' => 'Samsung']) }}">
                    <i class="bi bi-phone"></i>Samsung
                </a>
                <a class="tdh-nav-link" href="{{ route('products.index', ['sort' => 'best_selling']) }}">
                    <i class="bi bi-graph-up-arrow"></i>Bán chạy
                </a>
                <a class="tdh-nav-link" href="{{ route('orders.index') }}">
                    <i class="bi bi-bag-check"></i>Đơn hàng
                </a>
                <a class="tdh-nav-link" href="{{ route('front.home') }}#products">
                    <i class="bi bi-patch-check"></i>Bảo hành
                </a>
            </div>
        </div>
    </nav>
</header>
