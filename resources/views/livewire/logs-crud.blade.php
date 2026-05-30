{{-- resources/views/livewire/logs-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h4 class="fw-bold mb-0">Log de Alterações</h4>
            <small class="text-muted">Histórico de ações realizadas no sistema</small>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">

                {{-- Busca com filtro --}}
                <div class="col-md-5">
                    <div class="input-group">
                        <select wire:model.live="filtro"
                                class="form-select flex-shrink-1"
                                style="max-width:140px;border-radius:6px 0 0 6px;border-right:none">
                            <option value="todos">Todos</option>
                            <option value="usuario">Usuário</option>
                            <option value="descricao">Descrição</option>
                        </select>
                        <span class="input-group-text bg-white px-2" style="border-left:none;border-right:none">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               class="form-control"
                               placeholder="Digite para filtrar...">
                        @if($search)
                        <button class="btn btn-outline-secondary" wire:click="$set('search','')" title="Limpar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Filtro módulo --}}
                <div class="col-md-3">
                    <select wire:model.live="modulo" class="form-select">
                        <option value="">Todos os módulos</option>
                        @foreach($modulos as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filtro ação --}}
                <div class="col-md-2">
                    <select wire:model.live="acao" class="form-select">
                        <option value="">Todas as ações</option>
                        @foreach($acoes as $a)
                            <option value="{{ $a }}">{{ ucfirst($a) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Limpar filtros --}}
                <div class="col-md-2">
                    <button wire:click="limpar" class="btn btn-light w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Limpar
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:calc(100vh - 220px);overflow-y:auto">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light" style="position:sticky;top:0;z-index:10">
                        <tr>
                            <th class="ps-3" style="width:160px">Data / Hora</th>
                            <th style="width:150px">Usuário</th>
                            <th style="width:100px">Ação</th>
                            <th style="width:130px">Módulo</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td class="ps-3" style="font-size:13px;color:#666">
                                <i class="bi bi-clock me-1"></i>
                                {{ $log->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                         style="width:28px;height:28px;font-size:12px;background:#E30613;flex-shrink:0">
                                        {{ strtoupper(substr($log->user_name, 0, 1)) }}
                                    </div>
                                    <span style="font-size:13px">{{ $log->user_name }}</span>
                                </div>
                            </td>
                            <td>
                                @if($log->acao === 'criou')
                                    <span class="badge" style="background:#198754">
                                        <i class="bi bi-plus-circle me-1"></i>Criou
                                    </span>
                                @elseif($log->acao === 'editou')
                                    <span class="badge" style="background:#0d6efd">
                                        <i class="bi bi-pencil me-1"></i>Editou
                                    </span>
                                @elseif($log->acao === 'excluiu')
                                    <span class="badge" style="background:#dc3545">
                                        <i class="bi bi-trash me-1"></i>Excluiu
                                    </span>
                                @else
                                    <span class="badge bg-secondary">{{ $log->acao }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge" style="background:#1a1a1a;font-size:11px">
                                    {{ $log->modulo }}
                                </span>
                            </td>
                            <td style="font-size:13px">{{ $log->descricao }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
                                Nenhum registro encontrado.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white border-top-0">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
