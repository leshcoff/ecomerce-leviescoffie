<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProductoFactory extends Factory
{
    public function definition(): array
    {
        $usd = fake()->randomFloat(2, 5, 300);

        return [
            'categoria_id'      => Categoria::inRandomOrder()->value('id'),
            'sku'               => 'ESC-'.fake()->unique()->numerify('####'),
            'nombre'            => ucfirst(fake()->words(3, true)),
            'descripcion_corta' => fake()->sentence(10),
            'descripcion_larga' => fake()->paragraphs(2, true),
            'precio_usd'        => $usd,
            'precio_mxn'        => round($usd * 18.50, 2),
            'imagen'            => null,   // se asigna en afterCreating()
            'stock'             => fake()->numberBetween(0, 50),
            'fecha_vigencia'    => fake()->dateTimeBetween('now', '+1 year'),
            'activo'            => fake()->boolean(85),
        ];
    }

    /**
     * Después de guardar cada producto, copia una foto al azar de su categoría:
     * database/seeders/imagenes/{slug}/*.jpg  →  storage/app/public/productos/{SKU}.jpg
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Producto $producto) {
            if ($producto->imagen) {
                return;   // respeta la imagen si se pasó explícitamente: ->create(['imagen' => ...])
            }

            $slug = $producto->categoria?->slug;
            $fotos = $slug ? glob(database_path("seeders/imagenes/{$slug}/*.{jpg,jpeg,png,webp}"), GLOB_BRACE) : [];

            if (! $fotos) {
                return;   // sin fotos para esa categoría: se queda con la imagen de respaldo del accessor
            }

            $origen = Arr::random($fotos);
            $ruta = "productos/{$producto->sku}.".pathinfo($origen, PATHINFO_EXTENSION);

            Storage::disk('public')->put($ruta, file_get_contents($origen));
            $producto->updateQuietly(['imagen' => $ruta]);
        });
    }
}
