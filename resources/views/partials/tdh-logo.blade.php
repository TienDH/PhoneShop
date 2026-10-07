@php
    $logoHref = $href ?? route('front.home');
    $logoClass = $class ?? 'tdh-logo';
    $logoLabel = $label ?? 'TDH Phone';
@endphp

<a href="{{ $logoHref }}" class="{{ $logoClass }}" aria-label="{{ $logoLabel }}">
    <span class="tdh-logo-mark">
        <i class="bi bi-phone-fill"></i>
        <span class="tdh-logo-signal"></span>
    </span>
    <span class="tdh-logo-copy">
        <strong>TDH</strong>
        <span>Phone</span>
    </span>
</a>
