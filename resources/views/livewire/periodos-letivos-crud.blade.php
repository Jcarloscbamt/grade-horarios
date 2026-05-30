{{-- resources/views/livewire/periodos-letivos-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h4 class="fw-bold mb-0">Períodos Letivos</h2>
            <small class="text-muted">Calendário acadêmico e datas de avaliação</small>
        </div>
        @hasanyrole('admin|coordenador')
        <div class="d-flex gap-2">
            <button wire:click="prepararAvanco" class="btn btn-warning">
                <i class="bi bi-arrow-up-circle me-1"></i>Avançar Semestre das Turmas
            </button>
            <button wire:click="create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Novo Período
            </button>
        </div>
        @endhasanyrole
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light" style="position:sticky;top:0;z-index:10">
                    <tr>
                        <th class="ps-3">Período</th>
                        <th>Avaliação 1</th>
                        <th>Avaliação 2</th>
                        <th>Status</th>
                        <th class="text-center pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periodos as $periodo)
                    <tr>
                        <td class="ps-3 fw-medium">{{ $periodo->nome }}</td>
                        <td>
                            @if($periodo->avaliacao1_inicio)
                                {{ $periodo->avaliacao1_inicio->format('d/m/Y') }} a {{ $periodo->avaliacao1_fim?->format('d/m/Y') }}
                            @else — @endif
                        </td>
                        <td>
                            @if($periodo->avaliacao2_inicio)
                                {{ $periodo->avaliacao2_inicio->format('d/m/Y') }} a {{ $periodo->avaliacao2_fim?->format('d/m/Y') }}
                            @else — @endif
                        </td>
                        <td>
                            @if($periodo->ativo)
                                <span class="badge bg-success bg-opacity-10 text-success">Ativo</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Inativo</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            @hasanyrole('admin|coordenador')

                            <button wire:click="edit({{ $periodo->id }})" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></button>

                            @endhasanyrole
                            @hasrole('admin')
                            <button wire:click="confirmDelete({{ $periodo->id }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            @endhasrole
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum período letivo encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    {{-- Modal Avançar Semestre --}}
    @if($showAvancar)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.55)">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background:#f59e0b;color:white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-up-circle me-2"></i>Avançar Semestre das Turmas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="cancelarAvanco"></button>
                </div>
                <div class="modal-body">

                    @if(count($previewAvanco) > 0)
                    <div class="mb-3">
                        <h6 class="fw-bold text-success mb-2">
                            <i class="bi bi-check-circle me-1"></i>
                            {{ count($previewAvanco) }} turma(s) serão avançadas:
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Turma</th>
                                        <th>Curso</th>
                                        <th class="text-center">Semestre Atual</th>
                                        <th class="text-center">Novo Semestre</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewAvanco as $item)
                                    <tr>
                                        <td class="fw-medium">{{ $item['nome'] }}</td>
                                        <td><span class="badge bg-secondary">{{ $item['curso'] }}</span></td>
                                        <td class="text-center">{{ $item['semestre_atual'] }}º</td>
                                        <td class="text-center text-success fw-bold">{{ $item['semestre_novo'] }}º</td>
                                        <td class="text-center text-muted">{{ $item['max'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>Nenhuma turma para avançar.</div>
                    @endif

                    @if(count($previewConcluidas) > 0)
                    <div>
                        <h6 class="fw-bold text-muted mb-2">
                            <i class="bi bi-mortarboard me-1"></i>
                            {{ count($previewConcluidas) }} turma(s) já estão no último semestre (não serão alteradas):
                        </h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($previewConcluidas as $item)
                            <span class="badge bg-light text-dark border" style="font-size:12px">
                                {{ $item['nome'] }} — {{ $item['semestre'] }}º sem (concluído)
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="cancelarAvanco">Cancelar</button>
                    @if(count($previewAvanco) > 0)
                    <button type="button" class="btn btn-warning" wire:click="confirmarAvanco" wire:loading.attr="disabled">
                        <span wire:loading wire:target="confirmarAvanco" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="confirmarAvanco" class="bi bi-arrow-up-circle me-1"></i>
                        Confirmar Avanço de {{ count($previewAvanco) }} Turma(s)
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

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
                            <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="Ex: 2026/1">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Ano <span class="text-danger">*</span></label>
                            <input type="number" wire:model="ano" class="form-control @error('ano') is-invalid @enderror" placeholder="Ex: 2026">
                            @error('ano') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Semestre <span class="text-danger">*</span></label>
                            <select wire:model="semestre" class="form-select @error('semestre') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                <option value="1">1º Semestre</option>
                                <option value="2">2º Semestre</option>
                            </select>
                            @error('semestre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12"><hr class="my-1"><small class="text-muted fw-medium">Avaliação 1</small></div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Data Início</label>
                            <input type="date" wire:model="avaliacao1_inicio" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Data Fim</label>
                            <input type="date" wire:model="avaliacao1_fim" class="form-control">
                        </div>
                        <div class="col-12"><hr class="my-1"><small class="text-muted fw-medium">Avaliação 2</small></div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Data Início</label>
                            <input type="date" wire:model="avaliacao2_inicio" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Data Fim</label>
                            <input type="date" wire:model="avaliacao2_fim" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="ativo" id="ativo">
                                <label class="form-check-label fw-medium" for="ativo">Período letivo ativo</label>
                            </div>
                            <small class="text-muted">Ao ativar este período, os demais serão desativados automaticamente.</small>
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
                    <p class="text-muted mb-0">Tem certeza que deseja excluir este período letivo?</p>
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
