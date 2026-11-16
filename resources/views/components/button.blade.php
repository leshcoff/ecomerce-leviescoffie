@props(['variant' => 'primary'])

@php
    $variantes = [
        'primary'   => 'bg-primary-600 text-white hover:bg-primary-700 focus-visible:outline-primary-600',
        'secondary' => 'border border-primary-200 bg-white text-primary-700 hover:bg-primary-50 focus-visible:outline-primary-600',
        'danger'    => 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-600',
    ];
@endphp

<button {{ $attributes->merge([
    'type'  => 'button',
    'class' => 'inline-flex items-center justify-center gap-2 rounded-full px-6 py-2.5 font-semibold transition
                focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 '
                .$variantes[$variant],
]) }}>
    {{ $slot }}
</button>
