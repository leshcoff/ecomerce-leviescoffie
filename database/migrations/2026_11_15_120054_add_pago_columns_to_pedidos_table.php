<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Tipo de cambio con el que se cobró, y totales en USD congelados
            $table->decimal('tipo_cambio', 10, 4)->nullable()->after('total');
            $table->decimal('subtotal_usd', 12, 2)->nullable()->after('tipo_cambio');
            $table->decimal('impuestos_usd', 12, 2)->nullable()->after('subtotal_usd');
            $table->decimal('total_usd', 12, 2)->nullable()->after('impuestos_usd');

            // Comprobante del pago. NUNCA se guarda el número completo ni el CVV
            $table->string('pago_referencia', 40)->nullable()->after('estatus');
            $table->string('pago_marca', 20)->nullable()->after('pago_referencia');
            $table->string('pago_ultimos4', 4)->nullable()->after('pago_marca');
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['tipo_cambio', 'subtotal_usd', 'impuestos_usd', 'total_usd',
                'pago_referencia', 'pago_marca', 'pago_ultimos4']);
        });
    }
};
