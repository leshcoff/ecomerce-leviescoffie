<?php

namespace Database\Seeders;

use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PedidoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = Producto::disponibles()->get();

        if ($productos->isEmpty()) {
            $this->command->warn('No hay productos disponibles: corre primero ProductoSeeder.');

            return;
        }

        $tc = 18.50;   // tipo de cambio fijo para los datos de prueba

        for ($i = 0; $i < 50; $i++) {
            // Fecha al azar en los últimos 180 días, entre 8:00 y 22:59
            $fecha = now()->subDays(rand(0, 180))->setTime(rand(8, 22), rand(0, 59));

            // De 1 a 4 productos distintos, de 1 a 3 piezas cada uno
            $lineas = $productos->random(rand(1, min(4, $productos->count())))
                ->map(fn ($p) => ['producto' => $p, 'cantidad' => rand(1, 3)]);

            $subtotal = round($lineas->sum(fn ($l) => $l['producto']->precio_mxn * $l['cantidad']), 2);
            $iva = round($subtotal * 0.16, 2);

            $pedido = Pedido::create([
                'folio'           => 'ESC-'.$fecha->format('ymd').'-'.strtoupper(Str::random(6)),
                'correo'          => fake()->safeEmail(),
                'subtotal'        => $subtotal,
                'impuestos'       => $iva,
                'total'           => $subtotal + $iva,
                'tipo_cambio'     => $tc,
                'subtotal_usd'    => round($subtotal / $tc, 2),
                'impuestos_usd'   => round($iva / $tc, 2),
                'total_usd'       => round(($subtotal + $iva) / $tc, 2),
                // 4 de cada 6 pagados; el resto pendientes o cancelados (para probar el filtro de estatus)
                'estatus'         => fake()->randomElement(['pagado', 'pagado', 'pagado', 'pagado', 'pendiente', 'cancelado']),
                'pago_referencia' => 'SEED-'.strtoupper(Str::random(8)),
                'pago_marca'      => 'Visa',
                'pago_ultimos4'   => '4242',
                'fecha'           => $fecha,
            ]);

            foreach ($lineas as $l) {
                $pedido->detalles()->create([
                    'producto_id' => $l['producto']->id,
                    'cantidad'    => $l['cantidad'],
                    'precio_mxn'  => $l['producto']->precio_mxn,
                    'precio_usd'  => round($l['producto']->precio_mxn / $tc, 2),
                ]);
            }
        }
    }
}
