{{-- resources/views/livewire/professores-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Professores</h2>
            <small class="text-muted">Gerenciamento de professores</small>
        </div>
        @hasanyrole('admin|coordenador')
        <button wire:click="create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Novo Professor</button>
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
                    <option value="nome">Nome</option>
                    <option value="email">E-mail</option>
                    <option value="cpf">CPF</option>
                    <option value="telefone">Telefone</option>
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
                            <th class="ps-3">Nome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>CPF</th>
                            <th class="text-center pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($professores as $professor)
                        <tr>
                            <td class="ps-3 fw-medium">{{ $professor->nome }}</td>
                            <td>{{ $professor->email }}</td>
                            <td>{{ $professor->telefone ?? '—' }}</td>
                            <td>
                                @php
                                    $cpfLimpo = preg_replace('/\D/', '', $professor->cpf);
                                    $cpfFmt = strlen($cpfLimpo) === 11
                                        ? substr($cpfLimpo,0,3).'.'.substr($cpfLimpo,3,3).'.'.substr($cpfLimpo,6,3).'-'.substr($cpfLimpo,9,2)
                                        : $professor->cpf;
                                @endphp
                                {{ $cpfFmt }}
                            </td>
                            <td class="text-center pe-3">
                                @hasanyrole('admin|coordenador')

                                <button wire:click="edit({{ $professor->id }})" class="btn btn-sm btn-outline-secondary me-1" title="Editar"><i class="bi bi-pencil"></i></button>

                                @endhasanyrole
                                @hasrole('admin')
                                <button wire:click="confirmDelete({{ $professor->id }})" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
                                @endhasrole
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum professor encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($professores->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $professores->links() }}</div>
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
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Nome <span class="text-danger">*</span></label>
                            <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="Nome completo">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">CPF <span class="text-danger">*</span></label>
                            <input type="text"
                                wire:model.live="cpf"
                                class="form-control @error('cpf') is-invalid @enderror"
                                placeholder="000.000.000-00"
                                maxlength="14"
                                style="font-family:monospace">
                            @error('cpf')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                @if(strlen(preg_replace('/\D/', '', $cpf)) === 11)
                                    <div class="text-success mt-1" style="font-size:12px">
                                        <i class="bi bi-check-circle me-1"></i>CPF válido
                                    </div>
                                @endif
                            @enderror
                            <div class="form-text">Formatação automática ao digitar</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-medium">E-mail <span class="text-danger">*</span></label>
                            <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="email@exemplo.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Telefone</label>
                            <input type="text"
                                wire:model.live="telefone"
                                class="form-control @error('telefone') is-invalid @enderror"
                                placeholder="(65) 98119-0328"
                                maxlength="15"
                                style="font-family:monospace">
                            @error('telefone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Formatação automática ao digitar</div>
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
                    <p class="text-muted mb-0">Tem certeza que deseja excluir este professor?<br><small>Esta ação não pode ser desfeita.</small></p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" wire:click="delete" wire:loading.attr="disabled">
                        <span wire:loading wire:target="delete" class="spinner-border spinner-border-sm me-1"></span>Sim, excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
