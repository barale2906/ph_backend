<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Clase de exportación para resultados de votación.
 * 
 * Exporta los resultados detallados de una votación específica a Excel.
 */
class VotacionExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * Datos de la votación.
     * 
     * @var array
     */
    protected array $datos;

    /**
     * Constructor.
     * 
     * @param array $datos Datos de la votación
     */
    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    /**
     * Obtiene los datos para la exportación.
     * 
     * @return array
     */
    public function array(): array
    {
        $data = [];
        $pregunta = $this->datos['pregunta'];
        $resultados = $this->datos['resultados'];

        // Información de la pregunta
        $data[] = [
            'Campo' => 'Pregunta',
            'Valor' => $pregunta->pregunta,
        ];
        $data[] = [
            'Campo' => 'Tipo',
            'Valor' => $pregunta->tipo,
        ];
        $data[] = [
            'Campo' => 'Estado',
            'Valor' => $pregunta->estado,
        ];
        $data[] = [
            'Campo' => 'Total Votos',
            'Valor' => $resultados['total_votos'] ?? 0,
        ];
        $data[] = [
            'Campo' => 'Total Coeficiente',
            'Valor' => number_format($resultados['total_coeficientes'] ?? 0, 2) . '%',
        ];
        $data[] = []; // Línea en blanco

        // Resultados por opción
        $data[] = ['Opción', 'Votos', '% Votos', 'Coeficiente', '% Coeficiente'];
        foreach ($resultados['resultados'] ?? [] as $resultado) {
            $data[] = [
                'Opción' => $resultado['opcion_texto'],
                'Votos' => $resultado['votos_cantidad'],
                '% Votos' => number_format($resultado['votos_porcentaje'], 2) . '%',
                'Coeficiente' => number_format($resultado['coeficientes_suma'], 2) . '%',
                '% Coeficiente' => number_format($resultado['coeficientes_porcentaje'], 2) . '%',
            ];
        }

        return $data;
    }

    /**
     * Encabezados de las columnas.
     * 
     * @return array
     */
    public function headings(): array
    {
        return ['Campo', 'Valor'];
    }

    /**
     * Título de la hoja.
     * 
     * @return string
     */
    public function title(): string
    {
        return 'Resultados Votación';
    }
}
