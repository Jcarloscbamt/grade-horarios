{{-- resources/views/livewire/usuarios-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Usuários <button type="button" data-bs-toggle="modal" data-bs-target="#helpModal" class="btn btn-outline-secondary btn-sm rounded-circle ms-1" style="width:24px;height:24px;padding:0;font-size:12px;line-height:1" title="Ajuda">?</button></h2>
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
        <div class="d-flex gap-4 flex-wrap">
            <span><span class="badge bg-primary me-1">Admin</span> Acesso total — pode incluir, editar e <strong>excluir</strong></span>
            <span><span class="badge bg-success me-1">Coordenador</span> Pode incluir e editar — <strong>não pode excluir</strong></span>
        </div>
    </div>

    {{-- Barra de pesquisa --}}
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="form-control border-start-0"
                    placeholder="Pesquisar por nome ou e-mail..."
                    autocomplete="off">
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-body py-2">
            <select wire:model.live="filtroAtivo" class="form-select" style="max-width:220px">
                <option value="todos">Todos os status</option>
                <option value="ativos">Somente Ativos</option>
                <option value="inativos">Somente Inativos</option>
            </select>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light" style="position:sticky;top:0;z-index:10">
                    <tr>
                        <th class="ps-3">Usuário</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Cadastrado em</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    <tr class="{{ !($usuario->ativo ?? true) ? 'opacity-50' : '' }}">
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
                            @foreach($usuario->roles as $role)
                                @if($role->name === 'admin')
                                    <span class="badge bg-primary">Admin</span>
                                @elseif($role->name === 'coordenador')
                                    <span class="badge bg-success">Coordenador</span>
                                @else
                                    <span class="badge bg-secondary">{{ $role->name }}</span>
                                @endif
                            @endforeach
                            @if($usuario->roles->isEmpty())
                                <span class="badge bg-warning text-dark">Sem perfil</span>
                            @endif
                        </td>
                        <td>{{ $usuario->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <button wire:click="toggleAtivo({{ $usuario->id }})"
                                class="btn btn-sm {{ ($usuario->ativo ?? true) ? 'btn-success' : 'btn-secondary' }}"
                                title="{{ ($usuario->ativo ?? true) ? 'Desativar' : 'Ativar' }}">
                                <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>
                                {{ ($usuario->ativo ?? true) ? 'Ativo' : 'Inativo' }}
                            </button>
                        </td>
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
                    {{-- Campos ocultos para enganar o autocomplete do navegador --}}
                    <input type="text"     name="fake_user"  style="display:none" autocomplete="username">
                    <input type="password" name="fake_pass"  style="display:none" autocomplete="current-password">
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
                                placeholder="email@unisenaimt.com.br"
                                autocomplete="off">
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
                                autocomplete="new-password"
                                placeholder="Mínimo 8 caracteres">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Perfil de Acesso <span class="text-danger">*</span></label>
                            <select wire:model="perfil"
                                class="form-select @error('perfil') is-invalid @enderror">
                                <option value="">Selecione o perfil...</option>
                                <option value="admin">Admin — acesso total</option>
                                <option value="coordenador">Coordenador — não pode excluir</option>
                                <option value="consulta">Consulta — somente visualização</option>
                            </select>
                            @error('perfil') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            {{-- Descrição do perfil selecionado --}}
                            @if($perfil === 'admin')
                            <div class="mt-2 p-2 rounded bg-primary bg-opacity-10">
                                <small class="text-primary">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Acesso total: pode incluir, editar e excluir qualquer registro.
                                </small>
                            </div>
                            @elseif($perfil === 'coordenador')
                            <div class="mt-2 p-2 rounded bg-success bg-opacity-10">
                                <small class="text-success">
                                    <i class="bi bi-person-check me-1"></i>
                                    Pode incluir e editar registros, mas <strong>não pode excluir</strong>.
                                </small>
                            </div>
                            @elseif($perfil === 'consulta')
                            <div class="mt-2 p-2 rounded bg-warning bg-opacity-10">
                                <small class="text-warning">
                                    <i class="bi bi-eye me-1"></i>
                                    Somente visualização — não pode incluir, editar ou excluir.
                                </small>
                            </div>
                            @endif
                        </div>

                        {{-- Status Ativo/Inativo — só ao editar --}}
                        @if($usuarioId)
                        <div class="col-12">
                            <label class="form-label fw-medium">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="ativoSwitch" wire:model.live="ativo" style="cursor:pointer">
                                <label class="form-check-label" for="ativoSwitch">
                                    @if($ativo)
                                    <span class="text-success fw-medium"><i class="bi bi-check-circle-fill me-1"></i>Ativo — pode acessar o sistema</span>
                                    @else
                                    <span class="text-danger fw-medium"><i class="bi bi-x-circle-fill me-1"></i>Inativo — bloqueado do sistema</span>
                                    @endif
                                </label>
                            </div>
                        </div>
                        @endif

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



<x-help-modal titulo="Ajuda — Usuários">
<p class="text-muted mb-3">Gerenciamento de acesso ao sistema. Disponível apenas para Administradores.</p>
<ul class="list-unstyled">
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Admin:</strong> Acesso total ao sistema, incluindo usuários e logs</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Coordenador:</strong> Acesso a cadastros, gerador e relatórios</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Consulta:</strong> Apenas visualização da grade e relatórios</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Troca de Senha:</strong> Na primeira entrada o usuário é obrigado a trocar a senha</li>
</ul>
</x-help-modal>
</div>