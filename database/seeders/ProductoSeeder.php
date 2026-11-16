<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {

        Producto::factory()->count(35)->create();
        Producto::factory()->count(3)->create()->each->delete(); // eliminados lógicamente
    }
}
