<?php

namespace App\Livewire\Admin;

use App\Models\Pedido;
use App\Models\Producto;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Panel')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'productos' => Producto::count(),
            'pedidos'   => Pedido::count(),
        ]);
    }
}
