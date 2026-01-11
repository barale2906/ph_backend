<?php

namespace App\Http\Controllers\Barcodes;

use App\Http\Controllers\Controller;
use App\Services\Barcodes\BarcodeGeneratorService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Códigos de barras
 *
 * Genera códigos de barras para impresión sin alterar la lógica de votos.
 */
class BarcodePrintController extends Controller
{
    public function __construct(
        protected readonly BarcodeGeneratorService $barcodeGeneratorService
    ) {
    }

    /**
     * Genera un PDF con códigos de barras listos para imprimir.
     */
    public function imprimir(Request $request): Response
    {
        $validated = $request->validate([
            'inicio' => ['required', 'integer', 'min:1'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'copias' => ['required', 'integer', 'min:1'],
        ]);

        $pdf = $this->barcodeGeneratorService->renderPdf(
            $validated['inicio'],
            $validated['cantidad'],
            $validated['copias']
        );

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="codigos-barras.pdf"');
    }
}
