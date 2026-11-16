@props(['user', 'size' => 'h-8 w-8'])

{{-- Foto de Google; si no hay o falla al cargar, la inicial del nombre --}}
<span x-data="{ fallo: false }" {{ $attributes->class([$size, 'relative inline-flex shrink-0']) }}>
    @if ($user->avatar)
        <img src="{{ $user->avatar }}" alt="" referrerpolicy="no-referrer"
             x-show="!fallo" x-on:error="fallo = true"
             class="{{ $size }} rounded-full object-cover">
    @endif

    <span x-show="{{ $user->avatar ? 'fallo' : 'true' }}" @if ($user->avatar) x-cloak @endif
    class="{{ $size }} flex items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white">
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    </span>
</span>
