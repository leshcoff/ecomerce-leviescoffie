<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductosImagenes extends Command
{
    protected $signature = 'productos:imagenes {--forzar : Reemplaza también las imágenes que ya están en storage}';

    protected $description = 'Copia o descarga imágenes de ejemplo a storage/app/public/productos';

    public function handle(): int
    {
        $disco = Storage::disk('public');

        // Por defecto solo procesa productos sin imagen o con URL externa (http...)
        $productos = Producto::withTrashed()
            ->with('categoria')
            ->when(! $this->option('forzar'), fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('imagen')
                ->orWhere('imagen', 'like', 'http%')))
            ->get();

        if ($productos->isEmpty()) {
            $this->info('Todos los productos ya tienen imagen local. Usa --forzar para reemplazarlas.');

            return self::SUCCESS;
        }

        $fallidos = 0;

        $this->withProgressBar($productos, function (Producto $producto) use ($disco, &$fallidos) {
            $imagen = $this->desdeCarpeta($producto) ?? $this->descargar($producto);

            if (! $imagen) {
                $fallidos++;

                return;
            }

            [$contenido, $extension] = $imagen;
            $ruta = "productos/{$producto->sku}.{$extension}";

            $disco->put($ruta, $contenido);
            $producto->updateQuietly(['imagen' => $ruta]);
        });

        $this->newLine(2);
        $this->info("Listo: {$productos->count()} producto(s) procesados, {$fallidos} sin imagen.");

        return self::SUCCESS;
    }

    /** Foto al azar de database/seeders/imagenes/{slug-categoria}/ */
    private function desdeCarpeta(Producto $producto): ?array
    {
        $carpeta = database_path('seeders/imagenes/'.($producto->categoria?->slug ?? 'general'));
        $archivos = glob($carpeta.'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];

        if (! $archivos) {
            return null;
        }

        $archivo = $archivos[array_rand($archivos)];

        return [file_get_contents($archivo), strtolower(pathinfo($archivo, PATHINFO_EXTENSION))];
    }

    /** Respaldo: picsum (foto) y, si falla, placehold.co (texto con el nombre) */
    private function descargar(Producto $producto): ?array
    {
        $urls = [
            "https://picsum.photos/seed/{$producto->sku}/600/600",
            'https://placehold.co/600x600/png?text='.urlencode($producto->nombre),
        ];

        foreach ($urls as $url) {
            try {
                $respuesta = Http::timeout(10)->get($url);
                $tipo = (string) $respuesta->header('Content-Type');

                if ($respuesta->successful() && str_starts_with($tipo, 'image/')) {
                    $extension = match (true) {
                        str_contains($tipo, 'png')  => 'png',
                        str_contains($tipo, 'webp') => 'webp',
                        default                     => 'jpg',
                    };

                    return [$respuesta->body(), $extension];
                }
            } catch (Throwable) {
                // probamos con la siguiente URL
            }
        }

        return null;
    }
}
