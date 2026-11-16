<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Electronica', 'Hogar', 'Ropa', 'Deportes', 'Libros'] as $nombre) {
            Categoria::firstOrCreate(['slug' => Str::slug($nombre)], ['nombre' => $nombre]);
        }
    }
}
