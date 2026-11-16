{{--@props(['mxn', 'usd'])

--}}{{-- Muestra el precio en la moneda elegida en el header ($store.moneda) --}}{{--
<span x-data {{ $attributes }}>
    <span x-show="$store.moneda.actual !== 'USD'">${{ number_format($mxn, 2) }} <small class="text-xs font-normal">MXN</small></span>
    <span x-show="$store.moneda.actual === 'USD'" x-cloak>US${{ number_format($usd, 2) }} <small class="text-xs font-normal">USD</small></span>
</span>--}}


@props(['mxn', 'usd' => null])

{{-- Muestra el precio en la moneda elegida en el header ($store.moneda).
     Si no hay tipo de cambio (usd = null), siempre muestra MXN. --}}
<span x-data {{ $attributes }}>
    @if (is_null($usd))
        <span>${{ number_format($mxn, 2) }} <small class="text-xs font-normal">MXN</small></span>
    @else
        <span x-show="$store.moneda.actual !== 'USD'">${{ number_format($mxn, 2) }} <small class="text-xs font-normal">MXN</small></span>
        <span x-show="$store.moneda.actual === 'USD'" x-cloak>US${{ number_format($usd, 2) }} <small class="text-xs font-normal">USD</small></span>
    @endif
</span>
