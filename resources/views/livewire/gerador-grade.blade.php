{{-- resources/views/livewire/gerador-grade.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Gerador de Grade</h2>
            <small class="text-muted">Geração automática de grade de horários por turma</small>
        </div>
        @if($previewGerado)
        <button wire:click="limpar" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Nova Geração
        </button>
        @endif
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @error('geral')
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $message }}
        </div>
    @enderror

    {{-- ══════════════════════════════════════════════════════
         ETAPA 1 — Seleção de Turma e Períodos
         ══════════════════════════════════════════════════════ --}}
    @if(!$previewGerado)
    <div class="row g-4">

        {{-- Selecionar Turma --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0">
                        <span class="badge bg-primary me-2">1</span>
                        Selecionar Turma
                    </h6>
                </div>
                <div class="card-body">
                    <select wire:model.live="turma_id" class="form-select">
                        <option value="">Selecione a turma...</option>
                        @foreach($turmas as $turma)
                        <option value="{{ $turma->id }}">
                            {{ $turma->nome }} — {{ $turma->curso->sigla ?? '' }}
                        </option>
                        @endforeach
                    </select>

                    @if($turma_id)
                    @php
                        $turmaSel = $turmas->find($turma_id);
                        $qtdVinculos = \App\Models\ProfessorDisciplina::where('turma_id', $turma_id)->count();
                    @endphp
                    <div class="mt-3 p-3 rounded border bg-light">
                        <div class="fw-semibold">{{ $turmaSel->nome }}</div>
                        <div class="text-muted small">{{ $turmaSel->curso->nome ?? '' }}</div>
                        <div class="mt-2">
                            @if($qtdVinculos > 0)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>
                                    {{ $qtdVinculos }} disciplina(s) vinculada(s)
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Nenhuma disciplina vinculada. Cadastre vínculos nos professores.
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Selecionar Períodos Letivos --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0">
                        <span class="badge bg-primary me-2">2</span>
                        Selecionar Período(s) Letivo(s)
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Marque os períodos para os quais deseja gerar a grade.
                        Pode selecionar múltiplos períodos.
                    </p>
                    <div class="row g-2">
                        @foreach($periodosLetivos as $periodo)
                        <div class="col-md-6">
                            <div class="form-check p-0">
                                <label class="d-flex align-items-center gap-2 p-2 rounded border cursor-pointer
                                    {{ in_array($periodo->id, $periodos_selecionados) ? 'border-primary bg-primary bg-opacity-10' : 'border-light' }}"
                                    style="cursor:pointer">
                                    <input type="checkbox"
                                        wire:model.live="periodos_selecionados"
                                        value="{{ $periodo->id }}"
                                        class="form-check-input mt-0 flex-shrink-0">
                                    <div>
                                        <div class="fw-semibold" style="font-size:14px">{{ $periodo->nome }}</div>
                                        <div class="d-flex gap-1 mt-1">
                                            <span class="badge bg-secondary" style="font-size:10px">
                                                {{ $periodo->semestre }}º Sem
                                            </span>
                                            <span class="badge {{ $periodo->ativo ? 'bg-success' : 'bg-light text-muted border' }}"
                                                  style="font-size:10px">
                                                {{ $periodo->ativo ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @if(!empty($periodos_selecionados))
                    <div class="mt-3 text-muted small">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        {{ count($periodos_selecionados) }} período(s) selecionado(s)
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Botão Gerar --}}
        <div class="col-12">
            <button wire:click="gerarPrevia"
                wire:loading.attr="disabled"
                class="btn btn-primary btn-lg px-5"
                {{ (!$turma_id || empty($periodos_selecionados)) ? 'disabled' : '' }}>
                <span wire:loading wire:target="gerarPrevia"
                    class="spinner-border spinner-border-sm me-2"></span>
                <i wire:loading.remove wire:target="gerarPrevia"
                    class="bi bi-magic me-2"></i>
                Gerar Prévia da Grade
            </button>
            <span class="text-muted small ms-3">
                O sistema alocará disciplinas, professores e salas automaticamente.
            </span>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         ETAPA 2 — Prévia + Conflitos
         ══════════════════════════════════════════════════════ --}}
    @if($previewGerado)

    {{-- Conflitos --}}
    @if(!empty($conflitos))
    <div class="card border-danger border-2 mb-4">
        <div class="card-header bg-danger text-white py-2">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>{{ count($conflitos) }} conflito(s) detectado(s) — a grade não pode ser salva</strong>
        </div>
        <div class="card-body p-0">
            @foreach($conflitos as $i => $conflito)
            <div class="d-flex align-items-start gap-3 p-3 {{ $i < count($conflitos) - 1 ? 'border-bottom' : '' }}">
                <div class="text-danger mt-1">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="flex-grow-1">
                    <div style="font-size:13px">{!! $conflito['mensagem'] !!}</div>
                    <div class="text-muted small mt-1">
                        @if($conflito['tipo'] === 'sem_dia')
                            <i class="bi bi-arrow-right me-1"></i>
                            Acesse o cadastro de Professores e defina os dias disponíveis para este vínculo.
                        @elseif($conflito['tipo'] === 'sem_slot')
                            <i class="bi bi-arrow-right me-1"></i>
                            Redistribua os dias disponíveis do professor ou reduza o número de turmas.
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="card-footer bg-light py-2">
            <a href="{{ route('professores') }}" class="btn btn-sm btn-outline-danger me-2">
                <i class="bi bi-person-gear me-1"></i> Ir para Professores
            </a>
            <button wire:click="limpar" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Tentar Novamente
            </button>
        </div>
    </div>
    @endif

    {{-- Avisos de sala (não bloqueantes) --}}
    @if(!empty($avisosSemSala))
    <div class="card border-warning border-2 mb-4">
        <div class="card-header bg-warning bg-opacity-25 py-2">
            <i class="bi bi-exclamation-circle me-2"></i>
            <strong>{{ count($avisosSemSala) }} aviso(s) de sala</strong>
            — a grade pode ser salva, mas algumas aulas ficarão sem sala.
        </div>
        <div class="card-body p-0">
            @foreach($avisosSemSala as $i => $aviso)
            <div class="d-flex align-items-start gap-3 p-3 {{ $i < count($avisosSemSala) - 1 ? 'border-bottom' : '' }}">
                <div class="text-warning mt-1"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div style="font-size:13px">{!! $aviso['mensagem'] !!}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Prévia das grades geradas --}}
    @foreach($preview as $periodoId => $diasGrade)
    @php
        $periodoInfo = $periodosLetivos->find($periodoId);
    @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold">
                <i class="bi bi-calendar3 me-2"></i>
                Prévia — {{ $periodoInfo->nome ?? "Período $periodoId" }}
                <span class="badge bg-secondary ms-2">{{ $periodoInfo->semestre ?? '' }}º Sem</span>
            </span>
            <span class="badge bg-success">{{ count($diasGrade) }} dia(s) alocado(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:120px">HORÁRIO</th>
                            @foreach($diasNomes as $num => $label)
                            <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($horarios as $horario)
                        <tr class="{{ $horario->tipo === 'intervalo' ? 'table-secondary' : '' }}">
                            <td class="fw-semibold" style="font-size:12px">
                                {{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }}
                                –
                                {{ \Carbon\Carbon::parse($horario->hora_fim)->format('H:i') }}
                                @if($horario->tipo === 'intervalo')
                                <br><small class="text-muted">INTERVALO</small>
                                @endif
                            </td>
                            @foreach($diasNomes as $num => $label)
                            <td style="font-size:11px;min-width:120px">
                                @if($horario->tipo === 'intervalo')
                                    <span class="text-muted">INTERVALO</span>
                                @elseif(isset($diasGrade[$num][$horario->id]))
                                    @php $aula = $diasGrade[$num][$horario->id]; @endphp
                                    <div class="fw-semibold">{{ $aula['disciplina_nome'] }}</div>
                                    <div class="text-muted mt-1">
                                        <i class="bi bi-person me-1"></i>{{ $aula['professor_nome'] }}
                                    </div>
                                    @if($aula['sala_nome'])
                                    <div class="text-primary mt-1" style="font-size:10px">
                                        <i class="bi bi-building me-1"></i>{{ $aula['sala_nome'] }}
                                    </div>
                                    @else
                                    <div class="text-warning mt-1" style="font-size:10px">
                                        <i class="bi bi-exclamation-circle me-1"></i>Sem sala
                                    </div>
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
    @endforeach

    {{-- Botões de ação --}}
    <div class="d-flex gap-3 align-items-center mt-2 mb-5">
        <button wire:click="confirmarSalvar"
            wire:loading.attr="disabled"
            class="btn btn-success btn-lg px-5 {{ !empty($conflitos) ? 'disabled' : '' }}">
            <span wire:loading wire:target="confirmarSalvar"
                class="spinner-border spinner-border-sm me-2"></span>
            <i wire:loading.remove wire:target="confirmarSalvar"
                class="bi bi-floppy me-2"></i>
            Confirmar e Salvar Grade
        </button>

        <button wire:click="limpar" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Recomeçar
        </button>

        @if(!empty($conflitos))
        <span class="text-danger small">
            <i class="bi bi-lock me-1"></i>
            Resolva os conflitos antes de salvar.
        </span>
        @elseif(!empty($avisosSemSala))
        <span class="text-warning small">
            <i class="bi bi-exclamation-circle me-1"></i>
            Algumas aulas serão salvas sem sala — você pode ajustar manualmente depois.
        </span>
        @else
        <span class="text-success small">
            <i class="bi bi-check-circle me-1"></i>
            Grade sem conflitos — pronta para salvar.
        </span>
        @endif
    </div>

    @endif {{-- fim previewGerado --}}
</div>
