<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Grade de Horários — {{ $turma->nome }}</title>
    @php
        $cor = $turma->curso->cor_grade ?? '#E30613';
        $hex = ltrim($cor, '#');
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
        // Versão clara — mistura 55% com branco
        $rC = round($r + (255-$r)*0.55);
        $gC = round($g + (255-$g)*0.55);
        $bC = round($b + (255-$b)*0.55);
        $corClara = "rgb({$rC},{$gC},{$bC})";
        // Versão escura — reduz 35%
        $rE = round($r*0.65);
        $gE = round($g*0.65);
        $bE = round($b*0.65);
        $corEscura = "rgb({$rE},{$gE},{$bE})";
        // Online — amarelo dourado (igual ao modelo físico)
        $corOnline     = '#F5C518';
        $corOnlineText = '#7a5f00';
    @endphp
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: white;
            padding: 20px;
        }

        .btn-imprimir {
            position: fixed; top: 20px; right: 220px;
            background: {{ $cor }}; color: white; border: none;
            padding: 10px 20px; border-radius: 6px; font-size: 14px;
            cursor: pointer; z-index: 999; box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .header-grade {
            border-top: 5px solid {{ $cor }};
            border-bottom: 2px solid {{ $cor }};
            padding: 14px 16px; margin-bottom: 14px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .header-grade img { height: 56px; }
        .header-grade .titulo { text-align: center; }
        .header-grade .titulo .subtitulo-curso {
            font-size: 13px; color: #444; letter-spacing: 2px;
            text-transform: uppercase; display: block; margin-bottom: 6px;
            font-weight: bold;
        }
        .header-grade .titulo h4 {
            font-size: 32px; font-weight: 900;
            letter-spacing: 3px; color: #1a1a1a;
            text-transform: uppercase; line-height: 1;
        }
        .header-grade .titulo .nome-curso {
            font-size: 15px; color: #333; display: block; margin-top: 6px;
            font-weight: bold; letter-spacing: 1px; text-transform: uppercase;
        }
        .header-grade .info-turma { text-align: right; }
        .header-grade .info-turma .label-periodo {
            font-size: 12px; color: #555; text-transform: uppercase;
            letter-spacing: 1px; font-weight: bold;
        }
        .header-grade .info-turma .periodo {
            font-weight: 900; font-size: 26px; color: #1a1a1a; line-height: 1.1;
        }
        .header-grade .info-turma .turma-info {
            font-size: 13px; color: #333; margin-top: 4px; font-weight: bold;
        }
        .badge-semestre {
            background: {{ $cor }}; color: white;
            padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;
        }

        table { width: 100%; border-collapse: collapse; font-size: 12px; }

        /* Cabeçalho dias — versão escura da cor */
        thead th {
            background: {{ $corEscura }};
            color: white; padding: 12px 8px;
            text-align: center; font-weight: bold;
            letter-spacing: 1px;
            font-size: 13px;
            border: 2px solid white;
        }
        thead th:first-child { width: 110px; }

        tbody td { vertical-align: middle; text-align: center; padding: 0; border: 2px solid white; }

        /* Horário — cor principal */
        .col-horario {
            background: {{ $cor }}; color: white;
            padding: 0 8px; height: 110px; font-weight: bold; font-size: 13px;
            display: flex; align-items: center; justify-content: center;
            white-space: nowrap;
        }

        /* Horário intervalo — cor principal */
        .col-horario-intervalo {
            background: {{ $cor }}; color: white;
            padding: 0 8px; height: 55px; font-weight: bold; font-size: 13px;
            display: flex; align-items: center; justify-content: center;
            white-space: nowrap;
        }

        /* Aula presencial — cor principal */
        .celula-aula {
            background: {{ $cor }}; padding: 12px 10px; height: 110px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .celula-aula .disciplina {
            font-weight: bold; color: white;
            font-size: 14px; line-height: 1.5;
            word-break: break-word; white-space: normal;
        }

        /* Aula online — mesma cor principal com label ONLINE */
        .celula-online {
            background: {{ $cor }}; padding: 12px 10px; height: 110px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .celula-online .disciplina {
            font-weight: bold; color: white;
            font-size: 14px; line-height: 1.5;
            word-break: break-word; white-space: normal;
        }
        .celula-online .online-label {
            color: rgba(255,255,255,0.85); font-size: 12px; margin-top: 6px; font-weight: bold;
            background: rgba(0,0,0,0.2); padding: 2px 8px; border-radius: 3px;
        }

        /* Intervalo — cor principal */
        .celula-intervalo {
            background: {{ $cor }}; height: 55px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: bold; color: white; letter-spacing: 0.5px;
        }

        /* Intervalo online — cor principal */
        .celula-intervalo-online {
            background: {{ $cor }}; height: 55px;
            display: flex; align-items: center; justify-content: center;
        }
        .badge-online-intervalo {
            background: rgba(0,0,0,0.25); color: white;
            padding: 4px 12px; border-radius: 3px; font-size: 12px; font-weight: bold;
        }

        /* Vazia — branco */
        .celula-vazia {
            background: white; height: 110px;
            display: flex; align-items: center; justify-content: center;
            color: #ddd; font-size: 18px;
        }

        /* Linha professor */
        .col-prof-label {
            background: {{ $cor }}; color: white;
            font-weight: bold; font-size: 13px; letter-spacing: 0.5px; padding: 14px 6px;
        }
        .celula-professor {
            background: {{ $cor }}; padding: 12px 6px; font-size: 13px;
        }
        .prof-nome { font-weight: bold; color: white; font-size: 13px; }
        .prof-local { color: rgba(255,255,255,0.85); font-size: 12px; margin-top: 4px; font-weight: bold; }

        .info-bar {
            border-left: 6px solid {{ $corEscura }};
            background: {{ $cor }};
            padding: 14px 16px; margin-top: 14px;
            font-size: 15px;
            display: flex; flex-wrap: wrap; gap: 24px; align-items: center;
            line-height: 1.8; color: white;
        }
        .info-bar strong { color: white; }
        .info-bar .contato {
            margin-left: auto;
            color: white;
            font-size: 16px;
            font-weight: bold;
        }

        @media print {
            .btn-imprimir { display: none !important; }
            body { padding: 5px; }
        }
    </style>

</head>
<body>

<button class="btn-imprimir" id="btnImprimir" onclick="window.print()">&#128424; Imprimir / Salvar PDF</button>

<div class="header-grade">
    <div>
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="UniSENAI MT" style="height:56px">
        @else
            <span style="font-size:22px;font-weight:900;color:{{ $cor }}">UniSENAI MT</span>
        @endif
    </div>
    <div class="titulo">
        <span class="subtitulo-curso">Curso Superior de Tecnologia</span>
        <h4>Grade de Horários</h4>
        <span class="nome-curso">{{ $turma->curso->nome ?? '' }}</span>
    </div>
    <div class="info-turma">
        <div class="label-periodo">Período Letivo</div>
        <div class="periodo">{{ $periodo->nome }}</div>
        <div class="turma-info">
            Turma <strong>{{ $turma->nome }}</strong>
            <span class="badge-semestre">{{ $turma->semestre }}º SEM.</span>
        </div>
    </div>
</div>

@if(count($horarios) === 0)
    <div style="text-align:center;padding:60px;color:#999;font-size:14px">Nenhuma aula cadastrada.</div>
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
                    <span style="color:#ddd">—</span>
                @endif
            </td>
            @endforeach
        </tr>
    </tbody>
</table>

@if($periodo->avaliacao1_inicio || $periodo->avaliacao2_inicio || ($turma->curso && $turma->curso->telefone_coord))
@php
    $telefoneCoord  = $turma->curso->telefone_coord ?? '';
    $telefoneLimpo  = preg_replace('/\D/', '', $telefoneCoord);
    $whatsappLink   = $telefoneLimpo ? "https://wa.me/55{$telefoneLimpo}" : '';
    $qrUrl          = $whatsappLink ? "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($whatsappLink) . "&color=ffffff&bgcolor=" . ltrim($cor, '#') : '';
@endphp
<div style="display:flex;align-items:stretch;margin-top:12px;border-radius:4px;overflow:hidden;background:{{ $cor }}">

    {{-- Bloco 1: Avaliações --}}
    <div style="flex:1;padding:10px 16px;color:white;font-size:13px;line-height:1.8;border-right:2px solid rgba(255,255,255,0.2);display:flex;flex-direction:column;justify-content:flex-start">
        <strong style="font-size:13px;display:block;margin-bottom:4px">INFORMAÇÕES</strong>
        @if($periodo->avaliacao1_inicio)
        <div>&#9632; <strong>Avaliação 1:</strong> {{ $periodo->avaliacao1_inicio->format('d/m') }} a {{ $periodo->avaliacao1_fim?->format('d/m/Y') }}</div>
        @endif
        @if($periodo->avaliacao2_inicio)
        <div>&#9632; <strong>Avaliação 2:</strong> {{ $periodo->avaliacao2_inicio->format('d/m') }} a {{ $periodo->avaliacao2_fim?->format('d/m/Y') }}</div>
        @endif
    </div>

    {{-- Bloco 2: Contato Coordenação --}}
    <div style="flex:1;padding:10px 16px;color:white;font-size:13px;display:flex;flex-direction:column;justify-content:flex-start;border-right:2px solid rgba(255,255,255,0.2)">
        <strong style="font-size:13px;display:block;margin-bottom:4px;letter-spacing:0.5px">CONTATO COORDENAÇÃO</strong>
        @if($turma->curso && $turma->curso->email_coord)
        <div style="font-size:12px;margin-bottom:3px;line-height:1.8">
            ✉ {{ $turma->curso->email_coord }}
        </div>
        @endif
        @if($turma->curso && $turma->curso->telefone_coord)
        <div style="font-size:12px;display:flex;align-items:center;gap:5px;line-height:1.8">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            {{ $turma->curso->telefone_coord }}
        </div>
        @endif
    </div>

    {{-- Bloco 3: QR Code gerado por JS --}}
    @if($whatsappLink)
    <div style="background:{{ $corEscura }};padding:8px 12px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:110px">
        @if($qrBase64)
        @php
            $qrSvgClean = preg_replace('/<\?xml[^>]+\?>\s*/', '', $qrBase64);
            $isSvg = str_contains($qrSvgClean, '<svg');
        @endphp
        @if($isSvg)
            <div style="background:white;padding:3px;border-radius:4px;line-height:0;width:90px;height:90px;overflow:hidden">{!! $qrSvgClean !!}</div>
        @else
            <img src="{{ $qrBase64 }}" alt="QR WhatsApp" width="90" height="90"
                 style="display:block;border-radius:4px;background:white;padding:3px">
        @endif
        @else
        <div id="qrcode" style="background:white;padding:3px;border-radius:4px;width:90px;height:90px"></div>
        @endif
        <div style="color:white;font-size:9px;font-weight:bold;margin-top:4px;text-align:center;line-height:1.4">
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="white" style="vertical-align:middle"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Fale com a<br>Coordenação
        </div>
    </div>
    @endif

</div>
@endif
@endif

<script>
    // NÃO auto-imprime — a página carrega rápido e o usuário clica em Imprimir quando quiser.
    // (o preview de impressão do Chrome pode ser lento; ao separar visualização de impressão,
    //  a grade aparece instantaneamente e o usuário decide a hora de imprimir)
    window.addEventListener('beforeprint', function() { var b=document.getElementById('btnImprimir'); if(b) b.style.display='none'; });
    window.addEventListener('afterprint', function() { var b=document.getElementById('btnImprimir'); if(b) b.style.display='block'; });
</script>
</body>
</html>
