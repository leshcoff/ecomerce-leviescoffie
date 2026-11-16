<?php

namespace App\Livewire\Shop;

use App\Models\Pedido;
use App\Models\Producto;
use App\Services\CarritoService;
use App\Services\PagoSimuladoService;
use App\Services\StripePagoService;
use App\Services\TipoCambioService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.shop')]
#[Title('Checkout')]
class Checkout extends Component
{
    public string $correo = '';

    // Solo para el driver "mock" (con Stripe, la tarjeta se captura en la página de Stripe)
    public string $titular = '';
    public string $tarjeta = '';
    public string $vencimiento = '';
    public string $cvv = '';

    public ?string $rechazo = null;
    public ?int $pedidoId = null;

    /** stripe_card: cuando tiene valor, la vista muestra el formulario de tarjeta de Stripe */
    public ?string $clientSecret = null;

    /** Al volver de Stripe: ?session_id=... (pagó) o ?cancelado=ID (canceló) */
    public function mount(): void
    {
        // Cliente con sesión: su correo ya viene escrito (puede cambiarlo si quiere)
        $this->correo = auth()->user()?->email ?? '';

        if ($sessionId = request()->query('session_id')) {
            $this->confirmarStripe($sessionId);                       // driver stripe
        } elseif ($paymentIntent = request()->query('payment_intent')) {
            $this->confirmarTarjeta($paymentIntent);                  // driver stripe_card (si hubo redirección)
        } elseif ($id = request()->query('cancelado')) {
            $this->cancelarPendiente((int) $id);
        }
    }



    public function driver(): string
    {
        return config('services.pago.driver', 'mock');   // mock | stripe | stripe_card
    }

    /** true para "stripe" y "stripe_card": en ambos la tarjeta NO pasa por nuestro formulario */
    public function usaStripe(): bool
    {
        return str_starts_with($this->driver(), 'stripe');
    }


    protected function rules(): array
    {
        $reglas = ['correo' => ['required', 'email:rfc', 'max:255']];

        if ($this->usaStripe()) {
            return $reglas;
        }

        return $reglas + [
                'titular'     => ['required', 'string', 'max:100'],
                'tarjeta'     => ['required', function ($attr, $valor, $fail) {
                    if (! PagoSimuladoService::luhn($valor)) {
                        $fail('El número de tarjeta no es válido.');
                    }
                }],
                'vencimiento' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/', function ($attr, $valor, $fail) {
                    [$mes, $anio] = explode('/', $valor);
                    if (Carbon::create(2000 + (int) $anio, (int) $mes, 1)->endOfMonth()->isPast()) {
                        $fail('La tarjeta está vencida.');
                    }
                }],
                'cvv'         => ['required', 'digits_between:3,4'],
            ];
    }

    protected function messages(): array
    {
        return [
            'required'           => 'Este campo es obligatorio.',
            'correo.email'       => 'Escribe un correo válido.',
            'vencimiento.regex'  => 'Usa el formato MM/AA.',
            'cvv.digits_between' => 'El CVV tiene 3 o 4 dígitos.',
        ];
    }

    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['correo', 'titular', 'tarjeta', 'vencimiento', 'cvv'])) {
            $this->validateOnly($propiedad);
        }
    }

    #[On('carrito-actualizado')]
    public function refrescar(): void
    {
        if ($this->clientSecret) {
            $this->cancelarTarjeta();   // el monto cambió: hay que preparar el pago de nuevo
        }
    }

    public function pagar(): void
    {
        $this->validate();
        $this->rechazo = null;

        // 1) Recalcular todo en el servidor
        $carrito = app(CarritoService::class);
        $antes = $carrito->items();
        $lineas = $carrito->lineas();

        if ($lineas->isEmpty()) {
            $this->addError('carrito', 'Tu carrito está vacío.');

            return;
        }

        if ($carrito->items() != $antes) {
            $this->dispatch('carrito-actualizado', cantidad: $carrito->cantidadTotal());
            $this->addError('carrito', 'Algunas cantidades cambiaron por disponibilidad. Revisa tu pedido antes de pagar.');

            return;
        }

        $totales = $carrito->totales($lineas);

        // 2) Según el driver configurado en .env
        match ($this->driver()) {
            'stripe'      => $this->pagarConStripe($lineas, $totales),     // redirige a Stripe
            'stripe_card' => $this->prepararTarjeta($lineas, $totales),    // tarjeta dentro del sitio
            default       => $this->pagarConMock($lineas, $totales),       // simulador
        };
    }

    /* ======================= STRIPE ======================= */

    private function pagarConStripe(Collection $lineas, array $totales): void
    {
        foreach ($lineas as $l) {
            if ($l->producto->stock < $l->cantidad) {
                $this->rechazo = "Ya no hay suficiente stock de {$l->producto->nombre}.";

                return;
            }
        }

        // Pedido "pendiente": el stock se descuenta hasta confirmar el pago
        $pedido = DB::transaction(fn () => $this->crearPedido($lineas, $totales, 'pendiente'));
        session(['pedido_pendiente' => $pedido->id]);

        try {
            $url = app(StripePagoService::class)->crearSesion($pedido->load('detalles.producto'));
        } catch (Throwable $e) {
            report($e);
            $pedido->update(['estatus' => 'cancelado']);
            $this->rechazo = 'No pudimos conectar con Stripe. Intenta de nuevo.';

            return;
        }

        $this->redirect($url);   // a la página de pago de Stripe
    }

    private function confirmarStripe(string $sessionId): void
    {
        try {
            $r = app(StripePagoService::class)->consultar($sessionId);
        } catch (Throwable $e) {
            report($e);
            $this->rechazo = 'No pudimos    verificar el pago con Stripe.';

            return;
        }

        if (! $r['pagado']) {
            $this->rechazo = 'El pago no se completó.';

            return;
        }

        $this->completarPago($r);
    }


    /** Común a stripe y stripe_card: descuenta stock, marca "pagado", vacía carrito y abre el modal */
    private function completarPago(array $r): void
    {
        DB::transaction(function () use ($r) {
            $pedido = Pedido::with('detalles')->lockForUpdate()->find($r['pedido_id']);

            // Idempotente: si se llama dos veces, no se descuenta el stock dos veces
            if (! $pedido || $pedido->estatus !== 'pendiente') {
                return;
            }

            foreach ($pedido->detalles as $d) {
                $producto = Producto::withTrashed()->lockForUpdate()->find($d->producto_id);
                $producto->update(['stock' => max(0, $producto->stock - $d->cantidad)]);
            }

            $pedido->update([
                'estatus'         => 'pagado',
                'pago_referencia' => $r['referencia'],
                'pago_marca'      => $r['marca'],
                'pago_ultimos4'   => $r['ultimos4'],
            ]);
        });

        app(CarritoService::class)->vaciar();
        session()->forget('pedido_pendiente');
        $this->dispatch('carrito-actualizado', cantidad: 0);

        $this->pedidoId = $r['pedido_id'];   // abre el modal de confirmación
    }


    private function cancelarPendiente(int $id): void
    {
        // Solo cancela el pedido que este mismo navegador inició
        if (session('pedido_pendiente') === $id) {
            Pedido::whereKey($id)->where('estatus', 'pendiente')->update(['estatus' => 'cancelado']);
            session()->forget('pedido_pendiente');
        }

        $this->rechazo = 'Cancelaste el pago. Tu carrito sigue disponible.';
    }


    /* ======================= MOCK ======================= */

    /* ================= STRIPE CARD (Payment Element) ================= */

    /** 1) Crea el pedido "pendiente" y el PaymentIntent; la vista monta el formulario de tarjeta */
    private function prepararTarjeta(Collection $lineas, array $totales): void
    {
        if ($this->clientSecret) {
            return;   // el formulario de tarjeta ya está listo
        }

        foreach ($lineas as $l) {
            if ($l->producto->stock < $l->cantidad) {
                $this->rechazo = "Ya no hay suficiente stock de {$l->producto->nombre}.";

                return;
            }
        }

        $pedido = DB::transaction(fn () => $this->crearPedido($lineas, $totales, 'pendiente'));
        session(['pedido_pendiente' => $pedido->id]);

        try {
            $this->clientSecret = app(StripePagoService::class)->crearIntento($pedido);
        } catch (Throwable $e) {
            report($e);
            $pedido->update(['estatus' => 'cancelado']);
            $this->rechazo = 'No pudimos conectar con Stripe. Intenta de nuevo.';

            return;
        }

        // Avisa a Alpine (paso 4) que monte el formulario de tarjeta con este client_secret
        //$this->dispatch('stripe-montar', clientSecret: $this->clientSecret);
    }

    /** 2) Lo llama Alpine cuando Stripe.js confirma el pago. Se VERIFICA con Stripe, no se confía en el navegador */
    public function confirmarTarjeta(string $paymentIntentId): void
    {
        try {
            $r = app(StripePagoService::class)->consultarIntento($paymentIntentId);
        } catch (Throwable $e) {
            report($e);
            $this->rechazo = 'No pudimos verificar el pago con Stripe.';

            return;
        }

        $pedido = Pedido::find($r['pedido_id']);

        $valido = $r['pagado']
            && $pedido
            && $r['pedido_id'] === session('pedido_pendiente')            // es el pedido de ESTE navegador
            && abs($r['monto'] - (float) $pedido->total) < 0.01;          // y el monto coincide

        if (! $valido) {
            $this->rechazo = 'No pudimos confirmar el pago. Si se realizó un cargo, contáctanos con tu correo.';

            return;
        }

        $this->clientSecret = null;
        $this->completarPago($r);
    }

    /** 3) Si el cliente decide cambiar algo antes de pagar: cancela el pedido pendiente */
    public function cancelarTarjeta(): void
    {
        if ($id = session('pedido_pendiente')) {
            Pedido::whereKey($id)->where('estatus', 'pendiente')->update(['estatus' => 'cancelado']);
            session()->forget('pedido_pendiente');
        }

        $this->clientSecret = null;
    }


    private function pagarConMock(Collection $lineas, array $totales): void
    {
        try {
            $pedido = DB::transaction(function () use ($lineas, $totales) {
                $productos = Producto::whereIn('id', $lineas->pluck('producto.id'))
                    ->lockForUpdate()->get()->keyBy('id');

                foreach ($lineas as $l) {
                    if ($productos[$l->producto->id]->stock < $l->cantidad) {
                        throw new DomainException("Ya no hay suficiente stock de {$l->producto->nombre}.");
                    }
                }

                $pago = app(PagoSimuladoService::class)
                    ->cobrar($this->tarjeta, $this->vencimiento, $this->cvv, $totales['mxn']['total']);

                if (! $pago['aprobado']) {
                    throw new DomainException($pago['mensaje']);
                }

                $pedido = $this->crearPedido($lineas, $totales, 'pagado', [
                    'pago_referencia' => $pago['referencia'],
                    'pago_marca'      => $pago['marca'],
                    'pago_ultimos4'   => $pago['ultimos4'],
                ]);

                foreach ($lineas as $l) {
                    $productos[$l->producto->id]->decrement('stock', $l->cantidad);
                }

                return $pedido;
            });
        } catch (DomainException $e) {
            $this->rechazo = $e->getMessage();

            return;
        }

        app(CarritoService::class)->vaciar();
        $this->reset('titular', 'tarjeta', 'vencimiento', 'cvv');
        $this->pedidoId = $pedido->id;
        $this->dispatch('carrito-actualizado', cantidad: 0);
    }

    /* ======================= COMÚN ======================= */

    /** Crea el pedido con precios y tipo de cambio congelados */
    private function crearPedido(Collection $lineas, array $totales, string $estatus, array $pago = []): Pedido
    {
        $pedido = Pedido::create([
            'user_id'       => auth()->id(),   // null si compra como invitado
            'folio'         => 'ESC-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
            'correo'        => $this->correo,
            'subtotal'      => $totales['mxn']['subtotal'],
            'impuestos'     => $totales['mxn']['iva'],
            'total'         => $totales['mxn']['total'],
            'tipo_cambio'   => app(TipoCambioService::class)->valor(),
            'subtotal_usd'  => $totales['usd']['subtotal'] ?? null,
            'impuestos_usd' => $totales['usd']['iva'] ?? null,
            'total_usd'     => $totales['usd']['total'] ?? null,
            'estatus'       => $estatus,
            'fecha'         => now(),
            ...$pago,
        ]);

        foreach ($lineas as $l) {
            $pedido->detalles()->create([
                'producto_id' => $l->producto->id,
                'cantidad'    => $l->cantidad,
                'precio_mxn'  => $l->producto->precio_mxn,
                'precio_usd'  => $l->producto->precio_usd_actual,
            ]);
        }

        return $pedido;
    }

    #[Computed]
    public function pedido(): ?Pedido
    {
        return $this->pedidoId ? Pedido::with('detalles.producto')->find($this->pedidoId) : null;
    }

    public function render()
    {
        $carrito = app(CarritoService::class);
        $lineas = $carrito->lineas();

        return view('livewire.shop.checkout', [
            'lineas'     => $lineas,
            'totales'    => $carrito->totales($lineas),
            'tipoCambio' => app(TipoCambioService::class)->valor(),
            'usaStripe'  => $this->usaStripe(),
            'driver'     => $this->driver(),
            'stripeKey'  => config('services.stripe.key'),   // pk_test_: la llave PÚBLICA, segura para el navegador
            'pruebas'    => [
                ['4242 4242 4242 4242', 'Visa · aprobada'],
                ['5555 5555 5555 4444', 'Mastercard · aprobada'],
                ['4000 0000 0000 0002', 'Fondos insuficientes'],
                ['4000 0000 0000 9995', 'Tarjeta robada'],
            ],
        ]);
    }
}
