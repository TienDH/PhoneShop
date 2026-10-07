@extends('layouts.app')

@section('content')
<style>
/* ===== RESET & BASE ===== */
*{box-sizing:border-box}
body{background:#f6f8fb;font-family:'Be Vietnam Pro',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}

/* ===== HEADER ===== */
.site-header{background:linear-gradient(135deg,#c0392b 0%,#e74c3c 60%,#e84393 100%);padding:0;box-shadow:0 2px 16px rgba(0,0,0,.18)}
.header-top{background:rgba(0,0,0,.13);font-size:.78rem;padding:4px 0}
.header-main{padding:10px 0}
.logo-text{font-size:1.6rem;font-weight:900;letter-spacing:-1px;color:#fff;text-decoration:none}
.logo-text span{color:#ffe082}
.search-box{position:relative;flex:1;max-width:520px;margin:0 20px}
.search-box input{border-radius:24px;border:none;padding:10px 48px 10px 20px;font-size:.9rem;width:100%;outline:none;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.search-box button{position:absolute;right:4px;top:50%;transform:translateY(-50%);background:#e74c3c;border:none;border-radius:50%;width:34px;height:34px;color:#fff;cursor:pointer;transition:.2s}
.search-box button:hover{background:#c0392b}
.header-actions a{color:rgba(255,255,255,.9);text-decoration:none;font-size:.85rem;display:flex;flex-direction:column;align-items:center;gap:2px;padding:0 10px;transition:.2s}
.header-actions a:hover{color:#fff}
.header-actions a i{font-size:1.3rem}
.cart-badge{position:relative}
.cart-badge .badge{position:absolute;top:-6px;right:2px;background:#ffe082;color:#c0392b;font-size:.6rem;font-weight:800;border-radius:10px;padding:1px 5px;min-width:16px;text-align:center}
.nav-bar{background:rgba(0,0,0,.15);border-top:1px solid rgba(255,255,255,.1)}
.nav-bar .nav-link{color:rgba(255,255,255,.85);font-size:.84rem;padding:8px 14px;border-radius:4px;transition:.15s;white-space:nowrap}
.nav-bar .nav-link:hover,.nav-bar .nav-link.active{background:rgba(255,255,255,.15);color:#fff}
.nav-bar .nav-link i{margin-right:5px}

/* ===== HERO ===== */
.hero-section{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);padding:32px 0 0}
.hero-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:16px;overflow:hidden;cursor:pointer;transition:.3s;height:100%}
.hero-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.3)}
.hero-main{position:relative;background:linear-gradient(135deg,#e74c3c,#c0392b);border-radius:16px;overflow:hidden;padding:32px;min-height:260px;display:flex;flex-direction:column;justify-content:flex-end}
.hero-main::before{content:'';position:absolute;inset:0;background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="80" cy="20" r="40" fill="rgba(255,255,255,0.05)"/><circle cx="20" cy="80" r="30" fill="rgba(255,255,255,0.03)"/></svg>')}
.hero-main .tag{background:rgba(255,255,255,.2);color:#fff;border-radius:20px;padding:3px 12px;font-size:.75rem;font-weight:700;display:inline-block;margin-bottom:10px}
.hero-main h2{color:#fff;font-size:1.6rem;font-weight:900;margin:0 0 6px;line-height:1.2}
.hero-main p{color:rgba(255,255,255,.8);font-size:.85rem;margin:0 0 16px}
.hero-main .btn-hero{background:#fff;color:#e74c3c;border:none;border-radius:20px;padding:8px 20px;font-weight:700;font-size:.85rem;cursor:pointer;transition:.2s}
.hero-main .btn-hero:hover{background:#ffe082}
.promo-card{background:linear-gradient(135deg,#f39c12,#e67e22);border-radius:12px;padding:20px;color:#fff;margin-bottom:12px;position:relative;overflow:hidden;min-height:110px;display:flex;flex-direction:column;justify-content:flex-end}
.promo-card.blue{background:linear-gradient(135deg,#2980b9,#1abc9c)}
.promo-card h6{font-weight:800;margin:0;font-size:.95rem}
.promo-card small{opacity:.85;font-size:.75rem}
.promo-card .icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:2.8rem;opacity:.25}

/* ===== SECTION HEADER ===== */
.sec-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.sec-title{font-size:1.15rem;font-weight:800;color:#1a1a2e;position:relative;padding-left:14px}
.sec-title::before{content:'';position:absolute;left:0;top:2px;bottom:2px;width:4px;background:linear-gradient(#e74c3c,#c0392b);border-radius:2px}
.sec-link{color:#e74c3c;font-size:.85rem;text-decoration:none;font-weight:600}
.sec-link:hover{text-decoration:underline}

/* ===== CATEGORY STRIP ===== */
.cat-strip{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:20px;margin:20px 0}
.cat-item{display:flex;flex-direction:column;align-items:center;gap:8px;padding:12px 8px;border-radius:12px;cursor:pointer;transition:.2s;text-decoration:none;color:#333}
.cat-item:hover{background:#fff5f5;color:#e74c3c;transform:translateY(-2px)}
.cat-item .cat-img{width:64px;height:64px;object-fit:contain;border-radius:12px;border:2px solid #f0f0f0;padding:6px;background:#fafafa}
.cat-item .cat-name{font-size:.78rem;font-weight:700;text-align:center;line-height:1.2}
.cat-item .cat-icon{width:64px;height:64px;background:linear-gradient(135deg,#ffe5e5,#ffcccc);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.8rem}

/* ===== PRODUCT CARD ===== */
.prod-card{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:.25s;display:flex;flex-direction:column;height:100%;text-decoration:none;color:inherit;border:1px solid #eee}
.prod-card:hover{transform:translateY(-4px);box-shadow:0 10px 28px rgba(0,0,0,.12);border-color:#ffd0d0}
.prod-card .img-wrap{background:linear-gradient(135deg,#fafafa,#f4f4f4);padding:12px;display:flex;align-items:center;justify-content:center;height:160px;position:relative;overflow:hidden}
.prod-card .img-wrap img{max-height:140px;width:auto;max-width:100%;object-fit:contain;transition:.3s}
.prod-card:hover .img-wrap img{transform:scale(1.05)}
.prod-card .new-badge{position:absolute;top:10px;left:10px;background:#e74c3c;color:#fff;font-size:.65rem;font-weight:700;border-radius:4px;padding:2px 7px}
.prod-card .info{padding:12px 14px 14px;flex:1;display:flex;flex-direction:column}
.prod-card .prod-name{font-size:.88rem;font-weight:700;color:#1a1a2e;margin-bottom:6px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prod-card .prod-price{font-size:1.05rem;font-weight:800;color:#e74c3c;margin-top:auto}
.prod-card .prod-oldprice{font-size:.75rem;color:#aaa;text-decoration:line-through;margin-bottom:2px}
.prod-card .prod-meta{font-size:.72rem;color:#999;margin-top:4px}
.prod-card .add-btn{margin-top:10px;background:linear-gradient(135deg,#e74c3c,#c0392b);color:#fff;border:none;border-radius:8px;padding:7px;width:100%;font-size:.8rem;font-weight:700;cursor:pointer;transition:.2s;opacity:0;transform:translateY(4px)}
.prod-card:hover .add-btn{opacity:1;transform:none}

/* ===== SORT TABS ===== */
.sort-tabs{display:flex;gap:6px;flex-wrap:wrap}
.sort-tab{padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:600;border:1.5px solid #e0e0e0;background:#fff;color:#666;cursor:pointer;text-decoration:none;transition:.15s}
.sort-tab:hover,.sort-tab.active{background:#e74c3c;border-color:#e74c3c;color:#fff}

/* ===== BENEFITS ===== */
.benefit-strip{background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:16px;padding:24px;margin:24px 0}
.benefit-item{text-align:center;color:#fff}
.benefit-item .bi-icon{font-size:2rem;margin-bottom:10px;background:linear-gradient(135deg,#e74c3c,#e84393);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.benefit-item h6{font-weight:700;font-size:.9rem;margin-bottom:4px}
.benefit-item p{font-size:.75rem;color:rgba(255,255,255,.6);margin:0}

/* ===== FOOTER ===== */
.site-footer{background:linear-gradient(135deg,#1a1a2e,#0f3460);color:#fff;padding:40px 0 20px}
.footer-logo{font-size:1.4rem;font-weight:900;color:#fff;margin-bottom:10px}
.footer-logo span{color:#ffe082}
.footer-col h6{font-weight:700;color:#fff;margin-bottom:14px;font-size:.9rem;text-transform:uppercase;letter-spacing:.5px}
.footer-col a{display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:.83rem;margin-bottom:8px;transition:.15s}
.footer-col a:hover{color:#e74c3c;padding-left:4px}
.footer-divider{border-color:rgba(255,255,255,.1);margin:24px 0 16px}
.footer-bottom{font-size:.78rem;color:rgba(255,255,255,.5)}
</style>

{{-- ======= HERO ======= --}}
<div class="hero-section">
    <div class="container pb-3">
        <div class="row g-3">
            <div class="col-12 col-md-7">
                <div class="hero-main">
                    <span class="tag">Siêu Sale TDH</span>
                    <h2>Điện thoại cao cấp<br>Giá cực ưu đãi</h2>
                    <p>Giảm đến 30% — Freeship toàn quốc — Bảo hành 12 tháng</p>
                    <a href="#products" class="btn-hero">Mua ngay →</a>
                </div>
            </div>
            <div class="col-12 col-md-5">
                <div class="promo-card mb-3">
                    <span class="icon"><i class="bi bi-phone-fill"></i></span>
                    <small>Ưu đãi đặc biệt</small>
                    <h6>Thu cũ đổi mới — Lên đến 5 triệu</h6>
                </div>
                <div class="promo-card blue">
                    <span class="icon"><i class="bi bi-credit-card-fill"></i></span>
                    <small>Hỗ trợ tài chính</small>
                    <h6>Trả góp 0% — 12 tháng không lãi</h6>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">

{{-- ======= DANH MỤC ======= --}}
<div class="cat-strip">
    <div class="sec-header mb-3">
        <span class="sec-title">Danh mục sản phẩm</span>
    </div>
    <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2">
        @forelse($categories as $cat)
        <div class="col">
            <a href="{{ route('products.index', ['category' => $cat->id]) }}" class="cat-item">
                @if($cat->image)
                    <img src="{{ asset('storage/'.$cat->image) }}" class="cat-img" alt="{{ $cat->name }}">
                @else
                    <div class="cat-icon"><i class="bi bi-phone"></i></div>
                @endif
                <span class="cat-name">{{ $cat->name }}</span>
            </a>
        </div>
        @empty
        <div class="col-12 text-muted text-center py-3">Chưa có danh mục</div>
        @endforelse
    </div>
</div>

{{-- ======= NỔI BẬT ======= --}}
<div class="sec-header mt-4">
    <span class="sec-title">Sản phẩm bán chạy</span>
    <a href="{{ route('products.index', ['sort' => 'best_selling']) }}" class="sec-link">Xem tất cả →</a>
</div>
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 mb-4">
    @forelse($bestSellers as $p)
    @php $minPrice=$p->variants->min('price'); @endphp
    <div class="col">
        <a href="{{ route('product.show',$p->slug) }}" class="prod-card">
            <div class="img-wrap">
                <span class="new-badge">Đã bán {{ $p->sold_count }}</span>
                <img src="{{ $p->image ? asset('storage/'.$p->image) : 'https://via.placeholder.com/150?text=No+Image' }}" alt="{{ $p->name }}">
            </div>
            <div class="info">
                <div class="prod-name">{{ $p->name }}</div>
                @if($minPrice)
                    <div class="prod-price">{{ number_format($minPrice,0,',','.') }}₫</div>
                @else
                    <div class="prod-price" style="color:#aaa;font-size:.85rem">Liên hệ</div>
                @endif
                <span class="add-btn d-block text-center"><i class="bi bi-arrow-right me-1"></i>Xem chi tiết</span>
            </div>
        </a>
    </div>
    @empty
    <div class="col-12 text-muted py-3">Chưa có đơn hàng đã giao để xếp hạng.</div>
    @endforelse
</div>

{{-- ======= TẤT CẢ SẢN PHẨM ======= --}}
@php
$sort=request()->get('sort','new');
$sortedProducts=match($sort){
    'price_asc' =>$products->sortBy(fn($p)=>$p->variants->min('price')??PHP_INT_MAX),
    'price_desc'=>$products->sortByDesc(fn($p)=>$p->variants->min('price')??0),
    'popular'   =>$products->sortByDesc('id'),
    default     =>$products->sortByDesc('created_at'),
};
@endphp

<div id="products" class="sec-header mt-2">
    <span class="sec-title">🛒 Tất cả sản phẩm</span>
    <div class="sort-tabs">
        <a href="{{ route('products.index', ['sort' => 'new']) }}" class="sort-tab active">Mới nhất</a>
        <a href="{{ route('products.index', ['sort' => 'best_selling']) }}" class="sort-tab">Bán chạy</a>
        <a href="{{ route('products.index', ['sort' => 'price_asc']) }}" class="sort-tab">Giá thấp</a>
        <a href="{{ route('products.index', ['sort' => 'price_desc']) }}" class="sort-tab">Giá cao</a>
    </div>
</div>
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 mb-4">
    @forelse($sortedProducts as $p)
    @php $minPrice=$p->variants->min('price'); @endphp
    <div class="col">
        <a href="{{ route('product.show',$p->slug) }}" class="prod-card">
            <div class="img-wrap">
                <img src="{{ $p->image ? asset('storage/'.$p->image) : 'https://via.placeholder.com/150?text=No+Image' }}" alt="{{ $p->name }}">
            </div>
            <div class="info">
                <div class="prod-name">{{ $p->name }}</div>
                @if($minPrice)
                    <div class="prod-price">{{ number_format($minPrice,0,',','.') }}₫</div>
                @else
                    <div class="prod-price" style="color:#aaa;font-size:.85rem">Liên hệ</div>
                @endif
                <span class="add-btn d-block text-center"><i class="bi bi-arrow-right me-1"></i>Xem chi tiết</span>
            </div>
        </a>
    </div>
    @empty
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-box-seam display-4 d-block mb-3"></i>Chưa có sản phẩm nào.
    </div>
    @endforelse
</div>

{{-- ======= BENEFITS ======= --}}
<div class="benefit-strip">
    <div class="row row-cols-2 row-cols-md-4 g-3">
        <div class="col"><div class="benefit-item">
            <div class="bi-icon"><i class="bi bi-shield-check-fill"></i></div>
            <h6>Bảo hành chính hãng</h6>
            <p>12 tháng tại trung tâm</p>
        </div></div>
        <div class="col"><div class="benefit-item">
            <div class="bi-icon"><i class="bi bi-truck-front-fill"></i></div>
            <h6>Giao hàng nhanh</h6>
            <p>Freeship đơn từ 500K</p>
        </div></div>
        <div class="col"><div class="benefit-item">
            <div class="bi-icon"><i class="bi bi-credit-card-fill"></i></div>
            <h6>Trả góp 0%</h6>
            <p>Duyệt nhanh trong 5 phút</p>
        </div></div>
        <div class="col"><div class="benefit-item">
            <div class="bi-icon"><i class="bi bi-headset"></i></div>
            <h6>Hỗ trợ 24/7</h6>
            <p>Tư vấn tận tâm miễn phí</p>
        </div></div>
    </div>
</div>

</div>{{-- /container --}}

@endsection
