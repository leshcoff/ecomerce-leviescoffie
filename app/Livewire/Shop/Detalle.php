<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AgregaAlCarrito;
use App\Models\Producto;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.shop')]
class Detalle extends Component
{
    use AgregaAlCarrito;

    public Producto $producto;

    public function mount(Producto $producto): void
    {
        // La ruta encuentra el producto por SKU; aquí exigimos que esté activo y vigente
        abort_unless(Producto::disponibles()->whereKey($producto->id)->exists(), 404);

        $this->producto = $producto->load('categoria');
    }

    public function render()
    {
        return view('livewire.shop.detalle')->title($this->producto->nombre);
    }
}
