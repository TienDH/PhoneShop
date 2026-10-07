@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Product Management</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Add Product</a>
</div>

@php
    if (!function_exists('sortUrl')) {
        function sortUrl(string $column, string $currentSort, string $currentDir, string $route): string {
            $newDir = ($currentSort === $column && $currentDir === 'desc') ? 'asc' : 'desc';
            return route($route, ['sort' => $column, 'dir' => $newDir]);
        }
    }
    if (!function_exists('sortIcon')) {
        function sortIcon(string $column, string $currentSort, string $currentDir): string {
            if ($currentSort !== $column) return '<span class="text-muted ms-1" style="font-size:.7rem;">⇅</span>';
            return $currentDir === 'asc'
                ? '<span class="text-primary ms-1" style="font-size:.7rem;">▲</span>'
                : '<span class="text-primary ms-1" style="font-size:.7rem;">▼</span>';
        }
    }
@endphp

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>
                        <a href="{{ sortUrl('name', $sort, $dir, 'admin.products.index') }}" class="text-decoration-none text-dark">
                            Product Name {!! sortIcon('name', $sort, $dir) !!}
                        </a>
                    </th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Variants</th>
                    <th>
                        <a href="{{ sortUrl('created_at', $sort, $dir, 'admin.products.index') }}" class="text-decoration-none text-dark">
                            Created {!! sortIcon('created_at', $sort, $dir) !!}
                        </a>
                    </th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>
                            @if ($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="table-image">
                            @else
                                <span class="text-muted">No image</span>
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ optional($product->category)->name }}</td>
                        <td>
                            <span class="badge bg-{{ $product->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary text-nowrap"
                                data-bs-toggle="modal" data-bs-target="#product-variants-modal"
                                data-product-id="{{ $product->id }}"
                                data-product-name="{{ $product->name }}"
                                data-variants-url="{{ route('admin.products.variants.index', $product) }}"
                                aria-label="Variant của {{ $product->name }}">
                                <i class="bi bi-layers me-1" aria-hidden="true"></i>Variant
                                <span class="badge bg-primary ms-1" data-variant-count>{{ $product->variants_count }}</span>
                            </button>
                        </td>
                        <td class="text-muted small">{{ $product->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product and its variants?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $products->links() }}
</div>
@include('admin.products.variants-modal')
@endsection

