{{-- resources/views/livewire/relatorio-grade.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap me-2" style="color:#E30613"></i>Relatório — Grade de Horários</h2>
            <small class="text-muted">Visualize e exporte a grade por turma e período letivo</small>
        </div>
        @if($turma && count($periodosAtivos) > 0 && $totalAulas > 0)
        <div class="d-flex gap-2 d-print-none">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Imprimir / PDF
            </button>
            <button wire:click="exportarCsv" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar CSV
            </button>
        </div>
        @endif
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4 d-print-none">
        <div class="card-body">
            <div class="row g-3 align-items-start">

                {{-- Turma --}}
                <div class="col-md-5">
                    <label class="form-label fw-medium">Turma</label>
                    <select wire:model.live="turma_id" class="form-select">
                        <option value="">Selecione a turma...</option>
                        @foreach($turmas as $t)
                        <option value="{{ $t->id }}">{{ $t->nome }} — {{ $t->curso->sigla }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Períodos letivos - checkboxes --}}
                <div class="col-md-7">
                    <label class="form-label fw-medium">
                        Período(s) Letivo(s)
                        <span class="text-muted small fw-normal">— selecione um ou mais</span>
                    </label>
                    <div class="border rounded p-2" style="background:white">

                        {{-- Botão Todos --}}
                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
                            <button type="button" wire:click="toggleTodosPeriodos"
                                class="btn btn-sm {{ count($periodosSelecionados) >= count($periodosLetivos) && count($periodosLetivos) > 0 ? 'btn-dark' : 'btn-outline-dark' }}">
                                <i class="bi bi-check-all me-1"></i>
                                {{ count($periodosSelecionados) >= count($periodosLetivos) && count($periodosLetivos) > 0 ? 'Desmarcar todos' : 'Selecionar todos' }}
                            </button>
                            @if(count($periodosSelecionados) > 0)
                            <span class="badge bg-primary">{{ count($periodosSelecionados) }} selecionado(s)</span>
                            @endif
                        </div>

                        {{-- Checkboxes --}}
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($periodosLetivos as $p)
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="checkbox"
                                    wire:model.live="periodosSelecionados"
                                    value="{{ $p->id }}"
                                    id="per_{{ $p->id }}">
                                <label class="form-check-label" for="per_{{ $p->id }}">
                                    <span class="badge {{ in_array($p->id, $periodosSelecionados) ? 'bg-primary' : 'bg-light text-dark border' }}"
                                          style="font-size:12px;cursor:pointer">
                                        {{ $p->nome }}
                                        @if($p->ativo)<i class="bi bi-circle-fill ms-1" style="font-size:6px;color:#4ade80"></i>@endif
                                    </span>
                                </label>
                            </div>
                            @endforeach
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Resultados --}}
    @if(!$turma_id)
    <div class="text-center text-muted py-5">
        <i class="bi bi-arrow-up-circle fs-1 d-block mb-3 opacity-25"></i>
        <p>Selecione uma turma para começar.</p>
    </div>

    @elseif(empty($periodosSelecionados))
    <div class="text-center text-muted py-5">
        <i class="bi bi-calendar-check fs-1 d-block mb-3 opacity-25"></i>
        <p>Selecione ao menos um período letivo.</p>
    </div>

    @elseif($totalAulas === 0)
    <div class="text-center text-muted py-5">
        <i class="bi bi-calendar-x fs-1 d-block mb-3 opacity-25"></i>
        <p>Nenhuma aula cadastrada para os filtros selecionados.</p>
        <a href="{{ route('aulas') }}" class="btn btn-primary mt-2"><i class="bi bi-plus me-1"></i>Cadastrar Aulas</a>
    </div>

    @else

    {{-- Uma grade por período selecionado --}}
    @foreach($periodosAtivos as $periodo)
    @if(isset($grade[$periodo->id]))

    {{-- Cabeçalho do período --}}
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">{{ $turma->curso->nome }} — {{ $turma->nome }}</h5>
                    <span class="text-muted small">Período Letivo: <strong>{{ $periodo->nome }}</strong></span>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge" style="background:#E30613;font-size:13px">{{ $turma->semestre }}º Semestre</span>
                    @if($periodo->ativo)<span class="badge bg-success">Ativo</span>@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Grade --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="font-size:13px">
                    <thead>
                        <tr style="background:#1a1a1a;color:white">
                            <th class="text-center py-3" style="width:110px">Horário</th>
                            @foreach($dias as $num => $nome)
                            <th class="text-center py-3">{{ $nome }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($horarios as $horario)
                        @php $isIntervalo = strtolower($horario->tipo) === 'intervalo'; @endphp
                        <tr style="{{ $isIntervalo ? 'background:#f5f5f5' : '' }}">
                            <td class="text-center fw-bold py-2" style="background:#f0f0f0;font-size:11px;line-height:1.3">
                                {{ substr($horario->hora_inicio,0,5) }}<br>{{ substr($horario->hora_fim,0,5) }}
                                @if($isIntervalo)<br><span class="text-muted fw-normal">intervalo</span>@endif
                            </td>
                            @foreach($dias as $numDia => $nomeDia)
                            @php $aula = $grade[$periodo->id][$horario->id][$numDia] ?? null; @endphp
                            <td class="text-center py-2" style="{{ $isIntervalo ? 'background:#f5f5f5' : '' }}">
                                @if($isIntervalo)
                                    <span class="text-muted small">—</span>
                                @elseif($aula)
                                    <div class="fw-semibold" style="font-size:12px">{{ $aula->disciplina->nome }}</div>
                                    <div class="text-muted" style="font-size:11px">{{ $aula->professor->nome }}</div>
                                    @if($aula->sala)
                                    <div style="font-size:10px;color:#555"><i class="bi bi-building me-1"></i>{{ $aula->sala->nome }}</div>
                                    @else
                                    <div style="font-size:10px;color:#0d6efd"><i class="bi bi-wifi me-1"></i>Online</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @endif
    @endforeach
    @endif

    <style>
        @media print {
            .d-print-none { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</div>
