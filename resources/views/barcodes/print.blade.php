<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Códigos de barras</title>
    <style>
        @page { margin: 20px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: {{ $margen }}px;
        }
        .item {
            width: calc(25% - {{ $margen }}px);
            border: 1px dashed #ccc;
            padding: 8px;
            text-align: center;
            box-sizing: border-box;
        }
        .numero {
            margin-top: 6px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="grid">
        @foreach($codigos as $codigo)
            <div class="item">
                <img src="{{ $codigo['image_base64'] }}" alt="Código {{ $codigo['numero'] }}">
                <div class="numero">{{ $codigo['numero'] }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
