{{-- resources/views/livewire/cursos-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Cursos</h2>
            <small class="text-muted">Gerenciamento de cursos da instituição</small>
        </div>
        @hasanyrole('admin|coordenador')
        <button wire:click="create" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Novo Curso
        </button>
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
                    <option value="sigla">Sigla</option>
                    <option value="coordenador">Coordenador</option>
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
                            <th class="ps-3">Curso</th>
                            <th>Sigla</th>
                            <th>Nível</th>
                            <th>Coordenador</th>
                            <th>Contato</th>
                            <th class="text-center">Cor Grade</th>
                            <th class="text-center pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cursos as $curso)
                        <tr>
                            <td class="ps-3 fw-medium">{{ $curso->nome }}</td>
                            <td>
                                <span class="badge fw-semibold"
                                      style="background:{{ $curso->cor_grade ?? '#E30613' }}; color:white">
                                    {{ $curso->sigla }}
                                </span>
                            </td>
                            <td>{{ $curso->nivel }}</td>
                            <td>{{ $curso->coordenador }}</td>
                            <td>
                                <div>{{ $curso->email_coord }}</div>
                                @if($curso->telefone_coord)
                                    <small class="text-muted">{{ $curso->telefone_coord }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="rounded-circle border"
                                         style="width:24px;height:24px;background:{{ $curso->cor_grade ?? '#E30613' }}"></div>
                                    <code style="font-size:11px">{{ $curso->cor_grade ?? '#E30613' }}</code>
                                </div>
                            </td>
                            <td class="text-center pe-3">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="edit({{ $curso->id }})" class="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endhasanyrole
                                @hasrole('admin')
                                <button wire:click="confirmDelete({{ $curso->id }})" class="btn btn-sm btn-outline-danger" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endhasrole
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum curso encontrado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($cursos->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $cursos->links() }}</div>
        @endif
    </div>

    {{-- Modal: Incluir / Editar --}}
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
                            <label class="form-label fw-medium">Nome do Curso <span class="text-danger">*</span></label>
                            <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="Ex: Análise e Desenvolvimento de Sistemas">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">Sigla <span class="text-danger">*</span></label>
                            <input type="text" wire:model="sigla" class="form-control @error('sigla') is-invalid @enderror" placeholder="Ex: ADS" style="text-transform:uppercase">
                            @error('sigla') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">Nível <span class="text-danger">*</span></label>
                            <select wire:model="nivel" class="form-select @error('nivel') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                @foreach($niveis as $n)
                                    <option value="{{ $n }}">{{ $n }}</option>
                                @endforeach
                            </select>
                            @error('nivel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-medium">Coordenador <span class="text-danger">*</span></label>
                            <input type="text" wire:model="coordenador" class="form-control @error('coordenador') is-invalid @enderror" placeholder="Nome completo do coordenador">
                            @error('coordenador') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-medium">E-mail do Coordenador <span class="text-danger">*</span></label>
                            <input type="email" wire:model="email_coord" class="form-control @error('email_coord') is-invalid @enderror" placeholder="email@unisenaimt.com.br">
                            @error('email_coord') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">Telefone</label>
                            <input type="text" wire:model="telefone_coord"
                                class="form-control @error('telefone_coord') is-invalid @enderror"
                                placeholder="(65) 3612-9966"
                                maxlength="15">
                            @error('telefone_coord') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Formatação automática ao digitar</div>
                        </div>

                        {{-- Cor da Grade --}}
                        <div class="col-12">
                            <hr class="my-1">
                            <label class="form-label fw-medium">
                                <i class="bi bi-palette me-1"></i>
                                Cor da Grade de Horários <span class="text-danger">*</span>
                            </label>

                            {{-- Cores sugeridas --}}
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @foreach($coresSugeridas as $hex => $nome)
                                <button type="button"
                                    wire:click="$set('cor_grade', '{{ $hex }}')"
                                    title="{{ $nome }} ({{ $hex }})"
                                    class="rounded-circle border border-2 {{ $cor_grade === $hex ? 'border-dark' : 'border-white' }}"
                                    style="width:32px;height:32px;background:{{ $hex }};cursor:pointer;padding:0;outline:{{ $cor_grade === $hex ? '2px solid #333' : 'none' }}">
                                </button>
                                @endforeach
                            </div>

                            {{-- Input manual + color picker --}}
                            <div class="d-flex align-items-center gap-2">
                                <input type="color"
                                    wire:model.live="cor_grade"
                                    class="form-control form-control-color"
                                    style="width:48px;height:38px;padding:2px;cursor:pointer"
                                    title="Escolher cor personalizada">
                                <input type="text"
                                    wire:model.live="cor_grade"
                                    class="form-control @error('cor_grade') is-invalid @enderror"
                                    placeholder="#E30613"
                                    maxlength="7"
                                    style="font-family:monospace;max-width:120px">
                                {{-- Preview --}}
                                <div class="d-flex align-items-center gap-2 ms-2 p-2 rounded flex-grow-1"
                                     style="background:{{ $cor_grade }};min-height:38px">
                                    <span class="fw-bold text-white" style="font-size:13px;text-shadow:0 1px 2px rgba(0,0,0,0.5)">
                                        {{ strtoupper($sigla ?: 'ADS') }} — Preview da grade
                                    </span>
                                </div>
                            </div>
                            @error('cor_grade') <div class="text-danger mt-1" style="font-size:13px">{{ $message }}</div> @enderror
                            <small class="text-muted">Digite o código hexadecimal ou clique no seletor de cor. Ex: #E30613</small>
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

    {{-- Modal: Confirmar Exclusão --}}
    @if($showDelete)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center py-4">
                    <div class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill" style="font-size:3rem"></i></div>
                    <h5 class="fw-bold mb-2">Confirmar Exclusão</h5>
                    <p class="text-muted mb-0">Tem certeza que deseja excluir este curso?<br><small>Esta ação não pode ser desfeita.</small></p>
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
