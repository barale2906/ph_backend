<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Clase de exportación para lista de inmuebles.
 * 
 * Exporta la lista completa de inmuebles a Excel.
 */
class InmueblesExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * Datos de inmuebles.
     * 
     * @var array
     */
    protected array $datos;

    /**
     * Constructor.
     * 
     * @param array $datos Datos de inmuebles
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
        $inmuebles = $this->datos['inmuebles'] ?? [];

        foreach ($inmuebles as $inmueble) {
            $asistentes = $inmueble->asistentes->pluck('nombre')->join(', ');

            $data[] = [
                'ID' => $inmueble->id,
                'Nomenclatura' => $inmueble->nomenclatura,
                'Coeficiente' => $inmueble->coeficiente . '%',
                'Tipo' => $inmueble->tipo,
                'Propietario Documento' => $inmueble->propietario_documento ?? 'N/A',
                'Propietario Nombre' => $inmueble->propietario_nombre ?? 'N/A',
                'Teléfono' => $inmueble->telefono ?? 'N/A',
                'Email' => $inmueble->email ?? 'N/A',
                'Activo' => $inmueble->activo ? 'Sí' : 'No',
                'Asistentes' => $asistentes ?: 'N/A',
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
            'Nomenclatura',
            'Coeficiente',
            'Tipo',
            'Propietario Documento',
            'Propietario Nombre',
            'Teléfono',
            'Email',
            'Activo',
            'Asistentes',
        ];
    }

    /**
     * Título de la hoja.
     * 
     * @return string
     */
    public function title(): string
    {
        return 'Inmuebles';
    }
}
