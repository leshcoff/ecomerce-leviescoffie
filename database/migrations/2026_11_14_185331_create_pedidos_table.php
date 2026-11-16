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
        Schema::create('pedidos', function (Blueprint $table) {
        $table->id();
        $table->string('folio', 30)->unique();
        $table->string('correo');
        $table->decimal('subtotal', 12, 2)->default(0);
        $table->decimal('impuestos', 12, 2)->default(0);
        $table->decimal('total', 12, 2)->default(0);
        $table->string('estatus', 20)->default('pendiente')->index();
        $table->dateTime('fecha');
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
