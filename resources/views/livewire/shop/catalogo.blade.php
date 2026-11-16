<div class="grid gap-8 lg:grid-cols-[240px_1fr]">
    {{-- ====== FILTROS ====== --}}
    <aside x-data="{ abierto: false }" class="lg:sticky lg:top-24 lg:self-start">
        {{-- En móvil los filtros se pliegan (Alpine) --}}
        <button type="button" @click="abierto = !abierto"
                class="mb-3 flex w-full items-center justify-between rounded-lg border border-primary-200 bg-white px-4 py-2 font-semibold text-primary-700 lg:hidden">
            Filtros <x-heroicon-o-adjustments-horizontal class="h-5 w-5" />
        </button>

        <div :class="abierto ? 'block' : 'hidden'" class="space-y-6 rounded-xl bg-white p-4 shadow-sm lg:block">
            <div>
                <label class="mb-1 block text-sm font-semibold">Buscar</label>
                <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Nombre o SKU…"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold">Categoría</p>
                <ul class="space-y-1 text-sm">
                    <li>
                        <button wire:click="$set('categoria', '')"
                            @class(['w-full rounded px-2 py-1 text-left', 'bg-primary-100 font-semibold text-primary-700' => $categoria === '', 'hover:bg-gray-100' => $categoria !== ''])>
                            Todas
                        </button>
                    </li>
                    @foreach ($categorias as $c)
                        <li wire:key="cat-{{ $c->id }}">
                            <button wire:click="$set('categoria', '{{ $c->slug }}')"
                                @class(['flex w-full justify-between rounded px-2 py-1 text-left', 'bg-primary-100 font-semibold text-primary-700' => $categoria === $c->slug, 'hover:bg-gray-100' => $categoria !== $c->slug])>
                                {{ $c->nombre }} <span class="text-gray-400">{{ $c->productos_count }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold">Precio (MXN)</p>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" wire:model.live.debounce.500ms="precioMin" placeholder="Mín"
                           class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    <span class="text-gray-400">–</span>
                    <input type="number" min="0" wire:model.live.debounce.500ms="precioMax" placeholder="Máx"
                           class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="soloConStock" class="accent-primary-600">
                Solo disponibles en stock
            </label>

            <button wire:click="limpiarFiltros" class="text-sm text-primary-700 hover:underline">Limpiar filtros</button>
        </div>
    </aside>

    {{-- ====== CONTENEDOR DE RESULTADOS ====== --}}
    <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">
                {{ $productos->total() }} producto(s)
                <span wire:loading class="ml-2 text-primary-600">Actualizando…</span>
            </p>
            <select wire:model.live="orden" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                <option value="recientes">Más recientes</option>
                <option value="precio_asc">Precio: menor a mayor</option>
                <option value="precio_desc">Precio: mayor a menor</option>
                <option value="nombre">Nombre A-Z</option>
            </select>
        </div>

        <div wire:loading.class="opacity-50" class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($productos as $p)
                <article wire:key="p-{{ $p->id }}" class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:shadow-md">
                    <a href="{{ route('products.show', $p) }}" wire:navigate class="relative block aspect-square bg-gray-100">
                        <img src="{{ $p->imagen_url }}" alt="{{ $p->nombre }}" loading="lazy" class="h-full w-full object-cover">
                        @if ($p->agotado())
                            <span class="absolute left-3 top-3 rounded-full bg-gray-900/80 px-3 py-1 text-xs font-semibold text-white">Agotado</span>
                        @endif
                    </a>

                    <div class="flex flex-1 flex-col p-4">
                        <p class="text-xs uppercase tracking-wide text-primary-600">{{ $p->categoria->nombre }}</p>
                        <a href="{{ route('products.show', $p) }}" wire:navigate class="mt-1 font-semibold text-gray-800 hover:text-primary-700">{{ $p->nombre }}</a>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $p->descripcion_corta }}</p>

                        <div class="mt-auto flex items-center justify-between pt-4">
                            {{--<x-precio :mxn="$p->precio_mxn" :usd="$p->precio_usd" class="text-lg font-bold text-primary-700" />--}}
                            <x-precio :mxn="$p->precio_mxn" :usd="$p->precio_usd_actual" class="text-lg font-bold text-primary-700" />

                            <x-button wire:click="agregar({{ $p->id }})" class="!px-3" :disabled="$p->agotado()"
                                      title="Agregar al carrito" aria-label="Agregar {{ $p->nombre }} al carrito">
                                <x-heroicon-o-shopping-bag class="h-5 w-5" />
                            </x-button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl bg-white p-10 text-center text-gray-500">
                    No encontramos productos con esos filtros.
                    <button wire:click="limpiarFiltros" class="ml-1 text-primary-700 hover:underline">Limpiar filtros</button>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $productos->links() }}</div>
    </section>
</div>
