<?php

namespace App\Livewire\Concerns;

use App\Models\Producto;
use App\Services\CarritoService;

trait AgregaAlCarrito
{
    public function agregar(int $productoId, int $cantidad = 1): void
    {
        $producto = Producto::disponibles()->find($productoId);

        if (! $producto || $producto->agotado()) {
            $this->dispatch('notify', mensaje: 'Este producto ya no está disponible.');

            return;
        }

        $carrito    = app(CarritoService::class);
        $antes      = $carrito->cantidadDe($producto->id);
        $despues    = $carrito->agregar($producto, max(1, $cantidad));

        $this->dispatch('carrito-actualizado', cantidad: $carrito->cantidadTotal());
        $this->dispatch('notify', mensaje: $despues > $antes
            ? "Agregaste {$producto->nombre} ({$despues} en tu carrito)."
            : "Ya tienes el máximo disponible de {$producto->nombre} ({$producto->stock}).");
    }
}
