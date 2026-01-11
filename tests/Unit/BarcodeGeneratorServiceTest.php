<?php

namespace Tests\Unit;

use App\Services\Barcodes\BarcodeGeneratorService;
use Tests\TestCase;

class BarcodeGeneratorServiceTest extends TestCase
{
    /**
     * Valida que se generen los rangos y copias solicitadas.
     */
    public function test_generar_codigos_repite_rango_y_copias(): void
    {
        $service = new BarcodeGeneratorService();

        $codigos = $service->generarCodigos(inicio: 101, cantidad: 3, copias: 2);

        $this->assertCount(6, $codigos);
        $this->assertEquals(
            [101, 101, 102, 102, 103, 103],
            $codigos->pluck('numero')->all()
        );
        $this->assertStringStartsWith('data:image/png;base64,', $codigos->first()['image_base64']);
    }
}
