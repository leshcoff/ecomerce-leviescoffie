<?php

namespace App\Livewire\Shop;

use App\Models\Pedido;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shop')]
#[Title('Mis pedidos')]
class MisPedidos extends Component
{
    use WithPagination;

    public function render()
    {
        $usuario = auth()->user();

        $pedidos = Pedido::query()
            ->where(fn ($q) => $q
                ->where('user_id', $usuario->id)                         // ligados a su cuenta
                ->orWhere(fn ($q) => $q
                    ->whereNull('user_id')                               // o hechos como invitado
                    ->where('correo', $usuario->email)))                 // con su mismo correo
            ->with('detalles.producto')
            ->latest('fecha')
            ->paginate(5);

        return view('livewire.shop.mis-pedidos', compact('pedidos'));
    }
}
