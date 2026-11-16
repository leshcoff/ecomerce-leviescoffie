<?php

namespace App\Livewire\Shop;

use App\Models\Producto;
use App\Services\CarritoService;
use Livewire\Attributes\On;
use Livewire\Component;

class Carrito extends Component
{
    /** Cuando otro componente (catálogo o detalle) agrega algo, este se vuelve a dibujar */
    #[On('carrito-actualizado')]
    public function refrescar(): void
    {
        // Vacío a propósito: basta con que se ejecute para que Livewire llame a render()
    }

    public function cambiarCantidad(int $productoId, int $cantidad): void
    {
        $carrito = app(CarritoService::class);
        $producto = Producto::disponibles()->find($productoId);

        if (! $producto) {
            $carrito->quitar($productoId);
            $this->notificar($carrito, 'Ese producto ya no está disponible y se quitó del carrito.');

            return;
        }

        $carrito->actualizar($producto, $cantidad);   // nunca pasa del stock; 0 = quitar

        $this->notificar($carrito, $cantidad > $producto->stock
            ? "Solo hay {$producto->stock} disponibles de {$producto->nombre}."
            : null);
    }

    public function quitar(int $productoId): void
    {
        $carrito = app(CarritoService::class);
        $carrito->quitar($productoId);

        $this->notificar($carrito, 'Producto eliminado del carrito.');
    }

    public function vaciar(): void
    {
        $carrito = app(CarritoService::class);
        $carrito->vaciar();

        $this->notificar($carrito, 'Carrito vacío.');
    }

    private function notificar(CarritoService $carrito, ?string $mensaje): void
    {
        // Actualiza el contador del header (Alpine)
        $this->dispatch('carrito-actualizado', cantidad: $carrito->cantidadTotal());

        if ($mensaje) {
            $this->dispatch('notify', mensaje: $mensaje);
        }
    }

    public function render()
    {
        $carrito = app(CarritoService::class);
        $lineas = $carrito->lineas();   // datos frescos de la BD, ajustados al stock

        return view('livewire.shop.carrito', [
            'lineas'  => $lineas,
            'totales' => $carrito->totales($lineas),
        ]);
    }
}
