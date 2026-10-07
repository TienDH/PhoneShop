<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'TDH Phone')</title>

    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --tdh-red: #d70018;
            --tdh-red-dark: #a80716;
            --tdh-red-soft: #fff0f2;
            --tdh-ink: #101828;
            --tdh-muted: #667085;
            --tdh-border: #edf0f4;
            --tdh-page: #f6f8fb;
            --tdh-card: #ffffff;
            --tdh-shadow: 0 16px 42px rgba(16, 24, 40, .08);
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(215, 0, 24, .08), transparent 34rem),
                linear-gradient(180deg, #fff 0%, var(--tdh-page) 30rem);
            color: var(--tdh-ink);
            font-family: 'Be Vietnam Pro', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0;
        }

        a { transition: color .18s ease, background .18s ease, border-color .18s ease, transform .18s ease; }

        .tdh-main { min-height: 56vh; }
        .tdh-main > .container:first-child { padding-top: 1.5rem; padding-bottom: 1.5rem; }

        .tdh-logo {
            display: inline-flex;
            align-items: center;
            gap: .7rem;
            color: #fff;
            text-decoration: none;
            min-width: max-content;
        }

        .tdh-logo:hover { color: #fff; transform: translateY(-1px); }

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

        .tdh-logo-mark i { font-size: 1.35rem; }

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
            font-size: 1.2rem;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .tdh-logo-copy span {
            margin-top: .2rem;
            color: #ffe3e7;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .12em;
        }

        .tdh-header {
            position: sticky;
            top: 0;
            z-index: 1020;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(167, 7, 22, .96), rgba(215, 0, 24, .96)),
                linear-gradient(90deg, #d70018, #f04438);
            box-shadow: 0 14px 36px rgba(167, 7, 22, .22);
        }

        .tdh-topbar {
            border-bottom: 1px solid rgba(255, 255, 255, .14);
            background: rgba(16, 24, 40, .15);
            font-size: .78rem;
        }

        .tdh-topbar .container { min-height: 34px; }
        .tdh-topbar span { color: rgba(255, 255, 255, .9); }

        .tdh-header-main { padding: .9rem 0; }

        .tdh-search {
            position: relative;
            flex: 1 1 320px;
            max-width: 560px;
        }

        .tdh-search input {
            width: 100%;
            min-height: 44px;
            border: 0;
            border-radius: 999px;
            padding: .75rem 3.2rem .75rem 1.1rem;
            background: rgba(255, 255, 255, .96);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6), 0 10px 24px rgba(16, 24, 40, .16);
            color: var(--tdh-ink);
            outline: 0;
        }

        .tdh-search button {
            position: absolute;
            top: 50%;
            right: .3rem;
            width: 36px;
            height: 36px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 50%;
            background: var(--tdh-red);
            color: #fff;
        }

        .tdh-action-row { gap: .45rem; }

        .tdh-action {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            min-height: 42px;
            padding: .45rem .75rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            color: rgba(255, 255, 255, .9);
            text-decoration: none;
            background: rgba(255, 255, 255, .09);
            font-size: .86rem;
            font-weight: 700;
        }

        .tdh-action:hover,
        .tdh-action.active {
            color: #fff;
            background: rgba(255, 255, 255, .18);
            border-color: rgba(255, 255, 255, .36);
        }

        .tdh-cart-badge {
            position: absolute;
            top: -7px;
            right: -3px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffd166;
            color: #7a0916;
            font-size: .68rem;
            font-weight: 900;
            box-shadow: 0 0 0 2px var(--tdh-red);
        }

        .tdh-nav {
            border-top: 1px solid rgba(255, 255, 255, .12);
            background: rgba(16, 24, 40, .12);
        }

        .tdh-nav .nav {
            gap: .35rem;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .tdh-nav .nav::-webkit-scrollbar { display: none; }

        .tdh-nav-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            min-height: 42px;
            padding: .55rem .85rem;
            border-radius: 999px;
            color: rgba(255, 255, 255, .88);
            text-decoration: none;
            font-size: .86rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .tdh-nav-link:hover,
        .tdh-nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, .16);
        }

        .tdh-footer {
            margin-top: 2.5rem;
            padding: 3rem 0 1.25rem;
            color: #fff;
            background:
                radial-gradient(circle at 8% 0%, rgba(215, 0, 24, .38), transparent 28rem),
                linear-gradient(135deg, #101828, #1d2939);
        }

        .tdh-footer p,
        .tdh-footer a,
        .tdh-footer small { color: rgba(255, 255, 255, .68); }

        .tdh-footer a {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            text-decoration: none;
            margin-bottom: .55rem;
        }

        .tdh-footer a:hover { color: #fff; }
        .tdh-footer h6 { color: #fff; font-weight: 800; margin-bottom: 1rem; }
        .tdh-footer-divider { border-color: rgba(255, 255, 255, .12); }

        .card,
        .cart-table-wrap,
        .summary-card,
        .card-section,
        .success-card {
            border: 1px solid var(--tdh-border) !important;
            border-radius: 16px !important;
            box-shadow: var(--tdh-shadow) !important;
        }

        .card-header {
            border-bottom: 1px solid var(--tdh-border);
            background: linear-gradient(180deg, #fff, #fbfcfe);
            color: var(--tdh-ink);
            font-weight: 800;
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
            box-shadow: 0 10px 20px rgba(215, 0, 24, .18);
        }

        .btn-primary:hover,
        .btn-danger:hover {
            border-color: var(--tdh-red-dark);
            background: linear-gradient(135deg, var(--tdh-red-dark), var(--tdh-red));
        }

        .btn-outline-danger {
            color: var(--tdh-red);
            border-color: var(--tdh-red);
        }

        .btn-outline-danger:hover {
            border-color: var(--tdh-red);
            background: var(--tdh-red);
        }

        .text-danger { color: var(--tdh-red) !important; }
        .bg-danger { background-color: var(--tdh-red) !important; }

        .section-title,
        .section-head {
            border-left-color: var(--tdh-red) !important;
        }

        @media (max-width: 767.98px) {
            .tdh-topbar .container { justify-content: center !important; text-align: center; }
            .tdh-topbar-points { display: none !important; }
            .tdh-header-main { padding: .8rem 0; }
            .tdh-search { order: 3; flex-basis: 100%; max-width: none; }
            .tdh-action span:not(.tdh-cart-badge) { display: none; }
            .tdh-action { width: 42px; justify-content: center; padding: .45rem; }
        }
    </style>
    @stack('styles')
    <link href="{{ asset('css/store-management.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app">
        @sectionMissing('hide_customer_chrome')
            @include('partials.customer-header')
        @endif

        <main class="tdh-main">
            @yield('content')
        </main>

        @sectionMissing('hide_customer_chrome')
            @include('partials.customer-footer')
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
