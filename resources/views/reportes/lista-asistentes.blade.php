<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Asistentes</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        @if(isset($quorum))
        .quorum-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        @endif
    </style>
</head>
<body>
    <div class="header">
        <h1>LISTA DE ASISTENTES</h1>
        @if(isset($ph))
            <h2>{{ $ph->nombre }}</h2>
            <p>NIT: {{ $ph->nit }}</p>
        @endif
    </div>

    @if(isset($quorum))
        <div class="quorum-info">
            <strong>QUÓRUM:</strong><br>
            Total Inmuebles Registrados: {{ $quorum['total_inmuebles'] }}<br>
            Suma de Coeficientes: {{ $quorum['suma_coeficientes'] }}%<br>
            Porcentaje: {{ $quorum['porcentaje'] }}%
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Código Acceso</th>
                <th>Código Barras</th>
                <th>Inmuebles</th>
                <th>Coeficiente Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asistentes as $asistente)
                <tr>
                    <td>{{ $asistente->id }}</td>
                    <td>{{ $asistente->nombre }}</td>
                    <td>{{ $asistente->documento ?? 'N/A' }}</td>
                    <td>{{ $asistente->telefono ?? 'N/A' }}</td>
                    <td>{{ $asistente->codigo_acceso }}</td>
                    <td>{{ $asistente->barcode_numero ?? 'N/A' }}</td>
                    <td>{{ $asistente->inmuebles->pluck('nomenclatura')->join(', ') ?: 'N/A' }}</td>
                    <td>{{ round($asistente->inmuebles->sum('coeficiente'), 2) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">No hay asistentes registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Total de asistentes: {{ $asistentes->count() }}</p>
        <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
