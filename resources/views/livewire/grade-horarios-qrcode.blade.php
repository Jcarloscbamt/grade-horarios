{{-- resources/views/livewire/grade-horarios.blade.php --}}
<div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Grade de Horários</h2>
            <small class="text-muted">Visualização da grade por turma e período letivo</small>
        </div>
        @if($turma_id && $periodo_letivo_id)
        <a href="{{ route('grade.imprimir', ['turma_id' => $turma_id, 'periodo_letivo_id' => $periodo_letivo_id]) }}"
           target="_blank" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Imprimir
        </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-medium">Turma</label>
                    <select wire:model.live="turma_id" class="form-select">
                        <option value="">Selecione a turma...</option>
                        @foreach($turmas as $turma)
                        <option value="{{ $turma->id }}">{{ $turma->nome }} — {{ $turma->curso->sigla ?? '' }} {{ $turma->semestre }}º sem</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-medium">Período Letivo</label>
                    <select wire:model.live="periodo_letivo_id" class="form-select">
                        <option value="">Selecione o período...</option>
                        @foreach($periodosLetivos as $periodo)
                        <option value="{{ $periodo->id }}">
                            {{ $periodo->nome }}{{ $periodo->ativo ? ' — Ativo' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button wire:click="$set('turma_id', '')" class="btn btn-light w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($turma_id && $periodo_letivo_id)

        @if(count($horarios) === 0)
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                    Nenhuma aula cadastrada para esta turma neste período.
                    <div class="mt-3">
                        <a href="{{ route('aulas') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Cadastrar Aulas
                        </a>
                    </div>
                </div>
            </div>
        @else

        @php
            $turmaAtual   = $turmas->find($turma_id);
            $periodoAtual = $periodosLetivos->find($periodo_letivo_id);
            $cor          = $turmaAtual->curso->cor_grade ?? '#E30613';
            $hex          = ltrim($cor, '#');
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
            $rC = round($r + (255-$r)*0.55);
            $gC = round($g + (255-$g)*0.55);
            $bC = round($b + (255-$b)*0.55);
            $corClara  = "rgb({$rC},{$gC},{$bC})";
            $rE = round($r*0.65);
            $gE = round($g*0.65);
            $bE = round($b*0.65);
            $corEscura = "rgb({$rE},{$gE},{$bE})";
            // WhatsApp link
            $telefone = $turmaAtual->curso->telefone_coord ?? '';
            $telefoneNumeros = preg_replace('/\D/', '', $telefone);
            $whatsappLink = $telefoneNumeros ? "https://wa.me/55{$telefoneNumeros}" : '';
        @endphp

        {{-- Cabeçalho da grade --}}
        <div class="card border-0 shadow-sm mb-3" style="border-top:5px solid {{ $cor }}">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-3 d-flex align-items-center">
                        @php $logoPath = public_path('images/logo-unisenai.png'); @endphp
                        @if(file_exists($logoPath))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}"
                                 alt="UniSENAI MT" style="height:48px;max-width:160px;object-fit:contain">
                        @else
                            <span style="font-size:20px;font-weight:900;color:{{ $cor }}">UniSENAI MT</span>
                        @endif
                    </div>
                    <div class="col-md-6 text-center">
                        <div style="font-size:11px;color:#888;letter-spacing:2px;text-transform:uppercase;font-weight:bold">Curso Superior de Tecnologia</div>
                        <h4 class="fw-bold mb-0" style="letter-spacing:2px;font-size:20px">GRADE DE HORÁRIOS</h4>
                        <div style="font-size:13px;color:#555;font-weight:bold;text-transform:uppercase">{{ $turmaAtual->curso->nome ?? '' }}</div>
                    </div>
                    <div class="col-md-3 text-end">
                        <div style="font-size:10px;color:#888;text-transform:uppercase;letter-spacing:1px;font-weight:bold">Período Letivo</div>
                        <div style="font-size:22px;font-weight:900;color:#1a1a1a;line-height:1.1">{{ $periodoAtual->nome }}</div>
                        <div style="font-size:12px;color:#444;font-weight:bold;margin-top:4px">
                            Turma <strong>{{ $turmaAtual->nome }}</strong>
                            <span style="background:{{ $cor }};color:white;padding:2px 8px;border-radius:4px;font-size:11px;margin-left:4px">{{ $turmaAtual->semestre }}º SEM.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabela da grade --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table style="width:100%;border-collapse:separate;border-spacing:3px;font-size:13px">
                        <thead>
                            <tr>
                                <th style="background:{{ $corEscura }};color:white;padding:12px 8px;text-align:center;font-weight:bold;letter-spacing:1px;width:120px;border-radius:4px">HORÁRIO</th>
                                @foreach($dias as $num => $nome)
                                <th style="background:{{ $corEscura }};color:white;padding:12px 8px;text-align:center;font-weight:bold;letter-spacing:1px;border-radius:4px">{{ $nome }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($horarios as $horario)
                            @php $isIntervalo = strtolower($horario->tipo) === 'intervalo'; @endphp
                            <tr>
                                <td style="border-radius:4px;padding:0;vertical-align:middle;text-align:center">
                                    @if($isIntervalo)
                                    <div style="background:{{ $cor }};color:white;padding:10px 8px;height:55px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;border-radius:4px">
                                        {{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fim,0,5) }}
                                    </div>
                                    @else
                                    <div style="background:{{ $cor }};color:white;padding:0 8px;height:110px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;border-radius:4px">
                                        {{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fim,0,5) }}
                                    </div>
                                    @endif
                                </td>
                                @foreach($dias as $numDia => $nomeDia)
                                @php $aula = $grade[$horario->id][$numDia] ?? null; @endphp
                                <td style="padding:0;vertical-align:middle;text-align:center;border-radius:4px">
                                    @if($isIntervalo)
                                        @if($aula && $aula->modalidade === 'online')
                                        <div style="background:{{ $cor }};height:55px;display:flex;align-items:center;justify-content:center;border-radius:4px">
                                            <span style="background:rgba(0,0,0,0.25);color:white;padding:3px 10px;border-radius:3px;font-size:12px;font-weight:bold">ONLINE</span>
                                        </div>
                                        @else
                                        <div style="background:{{ $cor }};color:white;height:55px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:bold;letter-spacing:0.5px;border-radius:4px">
                                            INTERVALO
                                        </div>
                                        @endif
                                    @elseif($aula)
                                        @if($aula->modalidade === 'online')
                                        <div style="background:{{ $cor }};padding:10px 8px;height:110px;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:4px">
                                            <div style="font-weight:bold;color:white;font-size:13px;line-height:1.4;word-break:break-word">{{ $aula->disciplina->nome }}</div>
                                            <div style="background:rgba(0,0,0,0.2);color:white;font-size:11px;margin-top:6px;padding:2px 8px;border-radius:3px;font-weight:bold">ONLINE</div>
                                        </div>
                                        @else
                                        <div style="background:{{ $cor }};padding:10px 8px;height:110px;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:4px">
                                            <div style="font-weight:bold;color:white;font-size:13px;line-height:1.4;word-break:break-word">{{ $aula->disciplina->nome }}</div>
                                        </div>
                                        @endif
                                    @else
                                        <div style="background:white;height:110px;display:flex;align-items:center;justify-content:center;color:#ddd;font-size:18px;border-radius:4px;border:1px solid #eee">—</div>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach

                            {{-- Linha professor/local --}}
                            <tr>
                                <td style="padding:0;border-radius:4px">
                                    <div style="background:{{ $corEscura }};color:white;font-weight:bold;font-size:12px;letter-spacing:0.5px;padding:12px 6px;text-align:center;border-radius:4px">
                                        PROFESSOR(A)<br>LOCAL
                                    </div>
                                </td>
                                @foreach($dias as $numDia => $nomeDia)
                                @php
                                    $aulaDia = collect($grade)->flatMap(fn($h) => collect($h))->filter(fn($a) => $a->dia_semana == $numDia)->first();
                                @endphp
                                <td style="padding:0;border-radius:4px">
                                    <div style="background:{{ $cor }};padding:10px 6px;text-align:center;border-radius:4px">
                                        @if($aulaDia)
                                            <div style="font-weight:bold;color:white;font-size:13px">{{ strtoupper($aulaDia->professor->nome) }}</div>
                                            <div style="color:rgba(255,255,255,0.85);font-size:12px;margin-top:3px;font-weight:bold">{{ $aulaDia->sala?->nome ?? 'ONLINE' }}</div>
                                        @else
                                            <span style="color:rgba(255,255,255,0.3)">—</span>
                                        @endif
                                    </div>
                                </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Informações + QR Code --}}
        @if($periodoAtual->avaliacao1_inicio || $periodoAtual->avaliacao2_inicio || $whatsappLink)
        <div class="mt-3 d-flex align-items-stretch gap-0" style="border-radius:4px;overflow:hidden">

            {{-- Avaliações --}}
            <div style="background:{{ $cor }};padding:14px 20px;flex:1;color:white;font-size:15px;line-height:2">
                <strong>INFORMAÇÕES</strong><br>
                @if($periodoAtual->avaliacao1_inicio)
                <span>&#9632; <strong>Avaliação 1:</strong> {{ $periodoAtual->avaliacao1_inicio->format('d/m') }} a {{ $periodoAtual->avaliacao1_fim?->format('d/m/Y') }}</span><br>
                @endif
                @if($periodoAtual->avaliacao2_inicio)
                <span>&#9632; <strong>Avaliação 2:</strong> {{ $periodoAtual->avaliacao2_inicio->format('d/m') }} a {{ $periodoAtual->avaliacao2_fim?->format('d/m/Y') }}</span><br>
                @endif
                @if($turmaAtual->curso && $turmaAtual->curso->email_coord)
                <span style="font-size:13px">✉ {{ $turmaAtual->curso->email_coord }}</span>
                @endif
            </div>

            {{-- QR Code WhatsApp --}}
            @if($whatsappLink)
            <div style="background:{{ $corEscura }};padding:14px 20px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:140px">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($whatsappLink) }}&color=ffffff&bgcolor={{ urlencode(substr($corEscura, 4, -1)) }}"
                     alt="QR WhatsApp"
                     style="width:100px;height:100px;border-radius:4px"
                     onerror="this.style.display='none'">
                <div style="color:white;font-size:11px;font-weight:bold;margin-top:6px;text-align:center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white" style="vertical-align:middle;margin-right:3px"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Fale com a<br>Coordenação
                </div>
            </div>
            @endif
        </div>
        @endif

        @endif

    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-grid-3x3-gap-fill fs-1 d-block mb-3" style="opacity:0.3"></i>
                <h5 class="fw-semibold">Selecione uma turma e um período letivo</h5>
                <p class="mb-0">A grade de horários será exibida automaticamente.</p>
            </div>
        </div>
    @endif

</div>
