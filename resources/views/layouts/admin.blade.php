<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TDH Phone Admin')</title>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --tdh-red: #d70018;
            --tdh-red-dark: #a80716;
            --tdh-ink: #101828;
            --tdh-muted: #667085;
            --tdh-border: #edf0f4;
            --tdh-page: #f6f8fb;
            --tdh-shadow: 0 16px 42px rgba(16, 24, 40, .08);
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(215, 0, 24, .08), transparent 32rem),
                var(--tdh-page);
            color: var(--tdh-ink);
            font-family: 'Be Vietnam Pro', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0;
        }

        .admin-shell { min-height: 100vh; }

        .admin-sidebar {
            position: sticky;
            top: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 20% 0%, rgba(215, 0, 24, .38), transparent 18rem),
                linear-gradient(180deg, #111827, #1f2937);
            box-shadow: 14px 0 36px rgba(16, 24, 40, .16);
        }

        .tdh-logo {
            display: inline-flex;
            align-items: center;
            gap: .7rem;
            color: #fff;
            text-decoration: none;
        }

        .tdh-logo:hover { color: #fff; }

        .tdh-logo-mark {
            position: relative;
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #fff;
            color: var(--tdh-red);
            box-shadow: 0 12px 24px rgba(0, 0, 0, .16);
        }

        .tdh-logo-signal {
            position: absolute;
            right: 7px;
            top: 7px;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #12b76a;
            border: 2px solid #fff;
        }

        .tdh-logo-copy {
            display: flex;
            flex-direction: column;
            line-height: 1;
            text-transform: uppercase;
        }

        .tdh-logo-copy strong {
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .tdh-logo-copy span {
            margin-top: .2rem;
            color: #ffe3e7;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .12em;
        }

        .admin-brand {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, .12);
        }

        .admin-brand small {
            display: block;
            margin-top: .6rem;
            color: rgba(255, 255, 255, .56);
            font-weight: 600;
        }

        .admin-nav { padding: .75rem; }

        .admin-sidebar a.admin-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-height: 44px;
            margin-bottom: .35rem;
            padding: .7rem .85rem;
            border: 1px solid transparent;
            border-radius: 12px;
            color: rgba(255, 255, 255, .74);
            text-decoration: none;
            font-size: .92rem;
            font-weight: 700;
        }

        .admin-sidebar a.admin-link:hover,
        .admin-sidebar a.admin-link.active {
            color: #fff;
            background: rgba(255, 255, 255, .1);
            border-color: rgba(255, 255, 255, .14);
        }

        .admin-sidebar a.admin-link i {
            width: 22px;
            text-align: center;
            color: #ffd166;
        }

        .admin-content { min-height: 100vh; }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--tdh-border);
            background: rgba(255, 255, 255, .86);
            backdrop-filter: blur(14px);
        }

        .admin-page { padding: 2rem; }

        .admin-user-chip {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            padding: .45rem .75rem;
            border: 1px solid var(--tdh-border);
            border-radius: 999px;
            background: #fff;
        }

        .admin-avatar {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--tdh-red);
            color: #fff;
            font-weight: 900;
        }

        .card {
            border: 1px solid var(--tdh-border);
            border-radius: 16px;
            box-shadow: var(--tdh-shadow);
        }

        .table { color: var(--tdh-ink); }

        .table thead th {
            border-bottom: 1px solid var(--tdh-border);
            background: #f8fafc;
            color: #475467;
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .table td { border-color: #f0f2f5; }

        .table-image {
            height: 52px;
            width: 52px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid var(--tdh-border);
            background: #fff;
            padding: 3px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border-color: #d0d5dd;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--tdh-red);
            box-shadow: 0 0 0 .2rem rgba(215, 0, 24, .14);
        }

        .btn-primary,
        .btn-danger {
            border-color: var(--tdh-red);
            background: linear-gradient(135deg, var(--tdh-red), #f04438);
            box-shadow: 0 10px 20px rgba(215, 0, 24, .16);
        }

        .btn-primary:hover,
        .btn-danger:hover {
            border-color: var(--tdh-red-dark);
            background: linear-gradient(135deg, var(--tdh-red-dark), var(--tdh-red));
        }

        .btn-warning {
            border-color: #fdb022;
            background: #fdb022;
            color: #101828;
        }

        @media (max-width: 767.98px) {
            .admin-sidebar {
                position: relative;
                min-height: auto;
            }

            .admin-page { padding: 1.25rem; }
            .admin-topbar { position: relative; }
        }
    </style>
    @stack('styles')
    <link href="{{ asset('css/store-management.css') }}" rel="stylesheet">
</head>
<body>
    <div class="container-fluid admin-shell">
        <div class="row">
            <aside class="col-md-3 col-lg-2 px-0 admin-sidebar">
                <div class="admin-brand">
                    @include('partials.tdh-logo', ['href' => route('admin.dashboard'), 'label' => 'TDH Phone Admin'])
                    <small>Admin Control Center</small>
                </div>

                <nav class="admin-nav">
                    <a href="{{ route('admin.dashboard') }}" class="admin-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>Dashboard
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="admin-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap"></i>Categories
                    </a>
                    <a href="{{ route('admin.products.index') }}" class="admin-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                        <i class="bi bi-phone"></i>Products
                    </a>
                    <a href="{{ route('admin.orders.index') }}" class="admin-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i>Đơn hàng</a>
                    <a href="{{ route('admin.users.index') }}" class="admin-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i>Người dùng</a>
                    <a href="{{ route('admin.reports.index') }}" class="admin-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"><i class="bi bi-bar-chart"></i>Doanh thu</a>
                </nav>
            </aside>

            <main class="col-md-9 col-lg-10 px-0 admin-content">
                <header class="admin-topbar px-4 py-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <div class="fw-bold">TDH Phone Admin</div>
                        <div class="text-muted small">Quản lý sản phẩm, danh mục và biến thể</div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="admin-user-chip">
                            <span class="admin-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            <span>
                                <strong>{{ Auth::user()->name }}</strong>
                                <span class="text-muted ms-1">({{ Auth::user()->role }})</span>
                            </span>
                        </div>
                        <a href="{{ url('/') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-house me-1"></i>Homepage
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </button>
                        </form>
                    </div>
                </header>

                <div class="admin-page">
                    @if (session('success'))
                        <div class="alert alert-success shadow-sm border-0">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger shadow-sm border-0">{{ session('error') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger shadow-sm border-0">
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
