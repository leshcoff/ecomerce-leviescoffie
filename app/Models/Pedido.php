<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'folio', 'correo', 'subtotal', 'impuestos', 'total',
        'tipo_cambio', 'subtotal_usd', 'impuestos_usd', 'total_usd',
        'estatus', 'pago_referencia', 'pago_marca', 'pago_ultimos4', 'fecha',
    ];


    protected function casts(): array
    {
        return [
            'subtotal'      => 'decimal:2',
            'impuestos'     => 'decimal:2',
            'total'         => 'decimal:2',
            'tipo_cambio'   => 'decimal:4',
            'subtotal_usd'  => 'decimal:2',
            'impuestos_usd' => 'decimal:2',
            'total_usd'     => 'decimal:2',
            'fecha'         => 'datetime',
        ];
    }


    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
