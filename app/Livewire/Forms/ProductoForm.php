<?php

namespace App\Livewire\Forms;

use App\Models\Producto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProductoForm extends Form
{
    public ?Producto $producto = null;   // null = alta, con valor = edición
    public $categoria_id = '';
    public string $sku = '';
    public string $nombre = '';
    public string $descripcion_corta = '';
    public string $descripcion_larga = '';
    public $precio_usd = '';
    public $precio_mxn = '';
    public $stock = '';
    public string $fecha_vigencia = '';
    public bool $activo = true;
    public $imagen = null;               // archivo temporal de Livewire

    public function rules(): array
    {
        return [
            'sku' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]+$/',
                // Único en toda la tabla, incluidos los eliminados lógicamente (igual que el índice de la BD).
                // En edición se ignora el propio registro.
                Rule::unique('productos', 'sku')->ignore($this->producto?->id),
            ],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nombre'            => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9 ]+$/'],
            'descripcion_corta' => ['required', 'string', 'max:255'],
            'descripcion_larga' => ['required', 'string'],
            'precio_usd'        => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            'precio_mxn'        => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            'stock'             => ['required', 'integer', 'min:0'],
            'fecha_vigencia'    => ['required', 'date'],
            'activo'            => ['boolean'],
            // En alta la imagen es obligatoria; en edición solo si quieres cambiarla
            'imagen'            => [$this->producto?->imagen ? 'nullable' : 'required', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'         => 'El campo :attribute es obligatorio.',
            'string'           => 'El campo :attribute debe ser texto.',
            'max'              => 'El campo :attribute es demasiado largo.',
            'numeric'          => 'El campo :attribute debe ser un número.',
            'gt'               => 'El campo :attribute debe ser mayor a 0.',
            'decimal'          => 'El campo :attribute admite máximo 2 decimales.',
            'integer'          => 'El campo :attribute debe ser un número entero.',
            'min'              => 'El campo :attribute no puede ser negativo.',
            'date'             => 'El campo :attribute no es una fecha válida.',
            'sku.unique'       => 'Ya existe un producto con este SKU.',
            'sku.regex'        => 'El SKU solo admite letras, números y guiones.',
            'nombre.regex'     => 'El nombre solo admite letras sin acentos, números y espacios.',
            'precio_usd.max'   => 'El precio es demasiado alto.',
            'precio_mxn.max'   => 'El precio es demasiado alto.',
            'imagen.image'     => 'El archivo debe ser una imagen (jpg, png, webp…).',
            'imagen.max'       => 'La imagen no debe pesar más de 2 MB.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'sku' => 'SKU', 'nombre' => 'nombre', 'descripcion_corta' => 'descripción corta',
            'categoria_id' => 'categoría',
            'descripcion_larga' => 'descripción larga', 'precio_usd' => 'precio USD',
            'precio_mxn' => 'precio MXN', 'stock' => 'stock', 'fecha_vigencia' => 'fecha de vigencia',
            'imagen' => 'imagen',
        ];
    }

    public function setProducto(Producto $producto): void
    {
        $this->producto          = $producto;
        $this->categoria_id      = $producto->categoria_id ?? '';
        $this->sku               = $producto->sku;
        $this->nombre            = $producto->nombre;
        $this->descripcion_corta = (string) $producto->descripcion_corta;
        $this->descripcion_larga = (string) $producto->descripcion_larga;
        $this->precio_usd        = $producto->precio_usd;
        $this->precio_mxn        = $producto->precio_mxn;
        $this->stock             = $producto->stock;
        $this->fecha_vigencia    = $producto->fecha_vigencia?->format('Y-m-d') ?? '';
        $this->activo            = $producto->activo;
        $this->imagen            = null;
    }

    public function guardar(): Producto
    {
        $this->sku    = strtoupper(trim($this->sku));
        $this->nombre = trim($this->nombre);

        $datos = $this->validate();
        unset($datos['imagen']);

        if ($this->imagen) {
            $datos['imagen'] = $this->imagen->store('productos', 'public');

            // Borra la imagen anterior si era un archivo nuestro (no una URL externa)
            $anterior = $this->producto?->imagen;
            if ($anterior && ! str_starts_with($anterior, 'http')) {
                Storage::disk('public')->delete($anterior);
            }
        }

        $producto = $this->producto ?? new Producto;
        $producto->fill($datos)->save();

        $this->reset();

        return $producto;
    }
}
