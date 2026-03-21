<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade de Horários (P&B) — {{ $turma->nome }}</title>
    @php
        $telefoneCoord = $turma->curso->telefone_coord ?? '';
        $telefoneLimpo = preg_replace('/\D/', '', $telefoneCoord);
        $whatsappLink  = $telefoneLimpo ? "https://wa.me/55{$telefoneLimpo}" : '';
    @endphp
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: white;
            padding: 20px;
            color: #000;
        }

        .btn-imprimir {
            position: fixed; top: 16px; right: 16px;
            background: #222; color: white; border: none;
            padding: 10px 20px; border-radius: 6px; font-size: 14px;
            cursor: pointer; z-index: 999;
        }
        .btn-imprimir:hover { background: #444; }

        /* Cabeçalho */
        .header-grade {
            border-top: 4px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px 16px; margin-bottom: 12px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .header-grade img { height: 52px; }
        .header-grade .titulo { text-align: center; }
        .header-grade .titulo .subtitulo {
            font-size: 11px; color: #555; letter-spacing: 2px;
            text-transform: uppercase; display: block; margin-bottom: 4px; font-weight: bold;
        }
        .header-grade .titulo h4 {
            font-size: 26px; font-weight: 900; letter-spacing: 3px;
            text-transform: uppercase; color: #000; line-height: 1;
        }
        .header-grade .titulo .nome-curso {
            font-size: 13px; color: #333; display: block; margin-top: 4px;
            font-weight: bold; text-transform: uppercase;
        }
        .header-grade .info-turma { text-align: right; }
        .header-grade .info-turma .label { font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        .header-grade .info-turma .periodo { font-weight: 900; font-size: 22px; color: #000; line-height: 1.1; }
        .header-grade .info-turma .turma-info { font-size: 12px; color: #333; margin-top: 3px; font-weight: bold; }
        .badge-semestre {
            background: #000; color: white;
            padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;
        }

        /* Tabela */
        table { width: 100%; border-collapse: collapse; font-size: 12px; }

        thead th {
            background: #1a1a1a; color: white;
            padding: 10px 8px; text-align: center;
            font-weight: bold; letter-spacing: 1px; font-size: 13px;
            border: 1px solid #000;
        }
        thead th:first-child { width: 110px; }

        tbody td { border: 1px solid #999; vertical-align: middle; text-align: center; padding: 0; }

        /* Coluna horário */
        .col-horario {
            background: #f0f0f0; color: #000;
            padding: 0 8px; height: 90px;
            font-weight: bold; font-size: 12px;
            display: flex; align-items: center; justify-content: center;
            white-space: nowrap; border-right: 2px solid #000;
        }
        .col-horario-intervalo {
            background: #e8e8e8; color: #555;
            padding: 0 8px; height: 45px;
            font-weight: bold; font-size: 12px;
            display: flex; align-items: center; justify-content: center;
            white-space: nowrap; border-right: 2px solid #000;
        }

        /* Célula com aula */
        .celula-aula {
            background: white; padding: 10px 8px; height: 90px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border: 1px solid #ccc;
        }
        .celula-aula .disciplina {
            font-weight: bold; color: #000; font-size: 13px; line-height: 1.4;
            word-break: break-word; white-space: normal;
        }

        /* Célula online — borda tracejada para diferenciar */
        .celula-online {
            background: #f8f8f8; padding: 10px 8px; height: 90px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border: 2px dashed #555;
        }
        .celula-online .disciplina {
            font-weight: bold; color: #000; font-size: 13px; line-height: 1.4;
            word-break: break-word; white-space: normal;
        }
        .celula-online .online-label {
            color: #555; font-size: 11px; margin-top: 4px; font-weight: bold;
            border: 1px solid #555; padding: 1px 6px; border-radius: 3px;
        }

        /* Intervalo */
        .celula-intervalo {
            background: #ececec; height: 45px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: bold; color: #666;
        }
        .celula-intervalo-online {
            background: #ececec; height: 45px;
            display: flex; align-items: center; justify-content: center;
        }
        .badge-online-intervalo {
            border: 1px solid #555; color: #333;
            padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;
        }

        /* Vazia */
        .celula-vazia {
            background: white; height: 90px;
            display: flex; align-items: center; justify-content: center;
            color: #ccc; font-size: 16px;
        }

        /* Linha professor */
        .col-prof-label {
            background: #1a1a1a; color: white;
            font-weight: bold; font-size: 12px; padding: 10px 6px;
            border-right: 2px solid #000;
        }
        .celula-professor {
            background: #f0f0f0; padding: 10px 6px; font-size: 12px;
            border: 1px solid #ccc;
        }
        .prof-nome { font-weight: bold; color: #000; font-size: 12px; }
        .prof-local { color: #444; font-size: 11px; margin-top: 3px; }

        /* Barra informações */
        .info-bar {
            display: flex; align-items: stretch;
            margin-top: 12px; border: 2px solid #000; border-radius: 4px; overflow: hidden;
        }
        .info-bloco {
            flex: 1; padding: 10px 16px; font-size: 13px; color: #000;
            border-right: 1px solid #ccc; display: flex; flex-direction: column; justify-content: flex-start;
        }
        .info-bloco strong { font-size: 13px; display: block; margin-bottom: 4px; }
        .info-bloco-qr {
            background: #f0f0f0; padding: 8px 12px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-width: 110px;
        }

        @media print {
            .btn-imprimir { display: none !important; }
            body { padding: 5px; }
        }
    </style>
</head>
<body>

<button class="btn-imprimir" onclick="window.print()">&#128424; Imprimir / Salvar PDF</button>

{{-- Cabeçalho --}}
<div class="header-grade">
    <div>
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="UniSENAI MT" style="height:52px;filter:grayscale(100%)">
        @else
            <span style="font-size:20px;font-weight:900;color:#000">UniSENAI MT</span>
        @endif
    </div>
    <div class="titulo">
        <span class="subtitulo">Curso Superior de Tecnologia</span>
        <h4>Grade de Horários</h4>
        <span class="nome-curso">{{ $turma->curso->nome ?? '' }}</span>
    </div>
    <div class="info-turma">
        <div class="label">Período Letivo</div>
        <div class="periodo">{{ $periodo->nome }}</div>
        <div class="turma-info">
            Turma <strong>{{ $turma->nome }}</strong>
            <span class="badge-semestre">{{ $turma->semestre }}º SEM.</span>
        </div>
    </div>
</div>

@if(count($horarios) === 0)
    <div style="text-align:center;padding:60px;color:#999">Nenhuma aula cadastrada.</div>
@else
<table>
    <thead>
        <tr>
            <th>HORÁRIO</th>
            @foreach($dias as $num => $nome)<th>{{ $nome }}</th>@endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($horarios as $horario)
        @php $isIntervalo = strtolower($horario->tipo) === 'intervalo'; @endphp
        <tr>
            <td>
                @if($isIntervalo)
                <div class="col-horario-intervalo">{{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fim,0,5) }}</div>
                @else
                <div class="col-horario">{{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fim,0,5) }}</div>
                @endif
            </td>
            @foreach($dias as $numDia => $nomeDia)
            @php $aula = $grade[$horario->id][$numDia] ?? null; @endphp
            <td>
                @if($isIntervalo)
                    @if($aula && $aula->modalidade === 'online')
                        <div class="celula-intervalo-online"><span class="badge-online-intervalo">ONLINE</span></div>
                    @else
                        <div class="celula-intervalo">INTERVALO</div>
                    @endif
                @elseif($aula)
                    @if($aula->modalidade === 'online')
                    <div class="celula-online">
                        <div class="disciplina">{{ $aula->disciplina->nome }}</div>
                        <div class="online-label">ONLINE</div>
                    </div>
                    @else
                    <div class="celula-aula">
                        <div class="disciplina">{{ $aula->disciplina->nome }}</div>
                    </div>
                    @endif
                @else
                    <div class="celula-vazia">—</div>
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach

        {{-- Linha professor/local --}}
        <tr>
            <td class="col-prof-label">PROFESSOR(A)<br>LOCAL</td>
            @foreach($dias as $numDia => $nomeDia)
            @php
                $aulaDia = collect($grade)->flatMap(fn($h) => collect($h))->filter(fn($a) => $a->dia_semana == $numDia)->first();
            @endphp
            <td class="celula-professor">
                @if($aulaDia)
                    <div class="prof-nome">{{ strtoupper($aulaDia->professor->nome) }}</div>
                    <div class="prof-local">{{ $aulaDia->sala?->nome ?? 'ONLINE' }}</div>
                @else
                    <span style="color:#ccc">—</span>
                @endif
            </td>
            @endforeach
        </tr>
    </tbody>
</table>

{{-- Barra informações --}}
@if($periodo->avaliacao1_inicio || $periodo->avaliacao2_inicio || $whatsappLink)
<div class="info-bar">

    {{-- Avaliações --}}
    <div class="info-bloco">
        <strong>INFORMAÇÕES</strong>
        @if($periodo->avaliacao1_inicio)
        <div>&#9632; <strong>Avaliação 1:</strong> {{ $periodo->avaliacao1_inicio->format('d/m') }} a {{ $periodo->avaliacao1_fim?->format('d/m/Y') }}</div>
        @endif
        @if($periodo->avaliacao2_inicio)
        <div>&#9632; <strong>Avaliação 2:</strong> {{ $periodo->avaliacao2_inicio->format('d/m') }} a {{ $periodo->avaliacao2_fim?->format('d/m/Y') }}</div>
        @endif
    </div>

    {{-- Contato --}}
    <div class="info-bloco">
        <strong>CONTATO COORDENAÇÃO</strong>
        @if($turma->curso && $turma->curso->email_coord)
        <div style="font-size:12px;line-height:1.8">✉ {{ $turma->curso->email_coord }}</div>
        @endif
        @if($turma->curso && $turma->curso->telefone_coord)
        <div style="font-size:12px;line-height:1.8">&#9742; {{ $turma->curso->telefone_coord }}</div>
        @endif
    </div>

    {{-- QR Code P&B --}}
    @if($qrBase64)
    <div class="info-bloco-qr">
        <img src="{{ $qrBase64 }}" alt="QR WhatsApp" width="86" height="86"
             style="display:block;border:2px solid #000;border-radius:3px;filter:grayscale(100%)">
        <div style="font-size:9px;font-weight:bold;margin-top:4px;text-align:center;color:#000;line-height:1.4">
            &#9742; Fale com a<br>Coordenação
        </div>
    </div>
    @endif

</div>
@endif
@endif

</body>
</html>
