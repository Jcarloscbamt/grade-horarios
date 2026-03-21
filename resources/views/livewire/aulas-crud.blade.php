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
                <select wire:model.live="filtro" class="form-select flex-shrink-1" style="max-width:160px;border-radius:6px 0 0 6px;border-right:none">
                    <option value="todos">Todos os campos</option>
                    <option value="turma">Turma</option>
                    <option value="disciplina">Disciplina</option>
                    <option value="professor">Professor</option>
                    <option value="sala">Sala</option>
                    <option value="dia">Dia da semana</option>
                </select>
                <span class="input-group-text bg-white px-2" style="border-left:none;border-right:none">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Digite para filtrar...">
                @if($search)
                <button class="btn btn-outline-secondary" wire:click="$set('search', '')" title="Limpar">
                    <i class="bi bi-x-lg"></i>
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                        @php
                        $dias = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb'];
                        @endphp
                        @forelse($aulas as $aula)
                        <tr>
                            <td class="ps-3" style="text-transform:uppercase">{{ $dias[$aula->dia_semana] ?? $aula->dia_semana }}</td>
                            <td style="text-transform:uppercase">{{ $aula->turma->nome }}</td>
                            <td>{{ Str::limit($aula->disciplina->nome, 30) }}</td>
                            <td style="text-transform:uppercase">{{ $aula->professor->nome }}</td>
                            <td>{{ substr($aula->horario->hora_inicio,0,5) }} – {{ substr($aula->horario->hora_fim,0,5) }}</td>
                            <td style="text-transform:uppercase">{{ $aula->sala?->nome ?? '—' }}</td>
                            <td style="text-transform:uppercase">{{ $aula->modalidade }}</td>
                            <td style="text-transform:uppercase">{{ $aula->periodoLetivo->nome }}</td>
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

                    {{-- Alerta de erros de conflito/duplicidade --}}
                    @if($errors->has('geral'))
                    <div class="alert alert-danger py-2 mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Não foi possível salvar:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach($errors->get('geral') as $erro)
                                <li style="font-size:13px">{{ $erro }}</li>
                            @endforeach
                        </ul>
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
                            <select wire:model="sala_id" class="form-select">
                                <option value="">Online / Sem sala</option>
                                @foreach($salas as $sala)
                                <option value="{{ $sala->id }}">{{ $sala->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Opção cadastrar todos os horários do dia --}}
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       wire:model.live="todosHorarios"
                                       id="todosHorarios" role="switch">
                                <label class="form-check-label fw-medium" for="todosHorarios">
                                    <i class="bi bi-calendar-week me-1" style="color:#E30613"></i>
                                    Cadastrar em todos os horários do dia de uma vez
                                </label>
                            </div>
                            @if($todosHorarios)
                            <div class="mt-2 p-2 rounded" style="background:#fff3cd;font-size:13px">
                                <i class="bi bi-info-circle me-1" style="color:#856404"></i>
                                Serão criadas aulas em <strong>todos os horários</strong> cadastrados (exceto intervalos) para o dia selecionado.
                            </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">Horário <span class="text-danger">*</span></label>
                            <select wire:model="horario_id"
                                    class="form-select @error('horario_id') is-invalid @enderror"
                                    {{ $todosHorarios ? 'disabled' : '' }}>
                                <option value="">{{ $todosHorarios ? 'Todos os horários' : 'Selecione...' }}</option>
                                @if(!$todosHorarios)
                                @foreach($horarios as $horario)
                                <option value="{{ $horario->id }}">{{ substr($horario->hora_inicio,0,5) }} – {{ substr($horario->hora_fim,0,5) }} ({{ $horario->tipo }})</option>
                                @endforeach
                                @endif
                            </select>
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
                        <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>Salvar
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
