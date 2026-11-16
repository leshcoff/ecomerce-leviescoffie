<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // Solo para datos de demostración: borra las imágenes de productos anteriores
        Storage::disk('public')->deleteDirectory('productos');

        Producto::factory()->count(35)->create();
        Producto::factory()->count(3)->create()->each->delete(); // eliminados lógicamente
    }
}
