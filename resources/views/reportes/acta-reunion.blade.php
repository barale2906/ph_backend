<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta de Reunión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header h2 {
            margin: 10px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ACTA DE REUNIÓN</h1>
        @if(isset($ph))
            <h2>{{ $ph->nombre }}</h2>
            <p>NIT: {{ $ph->nit }}</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">INFORMACIÓN DE LA REUNIÓN</div>
        <div class="info-row">
            <span class="info-label">Tipo:</span>
            <span>{{ $reunion->tipo }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span>{{ $reunion->fecha->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Hora:</span>
            <span>{{ $reunion->hora }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Modalidad:</span>
            <span>{{ $reunion->modalidad }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span>{{ $reunion->estado }}</span>
        </div>
        @if($reunion->inicio_at)
            <div class="info-row">
                <span class="info-label">Inicio Real:</span>
                <span>{{ $reunion->inicio_at->format('d/m/Y H:i') }}</span>
            </div>
        @endif
        @if($reunion->cierre_at)
            <div class="info-row">
                <span class="info-label">Cierre Real:</span>
                <span>{{ $reunion->cierre_at->format('d/m/Y H:i') }}</span>
            </div>
        @endif
    </div>

    @if(isset($quorum))
        <div class="section">
            <div class="section-title">QUÓRUM</div>
            <div class="info-row">
                <span class="info-label">Total Inmuebles Registrados:</span>
                <span>{{ $quorum['total_inmuebles'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Suma de Coeficientes:</span>
                <span>{{ $quorum['suma_coeficientes'] }}%</span>
            </div>
            <div class="info-row">
                <span class="info-label">Porcentaje:</span>
                <span>{{ $quorum['porcentaje'] }}%</span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Inmuebles Activos:</span>
                <span>{{ $quorum['total_inmuebles_activos'] }}</span>
            </div>
        </div>
    @endif

    <div class="section">
        <div class="section-title">ASISTENTES</div>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
                    <th>Inmuebles</th>
                </tr>
            </thead>
            <tbody>
                @forelse($asistentes as $asistente)
                    <tr>
                        <td>{{ $asistente->nombre }}</td>
                        <td>{{ $asistente->documento ?? 'N/A' }}</td>
                        <td>{{ $asistente->telefono ?? 'N/A' }}</td>
                        <td>{{ $asistente->inmuebles->pluck('nomenclatura')->join(', ') ?: 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">No hay asistentes registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reunion->preguntas->count() > 0)
        <div class="section">
            <div class="section-title">RESULTADOS DE VOTACIONES</div>
            @foreach($reunion->preguntas as $pregunta)
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 13px; margin-bottom: 10px;">{{ $pregunta->pregunta }}</h3>
                    <p style="margin-bottom: 10px;">
                        <strong>Tipo:</strong> {{ $pregunta->tipo }} | 
                        <strong>Estado:</strong> {{ $pregunta->estado }}
                    </p>
                    @php
                        $resultados = $pregunta->obtenerResultados();
                    @endphp
                    <table>
                        <thead>
                            <tr>
                                <th>Opción</th>
                                <th>Votos</th>
                                <th>% Votos</th>
                                <th>Coeficiente</th>
                                <th>% Coeficiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultados['resultados'] as $resultado)
                                <tr>
                                    <td>{{ $resultado['opcion_texto'] }}</td>
                                    <td>{{ $resultado['votos_cantidad'] }}</td>
                                    <td>{{ number_format($resultado['votos_porcentaje'], 2) }}%</td>
                                    <td>{{ number_format($resultado['coeficientes_suma'], 2) }}%</td>
                                    <td>{{ number_format($resultado['coeficientes_porcentaje'], 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p style="margin-top: 10px;">
                        <strong>Total Votos:</strong> {{ $resultados['total_votos'] ?? 0 }} | 
                        <strong>Total Coeficiente:</strong> {{ number_format($resultados['total_coeficientes'] ?? 0, 2) }}%
                    </p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
