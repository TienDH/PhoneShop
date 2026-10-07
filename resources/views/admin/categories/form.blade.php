<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $category->description ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label for="image" class="form-label">Image</label>
    <input type="file" name="image" id="image" class="form-control">
    @if (! empty($category->image))
        <div class="mt-2">
            <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="table-image">
        </div>
    @endif
</div>
