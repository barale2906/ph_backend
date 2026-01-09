<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Votación</title>
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
        <h1>RESULTADOS DE VOTACIÓN</h1>
        @if(isset($ph))
            <h2>{{ $ph->nombre }}</h2>
            <p>NIT: {{ $ph->nit }}</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">INFORMACIÓN DE LA PREGUNTA</div>
        <div class="info-row">
            <span class="info-label">Pregunta:</span>
            <span>{{ $pregunta->pregunta }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tipo:</span>
            <span>{{ $pregunta->tipo }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span>{{ $pregunta->estado }}</span>
        </div>
        @if($pregunta->reunion)
            <div class="info-row">
                <span class="info-label">Reunión:</span>
                <span>{{ $pregunta->reunion->tipo }} - {{ $pregunta->reunion->fecha->format('d/m/Y') }}</span>
            </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">RESULTADOS</div>
        <table>
            <thead>
                <tr>
                    <th>Opción</th>
                    <th>Votos</th>
                    <th>Porcentaje</th>
                    <th>Coeficiente Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultados['resultados'] as $resultado)
                    <tr>
                        <td>{{ $resultado['opcion_texto'] }}</td>
                        <td>{{ $resultado['votos_cantidad'] }}</td>
                        <td>{{ number_format($resultado['votos_porcentaje'], 2) }}%</td>
                        <td>{{ number_format($resultado['coeficientes_suma'], 2) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 15px;">
            <div class="info-row">
                <span class="info-label">Total Votos:</span>
                <span>{{ $resultados['total_votos'] ?? 0 }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Coeficiente:</span>
                <span>{{ number_format($resultados['total_coeficientes'] ?? 0, 2) }}%</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
