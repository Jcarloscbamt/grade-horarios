{{-- resources/views/livewire/salas-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2"><h4 class="fw-bold mb-0">Salas <button type="button" data-bs-toggle="modal" data-bs-target="#helpModal" class="btn btn-outline-secondary btn-sm rounded-circle ms-1" style="width:24px;height:24px;padding:0;font-size:12px;line-height:1" title="Ajuda">?</button></h2><small class="text-muted">Gerenciamento de salas e laboratórios</small></div>
        @hasanyrole('admin|coordenador')
        <button wire:click="create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nova Sala</button>
        @endhasanyrole
    </div>

    @if(session()->has('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session()->has('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <select wire:model.live="filtro" class="form-select flex-shrink-1" style="max-width:160px;border-radius:6px 0 0 6px;border-right:none">
                            <option value="todos">Todos os campos</option>
                            <option value="nome">Nome</option>
                            <option value="tipo">Tipo</option>
                            <option value="bloco">Bloco</option>
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
            <div class="table-responsive" style="max-height:calc(100vh - 220px);overflow-y:auto">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light" style="position:sticky;top:0;z-index:10">
                        <tr>
                            <th class="ps-3">Nome</th>
                            <th>Tipo</th>
                            <th>Bloco</th>
                            <th>Capacidade</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salas as $sala)
                        <tr class="{{ !$sala->ativo ? 'opacity-50' : '' }}">
                            <td class="ps-3">{{ $sala->nome }}</td>
                            <td style="text-transform:uppercase">{{ $sala->tipo }}</td>
                            <td>{{ $sala->bloco ?? '—' }}</td>
                            <td>{{ $sala->capacidade ? $sala->capacidade . ' alunos' : '—' }}</td>
                            <td class="text-center">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="toggleAtivo({{ $sala->id }})"
                                    class="btn btn-sm {{ $sala->ativo ? 'btn-success' : 'btn-secondary' }}"
                                    title="{{ $sala->ativo ? 'Desativar' : 'Ativar' }}">
                                    <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>
                                    {{ $sala->ativo ? 'Ativo' : 'Inativo' }}
                                </button>
                                @else
                                <span class="badge {{ $sala->ativo ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $sala->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                                @endhasanyrole
                            </td>
                            <td class="text-center pe-3">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="edit({{ $sala->id }})" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></button>
                                @endhasanyrole
                                @hasrole('admin')
                                <button wire:click="confirmDelete({{ $sala->id }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                @endhasrole
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhuma sala encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($salas->hasPages())<div class="card-footer bg-white border-top-0">{{ $salas->links() }}</div>@endif
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
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Nome <span class="text-danger">*</span></label>
                            <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="Ex: SALA A06">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Bloco</label>
                            <input type="text" wire:model="bloco" class="form-control" placeholder="Ex: A">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Tipo <span class="text-danger">*</span></label>
                            <select wire:model="tipo" class="form-select @error('tipo') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                @foreach($tipos as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                            </select>
                            @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Capacidade</label>
                            <input type="number" wire:model="capacidade" class="form-control" placeholder="Nº de alunos" min="1">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" wire:model="ativo" id="ativoSala">
                                <label class="form-check-label fw-medium" for="ativoSala">
                                    Sala <strong>{{ $ativo ? 'Ativa' : 'Inativa' }}</strong>
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
                    <p class="text-muted mb-0">Tem certeza que deseja excluir esta sala?</p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" wire:click="delete" wire:loading.attr="disabled">Sim, excluir</button>
                </div>
            </div>
        </div>
    </div>
    @endif


<x-help-modal titulo="Ajuda — Salas">
<p class="text-muted mb-3">Cadastro das salas físicas disponíveis para alocação automática.</p>
<ul class="list-unstyled">
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Tipo:</strong> Sala de Aula ou Laboratório. Deve corresponder ao tipo definido na disciplina</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Bloco:</strong> O Gerador tenta alocar o bloco preferencial da disciplina primeiro, depois qualquer bloco</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Capacidade:</strong> Informativo, não interfere na alocação automática</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Ativo/Inativo:</strong> Salas inativas não são consideradas pelo Gerador</li>
</ul>
</x-help-modal>
</div>