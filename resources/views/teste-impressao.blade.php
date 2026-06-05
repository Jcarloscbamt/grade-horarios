{{-- TESTE 3: resources/views/teste-impressao.blade.php --}}
@php
    // Carrega o MESMO logo que a página da grade usa
    $logoPath = public_path('images/logo-unisenai.png');
    $logoBase64 = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;
    $tamanhoKB = file_exists($logoPath) ? round(filesize($logoPath) / 1024, 1) : 0;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste de Impressão 3</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 10px; text-align: center; }
        th { background: #E30613; color: white; }
    </style>
</head>
<body>
    <h2>Teste 3 — COM logo base64</h2>

    {{-- AQUI: o logo, igual à página da grade --}}
    @if($logoBase64)
        <img src="{{ $logoBase64 }}" alt="UniSENAI MT" style="height:56px">
        <p style="color:#999">Tamanho do logo: {{ $tamanhoKB }} KB</p>
    @else
        <p style="color:red">Logo não encontrado em {{ $logoPath }}</p>
    @endif

    <table>
        <thead>
            <tr><th>Horário</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th></tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 5; $i++)
            <tr>
                <td>18:45 - 22:00</td>
                <td>Disciplina {{ $i }}</td><td>Disciplina {{ $i }}</td>
                <td>Disciplina {{ $i }}</td><td>Disciplina {{ $i }}</td>
                <td>Disciplina {{ $i }}</td>
            </tr>
            @endfor
        </tbody>
    </table>
    <p style="margin-top:20px;color:#999">Teste 3 — com logo. Apague depois.</p>
</body>
</html>
