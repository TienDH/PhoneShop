@foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $color)
    @if(session($key))
        <div class="alert alert-{{ $color }}" role="status">{{ session($key) }}</div>
    @endif
@endforeach
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
