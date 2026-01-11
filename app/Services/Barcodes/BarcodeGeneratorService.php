<?php

namespace App\Services\Barcodes;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Servicio para generar y renderizar códigos de barras.
 *
 * Importante: este servicio solo genera códigos para impresión.
 * No persiste información ni valida unicidad histórica.
 */
class BarcodeGeneratorService
{
    /**
    * Genera los códigos listos para ser renderizados.
    */
    public function generarCodigos(int $inicio, int $cantidad, int $copias): Collection
    {
        $generator = new BarcodeGeneratorPNG();
        $ancho = config('barcodes.ancho', 2);
        $alto = config('barcodes.alto', 60);
        $formato = strtoupper(config('barcodes.formato', 'CODE_128'));
        $tipo = $formato === 'CODE_128'
            ? BarcodeGeneratorPNG::TYPE_CODE_128
            : BarcodeGeneratorPNG::TYPE_CODE_128; // formato aprobado

        $codigos = collect();

        for ($numero = $inicio; $numero < $inicio + $cantidad; $numero++) {
            $imagen = $generator->getBarcode((string) $numero, $tipo, $ancho, $alto);
            $base64 = 'data:image/png;base64,' . base64_encode($imagen);

            for ($i = 0; $i < $copias; $i++) {
                $codigos->push([
                    'numero' => $numero,
                    'image_base64' => $base64,
                ]);
            }
        }

        return $codigos;
    }

    /**
     * Renderiza el PDF listo para impresión.
     */
    public function renderPdf(int $inicio, int $cantidad, int $copias): string
    {
        $codigos = $this->generarCodigos($inicio, $cantidad, $copias);
        $margen = config('barcodes.margen', 8);
        $dpi = config('barcodes.dpi', 300);

        $html = view('barcodes.print', [
            'codigos' => $codigos,
            'margen' => $margen,
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('dpi', $dpi);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }
}
