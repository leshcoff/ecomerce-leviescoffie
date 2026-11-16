<?php

namespace App\Livewire\Admin\Productos;

use App\Livewire\Forms\ProductoForm;
use App\Models\Producto;
use App\Services\TipoCambioService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Title('Productos')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public ProductoForm $form;

    // Filtros en la URL: /admin/products?q=cafe&estado=activos (se conservan al volver)
    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    #[Url(except: '')]
    public string $estado = '';

    // Estado de los modales
    public bool $modalFormulario = false;
    public ?int $detalleId = null;
    public ?int $eliminarId = null;

    public function updated(string $propiedad): void
    {
        // Validación en tiempo real: cada vez que cambia un campo del formulario
        if (str_starts_with($propiedad, 'form.')) {
            $this->validateOnly($propiedad);

            return;
        }

        // Si cambia un filtro, regresa a la página 1
        if (in_array($propiedad, ['buscar', 'estado'])) {
            $this->resetPage();
        }
    }

    public function crear(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->modalFormulario = true;
    }

    public function editar(int $id): void
    {
        $this->resetValidation();
        $this->form->setProducto(Producto::findOrFail($id));
        $this->modalFormulario = true;
    }

    public function guardar(): void
    {
        $esNuevo = $this->form->producto === null;

        $this->form->guardar();

        $this->modalFormulario = false;
        $this->dispatch('notify', mensaje: $esNuevo ? 'Producto creado.' : 'Producto actualizado.');
    }

    public function ver(int $id): void
    {
        $this->detalleId = $id;
    }

    public function alternarActivo(int $id): void
    {
        $producto = Producto::findOrFail($id);
        $producto->update(['activo' => ! $producto->activo]);

        $this->dispatch('notify', mensaje: $producto->activo ? 'Producto activado.' : 'Producto inactivado.');
    }

    public function confirmarEliminar(int $id): void
    {
        $this->eliminarId = $id;
    }

    public function eliminar(): void
    {
        Producto::findOrFail($this->eliminarId)->delete();   // soft delete: llena deleted_at

        $this->eliminarId = null;
        $this->dispatch('notify', mensaje: 'Producto eliminado.');
    }

    #[Computed]
    public function detalle(): ?Producto
    {
        return $this->detalleId ? Producto::find($this->detalleId) : null;
    }

    public function render()
    {
        try {
            $tipoCambio = app(TipoCambioService::class)->usdMxn();
        } catch (Throwable) {
            $tipoCambio = null;
        }


        $productos = Producto::query()              // SoftDeletes ya excluye los eliminados
        ->when($this->buscar, fn ($q, $texto) => $q->where(fn ($q) => $q
            ->where('sku', 'like', "%{$texto}%")
            ->orWhere('nombre', 'like', "%{$texto}%")))
            ->when($this->estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($this->estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->latest()
            ->paginate(10);

        $categorias = \App\Models\Categoria::orderBy('nombre')->get();
        return view('livewire.admin.productos.index', compact('productos', 'tipoCambio', 'categorias'));

    }
}
