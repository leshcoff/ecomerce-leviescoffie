<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class TipoCambioService
{
    public const KEY = 'tipo_cambio:usd_mxn';
    public const KEY_RESPALDO = 'tipo_cambio:usd_mxn:respaldo';
    private ?array $enMemoria = null;

    /**
     * Devuelve el tipo de cambio USD→MXN.
     * 1) Si está en Redis, lo regresa sin llamar a la API.
     * 2) Si no está, consulta la API y lo guarda en Redis por TTL segundos.
     * 3) Si la API falla, usa el último valor bueno (respaldo) y NO lo guarda como vigente,
     *    para que la siguiente petición vuelva a intentar con la API.
     *
     * @return array{valor: float, consultado: string, origen: string}
     */
    private function obtener(): array
    {
        $redis = Cache::store('redis');

        if ($dato = $redis->get(self::KEY)) {
            return [...$dato, 'origen' => 'redis'];
        }

        try {
            $valor = (float) Http::timeout(5)
                ->retry(2, 300)
                ->get(config('services.tipo_cambio.url'))
                ->throw()
                ->json('rates.MXN');

            if ($valor <= 0) {
                throw new RuntimeException('La API no devolvió rates.MXN');
            }

            $dato = ['valor' => $valor, 'consultado' => now()->toDateTimeString()];

            $redis->put(self::KEY, $dato, config('services.tipo_cambio.ttl'));
            $redis->forever(self::KEY_RESPALDO, $dato);

            return [...$dato, 'origen' => 'api'];
        } catch (Throwable $e) {
            report($e);

            $respaldo = $redis->get(self::KEY_RESPALDO)
                ?? throw new RuntimeException('Tipo de cambio no disponible', previous: $e);

            return [...$respaldo, 'origen' => 'respaldo'];
        }
    }

    /** @return array{valor: float, consultado: string, origen: string} */
    public function usdMxn(): array
    {
        // ??= : solo llama a obtener() la primera vez; después devuelve lo memorizado
        return $this->enMemoria ??= $this->obtener();
    }

    /** El tipo de cambio como número, o null si no hay ninguno disponible */
    public function valor(): ?float
    {
        try {
            return $this->usdMxn()['valor'];
        } catch (Throwable) {
            return null;
        }
    }

    /** Convierte pesos a dólares con el TC de esta petición (2 decimales) */
    public function aUsd(float|string $mxn): ?float
    {
        $tc = $this->valor();

        return $tc ? round((float) $mxn / $tc, 2) : null;
    }
}
