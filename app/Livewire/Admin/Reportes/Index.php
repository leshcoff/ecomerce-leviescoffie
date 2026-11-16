<?php

namespace App\Livewire\Admin\Reportes;

use App\Models\Pedido;
use App\Models\PedidoDetalle;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Reportes de pedidos')]
class Index extends Component
{
    use WithPagination;

    // ---------- Filtros (en la URL: se conservan al recargar o compartir) ----------
    #[Url] public string $desde = '';
    #[Url] public string $hasta = '';
    #[Url(except: 'dia')] public string $agrupar = 'dia';     // dia | semana | mes
    #[Url(except: '')] public string $estatus = '';           // solo afecta la tabla
    #[Url(as: 'q', except: '')] public string $buscar = '';   // solo afecta la tabla

    public ?int $detalleId = null;                            // modal de detalle

    public function mount(): void
    {
        // Por defecto: últimos 30 días
        $this->desde = $this->desde ?: now()->subDays(29)->toDateString();
        $this->hasta = $this->hasta ?: now()->toDateString();
    }

    public function updated(string $propiedad): void
    {
        $this->resetPage();

        // Si cambió el periodo, la gráfica (Alpine + Chart.js) recibe los datos nuevos
        if (in_array($propiedad, ['desde', 'hasta', 'agrupar'])) {
            $this->enviarGrafica();
        }
    }

    /** Botones de rango rápido */
    public function rango(string $clave): void
    {
        [$this->desde, $this->hasta, $this->agrupar] = match ($clave) {
            '7d'    => [now()->subDays(6)->toDateString(), now()->toDateString(), 'dia'],
            'mes'   => [now()->startOfMonth()->toDateString(), now()->toDateString(), 'dia'],
            '6m'    => [now()->subMonths(6)->startOfMonth()->toDateString(), now()->toDateString(), 'mes'],
            default => [now()->subDays(29)->toDateString(), now()->toDateString(), 'semana'],   // 30d
        };

        $this->resetPage();
        $this->enviarGrafica();
    }

    private function enviarGrafica(): void
    {
        unset($this->serie);   // limpia el caché de #[Computed] para recalcular con los filtros nuevos
        $this->dispatch('grafica-actualizada', ...$this->serie);   // → etiquetas: [...], valores: [...]
    }

    // ---------- Base de las consultas ----------

    /** @return array{0: Carbon, 1: Carbon}  [inicio del primer día, fin del último día] */
    private function rangoFechas(): array
    {
        $ini = Carbon::parse($this->desde ?: now()->subDays(29))->startOfDay();
        $fin = Carbon::parse($this->hasta ?: now())->endOfDay();

        // Si alguien pone "desde" mayor que "hasta", los invertimos
        return $ini->lte($fin) ? [$ini, $fin] : [$fin->copy()->startOfDay(), $ini->copy()->endOfDay()];
    }

    /** Pedidos PAGADOS en el rango: base de indicadores, gráfica y Top 3 */
    private function pagadosEnRango()
    {
        return Pedido::where('estatus', 'pagado')->whereBetween('fecha', $this->rangoFechas());
    }

    // ---------- Datos para la vista ----------

    /** Ventas sin IVA por día, semana o mes; los periodos sin ventas aparecen con 0 */
    #[Computed]
    public function serie(): array
    {
        [$ini, $fin] = $this->rangoFechas();

        // 1) Agrupar en MySQL
        $formatoSql = match ($this->agrupar) {
            'mes'    => '%Y-%m',      // 2026-11
            'semana' => '%x-%v',      // 2026-46  (año y semana ISO)
            default  => '%Y-%m-%d',   // 2026-11-16
        };

        $ventas = $this->pagadosEnRango()
            ->selectRaw('DATE_FORMAT(fecha, ?) as periodo, SUM(subtotal) as ventas', [$formatoSql])
            ->groupBy('periodo')
            ->pluck('ventas', 'periodo');          // ['2026-11-16' => '1234.50', ...]

        // 2) Recorrer TODOS los periodos del rango (para no dejar huecos)
        [$inicio, $paso] = match ($this->agrupar) {
            'mes'    => [$ini->copy()->startOfMonth(), '1 month'],
            'semana' => [$ini->copy()->startOfWeek(Carbon::MONDAY), '1 week'],
            default  => [$ini->copy(), '1 day'],
        };

        $etiquetas = [];
        $valores = [];

        foreach (CarbonPeriod::create($inicio, $paso, $fin) as $f) {
            // La clave PHP debe coincidir con el formato de MySQL
            [$clave, $etiqueta] = match ($this->agrupar) {
                'mes'    => [$f->format('Y-m'), $f->format('m/Y')],
                'semana' => [$f->format('o-W'), 'Sem '.$f->format('W')],
                default  => [$f->format('Y-m-d'), $f->format('d/m')],
            };

            $etiquetas[] = $etiqueta;
            $valores[] = round((float) ($ventas[$clave] ?? 0), 2);
        }

        return ['etiquetas' => $etiquetas, 'valores' => $valores];
    }

    #[Computed]
    public function indicadores(): array
    {
        $base = $this->pagadosEnRango();

        $pedidos = (clone $base)->count();
        $total = (float) (clone $base)->sum('total');

        return [
            'ventas'   => (float) (clone $base)->sum('subtotal'),   // sin IVA
            'total'    => $total,                                    // con IVA
            'pedidos'  => $pedidos,
            'ticket'   => $pedidos ? $total / $pedidos : 0,
            'unidades' => (int) PedidoDetalle::whereIn('pedido_id', (clone $base)->select('id'))->sum('cantidad'),
        ];
    }

    #[Computed]
    public function top3()
    {
        [$ini, $fin] = $this->rangoFechas();

        return PedidoDetalle::query()
            ->join('pedidos', 'pedidos.id', '=', 'pedido_detalle.pedido_id')
            ->where('pedidos.estatus', 'pagado')
            ->whereNull('pedidos.deleted_at')
            ->whereBetween('pedidos.fecha', [$ini, $fin])
            ->selectRaw('pedido_detalle.producto_id,
                         SUM(pedido_detalle.cantidad) as unidades,
                         SUM(pedido_detalle.cantidad * pedido_detalle.precio_mxn) as importe')
            ->groupBy('pedido_detalle.producto_id')
            ->orderByDesc('unidades')
            ->limit(3)
            ->with('producto')   // la relación usa withTrashed(): también muestra productos eliminados
            ->get();
    }

    #[Computed]
    public function detalle(): ?Pedido
    {
        return $this->detalleId ? Pedido::with('detalles.producto')->find($this->detalleId) : null;
    }

    public function render()
    {
        // Tabla: TODOS los estatus (o el filtrado), con búsqueda por folio o correo
        $pedidos = Pedido::query()
            ->whereBetween('fecha', $this->rangoFechas())
            ->when($this->estatus, fn ($q, $e) => $q->where('estatus', $e))
            ->when($this->buscar, fn ($q, $t) => $q->where(fn ($q) => $q
                ->where('folio', 'like', "%{$t}%")
                ->orWhere('correo', 'like', "%{$t}%")))
            ->withSum('detalles as unidades', 'cantidad')
            ->latest('fecha')
            ->paginate(10);

        return view('livewire.admin.reportes.index', compact('pedidos'));
    }
}
