{{-- resources/views/livewire/disciplinas-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="fw-bold mb-0">Disciplinas</h2><small class="text-muted">Gerenciamento de disciplinas dos cursos</small></div>
        @hasanyrole('admin|coordenador')
        <button wire:click="create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nova Disciplina</button>
        @endhasanyrole
    </div>

    @if(session()->has('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session()->has('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <select wire:model.live="filtro" class="form-select flex-shrink-1" style="max-width:160px;border-radius:6px 0 0 6px;border-right:none">
                            <option value="todos">Todos os campos</option>
                            <option value="nome">Nome</option>
                            <option value="curso">Curso</option>
                        </select>
                        <span class="input-group-text bg-white px-2" style="border-left:none;border-right:none"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Digite para filtrar...">
                        @if($search)<button class="btn btn-outline-secondary" wire:click="$set('search', '')"><i class="bi bi-x-lg"></i></button>@endif
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="filtroAtivo" class="form-select">
                        <option value="todos">Todos os status</option>
                        <option value="ativos">Somente Ativas</option>
                        <option value="inativos">Somente Inativas</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nome</th>
                            <th>Curso</th>
                            <th class="text-center">Semestre</th>
                            <th class="text-center">CH</th>
                            <th>Sala Preferencial</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($disciplinas as $disciplina)
                        <tr class="{{ !$disciplina->ativo ? 'opacity-50' : '' }}">
                            <td class="ps-3 fw-medium">{{ $disciplina->nome }}</td>
                            <td>
                                <span class="badge fw-semibold" style="background:{{ $disciplina->curso->cor_grade ?? '#6c757d' }};color:white">
                                    {{ $disciplina->curso->sigla ?? '—' }}
                                </span>
                                <span class="ms-1 small">{{ $disciplina->curso->nome ?? '—' }}</span>
                            </td>
                            <td class="text-center"><span class="badge bg-secondary">{{ $disciplina->semestre_grade }}º sem</span></td>
                            <td class="text-center">{{ $disciplina->carga_horaria }}h</td>
                            <td>
                                @if($disciplina->tipo_sala)
                                    <span class="badge bg-light text-dark border" style="font-size:11px">
                                        <i class="bi bi-building me-1"></i>
                                        {{ $disciplina->tipo_sala }}{{ $disciplina->bloco_preferencial ? ' — Bloco ' . $disciplina->bloco_preferencial : '' }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="toggleAtivo({{ $disciplina->id }})"
                                    class="btn btn-sm {{ $disciplina->ativo ? 'btn-success' : 'btn-secondary' }}"
                                    title="{{ $disciplina->ativo ? 'Desativar' : 'Ativar' }}">
                                    <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>
                                    {{ $disciplina->ativo ? 'Ativo' : 'Inativo' }}
                                </button>
                                @else
                                <span class="badge {{ $disciplina->ativo ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $disciplina->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                                @endhasanyrole
                            </td>
                            <td class="text-center pe-3">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="edit({{ $disciplina->id }})" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></button>
                                @endhasanyrole
                                @hasrole('admin')
                                <button wire:click="confirmDelete({{ $disciplina->id }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                @endhasrole
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhuma disciplina encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($disciplinas->hasPages())<div class="card-footer bg-white border-top-0">{{ $disciplinas->links() }}</div>@endif
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
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Curso <span class="text-danger">*</span></label>
                            <select wire:model="curso_id" class="form-select @error('curso_id') is-invalid @enderror">
                                <option value="">Selecione o curso...</option>
                                @foreach($cursos as $curso)<option value="{{ $curso->id }}">{{ $curso->nome }}</option>@endforeach
                            </select>
                            @error('curso_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Semestre na Grade <span class="text-danger">*</span></label>
                            <select wire:model="semestre_grade" class="form-select @error('semestre_grade') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                @for($i = 1; $i <= 10; $i++)<option value="{{ $i }}">{{ $i }}º Semestre</option>@endfor
                            </select>
                            @error('semestre_grade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Nome da Disciplina <span class="text-danger">*</span></label>
                            <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="Ex: Banco de Dados II">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Carga Horária (h) <span class="text-danger">*</span></label>
                            <input type="number" wire:model="carga_horaria" class="form-control @error('carga_horaria') is-invalid @enderror" placeholder="Ex: 80" min="1">
                            @error('carga_horaria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12"><hr class="my-1">
                            <label class="form-label fw-medium"><i class="bi bi-building me-1 text-muted"></i>Sala Preferencial para Alocação Automática</label>
                            <div class="text-muted small mb-2">O gerador de grade usará este tipo + bloco para alocar a sala automaticamente.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tipo de Sala</label>
                            <select wire:model.live="tipo_sala" class="form-select @error('tipo_sala') is-invalid @enderror">
                                <option value="">Sem preferência</option>
                                @foreach($tiposSala as $tipo)<option value="{{ $tipo }}">{{ $tipo }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Bloco Preferencial</label>
                            <select wire:model="bloco_preferencial" class="form-select" {{ !$tipo_sala ? 'disabled' : '' }}>
                                <option value="">Qualquer bloco</option>
                                @foreach($blocos as $bloco)<option value="{{ $bloco }}">Bloco {{ $bloco }}</option>@endforeach
                            </select>
                            @if(!$tipo_sala)<div class="form-text text-muted">Selecione um tipo de sala primeiro.</div>@endif
                        </div>
                        @if($tipo_sala)
                        <div class="col-12">
                            <div class="p-2 rounded border bg-light d-inline-flex align-items-center gap-2">
                                <i class="bi bi-building text-primary"></i>
                                <span class="fw-semibold" style="font-size:13px">
                                    Sala alocada como: <span class="text-primary">{{ $tipo_sala }}{{ $bloco_preferencial ? ' — Bloco ' . $bloco_preferencial : ' (qualquer bloco)' }}</span>
                                </span>
                            </div>
                        </div>
                        @endif
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" wire:model="ativo" id="ativoDisciplina">
                                <label class="form-check-label fw-medium" for="ativoDisciplina">
                                    Disciplina <strong>{{ $ativo ? 'Ativa' : 'Inativa' }}</strong>
                                    <span class="badge ms-1 {{ $ativo ? 'bg-success' : 'bg-secondary' }}">{{ $ativo ? 'Ativa' : 'Inativa' }}</span>
                                </label>
                            </div>
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
                    <p class="text-muted mb-0">Tem certeza que deseja excluir esta disciplina?</p>
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
