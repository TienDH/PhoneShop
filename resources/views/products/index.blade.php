@extends('layouts.app')
@section('title', 'Sản phẩm | TDH Phone')
@section('content')
<div class="container commerce-page">
    <div class="commerce-toolbar">
        <div><h1>{{ request('q') ? 'Kết quả tìm kiếm' : 'Sản phẩm' }}</h1><div class="text-muted small mt-2">{{ $products->total() }} sản phẩm{{ request('q') ? ' cho “'.request('q').'”' : '' }}</div></div>
    </div>
    @include('partials.alerts')
    <div class="catalog-layout">
        <aside class="catalog-filter">
            <form action="{{ route('products.index') }}" method="GET">
                <h2 class="mb-3"><i class="bi bi-sliders me-2"></i>Bộ lọc</h2>
                <label for="filter-q" class="form-label">Từ khóa / SKU</label>
                <input id="filter-q" name="q" class="form-control mb-3" value="{{ request('q') }}" maxlength="100">
                <label for="filter-category" class="form-label">Danh mục</label>
                <select id="filter-category" name="category" class="form-select mb-3"><option value="">Tất cả danh mục</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</select>
                <label for="filter-storage" class="form-label">Dung lượng</label>
                <select id="filter-storage" name="storage" class="form-select mb-3"><option value="">Tất cả dung lượng</option>@foreach($storages as $storage)<option {{ request('storage') === $storage ? 'selected' : '' }}>{{ $storage }}</option>@endforeach</select>
                <label for="filter-color" class="form-label">Màu sắc</label>
                <select id="filter-color" name="color" class="form-select mb-3"><option value="">Tất cả màu sắc</option>@foreach($colors as $color)<option {{ request('color') === $color ? 'selected' : '' }}>{{ $color }}</option>@endforeach</select>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label for="filter-min" class="form-label">Giá từ</label><input type="number" min="0" step="1000" id="filter-min" name="min_price" class="form-control" value="{{ request('min_price') }}"></div>
                    <div class="col-6"><label for="filter-max" class="form-label">Đến</label><input type="number" min="0" step="1000" id="filter-max" name="max_price" class="form-control" value="{{ request('max_price') }}"></div>
                </div>
                <label for="filter-sort" class="form-label">Sắp xếp</label>
                <select name="sort" id="filter-sort" class="form-select mb-3">@foreach(['new' => 'Mới nhất', 'best_selling' => 'Bán chạy', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần'] as $value => $label)<option value="{{ $value }}" {{ $sort === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                <div class="form-check mb-3"><input type="checkbox" name="in_stock" value="1" id="filter-stock" class="form-check-input" {{ request('in_stock') ? 'checked' : '' }}><label for="filter-stock" class="form-check-label small">Chỉ sản phẩm còn hàng</label></div>
                <div class="d-flex gap-2"><button class="btn btn-danger" type="submit"><i class="bi bi-funnel me-1"></i>Lọc</button><a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Đặt lại</a></div>
            </form>
        </aside>
        <section aria-label="Sản phẩm">
            <div class="catalog-grid">
                @forelse($products as $product)
                    @include('products._card')
                @empty
                    <div class="commerce-empty" style="grid-column: 1 / -1"><i class="bi bi-search"></i>Không có sản phẩm phù hợp.</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $products->links() }}</div>
        </section>
    </div>
</div>
@endsection
