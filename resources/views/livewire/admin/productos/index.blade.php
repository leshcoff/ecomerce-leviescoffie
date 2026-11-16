<div>
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Productos</h1>
            <p class="text-sm text-gray-500">
                @if ($tipoCambio)
                    TC USD→MXN: <strong>{{ number_format($tipoCambio['valor'], 4) }}</strong>
                @else
                    <span class="text-red-600">Tipo de cambio no disponible</span>
                @endif
            </p>
        </div>


        <x-button wire:click="crear">
            <x-heroicon-o-plus class="h-5 w-5" />
            Nuevo producto
        </x-button>

    </div>

    {{-- Filtros: búsqueda en tiempo real --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por SKU o nombre…"
               class="w-full max-w-sm rounded border border-gray-300 bg-white px-3 py-2">

        <select wire:model.live="estado" class="rounded border border-gray-300 bg-white px-3 py-2">
            <option value="">Todos</option>
            <option value="activos">Activos</option>
            <option value="inactivos">Inactivos</option>
        </select>

        <span wire:loading wire:target="buscar, estado" class="self-center text-sm text-gray-500">Buscando…</span>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-xl border border-lila-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-lila-200 text-xs font-semibold uppercase text-lila-700">
            <tr>
                <th class="px-4 py-3">SKU</th>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3 text-right">Precio USD</th>
                <th class="px-4 py-3 text-right">Precio MXN</th>
                <th class="px-4 py-3 text-right">Stock</th>
                <th class="px-4 py-3 text-center">Activo</th>
                <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
            </thead>
            <tbody class="text-gray-600" wire:loading.class="opacity-50" wire:target="buscar, estado, gotoPage, nextPage, previousPage">
            @forelse ($productos as $p)
                <tr wire:key="producto-{{ $p->id }}" class="border-t border-lila-100 transition-colors hover:bg-lila-50">
                    <td class="px-4 py-3 font-mono">{{ $p->sku }}</td>
                    <td class="px-4 py-3 font-medium text-gray-700">{{ $p->nombre }}</td>
                    <td class="px-4 py-3 text-right">${{ number_format($p->precio_usd, 2) }}</td>
                    <td class="px-4 py-3 text-right">${{ number_format($p->precio_mxn, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ $p->stock }}</td>
                    <td class="px-4 py-3 text-center">
                        <button wire:click="alternarActivo({{ $p->id }})" title="Activar/Inactivar"
                            @class(['rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-green-100 text-green-700' => $p->activo,
                                    'bg-gray-200 text-gray-600'  => ! $p->activo])>
                            {{ $p->activo ? 'Activo' : 'Inactivo' }}
                        </button>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <div class="flex justify-end gap-1">
                            <button wire:click="ver({{ $p->id }})" title="Detalle" aria-label="Ver detalle de {{ $p->nombre }}"
                                    class="rounded-lg p-1.5 text-gray-500 transition hover:bg-lila-100 hover:text-lila-700">
                                <x-heroicon-o-eye class="h-5 w-5" />
                            </button>

                            <button wire:click="editar({{ $p->id }})" title="Editar" aria-label="Editar {{ $p->nombre }}"
                                    class="rounded-lg p-1.5 text-gray-500 transition hover:bg-lila-100 hover:text-lila-700">
                                <x-heroicon-o-pencil-square class="h-5 w-5" />
                            </button>

                            <button wire:click="confirmarEliminar({{ $p->id }})" title="Eliminar" aria-label="Eliminar {{ $p->nombre }}"
                                    class="rounded-lg p-1.5 text-gray-500 transition hover:bg-red-50 hover:text-red-600">
                                <x-heroicon-o-trash class="h-5 w-5" />
                            </button>
                        </div>
                    </td>
                    {{--<td class="space-x-2 whitespace-nowrap px-4 py-3 text-right">
                        <button wire:click="ver({{ $p->id }})" class="text-gray-600 hover:underline">Detalle</button>
                        <button wire:click="editar({{ $p->id }})" class="text-indigo-600 hover:underline">Editar</button>
                        <button wire:click="confirmarEliminar({{ $p->id }})" class="text-red-600 hover:underline">Eliminar</button>
                    </td>--}}
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay productos que coincidan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $productos->links() }}</div>

    {{-- ============ MODAL: ALTA / EDICIÓN ============ --}}
    <div x-data x-show="$wire.modalFormulario" x-cloak x-transition.opacity
         @keydown.escape.window="$wire.modalFormulario = false"
         class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="$wire.modalFormulario = false"
             class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
            <h2 class="mb-4 text-xl font-bold">{{ $form->producto ? 'Editar producto' : 'Nuevo producto' }}</h2>

            <form wire:submit="guardar" class="grid gap-4 sm:grid-cols-2">
                {{-- SKU: se valida mientras escribes (incluye "único" contra la BD) --}}
                <div>
                    <label class="mb-1 block text-sm font-medium">SKU</label>
                    <input type="text" wire:model.live.debounce.400ms="form.sku"
                        @class(['w-full rounded border px-3 py-2 uppercase', 'border-red-500' => $errors->has('form.sku'), 'border-gray-300' => ! $errors->has('form.sku')])>
                    @error('form.sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Nombre</label>
                    <input type="text" wire:model.live.debounce.400ms="form.nombre"
                        @class(['w-full rounded border px-3 py-2', 'border-red-500' => $errors->has('form.nombre'), 'border-gray-300' => ! $errors->has('form.nombre')])>
                    @error('form.nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Categoría</label>
                    <select wire:model.live="form.categoria_id"
                        @class(['w-full rounded border px-3 py-2', 'border-red-500' => $errors->has('form.categoria_id'), 'border-gray-300' => ! $errors->has('form.categoria_id')])>
                        <option value="">Selecciona…</option>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    @error('form.categoria_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Descripción corta con contador en vivo (Alpine, sin ir al servidor) --}}
                <div class="sm:col-span-2" x-data="{ largo: $wire.form.descripcion_corta.length }">
                    <label class="mb-1 flex justify-between text-sm font-medium">
                        Descripción corta
                        <span class="text-xs" :class="largo > 255 ? 'text-red-600' : 'text-gray-400'" x-text="largo + '/255'"></span>
                    </label>
                    <input type="text" wire:model.live.debounce.400ms="form.descripcion_corta"
                           @input="largo = $event.target.value.length"
                        @class(['w-full rounded border px-3 py-2', 'border-red-500' => $errors->has('form.descripcion_corta'), 'border-gray-300' => ! $errors->has('form.descripcion_corta')])>
                    @error('form.descripcion_corta') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Descripción larga</label>
                    <textarea rows="3" wire:model.blur="form.descripcion_larga"
                              @class(['w-full rounded border px-3 py-2', 'border-red-500' => $errors->has('form.descripcion_larga'), 'border-gray-300' => ! $errors->has('form.descripcion_larga')])></textarea>
                    @error('form.descripcion_larga') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Precio USD</label>
                    <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="form.precio_usd"
                        @class(['w-full rounded border px-3 py-2', 'border-red-500' => $errors->has('form.precio_usd'), 'border-gray-300' => ! $errors->has('form.precio_usd')])>
                    @error('form.precio_usd') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 flex justify-between text-sm font-medium">
                        Precio MXN
                        @if ($tipoCambio)
                            {{-- Alpine calcula con el TC en caché y Livewire valida el resultado --}}
                            <button type="button" class="text-xs text-indigo-600 hover:underline"
                                    @click="$wire.set('form.precio_mxn', (parseFloat($wire.form.precio_usd || 0) * {{ $tipoCambio['valor'] }}).toFixed(2))">
                                Calcular con TC
                            </button>
                        @endif
                    </label>
                    <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="form.precio_mxn"
                        @class(['w-full rounded border px-3 py-2', 'border-red-500' => $errors->has('form.precio_mxn'), 'border-gray-300' => ! $errors->has('form.precio_mxn')])>
                    @error('form.precio_mxn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Stock</label>
                    <input type="number" step="1" min="0" wire:model.live.debounce.400ms="form.stock"
                        @class(['w-full rounded border px-3 py-2', 'border-red-500' => $errors->has('form.stock'), 'border-gray-300' => ! $errors->has('form.stock')])>
                    @error('form.stock') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Fecha de vigencia</label>
                    <input type="date" wire:model.live="form.fecha_vigencia"
                        @class(['w-full rounded border px-3 py-2', 'border-red-500' => $errors->has('form.fecha_vigencia'), 'border-gray-300' => ! $errors->has('form.fecha_vigencia')])>
                    @error('form.fecha_vigencia') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Imagen</label>
                    <div class="flex items-center gap-4">
                        @if ($form->imagen && $form->imagen->isPreviewable() && ! $errors->has('form.imagen'))
                            <img src="{{ $form->imagen->temporaryUrl() }}" class="h-20 w-20 rounded object-cover" alt="">
                        @elseif ($form->producto?->imagen)
                            <img src="{{ $form->producto->imagen_url }}" class="h-20 w-20 rounded object-cover" alt="">
                        @endif

                        <div>
                            <input type="file" accept="image/*" wire:model="form.imagen" class="text-sm">
                            <p wire:loading wire:target="form.imagen" class="text-xs text-gray-500">Subiendo…</p>
                        </div>
                    </div>
                    @error('form.imagen') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" wire:model="form.activo"> Activo
                </label>

                <div class="flex justify-end gap-3 sm:col-span-2">
                    <button type="button" @click="$wire.modalFormulario = false" class="rounded border px-4 py-2">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="guardar, form.imagen"
                            class="rounded bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="guardar">Guardar</span>
                        <span wire:loading wire:target="guardar">Guardando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ MODAL: DETALLE ============ --}}
    <div x-data x-show="$wire.detalleId" x-cloak x-transition.opacity
         @keydown.escape.window="$wire.detalleId = null"
         class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="$wire.detalleId = null" class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
            @if ($this->detalle)
                @php($d = $this->detalle)
                <div class="flex gap-4">
                    <img src="{{ $d->imagen_url }}" class="h-28 w-28 rounded object-cover" alt="">
                    <div>
                        <p class="font-mono text-xs text-gray-500">{{ $d->sku }}</p>
                        <h2 class="text-xl font-bold">{{ $d->nombre }}</h2>
                        <p class="text-sm text-gray-600">{{ $d->descripcion_corta }}</p>
                    </div>
                </div>
                <p class="mt-4 whitespace-pre-line text-sm">{{ $d->descripcion_larga }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-gray-500">Precio USD</dt><dd>${{ number_format($d->precio_usd, 2) }}</dd>
                    <dt class="text-gray-500">Precio MXN</dt><dd>${{ number_format($d->precio_mxn, 2) }}</dd>
                    @if ($tipoCambio)
                        <dt class="text-gray-500">MXN al TC de hoy</dt>
                        <dd>${{ number_format($d->precio_usd * $tipoCambio['valor'], 2) }}</dd>
                    @endif
                    <dt class="text-gray-500">Stock</dt><dd>{{ $d->stock }}</dd>
                    <dt class="text-gray-500">Vigencia</dt><dd>{{ $d->fecha_vigencia?->format('d/m/Y') ?? '—' }}</dd>
                    <dt class="text-gray-500">Estatus</dt><dd>{{ $d->activo ? 'Activo' : 'Inactivo' }}</dd>
                </dl>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="$wire.detalleId = null" class="rounded border px-4 py-2">Cerrar</button>
                    <button wire:click="editar({{ $d->id }})" @click="$wire.detalleId = null"
                            class="rounded bg-indigo-600 px-4 py-2 font-semibold text-white">Editar</button>
                </div>
            @else
                <p class="text-center text-gray-500">Cargando…</p>
            @endif
        </div>
    </div>

    {{-- ============ MODAL: CONFIRMAR ELIMINAR ============ --}}
    <div x-data x-show="$wire.eliminarId" x-cloak x-transition.opacity
         @keydown.escape.window="$wire.eliminarId = null"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="$wire.eliminarId = null" class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-xl">
            <h2 class="mb-2 text-lg font-bold">¿Eliminar producto?</h2>
            <p class="mb-6 text-sm text-gray-600">Dejará de mostrarse en el listado y en la tienda. Los pedidos que ya lo incluyen se conservan.</p>
            <div class="flex justify-center gap-3">
                <button @click="$wire.eliminarId = null" class="rounded border px-4 py-2">Cancelar</button>
                <button wire:click="eliminar" wire:loading.attr="disabled"
                        class="rounded bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">Eliminar</button>
            </div>
        </div>
    </div>
</div>
