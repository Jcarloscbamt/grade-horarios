{{-- resources/views/livewire/grade-horarios.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h2 class="fw-bold mb-0">Grade de Horários <button type="button" data-bs-toggle="modal" data-bs-target="#helpModal" class="btn btn-outline-secondary btn-sm rounded-circle ms-1" style="width:24px;height:24px;padding:0;font-size:12px;line-height:1" title="Ajuda">?</button></h2>
            <small class="text-muted">Visualização da grade por turma e período letivo</small>
        </div>
        {{-- Botões de impressão por turma selecionada --}}
        @if(!empty($turmasSelecionadas) && $periodo_letivo_id && count($grades) > 0)
        @php $idsComGrade = collect($turmasAtivas)->filter(fn($t) => isset($grades[$t->id]))->pluck('id')->toArray(); @endphp

        @if(count($idsComGrade) > 1)
        {{-- VÁRIAS turmas: um botão para todas (colorido) e outro (P&B) — 1 grade por página --}}
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-secondary align-self-center">{{ count($idsComGrade) }} turmas</span>
            <a href="{{ route('grade.imprimir', ['turma_ids' => implode(',', $idsComGrade), 'periodo_letivo_id' => $periodo_letivo_id, 'modo' => 'colorido']) }}"
               target="_blank" class="btn btn-secondary btn-sm">
                <i class="bi bi-printer me-1"></i>Imprimir todas — Colorido
            </a>
            <a href="{{ route('grade.imprimir', ['turma_ids' => implode(',', $idsComGrade), 'periodo_letivo_id' => $periodo_letivo_id, 'modo' => 'pb']) }}"
               target="_blank" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-file-earmark-text me-1"></i>Imprimir todas — P&B
            </a>
        </div>
        @else
        {{-- UMA turma: botões individuais (colorido + P&B) --}}
        <div class="d-flex flex-wrap gap-2">
            @foreach($turmasAtivas as $t)
            @if(isset($grades[$t->id]))
            <div class="d-flex gap-1">
                <a href="{{ route('grade.imprimir', ['turma_id' => $t->id, 'periodo_letivo_id' => $periodo_letivo_id, 'modo' => 'colorido']) }}"
                   target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i>{{ $t->nome }} Colorido
                </a>
                <a href="{{ route('grade.imprimir', ['turma_id' => $t->id, 'periodo_letivo_id' => $periodo_letivo_id, 'modo' => 'pb']) }}"
                   target="_blank" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-file-earmark-text me-1"></i>P&B
                </a>
            </div>
            @endif
            @endforeach
        </div>
        @endif
        @endif
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-start">

                {{-- Curso --}}
                <div class="col-md-3">
                    <label class="form-label fw-medium">Curso</label>
                    <select wire:model.live="curso_id" class="form-select">
                        <option value="">Todos os cursos</option>
                        @foreach($cursos as $curso)
                        <option value="{{ $curso->id }}">{{ $curso->sigla }} — {{ $curso->nome }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Turmas — multi-select --}}
                <div class="col-md-5">
                    <label class="form-label fw-medium">
                        Turma(s) <span class="text-muted small fw-normal">— selecione uma ou mais</span>
                    </label>
                    <div class="border rounded p-2" style="background:white;max-height:130px;overflow-y:auto">
                        @if($turmas->isEmpty())
                            <span class="text-muted small">Nenhuma turma disponível.</span>
                        @else
                        <div class="d-flex align-items-center gap-2 pb-1 mb-1 border-bottom">
                            <button type="button" wire:click="toggleTodasTurmas"
                                class="btn btn-sm {{ count($turmasSelecionadas) >= count($turmas) && count($turmas) > 0 ? 'btn-dark' : 'btn-outline-dark' }}">
                                <i class="bi bi-check-all me-1"></i>
                                {{ count($turmasSelecionadas) >= count($turmas) && count($turmas) > 0 ? 'Desmarcar' : 'Todas' }}
                            </button>
                            @if(count($turmasSelecionadas) > 0)
                            <span class="badge bg-primary">{{ count($turmasSelecionadas) }} selecionada(s)</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @foreach($turmas as $t)
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="checkbox"
                                    wire:model.live="turmasSelecionadas"
                                    value="{{ $t->id }}" id="tur_{{ $t->id }}">
                                <label class="form-check-label" for="tur_{{ $t->id }}">
                                    <span class="badge {{ in_array($t->id, $turmasSelecionadas) ? 'bg-primary' : 'bg-light text-dark border' }}"
                                          style="font-size:11px;cursor:pointer">
                                        {{ $t->nome }}
                                    </span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Período Letivo --}}
                <div class="col-md-3">
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

                <div class="col-md-1 d-flex align-items-end">
                    <button wire:click="limpar" class="btn btn-light w-100" title="Limpar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Resultados --}}
    @if(empty($turmasSelecionadas) || !$periodo_letivo_id)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-grid-3x3-gap-fill fs-1 d-block mb-3" style="opacity:0.3"></i>
            <h5 class="fw-semibold">Selecione uma turma e um período letivo</h5>
            <p class="mb-0">A grade de horários será exibida automaticamente.</p>
        </div>
    </div>

    @elseif(count($grades) === 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
            Nenhuma aula cadastrada para os filtros selecionados.
            <div class="mt-3">
                <a href="{{ route('aulas') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Cadastrar Aulas
                </a>
            </div>
        </div>
    </div>

    @else

    {{-- Uma grade por turma selecionada --}}
    @foreach($turmasAtivas as $turmaAtual)
    @if(!isset($grades[$turmaAtual->id])) @continue @endif
    @php
        $grade        = $grades[$turmaAtual->id];
        $periodoAtual = $periodoObj;
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
        $qrCodeSvg = $qrCodes[$turmaAtual->id] ?? '';
        $telefoneCoord = $turmaAtual->curso->telefone_coord ?? '';
        $telefoneLimpo = preg_replace('/\D/', '', $telefoneCoord);
        $whatsappLink  = $telefoneLimpo ? "https://wa.me/55{$telefoneLimpo}" : '';
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

    {{-- Tabela --}}
    <div class="card border-0 shadow-sm mb-2">
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
                                <div style="background:{{ $cor }};color:white;padding:0 8px;height:{{ $isIntervalo ? '55' : '110' }}px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;border-radius:4px">
                                    {{ substr($horario->hora_inicio,0,5) }} - {{ substr($horario->hora_fim,0,5) }}
                                </div>
                            </td>
                            @foreach($dias as $numDia => $nomeDia)
                            @php $aula = $grade[$horario->id][$numDia] ?? null; @endphp
                            <td style="padding:0;vertical-align:middle;text-align:center;border-radius:4px">
                                @if($isIntervalo)
                                    <div style="background:{{ $cor }};color:white;height:55px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:bold;border-radius:4px">INTERVALO</div>
                                @elseif($aula)
                                    <div style="background:{{ $cor }};padding:10px 8px;height:110px;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:4px">
                                        <div style="font-weight:bold;color:white;font-size:13px;line-height:1.4;word-break:break-word">{{ $aula->disciplina->nome }}</div>
                                        @if($aula->modalidade === 'online')
                                        <div style="background:rgba(0,0,0,0.2);color:white;font-size:11px;margin-top:6px;padding:2px 8px;border-radius:3px">ONLINE</div>
                                        @endif
                                    </div>
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
                                        <div style="color:rgba(255,255,255,0.85);font-size:12px;margin-top:3px;font-weight:bold">
                                            @if($aulaDia->modalidade === 'online')
                                                🌐 ONLINE
                                            @else
                                                {{ $aulaDia->sala?->nome ?? '—' }}
                                            @endif
                                        </div>
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

    {{-- Informações + Contato --}}
    @if($periodoAtual && ($periodoAtual->avaliacao1_inicio || $periodoAtual->avaliacao2_inicio || $whatsappLink))
    <div class="mb-4 d-flex align-items-stretch" style="border-radius:4px;overflow:hidden">
        <div style="flex:1;background:{{ $cor }};padding:10px 16px;color:white;font-size:13px;line-height:1.8;border-right:2px solid rgba(255,255,255,0.2)">
            <strong style="font-size:13px;display:block;margin-bottom:4px">INFORMAÇÕES</strong>
            @if($periodoAtual->avaliacao1_inicio)
            <div>&#9632; <strong>Avaliação 1:</strong> {{ $periodoAtual->avaliacao1_inicio->format('d/m') }} a {{ $periodoAtual->avaliacao1_fim?->format('d/m/Y') }}</div>
            @endif
            @if($periodoAtual->avaliacao2_inicio)
            <div>&#9632; <strong>Avaliação 2:</strong> {{ $periodoAtual->avaliacao2_inicio->format('d/m') }} a {{ $periodoAtual->avaliacao2_fim?->format('d/m/Y') }}</div>
            @endif
        </div>
        <div style="flex:1;background:{{ $cor }};padding:10px 16px;color:white;font-size:13px;border-right:2px solid rgba(255,255,255,0.2)">
            <strong style="font-size:13px;display:block;margin-bottom:4px">CONTATO COORDENAÇÃO</strong>
            @if($turmaAtual->curso?->email_coord)
            <div style="font-size:12px">✉ {{ $turmaAtual->curso->email_coord }}</div>
            @endif
            @if($turmaAtual->curso?->telefone_coord)
            <div style="font-size:12px">📱 {{ $turmaAtual->curso->telefone_coord }}</div>
            @endif
        </div>
        @if($qrCodeSvg)
        <div style="background:{{ $corEscura }};padding:8px 12px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:100px">
            @php $qrClean = preg_replace('/<\?xml[^>]+\?>\s*/', '', $qrCodeSvg); @endphp
            <div style="background:white;padding:3px;border-radius:4px;line-height:0">{!! $qrClean !!}</div>
            <div style="color:white;font-size:9px;font-weight:bold;margin-top:4px;text-align:center">Fale com a<br>Coordenação</div>
        </div>
        @endif
    </div>
    @else
    <div class="mb-4"></div>
    @endif

    @endforeach
    @endif


<x-help-modal titulo="Ajuda — Grade de Horários">
<p class="text-muted mb-3">Visualização e impressão da grade de horários por turma e período letivo.</p>
<ul class="list-unstyled">
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Filtros:</strong> filtre por curso, turmas e período letivo. Pode selecionar uma ou várias turmas (ou "Todas").</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Imprimir 1 turma:</strong> aparecem os botões individuais (Colorido e P&B) de cada turma.</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Imprimir várias turmas:</strong> aparecem os botões "Imprimir todas — Colorido" e "Imprimir todas — P&B". Na impressão, sai <strong>uma grade por página</strong> (cada turma em sua folha).</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Intervalo:</strong> a linha de intervalo aparece automaticamente entre os horários de aula, se houver um horário do tipo "intervalo" cadastrado.</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Online:</strong> disciplinas online aparecem destacadas, sem sala física.</li>
</ul>
<div class="alert alert-info py-2 mt-2" style="font-size:12px">
    <i class="bi bi-lightbulb me-1"></i>
    Cada grade impressa traz o QR code para falar com a coordenação do curso (se o telefone estiver cadastrado no curso).
</div>
</x-help-modal>
</div>