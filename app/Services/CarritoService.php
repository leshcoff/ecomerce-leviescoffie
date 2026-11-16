<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Collection;

class CarritoService
{
    public const IVA = 0.16;
    private const KEY = 'carrito';

    /** @return array<int, int>  producto_id => cantidad */
    public function items(): array
    {
        return session(self::KEY, []);
    }

    public function cantidadDe(int $productoId): int
    {
        return $this->items()[$productoId] ?? 0;
    }

    public function cantidadTotal(): int
    {
        return array_sum($this->items());
    }

    /** Suma al carrito sin pasar del stock. Devuelve la cantidad final. */
    public function agregar(Producto $producto, int $cantidad = 1): int
    {
        return $this->poner($producto->id, min($this->cantidadDe($producto->id) + $cantidad, $producto->stock));
    }

    /** Fija una cantidad exacta (0 = quitar), sin pasar del stock. */
    public function actualizar(Producto $producto, int $cantidad): int
    {
        return $this->poner($producto->id, max(0, min($cantidad, $producto->stock)));
    }

    public function quitar(int $productoId): void
    {
        $this->poner($productoId, 0);
    }

    public function vaciar(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Líneas del carrito con datos frescos de la BD.
     * Descarta productos que ya no están disponibles y ajusta cantidades al stock actual.
     */
    public function lineas(): Collection
    {
        $items = $this->items();

        if (! $items) {
            return collect();
        }

        $productos = Producto::disponibles()->whereIn('id', array_keys($items))->get()->keyBy('id');

        $lineas = collect();
        $limpio = [];

        foreach ($items as $id => $cantidad) {
            $producto = $productos->get($id);

            if (! $producto || $producto->agotado()) {
                continue;
            }

            $cantidad = min($cantidad, $producto->stock);
            $limpio[$id] = $cantidad;

            $lineas->push((object) [
                'producto'    => $producto,
                'cantidad'    => $cantidad,
                'importe_mxn' => round($producto->precio_mxn * $cantidad, 2),
                'importe_usd' => $producto->precio_usd_actual === null ? null : round($producto->precio_usd_actual * $cantidad, 2),
            ]);
        }

        session([self::KEY => $limpio]);

        return $lineas;
    }

    /** @return array{mxn: array, usd: array}  cada una con subtotal, iva y total */
    public function totales(?Collection $lineas = null): array
    {
        $lineas ??= $this->lineas();

        $calcular = function (string $campo) use ($lineas) {
            $subtotal   = round($lineas->sum($campo), 2);
            $iva        = round($subtotal * self::IVA, 2);

            return ['subtotal' => $subtotal, 'iva' => $iva, 'total' => round($subtotal + $iva, 2)];
        };


        return [
            'mxn' => $calcular('importe_mxn'),
            'usd' => $lineas->contains(fn ($l) => $l->importe_usd === null) ? null : $calcular('importe_usd'),
        ];
    }

    private function poner(int $productoId, int $cantidad): int
    {
        $items = $this->items();

        if ($cantidad <= 0) {
            unset($items[$productoId]);
        } else {
            $items[$productoId] = $cantidad;
        }

        session([self::KEY => $items]);

        return max(0, $cantidad);
    }
}
