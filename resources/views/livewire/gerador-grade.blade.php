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
                <div class="col-md-5">
                    <label class="form-label fw-medium">Turma <span class="text-danger">*</span></label>
                    <select wire:model.live="turma_id" class="form-select">
                        <option value="">Selecione a turma...</option>
                        @foreach($turmas as $t)
                        <option value="{{ $t->id }}">{{ $t->nome }} — {{ $t->curso->sigla }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-medium">Período(s) Letivo(s) <span class="text-danger">*</span></label>
                    <div class="border rounded p-2 d-flex flex-wrap gap-2" style="background:white">
                        @foreach($periodosLetivos as $p)
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox"
                                wire:model.live="periodos_selecionados"
                                value="{{ $p->id }}" id="periodo_{{ $p->id }}">
                            <label class="form-check-label" for="periodo_{{ $p->id }}">
                                <span class="badge {{ in_array($p->id, $periodos_selecionados) ? 'bg-primary' : 'bg-light text-dark border' }}"
                                      style="font-size:12px;cursor:pointer">
                                    {{ $p->nome }}
                                    @if($p->ativo)<i class="bi bi-circle-fill ms-1" style="font-size:6px;color:#4ade80"></i>@endif
                                </span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-2">
                    <button wire:click="gerarPrevia"
                        wire:loading.attr="disabled"
                        class="btn btn-primary w-100"
                        {{ !$turma_id || empty($periodos_selecionados) ? 'disabled' : '' }}>
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
            <div class="d-flex flex-column gap-2">
                @foreach($conflitos as $c)
                <div class="d-flex align-items-start gap-2" style="font-size:13px">
                    <i class="bi bi-x-circle-fill text-danger mt-1"></i>
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
        <div class="card-body">
            <h6 class="fw-bold text-info mb-2"><i class="bi bi-building me-1"></i>{{ count($avisosSemSala) }} aula(s) sem sala alocada</h6>
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
                <h6 class="fw-bold mb-0"><i class="bi bi-table me-1 text-success"></i>Prévia — {{ count($preview) }} aula(s) a gerar</h6>
                <small class="text-muted">Revise antes de salvar</small>
            </div>
            <div class="d-flex gap-2">
                <button wire:click="limpar" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x me-1"></i>Cancelar
                </button>
                <button wire:click="salvarGrade"
                    wire:loading.attr="disabled"
                    class="btn btn-success btn-sm">
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
                            <th class="ps-3">Dia</th>
                            <th>Horário</th>
                            <th>Disciplina</th>
                            <th>Professor</th>
                            <th>Sala</th>
                            <th>Período</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($preview as $item)
                        <tr>
                            <td class="ps-3"><span class="badge bg-secondary">{{ $dias[$item['dia_semana']] ?? $item['dia_semana'] }}</span></td>
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
                            <td><span class="badge bg-light text-dark border">{{ $item['periodo_id'] }}</span></td>
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
        Nenhuma aula nova para gerar — todas as aulas já estão cadastradas ou há conflitos.
    </div>
    @endif

    @elseif($turma_id && !empty($periodos_selecionados))
    <div class="text-center text-muted py-5">
        <i class="bi bi-play-circle fs-1 d-block mb-3 opacity-25"></i>
        <p>Clique em <strong>Gerar Prévia</strong> para visualizar as aulas antes de salvar.</p>
    </div>
    @else
    <div class="text-center text-muted py-5">
        <i class="bi bi-magic fs-1 d-block mb-3 opacity-25"></i>
        <p>Selecione uma <strong>turma</strong> e um <strong>período letivo</strong> para começar.</p>
        <div class="mt-3 text-start d-inline-block">
            <div class="fw-semibold mb-2">Pré-requisitos:</div>
            <ul class="text-muted small">
                <li>Professores com vínculos de disciplina+turma+dias cadastrados</li>
                <li>Horários cadastrados (Configurações → Horários)</li>
                <li>Salas cadastradas (Cadastros → Salas)</li>
                <li>Período Letivo ativo</li>
            </ul>
        </div>
    </div>
    @endif

</div>
