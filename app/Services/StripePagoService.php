<?php

namespace App\Services;

use App\Models\Pedido;
use Stripe\StripeClient;

class StripePagoService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /** Crea la sesión de pago en Stripe y devuelve la URL a la que se envía al cliente */
    public function crearSesion(Pedido $pedido): string
    {
        // Un renglón por producto (Stripe trabaja en centavos)
        $renglones = $pedido->detalles->map(fn ($d) => [
            'quantity'   => $d->cantidad,
            'price_data' => [
                'currency'     => 'mxn',
                'unit_amount'  => (int) round($d->precio_mxn * 100),
                'product_data' => ['name' => $d->producto->nombre],
            ],
        ]);

        // El IVA como renglón aparte, para que el total de Stripe = total del pedido
        $renglones->push([
            'quantity'   => 1,
            'price_data' => [
                'currency'     => 'mxn',
                'unit_amount'  => (int) round($pedido->impuestos * 100),
                'product_data' => ['name' => 'IVA (16 %)'],
            ],
        ]);

        $sesion = $this->stripe->checkout->sessions->create([
            'mode'                => 'payment',
            'customer_email'      => $pedido->correo,
            'client_reference_id' => (string) $pedido->id,   // así sabemos a qué pedido pertenece
            'line_items'          => $renglones->values()->all(),
            'metadata'            => ['folio' => $pedido->folio],
            'expires_at'          => now()->addMinutes(30)->timestamp,
            // {CHECKOUT_SESSION_ID} lo reemplaza Stripe por el id real de la sesión
            'success_url'         => route('checkout').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'          => route('checkout').'?cancelado='.$pedido->id,
        ]);

        return $sesion->url;
    }

    /** Pregunta a Stripe (servidor a servidor) cómo quedó la sesión */
    public function consultar(string $sessionId): array
    {
        $sesion = $this->stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent.latest_charge'],   // para obtener marca y últimos 4
        ]);

        $tarjeta = $sesion->payment_intent?->latest_charge?->payment_method_details?->card;

        return [
            'pagado'     => $sesion->payment_status === 'paid',
            'pedido_id'  => (int) $sesion->client_reference_id,
            'monto'      => $sesion->amount_total / 100,
            'referencia' => $sesion->payment_intent?->id,
            'marca'      => $tarjeta ? ucfirst($tarjeta->brand) : null,
            'ultimos4'   => $tarjeta?->last4,
        ];
    }

    /* ============ Payment Element (pago dentro del sitio) ============ */

    public function crearIntento(Pedido $pedido): string
    {
        $intento = $this->stripe->paymentIntents->create([
            'amount'                    => (int) round($pedido->total * 100),
            'currency'                  => 'mxn',
            'receipt_email'             => $pedido->correo,
            'description'               => "Pedido {$pedido->folio}",
            'automatic_payment_methods' => ['enabled' => true],
            'metadata'                  => ['pedido_id' => $pedido->id, 'folio' => $pedido->folio],
        ]);

        return $intento->client_secret;
    }

    public function consultarIntento(string $paymentIntentId): array
    {
        $intento = $this->stripe->paymentIntents->retrieve($paymentIntentId, ['expand' => ['latest_charge']]);
        $tarjeta = $intento->latest_charge?->payment_method_details?->card;

        return [
            'pagado'     => $intento->status === 'succeeded',
            'pedido_id'  => (int) ($intento->metadata['pedido_id'] ?? 0),
            'monto'      => $intento->amount / 100,
            'referencia' => $intento->id,
            'marca'      => $tarjeta ? ucfirst($tarjeta->brand) : null,
            'ultimos4'   => $tarjeta?->last4,
        ];
    }

}
