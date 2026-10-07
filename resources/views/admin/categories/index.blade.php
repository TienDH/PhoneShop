@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Category Management</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Add Category</a>
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
                        <a href="{{ sortUrl('name', $sort, $dir, 'admin.categories.index') }}" class="text-decoration-none text-dark">
                            Name {!! sortIcon('name', $sort, $dir) !!}
                        </a>
                    </th>
                    <th>Slug</th>
                    <th>
                        <a href="{{ sortUrl('products_count', $sort, $dir, 'admin.categories.index') }}" class="text-decoration-none text-dark">
                            Products {!! sortIcon('products_count', $sort, $dir) !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ sortUrl('created_at', $sort, $dir, 'admin.categories.index') }}" class="text-decoration-none text-dark">
                            Created {!! sortIcon('created_at', $sort, $dir) !!}
                        </a>
                    </th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->id }}</td>
                        <td>
                            @if ($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="table-image">
                            @else
                                <span class="text-muted">No image</span>
                            @endif
                        </td>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->slug }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td class="text-muted small">{{ $category->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No categories found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $categories->links() }}
</div>
@endsection

