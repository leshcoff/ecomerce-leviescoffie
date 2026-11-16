<div>
    <a href="{{ route('home') }}" wire:navigate class="mb-6 inline-flex items-center gap-1 text-sm text-primary-700 hover:underline">
        <x-heroicon-o-arrow-left class="h-4 w-4" /> Volver al catálogo
    </a>

    <div class="grid gap-10 rounded-2xl bg-white p-6 shadow-sm md:grid-cols-2">
        <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="aspect-square w-full rounded-xl object-cover">

        <div class="flex flex-col">
            <p class="text-xs uppercase tracking-wide text-primary-600">{{ $producto->categoria?->nombre }}</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-800">{{ $producto->nombre }}</h1>
            <p class="mt-1 font-mono text-xs text-gray-400">SKU: {{ $producto->sku }}</p>

            <p class="mt-4 text-gray-600">{{ $producto->descripcion_corta }}</p>

            {{-- Ambos precios, la moneda elegida destacada --}}
            <div class="mt-6 rounded-xl bg-primary-50 p-4">
                {{--<x-precio :mxn="$producto->precio_mxn" :usd="$producto->precio_usd" class="text-3xl font-bold text-primary-700" />
                <p class="mt-1 text-sm text-gray-500">
                    ${{ number_format($producto->precio_mxn, 2) }} MXN · US${{ number_format($producto->precio_usd, 2) }} USD · + IVA
                </p>--}}
                <x-precio :mxn="$producto->precio_mxn" :usd="$producto->precio_usd_actual" class="text-3xl font-bold text-primary-700" />
                <p class="mt-1 text-sm text-gray-500">
                    ${{ number_format($producto->precio_mxn, 2) }} MXN
                    @if ($producto->precio_usd_actual)
                        · US${{ number_format($producto->precio_usd_actual, 2) }} USD
                    @endif
                    · + IVA
                </p>
            </div>

            <p @class(['mt-4 text-sm font-semibold', 'text-green-700' => ! $producto->agotado(), 'text-red-600' => $producto->agotado()])>
                {{ $producto->agotado() ? 'Agotado' : "{$producto->stock} disponibles" }}
            </p>

            @unless ($producto->agotado())
                {{-- Cantidad con Alpine (sin ir al servidor) y se envía al agregar --}}
                <div x-data="{ cant: 1, max: {{ $producto->stock }} }" class="mt-6 flex items-center gap-4">
                    <div class="flex items-center rounded-full border border-primary-200">
                        <button type="button" @click="cant = Math.max(1, cant - 1)" class="px-3 py-2 text-primary-700" aria-label="Menos">
                            <x-heroicon-o-minus class="h-4 w-4" />
                        </button>
                        <input type="number" x-model.number="cant" min="1" :max="max"
                               @change="cant = Math.min(max, Math.max(1, cant || 1))"
                               class="w-14 border-0 text-center font-semibold focus:ring-0">
                        <button type="button" @click="cant = Math.min(max, cant + 1)" class="px-3 py-2 text-primary-700" aria-label="Más">
                            <x-heroicon-o-plus class="h-4 w-4" />
                        </button>
                    </div>

                    <x-button class="flex-1" @click="$wire.agregar({{ $producto->id }}, cant)"
                              wire:loading.attr="disabled" wire:target="agregar">
                        <x-heroicon-o-shopping-bag class="h-5 w-5" /> Agregar al carrito
                    </x-button>
                </div>
            @endunless

            <div class="mt-8 border-t border-gray-100 pt-6">
                <h2 class="mb-2 font-semibold text-gray-800">Descripción</h2>
                <p class="whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ $producto->descripcion_larga }}</p>
            </div>
        </div>
    </div>
</div>
