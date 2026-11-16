{{-- Alpine controla abrir/cerrar; Livewire, el contenido --}}
<div x-data="{ abierto: false }"
     @abrir-carrito.window="abierto = true"
     @keydown.escape.window="abierto = false">

    {{-- Fondo oscuro --}}
    <div x-show="abierto" x-cloak x-transition.opacity @click="abierto = false"
         class="fixed inset-0 z-40 bg-black/40"></div>

    {{-- Panel que entra desde la derecha --}}
    <aside x-show="abierto" x-cloak
           x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl"
           role="dialog" aria-label="Carrito de compras">

        {{-- Encabezado --}}
        <div class="flex items-center justify-between border-b border-primary-100 px-5 py-4">
            <h2 class="text-lg font-bold text-primary-700">
                Tu carrito <span class="text-sm font-normal text-gray-500">({{ $lineas->sum('cantidad') }})</span>
            </h2>
            <button type="button" @click="abierto = false" class="rounded-full p-1 text-gray-500 hover:bg-primary-50" aria-label="Cerrar">
                <x-heroicon-o-x-mark class="h-6 w-6" />
            </button>
        </div>

        {{-- Productos --}}
        <div class="flex-1 overflow-y-auto px-5 py-4" wire:loading.class="opacity-60">
            @forelse ($lineas as $l)
                <div wire:key="linea-{{ $l->producto->id }}" class="flex gap-4 border-b border-gray-100 py-4 last:border-0">
                    <img src="{{ $l->producto->imagen_url }}" alt="" class="h-20 w-20 rounded-lg bg-gray-50 object-cover">

                    <div class="flex flex-1 flex-col">
                        <div class="flex justify-between gap-2">
                            <a href="{{ route('products.show', $l->producto) }}" wire:navigate
                               class="font-semibold text-gray-800 hover:text-primary-700">{{ $l->producto->nombre }}</a>
                            <button wire:click="quitar({{ $l->producto->id }})" title="Eliminar" aria-label="Eliminar {{ $l->producto->nombre }}"
                                    class="text-gray-400 hover:text-red-600">
                                <x-heroicon-o-trash class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="font-mono text-xs text-gray-400">{{ $l->producto->sku }}</p>

                        <div class="mt-auto flex items-center justify-between pt-2">
                            {{-- Cantidad: −/+ van al servidor, que respeta el stock --}}
                            <div class="flex items-center rounded-full border border-primary-200 text-sm">
                                <button wire:click="cambiarCantidad({{ $l->producto->id }}, {{ $l->cantidad - 1 }})"
                                        @disabled($l->cantidad <= 1) class="px-2.5 py-1 text-primary-700 disabled:opacity-30" aria-label="Menos">
                                    <x-heroicon-o-minus class="h-4 w-4" />
                                </button>
                                <input type="number" min="1" max="{{ $l->producto->stock }}" value="{{ $l->cantidad }}"
                                       @change="$wire.cambiarCantidad({{ $l->producto->id }}, parseInt($event.target.value) || 1)"
                                       class="w-12 border-0 p-0 text-center font-semibold focus:ring-0">
                                <button wire:click="cambiarCantidad({{ $l->producto->id }}, {{ $l->cantidad + 1 }})"
                                        @disabled($l->cantidad >= $l->producto->stock) class="px-2.5 py-1 text-primary-700 disabled:opacity-30" aria-label="Más">
                                    <x-heroicon-o-plus class="h-4 w-4" />
                                </button>
                            </div>

                            <x-precio :mxn="$l->importe_mxn" :usd="$l->importe_usd" class="font-bold text-primary-700" />
                        </div>

                        @if ($l->cantidad >= $l->producto->stock)
                            <p class="mt-1 text-xs text-amber-600">Máximo disponible: {{ $l->producto->stock }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center text-center text-gray-500">
                    <x-heroicon-o-shopping-bag class="mb-3 h-14 w-14 text-primary-200" />
                    <p>Tu carrito está vacío.</p>
                    <button @click="abierto = false" class="mt-2 text-primary-700 hover:underline">Seguir comprando</button>
                </div>
            @endforelse
        </div>

        {{-- Totales y acciones --}}
        @if ($lineas->isNotEmpty())
            <div class="space-y-2 border-t border-primary-100 bg-primary-50 px-5 py-4 text-sm">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <x-precio :mxn="$totales['mxn']['subtotal']" :usd="$totales['usd']['subtotal']" />
                </div>
                <div class="flex justify-between">
                    <span>IVA (16 %)</span>
                    <x-precio :mxn="$totales['mxn']['iva']" :usd="$totales['usd']['iva']" />
                </div>
                <div class="flex justify-between text-base font-bold text-primary-700">
                    <span>Total</span>
                    <x-precio :mxn="$totales['mxn']['total']" :usd="$totales['usd']['total']" />
                </div>

                @if (Route::has('checkout'))
                    <a href="{{ route('checkout') }}" wire:navigate
                       class="mt-3 flex w-full items-center justify-center rounded-full bg-primary-600 py-3 font-semibold text-white hover:bg-primary-700">
                        Ir a pagar
                    </a>
                @else
                    <x-button class="mt-3 w-full" disabled>Ir a pagar (próximamente)</x-button>
                @endif

                <button wire:click="vaciar" wire:confirm="¿Vaciar el carrito?"
                        class="w-full pt-1 text-center text-xs text-gray-500 hover:text-red-600">Vaciar carrito</button>
            </div>
        @endif
    </aside>
</div>
