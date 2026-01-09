<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Clase de exportación para datos completos de una reunión.
 * 
 * Genera un archivo Excel con múltiples hojas:
 * - Información de la reunión
 * - Asistentes
 * - Votaciones
 * - Estadísticas
 */
class ReunionExport implements WithMultipleSheets
{
    /**
     * Datos de la reunión.
     * 
     * @var array
     */
    protected array $datos;

    /**
     * Constructor.
     * 
     * @param array $datos Datos de la reunión
     */
    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    /**
     * Obtiene las hojas del libro Excel.
     * 
     * @return array
     */
    public function sheets(): array
    {
        return [
            new ReunionInfoSheet($this->datos),
            new AsistentesSheet($this->datos['asistentes'] ?? []),
            new VotacionesSheet($this->datos['reunion']->preguntas ?? []),
        ];
    }
}

/**
 * Hoja de información de la reunión.
 */
class ReunionInfoSheet implements FromArray, WithHeadings, WithTitle
{
    protected array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function array(): array
    {
        $reunion = $this->datos['reunion'];
        $quorum = $this->datos['quorum'] ?? [];

        return [
            [
                'Campo' => 'ID',
                'Valor' => $reunion->id,
            ],
            [
                'Campo' => 'Tipo',
                'Valor' => $reunion->tipo,
            ],
            [
                'Campo' => 'Fecha',
                'Valor' => $reunion->fecha->format('d/m/Y'),
            ],
            [
                'Campo' => 'Hora',
                'Valor' => $reunion->hora,
            ],
            [
                'Campo' => 'Modalidad',
                'Valor' => $reunion->modalidad,
            ],
            [
                'Campo' => 'Estado',
                'Valor' => $reunion->estado,
            ],
            [
                'Campo' => 'Inicio',
                'Valor' => $reunion->inicio_at ? $reunion->inicio_at->format('d/m/Y H:i') : 'N/A',
            ],
            [
                'Campo' => 'Cierre',
                'Valor' => $reunion->cierre_at ? $reunion->cierre_at->format('d/m/Y H:i') : 'N/A',
            ],
            [
                'Campo' => 'Total Inmuebles Registrados',
                'Valor' => $quorum['total_inmuebles'] ?? 0,
            ],
            [
                'Campo' => 'Suma Coeficientes',
                'Valor' => ($quorum['suma_coeficientes'] ?? 0) . '%',
            ],
            [
                'Campo' => 'Porcentaje Quórum',
                'Valor' => ($quorum['porcentaje'] ?? 0) . '%',
            ],
        ];
    }

    public function headings(): array
    {
        return ['Campo', 'Valor'];
    }

    public function title(): string
    {
        return 'Información Reunión';
    }
}

/**
 * Hoja de asistentes.
 */
class AsistentesSheet implements FromArray, WithHeadings, WithTitle
{
    protected $asistentes;

    public function __construct($asistentes)
    {
        $this->asistentes = $asistentes;
    }

    public function array(): array
    {
        $data = [];
        foreach ($this->asistentes as $asistente) {
            $inmuebles = $asistente->inmuebles->pluck('nomenclatura')->join(', ');
            $data[] = [
                'ID' => $asistente->id,
                'Nombre' => $asistente->nombre,
                'Documento' => $asistente->documento ?? 'N/A',
                'Teléfono' => $asistente->telefono ?? 'N/A',
                'Código Acceso' => $asistente->codigo_acceso,
                'Inmuebles' => $inmuebles ?: 'N/A',
            ];
        }
        return $data;
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'Documento', 'Teléfono', 'Código Acceso', 'Inmuebles'];
    }

    public function title(): string
    {
        return 'Asistentes';
    }
}

/**
 * Hoja de votaciones.
 */
class VotacionesSheet implements FromArray, WithHeadings, WithTitle
{
    protected $preguntas;

    public function __construct($preguntas)
    {
        $this->preguntas = $preguntas;
    }

    public function array(): array
    {
        $data = [];
        foreach ($this->preguntas as $pregunta) {
            $resultados = $pregunta->obtenerResultados();
            foreach ($resultados['resultados'] as $resultado) {
                $data[] = [
                    'Pregunta' => $pregunta->pregunta,
                    'Tipo' => $pregunta->tipo,
                    'Estado' => $pregunta->estado,
                    'Opción' => $resultado['opcion_texto'],
                    'Votos' => $resultado['votos_cantidad'],
                    '% Votos' => number_format($resultado['votos_porcentaje'], 2) . '%',
                    'Coeficiente' => number_format($resultado['coeficientes_suma'], 2) . '%',
                    '% Coeficiente' => number_format($resultado['coeficientes_porcentaje'], 2) . '%',
                ];
            }
        }
        return $data;
    }

    public function headings(): array
    {
        return ['Pregunta', 'Tipo', 'Estado', 'Opción', 'Votos', '% Votos', 'Coeficiente', '% Coeficiente'];
    }

    public function title(): string
    {
        return 'Votaciones';
    }
}
