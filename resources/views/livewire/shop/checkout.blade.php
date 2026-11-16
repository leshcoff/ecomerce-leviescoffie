<div>
    <h1 class="mb-6 text-2xl font-bold text-gray-800">Checkout</h1>

    @if ($lineas->isEmpty() && ! $pedidoId)
        <div class="rounded-2xl bg-white p-10 text-center shadow-sm">
            <x-heroicon-o-shopping-bag class="mx-auto mb-3 h-14 w-14 text-primary-200" />
            <p class="text-gray-500">Tu carrito está vacío.</p>
            <a href="{{ route('home') }}" wire:navigate class="mt-3 inline-block text-primary-700 hover:underline">Ir al catálogo</a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_400px]">
            {{-- ================= RESUMEN / COTIZACIÓN ================= --}}
            <section class="space-y-4">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-primary-100 px-5 py-3">
                        <h2 class="font-semibold text-primary-700">Tu pedido</h2>
                        <button type="button" @click="$dispatch('abrir-carrito')" class="text-sm text-primary-700 hover:underline">Editar carrito</button>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-primary-50 text-xs uppercase text-primary-700">
                        <tr>
                            <th class="px-5 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-center">Cant.</th>
                            <th class="px-3 py-2 text-right">Precio</th>
                            <th class="px-5 py-2 text-right">Importe MXN</th>
                            <th class="px-5 py-2 text-right">Importe USD</th>
                        </tr>
                        </thead>
                        <tbody class="text-gray-600">
                        @foreach ($lineas as $l)
                            <tr wire:key="co-{{ $l->producto->id }}" class="border-t border-primary-100">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $l->producto->imagen_url }}" alt="" class="h-12 w-12 rounded-lg object-cover">
                                        <div>
                                            <p class="font-medium text-gray-700">{{ $l->producto->nombre }}</p>
                                            <p class="font-mono text-xs text-gray-400">{{ $l->producto->sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">{{ $l->cantidad }}</td>
                                <td class="px-3 py-3 text-right">${{ number_format($l->producto->precio_mxn, 2) }}</td>
                                <td class="px-5 py-3 text-right">${{ number_format($l->importe_mxn, 2) }}</td>
                                <td class="px-5 py-3 text-right">{{ $l->importe_usd !== null ? 'US$'.number_format($l->importe_usd, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totales en ambas monedas, lado a lado --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (['mxn' => 'MXN', 'usd' => 'USD'] as $clave => $moneda)
                        <div class="rounded-2xl bg-white p-5 shadow-sm">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-primary-600">Total en {{ $moneda }}</p>
                            @if ($totales[$clave])
                                <dl class="space-y-1 text-sm">
                                    <div class="flex justify-between"><dt>Subtotal</dt><dd>${{ number_format($totales[$clave]['subtotal'], 2) }}</dd></div>
                                    <div class="flex justify-between"><dt>IVA (16 %)</dt><dd>${{ number_format($totales[$clave]['iva'], 2) }}</dd></div>
                                    <div class="flex justify-between border-t border-primary-100 pt-2 text-lg font-bold text-primary-700">
                                        <dt>Total</dt><dd>${{ number_format($totales[$clave]['total'], 2) }} {{ $moneda }}</dd>
                                    </div>
                                </dl>
                            @else
                                <p class="text-sm text-gray-400">Tipo de cambio no disponible.</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($tipoCambio)
                    <p class="text-xs text-gray-400">Tipo de cambio aplicado: 1 USD = ${{ number_format($tipoCambio, 4) }} MXN. El cargo se realiza en MXN.</p>
                @endif

                @error('carrito')
                <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">{{ $message }}</p>
                @enderror
            </section>

            {{-- ================= DATOS DE PAGO ================= --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <form wire:submit="pagar" class="space-y-4 rounded-2xl bg-white p-6 shadow-sm">
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Correo del comprador</label>

                        <input type="email" wire:model.blur="correo" autocomplete="email" placeholder="tucorreo@ejemplo.com"
                            @disabled($clientSecret)
                            @class(['w-full rounded-lg border px-3 py-2 disabled:bg-gray-100', 'border-red-500' => $errors->has('correo'), 'border-gray-300' => ! $errors->has('correo')])>


                        @error('correo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-400">Te enviaremos aquí la confirmación del pedido.</p>
                    </div>

                    <hr class="border-primary-100">

                    {{-- Campos de tarjeta: solo con el simulador. Con Stripe se capturan en su página --}}
                    @unless ($usaStripe)
                        <hr class="border-primary-100">

                        <div>
                            <label class="mb-1 block text-sm font-semibold">Titular de la tarjeta</label>
                            <input type="text" wire:model.blur="titular" autocomplete="cc-name"
                                @class(['w-full rounded-lg border px-3 py-2', 'border-red-500' => $errors->has('titular'), 'border-gray-300' => ! $errors->has('titular')])>
                            @error('titular') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold">Número de tarjeta</label>
                            {{-- Alpine agrupa los dígitos de 4 en 4 mientras escribes --}}
                            <input type="text" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000"
                                   wire:model.blur="tarjeta"
                                   @input="$el.value = $el.value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim()"
                                @class(['w-full rounded-lg border px-3 py-2 font-mono tracking-wider', 'border-red-500' => $errors->has('tarjeta'), 'border-gray-300' => ! $errors->has('tarjeta')])>
                            @error('tarjeta') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-sm font-semibold">Vencimiento</label>
                                {{-- Alpine pone la diagonal: 1228 → 12/28 --}}
                                <input type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/AA"
                                       wire:model.blur="vencimiento"
                                       @input="let v = $el.value.replace(/\D/g, '').slice(0, 4); $el.value = v.length > 2 ? v.slice(0, 2) + '/' + v.slice(2) : v"
                                    @class(['w-full rounded-lg border px-3 py-2 font-mono', 'border-red-500' => $errors->has('vencimiento'), 'border-gray-300' => ! $errors->has('vencimiento')])>
                                @error('vencimiento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-semibold">CVV</label>
                                <input type="password" inputmode="numeric" autocomplete="cc-csc" maxlength="4"
                                       wire:model.blur="cvv"
                                    @class(['w-full rounded-lg border px-3 py-2 font-mono', 'border-red-500' => $errors->has('cvv'), 'border-gray-300' => ! $errors->has('cvv')])>
                                @error('cvv') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endunless

                    @if ($driver === 'stripe_card' && $clientSecret)
                        {{-- 2ª etapa: formulario de tarjeta de Stripe dentro del sitio.
                             wire:ignore → Livewire no toca este bloque al redibujar (si no, borraría el iframe de Stripe).
                             wire:key    → si se prepara un pago nuevo, Alpine crea el componente desde cero. --}}
                        <div wire:ignore wire:key="pago-{{ $clientSecret }}"
                             x-data="pagoTarjeta(@js($clientSecret), @js($stripeKey), @js(route('checkout')))"
                             class="space-y-3">

                            <div x-ref="tarjeta" class="min-h-24"></div>
                            <p x-show="!listo" class="text-center text-sm text-gray-400">Cargando formulario seguro…</p>

                            <p x-show="error" x-text="error" x-cloak class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600"></p>

                            <x-button type="button" class="w-full !py-3 text-base" @click="pagar()" x-bind:disabled="!listo || procesando">
                                <span x-show="!procesando">Pagar ${{ number_format($totales['mxn']['total'], 2) }} MXN</span>
                                <span x-show="procesando" x-cloak>Procesando pago…</span>
                            </x-button>

                            <button type="button" wire:click="cancelarTarjeta" x-show="!procesando"
                                    class="w-full text-center text-xs text-gray-500 hover:text-primary-700">
                                Cambiar datos
                            </button>
                        </div>
                    @else
                        {{-- 1ª etapa (o drivers mock / stripe): botón normal de Livewire --}}
                        <x-button type="submit" class="w-full !py-3 text-base" wire:loading.attr="disabled" wire:target="pagar">
                            <span wire:loading.remove wire:target="pagar">
                                @if ($driver === 'stripe_card')
                                    Continuar al pago
                                @else
                                    {{ $usaStripe ? 'Pagar con Stripe' : 'Pagar' }} ${{ number_format($totales['mxn']['total'], 2) }} MXN
                                @endif
                            </span>
                                                <span wire:loading wire:target="pagar">
                                {{ $usaStripe ? 'Conectando con Stripe…' : 'Validando con el banco…' }}
                            </span>
                        </x-button>
                    @endif


                    <p class="flex items-center justify-center gap-1 text-xs text-gray-400">
                        <x-heroicon-o-lock-closed class="h-3.5 w-3.5" />
                        {{ $usaStripe ? 'Pago seguro con Stripe (modo prueba)' : 'Pago simulado · no se guarda tu tarjeta' }}
                    </p>

                    {{-- Ayuda: tarjetas de prueba --}}
                    <details class="rounded-lg bg-primary-50 p-3 text-xs">
                        <summary class="cursor-pointer font-semibold text-primary-700">Tarjetas de prueba</summary>
                        @if ($usaStripe)
                            <ul class="mt-2 space-y-1 text-gray-600">
                                <li><span class="font-mono">4242 4242 4242 4242</span> — aprobada</li>
                                <li><span class="font-mono">4000 0000 0000 0002</span> — rechazada</li>
                                <li><span class="font-mono">4000 0025 0000 3155</span> — pide 3D Secure</li>
                                <li class="text-gray-400">Fecha futura cualquiera y CVC de 3 dígitos cualquiera.</li>
                            </ul>
                        @else
                            <ul class="mt-2 space-y-1">
                                @foreach ($pruebas as [$numero, $resultado])
                                    <li>
                                        <button type="button" class="font-mono text-primary-700 hover:underline"
                                                @click="$wire.set('tarjeta', '{{ $numero }}'); $wire.set('vencimiento', '12/30'); $wire.set('cvv', '123')">
                                            {{ $numero }}
                                        </button>
                                        <span class="text-gray-500">— {{ $resultado }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </details>

                </form>
            </aside>
        </div>
    @endif

    {{-- ================= MODAL: PAGO RECHAZADO ================= --}}
    <div x-data x-show="$wire.rechazo" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="$wire.rechazo = null" class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
            <x-heroicon-o-x-circle class="mx-auto mb-3 h-14 w-14 text-red-500" />
            <h2 class="text-lg font-bold text-gray-800">Pago no completado</h2>
            <p class="mt-2 text-sm text-gray-600" x-text="$wire.rechazo"></p>
            <p class="mt-1 text-xs text-gray-400">No se realizó ningún cargo.</p>
            <x-button class="mt-5 w-full" @click="$wire.rechazo = null">Entendido</x-button>
        </div>
    </div>

    {{-- ================= MODAL: PEDIDO CONFIRMADO ================= --}}
    @if ($this->pedido)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <div class="text-center">
                    <x-heroicon-o-check-circle class="mx-auto mb-2 h-14 w-14 text-green-500" />
                    <h2 class="text-xl font-bold text-gray-800">¡Pedido confirmado!</h2>
                    <p class="mt-1 font-mono text-lg text-primary-700">{{ $this->pedido->folio }}</p>
                </div>

                <dl class="mt-5 space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Correo</dt><dd>{{ $this->pedido->correo }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Pago</dt><dd>{{ $this->pedido->pago_marca }} •••• {{ $this->pedido->pago_ultimos4 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Autorización</dt><dd class="font-mono">{{ $this->pedido->pago_referencia }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Productos</dt><dd>{{ $this->pedido->detalles->sum('cantidad') }}</dd></div>
                    <div class="flex justify-between border-t border-primary-100 pt-2 font-bold text-primary-700">
                        <dt>Total</dt>
                        <dd class="text-right">
                            ${{ number_format($this->pedido->total, 2) }} MXN
                            @if ($this->pedido->total_usd)
                                <span class="block text-xs font-normal text-gray-500">US${{ number_format($this->pedido->total_usd, 2) }} USD</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <a href="{{ route('home') }}" wire:navigate
                   class="mt-6 flex w-full items-center justify-center rounded-full bg-primary-600 py-3 font-semibold text-white hover:bg-primary-700">
                    Seguir comprando
                </a>
            </div>
        </div>
    @endif
</div>
