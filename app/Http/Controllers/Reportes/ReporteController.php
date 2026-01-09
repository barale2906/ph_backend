<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use App\Exports\ReunionExport;
use App\Exports\AsistentesExport;
use App\Exports\VotacionExport;
use App\Exports\InmueblesExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @group Reportes
 * 
 * Controlador para la generación de reportes en diferentes formatos (PDF, Excel, Word).
 * 
 * Todos los reportes se generan desde la base de datos del PH actual.
 * 
 * IMPORTANTE: Requiere middleware 'tenant' para acceder a la base de datos de la PH.
 */
class ReporteController extends Controller
{
    /**
     * Instancia del servicio de reportes.
     * 
     * @var ReporteService
     */
    protected ReporteService $reporteService;

    /**
     * Constructor.
     * 
     * @param ReporteService $reporteService
     */
    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    /**
     * Generar acta de reunión en PDF
     * 
     * Genera un acta formal de la reunión en formato PDF con toda la información:
     * - Datos de la reunión
     * - Lista de asistentes
     * - Quórum alcanzado
     * - Resultados de votaciones
     * - Orden del día
     * 
     * @authenticated
     * 
     * @urlParam reunion int required ID de la reunión. Example: 1
     * 
     * @response 200 file PDF del acta de reunión
     * @response 404 {"message": "Reunión no encontrada"}
     * 
     * @param int $reunionId ID de la reunión
     * @return Response
     */
    public function actaReunionPdf(int $reunionId): Response
    {
        $datos = $this->reporteService->obtenerDatosReunion($reunionId);

        $html = view('reportes.acta-reunion', $datos)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'acta-reunion-' . $reunionId . '-' . now()->format('Y-m-d') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"');
    }

    /**
     * Generar lista de asistentes en PDF
     * 
     * Genera un reporte PDF con la lista completa de asistentes y los inmuebles que representan.
     * 
     * @authenticated
     * 
     * @urlParam reunion int optional ID de la reunión. Example: 1
     * 
     * @response 200 file PDF de la lista de asistentes
     * 
     * @param Request $request
     * @return Response
     */
    public function listaAsistentesPdf(Request $request): Response
    {
        $reunionId = $request->query('reunion_id');
        $datos = $this->reporteService->obtenerDatosAsistentes($reunionId);

        $html = view('reportes.lista-asistentes', $datos)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'lista-asistentes-' . now()->format('Y-m-d') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"');
    }

    /**
     * Generar resultados de votación en PDF
     * 
     * Genera un reporte PDF con los resultados detallados de una votación específica.
     * 
     * @authenticated
     * 
     * @urlParam pregunta int required ID de la pregunta. Example: 1
     * 
     * @response 200 file PDF de resultados de votación
     * @response 404 {"message": "Pregunta no encontrada"}
     * 
     * @param int $preguntaId ID de la pregunta
     * @return Response
     */
    public function resultadosVotacionPdf(int $preguntaId): Response
    {
        $datos = $this->reporteService->obtenerDatosVotacion($preguntaId);

        $html = view('reportes.resultados-votacion', $datos)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nombreArchivo = 'resultados-votacion-' . $preguntaId . '-' . now()->format('Y-m-d') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombreArchivo . '"');
    }

    /**
     * Exportar datos de reunión a Excel
     * 
     * Exporta todos los datos de una reunión a un archivo Excel incluyendo:
     * - Información de la reunión
     * - Lista de asistentes
     * - Resultados de votaciones
     * - Estadísticas
     * 
     * @authenticated
     * 
     * @urlParam reunion int required ID de la reunión. Example: 1
     * 
     * @response 200 file Excel con los datos de la reunión
     * @response 404 {"message": "Reunión no encontrada"}
     * 
     * @param int $reunionId ID de la reunión
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarReunionExcel(int $reunionId)
    {
        $datos = $this->reporteService->obtenerDatosReunion($reunionId);
        $nombreArchivo = 'reunion-' . $reunionId . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ReunionExport($datos), $nombreArchivo);
    }

    /**
     * Exportar lista de asistentes a Excel
     * 
     * Exporta la lista completa de asistentes con sus inmuebles a Excel.
     * 
     * @authenticated
     * 
     * @queryParam reunion_id int optional ID de la reunión. Example: 1
     * 
     * @response 200 file Excel con la lista de asistentes
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarAsistentesExcel(Request $request)
    {
        $reunionId = $request->query('reunion_id');
        $datos = $this->reporteService->obtenerDatosAsistentes($reunionId);
        $nombreArchivo = 'asistentes-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new AsistentesExport($datos), $nombreArchivo);
    }

    /**
     * Exportar resultados de votación a Excel
     * 
     * Exporta los resultados detallados de una votación a Excel.
     * 
     * @authenticated
     * 
     * @urlParam pregunta int required ID de la pregunta. Example: 1
     * 
     * @response 200 file Excel con los resultados de votación
     * @response 404 {"message": "Pregunta no encontrada"}
     * 
     * @param int $preguntaId ID de la pregunta
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarVotacionExcel(int $preguntaId)
    {
        $datos = $this->reporteService->obtenerDatosVotacion($preguntaId);
        $nombreArchivo = 'votacion-' . $preguntaId . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new VotacionExport($datos), $nombreArchivo);
    }

    /**
     * Exportar lista de inmuebles a Excel
     * 
     * Exporta la lista completa de inmuebles a Excel.
     * 
     * @authenticated
     * 
     * @response 200 file Excel con la lista de inmuebles
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarInmueblesExcel()
    {
        $datos = $this->reporteService->obtenerDatosInmuebles();
        $nombreArchivo = 'inmuebles-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new InmueblesExport($datos), $nombreArchivo);
    }

    /**
     * Generar acta de reunión en Word
     * 
     * Genera un acta formal de la reunión en formato Word (.docx) editable.
     * 
     * @authenticated
     * 
     * @urlParam reunion int required ID de la reunión. Example: 1
     * 
     * @response 200 file Word del acta de reunión
     * @response 404 {"message": "Reunión no encontrada"}
     * 
     * @param int $reunionId ID de la reunión
     * @return Response
     */
    public function actaReunionWord(int $reunionId): Response
    {
        $datos = $this->reporteService->obtenerDatosReunion($reunionId);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Título
        $section->addText('ACTA DE REUNIÓN', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $section->addTextBreak(1);

        // Información de la reunión
        $section->addText('INFORMACIÓN DE LA REUNIÓN', ['bold' => true, 'size' => 14]);
        $section->addText('Tipo: ' . $datos['reunion']->tipo);
        $section->addText('Fecha: ' . $datos['reunion']->fecha->format('d/m/Y'));
        $section->addText('Hora: ' . $datos['reunion']->hora);
        $section->addText('Modalidad: ' . $datos['reunion']->modalidad);
        $section->addText('Estado: ' . $datos['reunion']->estado);
        $section->addTextBreak(1);

        // Quórum
        if ($datos['quorum']) {
            $section->addText('QUÓRUM', ['bold' => true, 'size' => 14]);
            $section->addText('Total inmuebles registrados: ' . $datos['quorum']['total_inmuebles']);
            $section->addText('Suma de coeficientes: ' . $datos['quorum']['suma_coeficientes'] . '%');
            $section->addText('Porcentaje: ' . $datos['quorum']['porcentaje'] . '%');
            $section->addTextBreak(1);
        }

        // Asistentes
        $section->addText('ASISTENTES', ['bold' => true, 'size' => 14]);
        foreach ($datos['asistentes'] as $asistente) {
            $section->addText($asistente->nombre . ' - ' . ($asistente->documento ?? 'Sin documento'));
            if ($asistente->inmuebles->count() > 0) {
                $inmuebles = $asistente->inmuebles->pluck('nomenclatura')->join(', ');
                $section->addText('  Inmuebles: ' . $inmuebles, ['italic' => true]);
            }
        }
        $section->addTextBreak(1);

        // Votaciones
        if ($datos['reunion']->preguntas->count() > 0) {
            $section->addText('RESULTADOS DE VOTACIONES', ['bold' => true, 'size' => 14]);
            foreach ($datos['reunion']->preguntas as $pregunta) {
                $section->addText($pregunta->pregunta, ['bold' => true]);
                $resultados = $pregunta->obtenerResultados();
                foreach ($resultados['resultados'] as $resultado) {
                    $section->addText('  ' . $resultado['opcion_texto'] . ': ' . $resultado['votos_cantidad'] . ' votos (' . number_format($resultado['votos_porcentaje'], 2) . '%)');
                }
                $section->addTextBreak(1);
            }
        }

        // Guardar en memoria
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'acta');
        $objWriter->save($tempFile);

        $nombreArchivo = 'acta-reunion-' . $reunionId . '-' . now()->format('Y-m-d') . '.docx';

        return response()->download($tempFile, $nombreArchivo)->deleteFileAfterSend(true);
    }

    /**
     * Obtener estadísticas de reunión
     * 
     * Obtiene estadísticas generales de una reunión en formato JSON.
     * 
     * @authenticated
     * 
     * @urlParam reunion int required ID de la reunión. Example: 1
     * 
     * @response 200 {
     *   "total_preguntas": 5,
     *   "preguntas_abiertas": 2,
     *   "preguntas_cerradas": 3,
     *   "total_votos": 150,
     *   "quorum": {...},
     *   "total_asistentes": 25,
     *   "total_inmuebles": 50
     * }
     * 
     * @param int $reunionId ID de la reunión
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticasReunion(int $reunionId): \Illuminate\Http\JsonResponse
    {
        $estadisticas = $this->reporteService->obtenerEstadisticasReunion($reunionId);

        return response()->json($estadisticas);
    }
}
