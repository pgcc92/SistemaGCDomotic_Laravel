<?php

namespace App\Http\Controllers;

use App\Domain\Tenant\TenantContext;
use App\Infrastructure\Remote\RemoteDataClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class ReportesController
{
    public function __construct(
        private readonly RemoteDataClient $data,
        private readonly TenantContext $tenant,
    ) {
    }

    public function index(): View
    {
        $dashboard = $this->data->dashboard();

        return view('reportes.index', [
            'dashboard' => $dashboard,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $payload = $request->validate([
            'format' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'periodo' => ['nullable', 'string'],
            'from' => ['nullable', 'string'],
            'to' => ['nullable', 'string'],
            'download' => ['nullable'],
        ]);

        $format = (string) ($payload['format'] ?? 'a4');
        if (!in_array($format, ['a4', 'ticket80', 'ticket58'], true)) {
            $format = 'a4';
        }

        $type = (string) ($payload['type'] ?? 'resumen');
        if (!in_array($type, ['resumen', 'productos_lista', 'productos_kardex', 'productos_detalle', 'ventas_detalle', 'tickets_resumen'], true)) {
            $type = 'resumen';
        }

        $dashboard = $this->data->dashboard();
        $branding = $this->tenant->branding();

        $logoDataUri = $this->logoDataUri($branding->logoUrl ?: $branding->loginLogoUrl);

        $view = match ($type) {
            'productos_lista' => 'reportes.pdf_productos_lista',
            'productos_kardex' => 'reportes.pdf_productos_kardex',
            'productos_detalle' => 'reportes.pdf_productos_detalle',
            'ventas_detalle' => 'reportes.pdf_ventas_detalle',
            'tickets_resumen' => 'reportes.pdf_tickets_resumen',
            default => 'reportes.pdf',
        };

        $data = [
            'format' => $format,
            // Compatibilidad con versiones previas de vistas PDF
            'fmt' => $format,
            'type' => $type,
            'branding' => $branding,
            'logoDataUri' => $logoDataUri,
            'generatedAt' => now(),
            'dashboard' => $dashboard,
            'filters' => [
                'periodo' => (string) ($payload['periodo'] ?? ''),
                'from' => (string) ($payload['from'] ?? ''),
                'to' => (string) ($payload['to'] ?? ''),
            ],
        ];

        if ($type === 'productos_lista') {
            $data['productos'] = $this->data->productos(500);
            // Traemos stock de los productos listados para que el "stock total" cuadre siempre.
            // (Además, evita depender del limit cuando hay muchas filas de stock_sucursal.)
            $ids = [];
            foreach (($data['productos'] ?? []) as $p) {
                $id = (int) (is_array($p) ? ($p['id'] ?? 0) : ($p->id ?? 0));
                if ($id > 0) $ids[] = $id;
            }
            $data['stock'] = $this->data->productosStockData(5000, $ids);
        } elseif ($type === 'productos_kardex') {
            $data['productos'] = $this->data->productos(500);
            $from = now()->startOfMonth()->toDateString();
            $to = now()->endOfMonth()->toDateString();
            $data['kardex'] = $this->data->productosKardexData(2000, $from, $to);
            $data['filters']['from'] = $data['filters']['from'] ?: $from;
            $data['filters']['to'] = $data['filters']['to'] ?: $to;
        } elseif ($type === 'productos_detalle') {
            // A4 recomendado por el volumen. Para tickets se intentará igual, pero puede ser muy largo.
            $data['productos'] = $this->data->productos(500);
            $from = now()->startOfMonth()->toDateString();
            $to = now()->endOfMonth()->toDateString();
            $data['kardex'] = $this->data->productosKardexData(4000, $from, $to);
            $data['filters']['from'] = $data['filters']['from'] ?: $from;
            $data['filters']['to'] = $data['filters']['to'] ?: $to;
        } elseif ($type === 'ventas_detalle') {
            // Por defecto: mes actual si no hay filtro
            $from = (string) ($payload['from'] ?? '');
            $to = (string) ($payload['to'] ?? '');
            $periodo = (string) ($payload['periodo'] ?? '');
            if ($from === '' && $to === '' && preg_match('/^\\d{4}-\\d{2}$/', $periodo)) {
                $from = "{$periodo}-01";
                $to = date('Y-m-d', strtotime("{$from} +1 month -1 day"));
            }
            if ($from === '' && $to === '') {
                $from = now()->startOfMonth()->toDateString();
                $to = now()->endOfMonth()->toDateString();
            }
            // Reutilizamos el API remoto: /ventas admite filtros from/to.
            $data['ventas'] = $this->data->ventasReport($from, $to);
            $data['filters']['from'] = $from;
            $data['filters']['to'] = $to;
        } elseif ($type === 'tickets_resumen') {
            $data['tickets'] = $this->data->tickets(200);
            // Compat: algunas vistas esperaban $byEstado desde el controlador.
            $by = [];
            foreach (($data['tickets'] ?? []) as $t) {
                $estado = (string) (is_array($t) ? ($t['estado'] ?? '—') : ($t->estado ?? '—'));
                $by[$estado] = ($by[$estado] ?? 0) + 1;
            }
            $data['byEstado'] = $by;
        }

        $pdf = Pdf::loadView($view, $data);

        $paper = $this->paperFor($format);
        $pdf->setPaper($paper, 'portrait');

        $filename = 'reporte-' . $type . '-' . now()->format('Ymd-His') . '-' . $format . '.pdf';
        $download = (bool) ($payload['download'] ?? false);
        return $download ? $pdf->download($filename) : $pdf->stream($filename);
    }

    /** @return string|array<int,float> */
    private function paperFor(string $format): string|array
    {
        if ($format === 'a4') {
            return 'a4';
        }

        // Dompdf usa puntos (pt). 1mm = 2.834645669pt
        $mmToPt = fn (float $mm): float => $mm * 2.834645669;

        $widthMm = $format === 'ticket58' ? 58.0 : 80.0;
        // Altura: rollo continuo. Usamos una altura suficiente para resumen (A4 alto).
        $heightMm = 297.0;

        $w = $mmToPt($widthMm);
        $h = $mmToPt($heightMm);

        return [0, 0, $w, $h];
    }

    private function logoDataUri(?string $url): ?string
    {
        $url = is_string($url) ? trim($url) : '';
        if ($url === '') return null;

        // Rutas esperadas: /files/settings/YYYY/MM/uuid.ext
        if (preg_match('#^/files/settings/(?<y>\\d{4})/(?<m>\\d{2})/(?<f>[0-9a-fA-F-]{36}(?:_thumb)?\\.(?:jpg|png|webp))$#', $url, $m)) {
            $uploadRoot = rtrim((string) config('gc_uploads.root', base_path('storage')), "/\\");
            $path = $uploadRoot . DIRECTORY_SEPARATOR . "settings/{$m['y']}/{$m['m']}/{$m['f']}";
            if (is_file($path)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    default => 'application/octet-stream',
                };
                $bin = @file_get_contents($path);
                if (is_string($bin) && $bin !== '') {
                    return "data:{$mime};base64," . base64_encode($bin);
                }
            }
        }

        return null;
    }
}
