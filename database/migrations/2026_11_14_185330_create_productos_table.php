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
        Schema::create('productos', function (Blueprint $table) {
        $table->id();
        $table->string('sku', 50)->unique();
        $table->string('nombre');
        $table->string('descripcion_corta')->nullable();
        $table->text('descripcion_larga')->nullable();
        $table->decimal('precio_usd', 10, 2)->default(0);
        $table->decimal('precio_mxn', 10, 2)->default(0);
        $table->string('imagen')->nullable();          // ruta en storage, no el archivo
        $table->unsignedInteger('stock')->default(0);
        $table->date('fecha_vigencia')->nullable();
        $table->boolean('activo')->default(true);
        $table->timestamps();
        $table->softDeletes();                          // agrega la columna deleted_at
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
