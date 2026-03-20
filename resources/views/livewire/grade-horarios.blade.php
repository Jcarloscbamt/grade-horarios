{{-- resources/views/livewire/grade-horarios.blade.php --}}
<div>

    {{-- Cabeçalho --}}
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

    {{-- Filtros --}}
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
                                {{-- Coluna horário --}}
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

        {{-- Informações do período --}}
        @if($periodoAtual->avaliacao1_inicio || $periodoAtual->avaliacao2_inicio)
        <div class="mt-3 d-flex flex-wrap gap-4 align-items-center" style="border-left:6px solid {{ $cor }};background:{{ $cor }};padding:14px 16px;border-radius:4px;font-size:15px;color:white">
            <strong>INFORMAÇÕES</strong>
            @if($periodoAtual->avaliacao1_inicio)
            <span>&#9632; <strong>Avaliação 1:</strong> {{ $periodoAtual->avaliacao1_inicio->format('d/m') }} a {{ $periodoAtual->avaliacao1_fim?->format('d/m/Y') }}</span>
            @endif
            @if($periodoAtual->avaliacao2_inicio)
            <span>&#9632; <strong>Avaliação 2:</strong> {{ $periodoAtual->avaliacao2_inicio->format('d/m') }} a {{ $periodoAtual->avaliacao2_fim?->format('d/m/Y') }}</span>
            @endif
            @if($turmaAtual->curso && $turmaAtual->curso->email_coord)
            <span style="margin-left:auto;font-size:14px;font-weight:bold">
                ✉ {{ $turmaAtual->curso->email_coord }}
                @if($turmaAtual->curso->telefone_coord)
                &nbsp;|&nbsp; {{ $turmaAtual->curso->telefone_coord }}
                @endif
            </span>
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
