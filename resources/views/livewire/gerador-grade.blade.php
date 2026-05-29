{{-- resources/views/livewire/gerador-grade.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-magic me-2" style="color:#E30613"></i>Gerador de Grade</h2>
            <small class="text-muted">Gera automaticamente as aulas com base nos vínculos professor-disciplina</small>
        </div>
    </div>

    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">

                {{-- Período Letivo — único (dropdown) --}}
                <div class="col-md-3">
                    <label class="form-label fw-medium">Período Letivo <span class="text-danger">*</span></label>
                    <select wire:model.live="periodo_letivo_id" class="form-select">
                        <option value="">Selecione...</option>
                        @foreach($periodosLetivos as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->nome }}{{ $p->ativo ? ' — Ativo' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Turmas — multi-seleção --}}
                <div class="col-md-7">
                    <label class="form-label fw-medium">
                        Turma(s) <span class="text-danger">*</span>
                        <span class="text-muted small fw-normal">— selecione uma ou mais</span>
                    </label>
                    <div class="border rounded p-2" style="background:white">
                        <div class="d-flex align-items-center gap-2 pb-1 mb-2 border-bottom">
                            <button type="button" wire:click="toggleTodasTurmas"
                                class="btn btn-sm {{ count($turmasSelecionadas) >= count($turmas) && count($turmas) > 0 ? 'btn-dark' : 'btn-outline-dark' }}">
                                <i class="bi bi-check-all me-1"></i>
                                {{ count($turmasSelecionadas) >= count($turmas) && count($turmas) > 0 ? 'Desmarcar todas' : 'Selecionar todas' }}
                            </button>
                            @if(count($turmasSelecionadas) > 0)
                            <span class="badge bg-primary">{{ count($turmasSelecionadas) }} selecionada(s)</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($turmas as $t)
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="checkbox"
                                    wire:model.live="turmasSelecionadas"
                                    value="{{ $t->id }}" id="t_{{ $t->id }}">
                                <label class="form-check-label" for="t_{{ $t->id }}">
                                    <span class="badge {{ in_array($t->id, $turmasSelecionadas) ? 'bg-primary' : 'bg-light text-dark border' }}"
                                          style="font-size:12px;cursor:pointer">
                                        {{ $t->nome }}
                                        <small class="opacity-75">{{ $t->curso->sigla }}</small>
                                    </span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Botão Gerar --}}
                <div class="col-md-2">
                    <button wire:click="gerarPrevia"
                        wire:loading.attr="disabled"
                        class="btn btn-primary w-100"
                        {{ !$periodo_letivo_id || empty($turmasSelecionadas) ? 'disabled' : '' }}>
                        <span wire:loading wire:target="gerarPrevia" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="gerarPrevia" class="bi bi-play-circle me-1"></i>
                        Gerar Prévia
                    </button>
                </div>

            </div>
        </div>
    </div>

    @if($previewGerado)

    {{-- Conflitos --}}
    @if(count($conflitos) > 0)
    <div class="card border-0 shadow-sm mb-3 border-start border-warning border-4">
        <div class="card-body">
            <h6 class="fw-bold text-warning mb-3"><i class="bi bi-exclamation-triangle me-1"></i>{{ count($conflitos) }} conflito(s) detectado(s)</h6>
            <div class="d-flex flex-column gap-1">
                @foreach($conflitos as $c)
                <div class="d-flex align-items-start gap-2" style="font-size:13px">
                    <i class="bi bi-x-circle-fill text-danger mt-1 flex-shrink-0"></i>
                    <span>{{ $c['mensagem'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Avisos sem sala --}}
    @if(count($avisosSemSala) > 0)
    <div class="card border-0 shadow-sm mb-3 border-start border-info border-4">
        <div class="card-body py-3">
            <h6 class="fw-bold text-info mb-2"><i class="bi bi-building me-1"></i>{{ count($avisosSemSala) }} aviso(s) de sala</h6>
            @foreach($avisosSemSala as $a)
            <div style="font-size:13px"><i class="bi bi-dash me-1"></i>{{ $a['mensagem'] }}</div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Prévia --}}
    @if(count($preview) > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0"><i class="bi bi-table me-1 text-success"></i>Prévia — {{ count($preview) }} aula(s)</h6>
                <small class="text-muted">Revise antes de salvar</small>
            </div>
            <div class="d-flex gap-2">
                <button wire:click="limpar" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x me-1"></i>Cancelar
                </button>
                <button wire:click="salvarGrade" wire:loading.attr="disabled" class="btn btn-success btn-sm">
                    <span wire:loading wire:target="salvarGrade" class="spinner-border spinner-border-sm me-1"></span>
                    <i wire:loading.remove wire:target="salvarGrade" class="bi bi-floppy me-1"></i>
                    Salvar Grade
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:13px">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Turma</th>
                            <th>Dia</th>
                            <th>Horário</th>
                            <th>Disciplina</th>
                            <th>Professor</th>
                            <th>Sala</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $turmaAtual = ''; @endphp
                        @foreach($preview as $item)
                        @if($item['turma_nome'] !== $turmaAtual)
                        @php $turmaAtual = $item['turma_nome']; @endphp
                        <tr class="table-secondary">
                            <td colspan="6" class="ps-3 fw-bold py-1" style="font-size:12px;letter-spacing:.5px">
                                <i class="bi bi-people me-1"></i>{{ $turmaAtual }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td class="ps-3 text-muted" style="font-size:11px"></td>
                            <td><span class="badge bg-secondary">{{ $dias[$item['dia_semana']] ?? $item['dia_semana'] }}</span></td>
                            <td style="font-family:monospace">{{ $item['horario'] }}</td>
                            <td class="fw-medium">{{ $item['disciplina'] }}</td>
                            <td>{{ $item['professor'] }}</td>
                            <td>
                                @if($item['sala_id'])
                                <i class="bi bi-building me-1 text-muted"></i>{{ $item['sala'] }}
                                @else
                                <span class="text-warning small"><i class="bi bi-exclamation-triangle me-1"></i>Sem sala</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @else
    <div class="text-center text-muted py-4">
        <i class="bi bi-calendar-x fs-3 d-block mb-2 opacity-50"></i>
        Nenhuma aula nova para gerar — todas já estão cadastradas ou há conflitos.
    </div>
    @endif

    @else
    <div class="text-center text-muted py-5">
        <i class="bi bi-magic fs-1 d-block mb-3 opacity-25"></i>
        <p>Selecione o <strong>período letivo</strong> e as <strong>turmas</strong> para gerar a grade.</p>
    </div>
    @endif

</div>
