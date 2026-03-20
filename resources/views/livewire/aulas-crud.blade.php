{{-- resources/views/livewire/aulas-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Aulas</h2>
            <small class="text-muted">Grade de horários — aulas por turma e período letivo</small>
        </div>
        @hasanyrole('admin|coordenador')
        <button wire:click="create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nova Aula</button>
        @endhasanyrole
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-start-0" placeholder="Pesquisar por turma, disciplina ou professor...">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="border-collapse:collapse">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Dia</th>
                            <th>Turma</th>
                            <th>Disciplina</th>
                            <th>Professor</th>
                            <th>Horário</th>
                            <th>Sala</th>
                            <th>Modalidade</th>
                            <th>Período</th>
                            <th class="text-center pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $dias = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb']; @endphp
                        @forelse($aulas as $i => $aula)
                        <tr style="{{ $loop->even ? 'background:#f8f9fa' : 'background:#ffffff' }}">
                            <td class="ps-3">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold">
                                    {{ $dias[$aula->dia_semana] ?? $aula->dia_semana }}
                                </span>
                            </td>
                            <td class="fw-medium">{{ $aula->turma->nome }}</td>
                            <td>{{ Str::limit($aula->disciplina->nome, 30) }}</td>
                            <td>{{ $aula->professor->nome }}</td>
                            <td>{{ substr($aula->horario->hora_inicio,0,5) }} – {{ substr($aula->horario->hora_fim,0,5) }}</td>
                            <td>{{ $aula->sala?->nome ?? '—' }}</td>
                            <td>
                                @if($aula->modalidade === 'online')
                                    <span class="badge bg-warning bg-opacity-10 text-warning-emphasis">Online</span>
                                @elseif($aula->modalidade === 'híbrido')
                                    <span class="badge bg-info bg-opacity-10 text-info">Híbrido</span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success">Presencial</span>
                                @endif
                            </td>
                            <td>{{ $aula->periodoLetivo->nome }}</td>
                            <td class="text-center pe-3">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="edit({{ $aula->id }})" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></button>
                                @endhasanyrole
                                @hasrole('admin')
                                <button wire:click="confirmDelete({{ $aula->id }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                @endhasrole
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhuma aula encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($aulas->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $aulas->links() }}</div>
        @endif
    </div>

    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">{{ $modalTitle }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">

                    {{-- Alertas de conflito --}}
                    @if($errors->any())
                    <div class="alert alert-danger py-2 mb-3">
                        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Não foi possível salvar:</div>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li style="font-size:13px">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Tipo de lançamento — só exibe em inclusão --}}
                    @if(!$aulaId)
                    <div class="mb-3 p-3 rounded" style="background:#f8f9fa; border:1px solid #e9ecef">
                        <label class="form-label fw-medium mb-2">
                            <i class="bi bi-lightning-charge me-1 text-warning"></i>
                            Tipo de lançamento
                        </label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    wire:model="tipo_lancamento"
                                    wire:change="$set('tipo_lancamento', 'unico')"
                                    value="unico" id="lancUnico"
                                    {{ $tipo_lancamento === 'unico' ? 'checked' : '' }}>
                                <label class="form-check-label" for="lancUnico">
                                    <strong>Horário específico</strong>
                                    <small class="d-block text-muted">Selecionar um horário manualmente</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                    wire:model="tipo_lancamento"
                                    wire:change="$set('tipo_lancamento', 'todos_horarios')"
                                    value="todos_horarios" id="lancTodos"
                                    {{ $tipo_lancamento === 'todos_horarios' ? 'checked' : '' }}>
                                <label class="form-check-label" for="lancTodos">
                                    <strong>Todos os horários do dia</strong>
                                    <small class="d-block text-muted">Cria automaticamente para todos os blocos</small>
                                </label>
                            </div>
                        </div>

                        {{-- Aviso quando selecionar todos os horários --}}
                        @if($tipo_lancamento === 'todos_horarios')
                        <div class="mt-2 p-2 rounded" style="background:#fff3cd; border:1px solid #ffc107">
                            <small style="color:#856404">
                                <i class="bi bi-info-circle me-1"></i>
                                O sistema criará uma aula para <strong>cada bloco de horário</strong> cadastrado
                                (aulas e intervalo). Horários que já tiverem conflito serão ignorados com aviso.
                            </small>
                        </div>
                        @endif
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Turma <span class="text-danger">*</span></label>
                            <select wire:model="turma_id" class="form-select @error('turma_id') is-invalid @enderror">
                                <option value="">Selecione a turma...</option>
                                @foreach($turmas as $turma)
                                <option value="{{ $turma->id }}">{{ $turma->nome }}</option>
                                @endforeach
                            </select>
                            @error('turma_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Período Letivo <span class="text-danger">*</span></label>
                            <select wire:model="periodo_letivo_id" class="form-select @error('periodo_letivo_id') is-invalid @enderror">
                                <option value="">Selecione o período...</option>
                                @foreach($periodosLetivos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nome }}{{ $periodo->ativo ? ' (ativo)' : '' }}</option>
                                @endforeach
                            </select>
                            @error('periodo_letivo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Disciplina <span class="text-danger">*</span></label>
                            <select wire:model="disciplina_id" class="form-select @error('disciplina_id') is-invalid @enderror">
                                <option value="">Selecione a disciplina...</option>
                                @foreach($disciplinas as $disciplina)
                                <option value="{{ $disciplina->id }}">{{ $disciplina->nome }}</option>
                                @endforeach
                            </select>
                            @error('disciplina_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Professor <span class="text-danger">*</span></label>
                            <select wire:model="professor_id" class="form-select @error('professor_id') is-invalid @enderror">
                                <option value="">Selecione o professor...</option>
                                @foreach($professores as $professor)
                                <option value="{{ $professor->id }}">{{ $professor->nome }}</option>
                                @endforeach
                            </select>
                            @error('professor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Sala</label>
                            <select wire:model="sala_id" class="form-select @error('sala_id') is-invalid @enderror">
                                <option value="">Online / Sem sala</option>
                                @foreach($salas as $sala)
                                <option value="{{ $sala->id }}">{{ $sala->nome }}</option>
                                @endforeach
                            </select>
                            @error('sala_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Horário — sempre visível, desabilitado quando for "todos os horários" --}}
                        <div class="col-md-4">
                            <label class="form-label fw-medium">
                                Horário
                                @if($tipo_lancamento === 'unico' || $aulaId)
                                    <span class="text-danger">*</span>
                                @else
                                    <small class="text-muted fw-normal">(automático)</small>
                                @endif
                            </label>
                            <select wire:model="horario_id"
                                class="form-select @error('horario_id') is-invalid @enderror"
                                {{ ($tipo_lancamento === 'todos_horarios' && !$aulaId) ? 'disabled' : '' }}>
                                <option value="">Selecione...</option>
                                @foreach($horarios as $horario)
                                <option value="{{ $horario->id }}">
                                    {{ substr($horario->hora_inicio,0,5) }} – {{ substr($horario->hora_fim,0,5) }}
                                    ({{ $horario->tipo }})
                                </option>
                                @endforeach
                            </select>
                            @if($tipo_lancamento === 'todos_horarios' && !$aulaId)
                                <small class="text-muted">Todos os blocos serão gerados automaticamente</small>
                            @endif
                            @error('horario_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Dia da Semana <span class="text-danger">*</span></label>
                            <select wire:model="dia_semana" class="form-select @error('dia_semana') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                @foreach($dias as $num => $nome)
                                <option value="{{ $num }}">{{ $nome }}</option>
                                @endforeach
                            </select>
                            @error('dia_semana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Modalidade <span class="text-danger">*</span></label>
                            <select wire:model="modalidade" class="form-select @error('modalidade') is-invalid @enderror">
                                @foreach($modalidades as $m)
                                <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                                @endforeach
                            </select>
                            @error('modalidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                        @if($tipo_lancamento === 'todos_horarios' && !$aulaId)
                            <i class="bi bi-lightning-charge me-1"></i>Gerar todos os horários
                        @else
                            Salvar
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($showDelete)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center py-4">
                    <div class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill" style="font-size:3rem"></i></div>
                    <h5 class="fw-bold mb-2">Confirmar Exclusão</h5>
                    <p class="text-muted mb-0">Tem certeza que deseja excluir esta aula?</p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" wire:click="delete" wire:loading.attr="disabled">Sim, excluir</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
