{{-- resources/views/livewire/gerador-grade.blade.php --}}
<div>
    {{-- Cabeçalho --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-magic me-2" style="color:#E30613"></i>Gerador de Grade
            </h4>
            <button type="button" data-bs-toggle="modal" data-bs-target="#helpModalGerador"
                class="btn btn-outline-secondary btn-sm rounded-circle"
                style="width:24px;height:24px;padding:0;font-size:12px;line-height:1" title="Ajuda">?</button>
        </div>
        <small class="text-muted">Gera automaticamente as aulas com base nos vínculos professor-disciplina</small>
    </div>

    {{-- Alertas --}}
    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-2">

                {{-- Coluna esquerda: Curso + Período empilhados --}}
                <div class="col-md-3">
                    <div class="mb-2">
                        <label class="form-label fw-medium small mb-1">Curso</label>
                        <select wire:model.live="curso_id" class="form-select form-select-sm">
                            <option value="">Todos os cursos</option>
                            @foreach($cursos as $c)
                            <option value="{{ $c->id }}">{{ $c->sigla }} — {{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-medium small mb-1">Período Letivo <span class="text-danger">*</span></label>
                        <select wire:model.live="periodo_letivo_id" class="form-select form-select-sm">
                            <option value="">Selecione...</option>
                            @foreach($periodosLetivos as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }}{{ $p->ativo ? ' ●' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Coluna centro: Turmas horizontais --}}
                <div class="col-md-7">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-medium small mb-0">Turma(s) <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            @if(count($turmasSelecionadas) > 0)
                            <span class="badge bg-primary" style="font-size:10px">{{ count($turmasSelecionadas) }} selecionada(s)</span>
                            @endif
                            <button type="button" wire:click="toggleTodasTurmas"
                                class="btn btn-outline-secondary" style="font-size:10px;padding:1px 7px">
                                {{ count($turmasSelecionadas) >= count($turmas) && count($turmas) > 0 ? 'Desmarcar' : 'Todas' }}
                            </button>
                        </div>
                    </div>
                    <div class="border rounded p-2 d-flex flex-wrap gap-1" style="min-height:70px;background:#fafafa">
                        @forelse($turmas as $t)
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox"
                                wire:model.live="turmasSelecionadas"
                                value="{{ $t->id }}" id="t_{{ $t->id }}">
                            <label class="form-check-label" for="t_{{ $t->id }}" style="cursor:pointer">
                                <span class="badge {{ in_array($t->id, $turmasSelecionadas) ? 'bg-primary' : 'bg-light text-dark border' }}"
                                      style="font-size:11px;white-space:nowrap">
                                    {{ $t->nome }}
                                    <span class="opacity-75">{{ $t->semestre }}º</span>
                                </span>
                            </label>
                        </div>
                        @empty
                        <span class="text-muted small align-self-center">
                            <i class="bi bi-info-circle me-1"></i>Nenhuma turma encontrada
                        </span>
                        @endforelse
                    </div>
                    <div class="form-text mt-1">
                        <i class="bi bi-check-square me-1"></i>Clique nos badges para selecionar as turmas
                    </div>
                </div>

                {{-- Botões Gerar + Cancelar --}}
                <div class="col-md-2 d-flex align-items-center gap-2">
                    <button wire:click="gerarPrevia"
                        wire:loading.attr="disabled"
                        class="btn btn-primary w-100"
                        {{ !$periodo_letivo_id || empty($turmasSelecionadas) ? 'disabled' : '' }}>
                        <span wire:loading wire:target="gerarPrevia" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="gerarPrevia" class="bi bi-play-circle me-1"></i>
                        Gerar Prévia
                    </button>
                    @if($previewGerado || count($conflitos) > 0)
                    <button wire:click="resetPreview" class="btn btn-outline-secondary" title="Cancelar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    @endif
                </div>

            </div>
        </div>
    </div>

    @if($previewGerado)

    {{-- Conflitos --}}
    @if(count($conflitos) > 0)
    <div class="card border-0 shadow-sm mb-3 border-start border-warning border-4">
        <div class="card-body">
            <h6 class="fw-bold text-warning mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ count($conflitos) }} conflito(s) detectado(s)
            </h6>
            <div class="d-flex flex-column gap-3">
                @foreach($conflitos as $c)
                <div class="border rounded p-2" style="background:#fffbeb;font-size:13px">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-x-circle-fill text-danger mt-1 flex-shrink-0"></i>
                        <span class="fw-medium">{{ $c['mensagem'] }}</span>
                    </div>
                    @if(!empty($c['dias_livres']))
                    <div class="ms-4">
                        <div class="text-success fw-medium mb-1" style="font-size:12px">
                            <i class="bi bi-lightbulb me-1"></i>Dia disponível sem conflito para {{ $c['professor'] ?? '' }}:
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @foreach($c['dias_livres'] as $dia)
                            <span class="badge bg-success" style="font-size:11px">{{ $dia['nome'] }}</span>
                            @endforeach
                            @if(isset($c['professor_id']))
                            <button type="button"
                                wire:click="aceitarSugestao({{ $c['professor_id'] }}, {{ $c['turma_id'] }}, {{ $c['disciplina_id'] }}, {{ json_encode(array_column($c['dias_livres'], 'num')) }})"
                                wire:loading.attr="disabled"
                                class="btn btn-success btn-sm py-0 ms-1" style="font-size:11px">
                                <i class="bi bi-check2-circle me-1"></i>Aceitar e regerar
                            </button>
                            @endif
                        </div>
                        <div class="text-muted mt-1" style="font-size:11px">
                            <i class="bi bi-info-circle me-1"></i>
                            Ao aceitar, este dia será adicionado ao cadastro do professor e a grade será regerada.
                        </div>
                    </div>
                    @elseif(isset($c['professor_id']))
                    <div class="ms-4 text-danger" style="font-size:12px">
                        <i class="bi bi-calendar-x me-1"></i>Nenhum dia livre. Ajuste manualmente o cadastro do professor.
                    </div>
                    @endif
                    @if(isset($c['professor_id']))
                    <div class="ms-4 mt-2">
                        <a href="{{ route('professores') }}?editar={{ $c['professor_id'] }}"
                            target="_blank"
                            class="btn btn-outline-secondary btn-sm py-0" style="font-size:11px">
                            <i class="bi bi-person-gear me-1"></i>
                            Abrir cadastro de {{ $c['professor'] ?? 'Professor' }} (nova aba)
                        </a>
                    </div>
                    @endif
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
                <button wire:click="resetPreview" class="btn btn-outline-secondary btn-sm">
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
            <div class="table-responsive" style="max-height:calc(100vh - 220px);overflow-y:auto">
                <table class="table table-hover align-middle mb-0" style="font-size:13px">
                    <thead class="table-light" style="position:sticky;top:0;z-index:10">
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
                                @if($item['modalidade'] === 'online')
                                <span class="badge bg-primary" style="font-size:11px"><i class="bi bi-wifi me-1"></i>Online</span>
                                @elseif($item['sala_id'])
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

    {{-- Modal de Ajuda --}}
    <div class="modal fade" id="helpModalGerador" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background:#1a1a1a;color:white;border-bottom:3px solid #E30613">
                    <h5 class="modal-title fw-bold"><i class="bi bi-question-circle me-2"></i>Ajuda — Gerador de Grade</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="font-size:14px">
                    <p class="text-muted mb-3">Gera automaticamente a grade de horários com base nos vínculos professor-disciplina.</p>
                    <ol class="mb-3">
                        <li class="mb-2">Selecione o <strong>Curso</strong> (opcional, para filtrar turmas)</li>
                        <li class="mb-2">Selecione o <strong>Período Letivo</strong></li>
                        <li class="mb-2">Selecione as <strong>Turmas</strong></li>
                        <li class="mb-2">Clique em <strong>Gerar Prévia</strong> e revise antes de salvar</li>
                        <li class="mb-2">Clique em <strong>Salvar Grade</strong></li>
                    </ol>
                    <hr>
                    <p class="fw-semibold mb-2">Regras automáticas:</p>
                    <ul class="list-unstyled">
                        <li class="mb-1"><i class="bi bi-shield-check text-success me-1"></i>Professor não pode ter aulas em turmas diferentes no mesmo dia</li>
                        <li class="mb-1"><i class="bi bi-shield-check text-success me-1"></i>Disciplinas filtradas pelo semestre atual da turma</li>
                        <li class="mb-1"><i class="bi bi-shield-check text-success me-1"></i>Cada disciplina aparece no máximo uma vez por semana</li>
                        <li class="mb-1"><i class="bi bi-shield-check text-success me-1"></i>Salas alocadas por tipo e bloco preferencial</li>
                    </ul>
                    <div class="alert alert-warning py-2 mt-3" style="font-size:12px">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Pré-requisitos:</strong> Professores com vínculos, horários, salas e período letivo ativo.
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

</div>
