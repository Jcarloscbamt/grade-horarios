{{-- resources/views/livewire/usuarios-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Usuários</h2>
            <small class="text-muted">Gerenciamento de usuários e perfis de acesso</small>
        </div>
        <button wire:click="create" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Novo Usuário
        </button>
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

    {{-- Legenda de perfis --}}
    <div class="alert alert-light border mb-3 py-2">
        <div class="d-flex gap-4 flex-wrap" style="font-size:13px">
            <span><span class="badge bg-primary me-1">Admin</span> Acesso total — incluir, editar e excluir</span>
            <span><span class="badge bg-success me-1">Coordenador</span> Incluir e editar — não pode excluir</span>
            <span><span class="badge bg-secondary me-1">Consulta</span> Somente visualização — sem ações</span>
        </div>
    </div>

    {{-- Barra de pesquisa --}}
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="form-control border-start-0"
                    placeholder="Pesquisar por nome ou e-mail...">
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Usuário</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th>Cadastrado em</th>
                        <th class="text-center pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                      style="width:34px;height:34px;font-size:14px;flex-shrink:0">
                                    {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                </span>
                                <div>
                                    <div class="fw-medium">{{ $usuario->name }}</div>
                                    @if($usuario->id === auth()->id())
                                        <small class="text-muted">(você)</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $usuario->email }}</td>
                        <td>
                            @php $role = $usuario->getRoleNames()->first(); @endphp
                            @if($role === 'admin')
                                <span class="badge bg-primary">Admin</span>
                            @elseif($role === 'coordenador')
                                <span class="badge bg-success">Coordenador</span>
                            @elseif($role === 'consulta')
                                <span class="badge bg-secondary">Consulta</span>
                            @else
                                <span class="badge bg-warning text-dark">Sem perfil</span>
                            @endif
                        </td>
                        <td>
                            @if($usuario->password_change_required)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-shield-exclamation me-1"></i>Troca de senha pendente
                                </span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-check-circle me-1"></i>Ativo
                                </span>
                            @endif
                        </td>
                        <td>{{ $usuario->created_at->format('d/m/Y') }}</td>
                        <td class="text-center pe-3">
                            <button wire:click="edit({{ $usuario->id }})"
                                class="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($usuario->id !== auth()->id())
                            <button wire:click="confirmDelete({{ $usuario->id }})"
                                class="btn btn-sm btn-outline-danger" title="Excluir">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Nenhum usuário encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($usuarios->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $usuarios->links() }}</div>
        @endif
    </div>

    {{-- Modal: Incluir / Editar --}}
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
                            <label class="form-label fw-medium">Nome <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Nome completo">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">E-mail <span class="text-danger">*</span></label>
                            <input type="email" wire:model="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="email@unisenaimt.com.br">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">
                                Senha
                                @if($usuarioId)
                                    <small class="text-muted fw-normal">(deixe em branco para manter a atual)</small>
                                @else
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input type="password" wire:model="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Mínimo 8 caracteres">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if(!$usuarioId)
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                O usuário será solicitado a trocar a senha no primeiro login.
                            </div>
                            @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Perfil de Acesso <span class="text-danger">*</span></label>
                            <select wire:model.live="role"
                                class="form-select @error('role') is-invalid @enderror">
                                <option value="">Selecione o perfil...</option>
                                <option value="admin">Admin — acesso total</option>
                                <option value="coordenador">Coordenador — não pode excluir</option>
                                <option value="consulta">Consulta — somente visualização</option>
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            @if($role === 'admin')
                            <div class="mt-2 p-2 rounded bg-primary bg-opacity-10">
                                <small class="text-primary">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Acesso total: incluir, editar e excluir qualquer registro.
                                </small>
                            </div>
                            @elseif($role === 'coordenador')
                            <div class="mt-2 p-2 rounded bg-success bg-opacity-10">
                                <small class="text-success">
                                    <i class="bi bi-person-check me-1"></i>
                                    Pode incluir e editar registros, mas <strong>não pode excluir</strong>.
                                </small>
                            </div>
                            @elseif($role === 'consulta')
                            <div class="mt-2 p-2 rounded" style="background:#f8f9fa">
                                <small class="text-secondary">
                                    <i class="bi bi-eye me-1"></i>
                                    Somente visualização — não pode incluir, editar nem excluir.
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                        Salvar
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
                    <div class="text-danger mb-3">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size:3rem"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Confirmar Exclusão</h5>
                    <p class="text-muted mb-0">
                        Tem certeza que deseja excluir este usuário?<br>
                        <small>Esta ação não pode ser desfeita.</small>
                    </p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" wire:click="delete" wire:loading.attr="disabled">
                        <span wire:loading wire:target="delete" class="spinner-border spinner-border-sm me-1"></span>
                        Sim, excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
