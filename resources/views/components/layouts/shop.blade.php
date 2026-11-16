<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Tienda' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{-- Stripe.js: solo con el driver "stripe_card" (tarjeta dentro del sitio) --}}
    @if (config('services.pago.driver') === 'stripe_card')
        <script src="https://js.stripe.com/v3/"></script>
    @endif
</head>
<body class="min-h-screen bg-gray-50 text-gray-700">
<header class="sticky top-0 z-30 border-b border-primary-100 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
        <a href="{{ route('home') }}" wire:navigate class="text-xl font-bold text-primary-700">{{ config('app.name') }}</a>

        <div class="flex items-center gap-4">
            {{-- Moneda: 100 % Alpine --}}
            <div x-data class="flex border  rounded-full border-primary-200 p-0.5 text-sm">
                <template x-for="m in ['MXN', 'USD']" :key="m">
                    <button type="button" x-text="m" @click="$store.moneda.cambiar(m)"
                            :class="$store.moneda.actual === m ? 'bg-primary-600 text-white' : 'text-primary-700 hover:bg-primary-50'"
                            class="rounded-full px-3 py-1 font-semibold transition">
                    </button>
                </template>
            </div>

            {{-- Contador (Alpine) + abre el panel del carrito con un evento del navegador --}}
            <button type="button"
                    x-data="{ cantidad: {{ app(\App\Services\CarritoService::class)->cantidadTotal() }} }"
                    @carrito-actualizado.window="cantidad = $event.detail.cantidad"
                    @click="$dispatch('abrir-carrito')"
                    class="relative rounded-full p-2 text-primary-700 hover:bg-primary-50" aria-label="Abrir carrito">
                <x-heroicon-o-shopping-bag class="h-7 w-7" />
                <span x-show="cantidad > 0" x-text="cantidad" x-cloak
                      class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1 text-xs font-bold text-white"></span>
            </button>

            {{-- ===== Cuenta del cliente ===== --}}
            @auth
                <div x-data="{ abierto: false }" class="relative"
                     @click.outside="abierto = false" @keydown.escape.window="abierto = false">
                    <button type="button" @click="abierto = !abierto"
                            class="flex items-center gap-2 rounded-full p-1 hover:bg-primary-50" aria-label="Mi cuenta">
                        <x-avatar :user="auth()->user()" />
                        <x-heroicon-o-chevron-down class="h-4 w-4 text-primary-700" />
                    </button>

                    <div x-show="abierto" x-cloak x-transition.origin.top.right
                         class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-primary-100 bg-white shadow-lg">
                        <div class="border-b border-primary-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                        </div>

                        @if (Route::has('account.orders'))
                            <a href="{{ route('account.orders') }}" wire:navigate
                               class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-primary-50">
                                <x-heroicon-o-shopping-bag class="h-4 w-4" /> Mis pedidos
                            </a>
                        @endif

                        @if (auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-primary-50">
                                <x-heroicon-o-cog-6-tooth class="h-4 w-4" /> Panel de administración
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="border-t border-primary-100">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                <x-heroicon-o-arrow-right-on-rectangle class="h-4 w-4" /> Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            @else
                {{-- Sin wire:navigate: es una redirección a Google, no una página del sitio --}}
                <a href="{{ route('social.redirect', 'google') }}"
                   class="flex items-center gap-2 rounded-full border border-primary-200 px-3 py-1.5 text-sm font-semibold text-primary-700 hover:bg-primary-50">
                    <x-heroicon-o-user-circle class="h-5 w-5" />
                    <span class="hidden sm:inline">Entrar con Google</span>
                </a>
            @endauth


        </div>
    </div>
</header>

{{-- Mensajes que llegan con ->with('error', ...) (por ejemplo, desde el login con Google) --}}
@if (session('error'))
    <div x-data="{ visible: true }" x-show="visible" x-transition
         class="mx-auto mt-4 flex max-w-6xl items-start justify-between gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
        <span>{{ session('error') }}</span>
        <button type="button" @click="visible = false" aria-label="Cerrar">
            <x-heroicon-o-x-mark class="h-5 w-5" />
        </button>
    </div>
@endif


@php
    // Mismo valor que usan los precios de esta página (singleton + memoria): no vuelve a leer Redis
    try {
        $tipoCambio = app(\App\Services\TipoCambioService::class)->usdMxn();
        $venceA = \Illuminate\Support\Carbon::parse($tipoCambio['consultado'])
            ->addSeconds(config('services.tipo_cambio.ttl'));
    } catch (\Throwable) {
        $tipoCambio = null;
    }
@endphp
<main class="mx-auto max-w-6xl px-4 py-8">
    {{ $slot }}
</main>

<footer class="py-8 text-center text-xs text-gray-400">© {{ date('Y') }}
    {{ config('app.name') }} · Escoffie Leví - Carrito demo
    <p>
        @if ($tipoCambio)
            Tipo de cambio: <strong class="text-gray-500">1 USD = ${{ number_format($tipoCambio['valor'], 4) }} MXN</strong>
            · consultado {{ \Illuminate\Support\Carbon::parse($tipoCambio['consultado'])->format('d/m/Y H:i') }}
            · vigente hasta {{ $venceA->format('H:i') }}
            · origen:
            <span @class([
                'rounded px-1.5 py-0.5 font-mono',
                'bg-green-100 text-green-700' => $tipoCambio['origen'] === 'redis',
                'bg-blue-100 text-blue-700'   => $tipoCambio['origen'] === 'api',
                'bg-amber-100 text-amber-700' => $tipoCambio['origen'] === 'respaldo',
            ])>{{ $tipoCambio['origen'] }}</span>
        @else
            <span class="text-red-500">Tipo de cambio no disponible · precios solo en MXN</span>
        @endif
    </p>
</footer>

{{-- Panel lateral carrito (componente Livewire de la tienda) --}}
<livewire:shop.carrito />

{{-- Toast: escucha dispatch('notify') de Livewire --}}
<div x-data="{ mostrar: false, mensaje: '', timer: null }"
     @notify.window="mensaje = $event.detail.mensaje; mostrar = true; clearTimeout(timer); timer = setTimeout(() => mostrar = false, 3000)"
     x-show="mostrar" x-cloak x-transition
     class="fixed bottom-4 right-4 z-50 max-w-sm rounded-xl bg-gray-900 px-4 py-3 text-sm text-white shadow-lg">
    <span x-text="mensaje"></span>
</div>

@livewireScripts
</body>
</html>
