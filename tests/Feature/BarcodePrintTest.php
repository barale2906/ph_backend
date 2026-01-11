<?php

namespace Tests\Feature;

use App\Models\Ph;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodePrintTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Ph $ph;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->logistica()->create();

        $this->ph = Ph::create([
            'nit' => '901999999',
            'nombre' => 'PH Codigos',
            'db_name' => 'ph_backend_test',
            'estado' => 'activo',
        ]);

        $this->user->phs()->attach($this->ph->id, ['rol' => 'LOGISTICA']);
    }

    public function test_imprime_codigos_de_barras_en_pdf(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('X-PH-NIT', $this->ph->nit)
            ->postJson('/api/barcodes/print', [
                'inicio' => 101,
                'cantidad' => 2,
                'copias' => 2,
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="codigos-barras.pdf"');
        $this->assertNotEmpty($response->getContent());
    }
}
