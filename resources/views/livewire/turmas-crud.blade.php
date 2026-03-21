{{-- resources/views/livewire/turmas-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Turmas</h2>
            <small class="text-muted">Gerenciamento de turmas por curso</small>
        </div>
        @hasanyrole('admin|coordenador')
        <button wire:click="create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nova Turma</button>
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
                    <option value="nome">Turma</option>
                    <option value="curso">Curso</option>
                    <option value="semestre">Semestre</option>
                    <option value="periodo">Período</option>
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
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Turma</th>
                        <th>Curso</th>
                        <th>Semestre Atual</th>
                        <th>Ano/Período</th>
                        <th class="text-center pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($turmas as $turma)
                    <tr>
                        <td class="ps-3 fw-medium">{{ $turma->nome }}</td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $turma->curso->sigla }}</span></td>
                        <td>{{ $turma->semestre }}º semestre</td>
                        <td>{{ $turma->ano }}/{{ $turma->periodo }}</td>
                        <td class="text-center pe-3">
                            @hasanyrole('admin|coordenador')

                            <button wire:click="edit({{ $turma->id }})" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></button>

                            @endhasanyrole
                            @hasrole('admin')
                            <button wire:click="confirmDelete({{ $turma->id }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            @endhasrole
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhuma turma encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($turmas->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $turmas->links() }}</div>
        @endif
    </div>

    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">{{ $modalTitle }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium">Curso <span class="text-danger">*</span></label>
                            <select wire:model="curso_id" class="form-select @error('curso_id') is-invalid @enderror">
                                <option value="">Selecione o curso...</option>
                                @foreach($cursos as $curso)
                                <option value="{{ $curso->id }}">{{ $curso->nome }} ({{ $curso->sigla }})</option>
                                @endforeach
                            </select>
                            @error('curso_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Nome da Turma <span class="text-danger">*</span></label>
                            <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="Ex: ADS23/2">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Semestre Atual <span class="text-danger">*</span></label>
                            <input type="number" wire:model="semestre" class="form-control @error('semestre') is-invalid @enderror" placeholder="Ex: 6" min="1" max="10">
                            @error('semestre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Ano <span class="text-danger">*</span></label>
                            <input type="number" wire:model="ano" class="form-control @error('ano') is-invalid @enderror" placeholder="Ex: 2026">
                            @error('ano') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Período <span class="text-danger">*</span></label>
                            <select wire:model="periodo" class="form-select @error('periodo') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                <option value="1">1º Período</option>
                                <option value="2">2º Período</option>
                            </select>
                            @error('periodo') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <p class="text-muted mb-0">Tem certeza que deseja excluir esta turma?</p>
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
