<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Clase de exportación para lista de asistentes.
 * 
 * Exporta la lista completa de asistentes con sus inmuebles a Excel.
 */
class AsistentesExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * Datos de asistentes.
     * 
     * @var array
     */
    protected array $datos;

    /**
     * Constructor.
     * 
     * @param array $datos Datos de asistentes
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
        $asistentes = $this->datos['asistentes'] ?? [];

        foreach ($asistentes as $asistente) {
            $inmuebles = $asistente->inmuebles->pluck('nomenclatura')->join(', ');
            $coeficientes = $asistente->inmuebles->sum('coeficiente');

            $data[] = [
                'ID' => $asistente->id,
                'Nombre' => $asistente->nombre,
                'Documento' => $asistente->documento ?? 'N/A',
                'Teléfono' => $asistente->telefono ?? 'N/A',
                'Código Acceso' => $asistente->codigo_acceso,
                'Inmuebles' => $inmuebles ?: 'N/A',
                'Coeficiente Total' => round($coeficientes, 2) . '%',
                'Total Inmuebles' => $asistente->inmuebles->count(),
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
        return [
            'ID',
            'Nombre',
            'Documento',
            'Teléfono',
            'Código Acceso',
            'Inmuebles',
            'Coeficiente Total',
            'Total Inmuebles',
        ];
    }

    /**
     * Título de la hoja.
     * 
     * @return string
     */
    public function title(): string
    {
        return 'Asistentes';
    }
}
