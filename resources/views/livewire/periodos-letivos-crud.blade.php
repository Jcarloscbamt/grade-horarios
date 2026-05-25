{{-- resources/views/livewire/periodos-letivos-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Períodos Letivos</h2>
            <small class="text-muted">Gerenciamento de períodos e datas de avaliação</small>
        </div>
        @hasanyrole('admin|coordenador')
        <button wire:click="create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Novo Período
        </button>
        @endhasanyrole
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Aviso sobre ativação manual --}}
    <div class="alert alert-info border-0 mb-3 py-2" style="font-size:13px">
        <i class="bi bi-info-circle me-1"></i>
        A ativação/desativação dos períodos é <strong>manual</strong>.
        Use o botão <i class="bi bi-toggle-on"></i> na lista para alternar o status de cada período.
    </div>

    {{-- Busca --}}
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="form-control" placeholder="Buscar por nome ou ano...">
                @if($search)
                <button class="btn btn-outline-secondary" wire:click="$set('search', '')">
                    <i class="bi bi-x-lg"></i>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Período</th>
                            <th class="text-center">Semestre</th>
                            <th>Avaliação 1</th>
                            <th>Avaliação 2</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periodos as $periodo)
                        <tr class="{{ $periodo->ativo ? 'table-success bg-opacity-25' : '' }}">
                            <td class="ps-3 fw-bold">{{ $periodo->nome }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $periodo->semestre }}º Sem</span>
                            </td>
                            <td style="font-size:13px">
                                @if($periodo->avaliacao1_inicio && $periodo->avaliacao1_fim)
                                    {{ $periodo->avaliacao1_inicio->format('d/m/Y') }}
                                    → {{ $periodo->avaliacao1_fim->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td style="font-size:13px">
                                @if($periodo->avaliacao2_inicio && $periodo->avaliacao2_fim)
                                    {{ $periodo->avaliacao2_inicio->format('d/m/Y') }}
                                    → {{ $periodo->avaliacao2_fim->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                {{-- Toggle manual de ativo/inativo --}}
                                <button wire:click="toggleAtivo({{ $periodo->id }})"
                                    class="btn btn-sm {{ $periodo->ativo ? 'btn-success' : 'btn-outline-secondary' }}"
                                    title="{{ $periodo->ativo ? 'Clique para desativar' : 'Clique para ativar' }}">
                                    <i class="bi bi-toggle-{{ $periodo->ativo ? 'on' : 'off' }} me-1"></i>
                                    {{ $periodo->ativo ? 'Ativo' : 'Inativo' }}
                                </button>
                            </td>
                            <td class="text-center pe-3">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="edit({{ $periodo->id }})"
                                    class="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endhasanyrole
                                @hasrole('admin')
                                <button wire:click="confirmDelete({{ $periodo->id }})"
                                    class="btn btn-sm btn-outline-danger" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endhasrole
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum período encontrado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($periodos->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $periodos->links() }}</div>
        @endif
    </div>

    {{-- Modal Incluir/Editar --}}
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

                        <div class="col-md-4">
                            <label class="form-label fw-medium">Nome <span class="text-danger">*</span></label>
                            <input type="text" wire:model="nome"
                                class="form-control @error('nome') is-invalid @enderror"
                                placeholder="Ex: 2026/1">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">Ano <span class="text-danger">*</span></label>
                            <input type="number" wire:model="ano"
                                class="form-control @error('ano') is-invalid @enderror"
                                placeholder="2026" min="2020" max="2099">
                            @error('ano') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">Semestre <span class="text-danger">*</span></label>
                            <select wire:model="semestre"
                                class="form-select @error('semestre') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                <option value="1">1º Semestre</option>
                                <option value="2">2º Semestre</option>
                            </select>
                            @error('semestre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12"><hr class="my-1"><small class="fw-bold text-muted">Avaliação 1</small></div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Data Início</label>
                            <input type="date" wire:model="avaliacao1_inicio"
                                class="form-control @error('avaliacao1_inicio') is-invalid @enderror">
                            @error('avaliacao1_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Data Fim</label>
                            <input type="date" wire:model="avaliacao1_fim"
                                class="form-control @error('avaliacao1_fim') is-invalid @enderror">
                            @error('avaliacao1_fim') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12"><hr class="my-1"><small class="fw-bold text-muted">Avaliação 2</small></div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Data Início</label>
                            <input type="date" wire:model="avaliacao2_inicio"
                                class="form-control @error('avaliacao2_inicio') is-invalid @enderror">
                            @error('avaliacao2_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Data Fim</label>
                            <input type="date" wire:model="avaliacao2_fim"
                                class="form-control @error('avaliacao2_fim') is-invalid @enderror">
                            @error('avaliacao2_fim') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <hr class="my-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="ativo" id="ativo" role="switch">
                                <label class="form-check-label fw-medium" for="ativo">
                                    Período letivo ativo
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Vários períodos podem estar ativos simultaneamente.
                                A ativação/desativação é manual.
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="save"
                        wire:loading.attr="disabled">
                        <span wire:loading wire:target="save"
                            class="spinner-border spinner-border-sm me-1"></span>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Confirmar Exclusão --}}
    @if($showDelete)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center py-4">
                    <div class="text-danger mb-3">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size:3rem"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Confirmar Exclusão</h5>
                    <p class="text-muted mb-0">
                        Tem certeza que deseja excluir este período letivo?<br>
                        <small>Esta ação não pode ser desfeita.</small>
                    </p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" wire:click="delete"
                        wire:loading.attr="disabled">
                        <span wire:loading wire:target="delete"
                            class="spinner-border spinner-border-sm me-1"></span>Sim, excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
