<div class="space-y-6">
    <h1 class="text-2xl font-bold">Reportes</h1>

    {{-- ================= 1. FILTROS (una fila) ================= --}}
    <div class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm">
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-500">Desde</label>
            <input type="date" wire:model.live="desde" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-gray-500">Hasta</label>
            <input type="date" wire:model.live="hasta" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
        </div>

        <div class="flex gap-1">
            @foreach (['7d' => '7 días', '30d' => '30 días', 'mes' => 'Este mes', '6m' => '6 meses'] as $clave => $texto)
                <button type="button" wire:click="rango('{{ $clave }}')"
                        class="rounded-full border border-primary-200 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-50">
                    {{ $texto }}
                </button>
            @endforeach
        </div>

        <div class="ml-auto">
            <label class="mb-1 block text-xs font-semibold text-gray-500">Agrupar por</label>
            <select wire:model.live="agrupar" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
                <option value="dia">Día</option>
                <option value="semana">Semana</option>
                <option value="mes">Mes</option>
            </select>
        </div>

        <span wire:loading class="text-xs text-primary-600">Actualizando…</span>
    </div>

    {{-- ================= 2. INDICADORES ================= --}}
    @php($k = $this->indicadores)
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['Ventas sin IVA', '$'.number_format($k['ventas'], 2)],
            ['Total cobrado (con IVA)', '$'.number_format($k['total'], 2)],
            ['Pedidos pagados', number_format($k['pedidos'])],
            ['Ticket promedio', '$'.number_format($k['ticket'], 2)],
            ['Unidades vendidas', number_format($k['unidades'])],
        ] as [$titulo, $valor])
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-800">{{ $valor }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        {{-- ================= 3. GRÁFICA ================= --}}
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-800">Ventas por periodo</h2>
            <p class="mb-4 text-xs text-gray-500">Ventas sin IVA (MXN) de pedidos pagados</p>

            {{-- wire:ignore: Livewire NO toca este bloque (si lo redibujara, borraría el canvas).
                 Los datos nuevos llegan con el evento "grafica-actualizada". --}}
            <div wire:ignore
                 x-data="graficaVentas(@js($this->serie))"
                 @grafica-actualizada.window="actualizar($event.detail)"
                 class="relative h-72">
                <canvas x-ref="lienzo" role="img" aria-label="Gráfica de ventas por periodo"></canvas>
            </div>

            {{-- Vista en tabla: accesibilidad y para ver los números exactos --}}
            <details class="mt-4 text-sm">
                <summary class="cursor-pointer text-xs text-primary-700">Ver como tabla</summary>
                <table class="mt-2 w-full text-xs">
                    <thead class="text-gray-500"><tr><th class="py-1 text-left">Periodo</th><th class="py-1 text-right">Ventas</th></tr></thead>
                    <tbody class="text-gray-600">
                    @foreach ($this->serie['etiquetas'] as $i => $etiqueta)
                        <tr class="border-t border-gray-100">
                            <td class="py-1">{{ $etiqueta }}</td>
                            <td class="py-1 text-right">${{ number_format($this->serie['valores'][$i], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </details>
        </section>

        {{-- ================= 4. TOP 3 ================= --}}
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-800">Top 3 más vendidos</h2>
            <p class="mb-4 text-xs text-gray-500">Por unidades, en el periodo</p>

            @php($maximo = max(1, (int) $this->top3->max('unidades')))
            <ol class="space-y-4">
                @forelse ($this->top3 as $i => $t)
                    <li wire:key="top-{{ $t->producto_id }}" class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-700">{{ $i + 1 }}</span>
                        <img src="{{ $t->producto?->imagen_url }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-700">{{ $t->producto?->nombre ?? 'Producto eliminado' }}</p>
                            {{-- Barra proporcional al #1 (100 %) --}}
                            <div class="mt-1 h-1.5 rounded-full bg-gray-100">
                                <div class="h-1.5 rounded-full bg-primary-500" style="width: {{ round($t->unidades / $maximo * 100) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ $t->unidades }} uds · ${{ number_format($t->importe, 2) }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-400">Sin ventas en este periodo.</li>
                @endforelse
            </ol>
        </section>
    </div>

    {{-- ================= 5. PEDIDOS ================= --}}
    <section class="rounded-xl bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-primary-100 p-4">
            <h2 class="mr-auto font-semibold text-gray-800">
                Pedidos <span class="text-sm font-normal text-gray-400">({{ $pedidos->total() }})</span>
            </h2>
            <select wire:model.live="estatus" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
                <option value="">Todos los estatus</option>
                <option value="pagado">Pagado</option>
                <option value="pendiente">Pendiente</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Folio o correo…"
                   class="w-56 rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
        </div>


        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-primary-200 text-xs font-semibold uppercase text-primary-700">
                <tr>
                    <th class="px-4 py-3">Folio</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Correo</th>
                    <th class="px-4 py-3 text-right">Uds.</th>
                    <th class="px-4 py-3 text-right">Total MXN</th>
                    <th class="px-4 py-3 text-right">Total USD</th>
                    <th class="px-4 py-3 text-center">Estatus</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="text-gray-600" wire:loading.class="opacity-50">
                @forelse ($pedidos as $p)
                    <tr wire:key="ped-{{ $p->id }}" class="border-t border-primary-100 hover:bg-primary-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $p->folio }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $p->fecha->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $p->correo }}</td>
                        <td class="px-4 py-3 text-right">{{ $p->unidades }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format($p->total, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ $p->total_usd ? 'US$'.number_format($p->total_usd, 2) : '—' }}</td>
                        <td class="px-4 py-3 text-center">
                                <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-green-100 text-green-700' => $p->estatus === 'pagado',
                                    'bg-amber-100 text-amber-700' => $p->estatus === 'pendiente',
                                    'bg-gray-200 text-gray-600'   => $p->estatus === 'cancelado'])>
                                    {{ ucfirst($p->estatus) }}
                                </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="$set('detalleId', {{ $p->id }})" title="Ver detalle"
                                    aria-label="Ver detalle del pedido {{ $p->folio }}"
                                    class="rounded-lg p-1.5 text-gray-500 hover:bg-primary-100 hover:text-primary-700">
                                <x-heroicon-o-eye class="h-5 w-5" />
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No hay pedidos en este periodo.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $pedidos->links() }}</div>

    </section>

</div>
