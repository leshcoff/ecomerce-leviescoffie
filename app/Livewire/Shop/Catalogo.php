<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AgregaAlCarrito;
use App\Models\Categoria;
use App\Models\Producto;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shop')]
#[Title('Catálogo')]
class Catalogo extends Component
{
    use AgregaAlCarrito, WithPagination;

    // Todos los filtros viven en la URL: se pueden compartir y se conservan al volver
    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    #[Url(except: '')]
    public string $categoria = '';

    #[Url(as: 'min', except: '')]
    public $precioMin = '';

    #[Url(as: 'max', except: '')]
    public $precioMax = '';

    #[Url(as: 'stock', except: false)]
    public bool $soloConStock = false;

    #[Url(except: 'recientes')]
    public string $orden = 'recientes';

    public function updated(string $propiedad): void
    {
        $this->resetPage();   // cualquier cambio de filtro regresa a la página 1
    }

    public function limpiarFiltros(): void
    {
        $this->reset('buscar', 'categoria', 'precioMin', 'precioMax', 'soloConStock', 'orden');
        $this->resetPage();
    }

    public function render()
    {
        $productos = Producto::query()
            ->disponibles()
            ->with('categoria')
            ->when($this->buscar, fn ($q, $t) => $q->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$t}%")
                ->orWhere('sku', 'like', "%{$t}%"))
            )
            ->when($this->categoria, fn ($q, $slug) => $q->whereRelation('categoria', 'slug', $slug))
            ->when(is_numeric($this->precioMin), fn ($q) => $q->where('precio_mxn', '>=', $this->precioMin))
            ->when(is_numeric($this->precioMax), fn ($q) => $q->where('precio_mxn', '<=', $this->precioMax))
            ->when($this->soloConStock, fn ($q) => $q->where('stock', '>', 0))
            ->when($this->orden === 'precio_asc', fn ($q) => $q->orderBy('precio_mxn'))
            ->when($this->orden === 'precio_desc', fn ($q) => $q->orderByDesc('precio_mxn'))
            ->when($this->orden === 'nombre', fn ($q) => $q->orderBy('nombre'))
            ->when($this->orden === 'recientes', fn ($q) => $q->latest())
            ->paginate(12);

        // Cuántos productos disponibles tiene cada categoría
        $categorias = Categoria::withCount(['productos' => fn ($q) => $q->disponibles()])
            ->orderBy('nombre')->get();

        return view('livewire.shop.catalogo', compact('productos', 'categorias'));
    }
}
