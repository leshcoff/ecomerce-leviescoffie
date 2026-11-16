<div>
    <h1 class="mb-6 text-2xl font-bold text-gray-800">Mis pedidos</h1>

    @if ($pedidos->isEmpty())
        <div class="rounded-2xl bg-white p-10 text-center shadow-sm">
            <x-heroicon-o-shopping-bag class="mx-auto mb-3 h-14 w-14 text-primary-200" />
            <p class="text-gray-500">Todavía no tienes pedidos.</p>
            <a href="{{ route('home') }}" wire:navigate class="mt-3 inline-block text-primary-700 hover:underline">Ir al catálogo</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($pedidos as $p)
                {{-- Alpine + plugin "collapse" (viene incluido en Livewire 3): despliega el detalle --}}
                <article wire:key="mp-{{ $p->id }}" x-data="{ abierto: false }" class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <button type="button" @click="abierto = !abierto"
                            class="flex w-full flex-wrap items-center gap-x-6 gap-y-2 px-5 py-4 text-left hover:bg-primary-50">
                        <div class="min-w-40">
                            <p class="font-mono text-sm font-bold text-primary-700">{{ $p->folio }}</p>
                            <p class="text-xs text-gray-500">{{ $p->fecha->format('d/m/Y H:i') }}</p>
                        </div>

                        <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-green-100 text-green-700' => $p->estatus === 'pagado',
                            'bg-amber-100 text-amber-700' => $p->estatus === 'pendiente',
                            'bg-gray-200 text-gray-600'   => $p->estatus === 'cancelado'])>
                            {{ ucfirst($p->estatus) }}
                        </span>

                        <p class="text-sm text-gray-500">{{ $p->detalles->sum('cantidad') }} producto(s)</p>

                        <div class="ml-auto text-right">
                            <p class="font-bold text-gray-800">${{ number_format($p->total, 2) }} MXN</p>
                            @if ($p->total_usd)
                                <p class="text-xs text-gray-500">US${{ number_format($p->total_usd, 2) }}</p>
                            @endif
                        </div>

                        <x-heroicon-o-chevron-down class="h-5 w-5 text-primary-700 transition" ::class="abierto && 'rotate-180'" />
                    </button>

                    <div x-show="abierto" x-collapse x-cloak class="border-t border-primary-100 px-5 py-4">
                        <ul class="space-y-3">
                            @foreach ($p->detalles as $l)
                                <li class="flex items-center gap-3">
                                    <img src="{{ $l->producto?->imagen_url }}" alt="" class="h-12 w-12 rounded-lg object-cover">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-700">{{ $l->producto?->nombre ?? 'Producto no disponible' }}</p>
                                        <p class="text-xs text-gray-500">{{ $l->cantidad }} × ${{ number_format($l->precio_mxn, 2) }}</p>
                                    </div>
                                    <p class="text-sm text-gray-700">${{ number_format($l->precio_mxn * $l->cantidad, 2) }}</p>
                                </li>
                            @endforeach
                        </ul>

                        <dl class="mt-4 space-y-1 border-t border-primary-100 pt-3 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Subtotal</dt><dd>${{ number_format($p->subtotal, 2) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">IVA (16 %)</dt><dd>${{ number_format($p->impuestos, 2) }}</dd></div>
                            <div class="flex justify-between font-bold text-primary-700"><dt>Total</dt><dd>${{ number_format($p->total, 2) }} MXN</dd></div>
                            @if ($p->pago_marca)
                                <div class="flex justify-between text-xs text-gray-500">
                                    <dt>Pago</dt><dd>{{ $p->pago_marca }} •••• {{ $p->pago_ultimos4 }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $pedidos->links() }}</div>
    @endif
</div>
