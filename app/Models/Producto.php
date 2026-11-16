<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Services\TipoCambioService;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'categoria_id', 'sku', 'nombre', 'descripcion_corta', 'descripcion_larga',
        'precio_usd', 'precio_mxn', 'imagen', 'stock', 'fecha_vigencia', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_usd'     => 'decimal:2',
            'precio_mxn'     => 'decimal:2',
            'stock'          => 'integer',
            'fecha_vigencia' => 'date',
            'activo'         => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Producto::disponibles() → activos y vigentes.
     * Los eliminados lógicamente ya los excluye SoftDeletes.
     */
    #[Scope]
    protected function disponibles(Builder $query): void
    {
        $query->where('activo', true)
            ->where(fn (Builder $q) => $q
                ->whereNull('fecha_vigencia')
                ->orWhereDate('fecha_vigencia', '>=', today()));
    }

    /** $producto->imagen_url → URL externa, archivo en storage o imagen de respaldo */
    protected function imagenUrl(): Attribute
    {
        return Attribute::get(fn () => match (true) {
            blank($this->imagen)                   => 'https://placehold.co/600x600?text=Sin+imagen',
            str_starts_with($this->imagen, 'http') => $this->imagen,
            default                                => Storage::url($this->imagen),
        });
    }

    public function agotado(): bool
    {
        return $this->stock <= 0;
    }


    /** $producto->precio_usd_actual → precio_mxn ÷ tipo de cambio de esta petición */
    protected function precioUsdActual(): Attribute
    {
        return Attribute::get(fn () => app(TipoCambioService::class)->aUsd($this->precio_mxn));
    }
}
