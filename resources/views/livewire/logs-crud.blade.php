{{-- resources/views/livewire/logs-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Log de Alterações</h4> <button type="button" data-bs-toggle="modal" data-bs-target="#helpModal" class="btn btn-outline-secondary btn-sm rounded-circle ms-1" style="width:24px;height:24px;padding:0;font-size:12px;line-height:1" title="Ajuda">?</button>
            <small class="text-muted">Histórico de ações realizadas no sistema</small>
        </div>
    </div>

    {{-- Limpeza de logs --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            @if(session('msgLog'))
            <div class="alert alert-success py-2 mb-2"><i class="bi bi-check-circle me-1"></i>{{ session('msgLog') }}</div>
            @endif
            @if(!empty($resultadoLimpeza))
            <div class="alert alert-info py-2 mb-2">
                <i class="bi bi-trash me-1"></i>
                @if($resultadoLimpeza['total'] > 0)
                Removidos {{ $resultadoLimpeza['total'] }} registro(s) com mais de {{ $resultadoLimpeza['dias'] }} dias
                ({{ $resultadoLimpeza['logs'] }} de ações, {{ $resultadoLimpeza['emails'] }} de e-mail, {{ $resultadoLimpeza['whatsapp'] }} de WhatsApp).
                @else
                Nenhum registro com mais de {{ $resultadoLimpeza['dias'] }} dias foi encontrado para apagar.
                @endif
            </div>
            @endif

            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-clock-history text-danger"></i>
                <span class="fw-semibold">Limpeza de logs antigos</span>
                <span class="badge bg-light text-dark border">Total atual: {{ $totalRegistros }} registro(s)</span>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-medium mb-1">Manter os logs dos últimos</label>
                    <select wire:model.live="retencaoDias" class="form-select form-select-sm">
                        <option value="15">15 dias</option>
                        <option value="30">30 dias</option>
                        <option value="60">60 dias</option>
                        <option value="90">90 dias</option>
                        <option value="180">180 dias (6 meses)</option>
                        <option value="365">365 dias (1 ano)</option>
                        <option value="0">Nunca apagar</option>
                    </select>
                    <div class="small text-muted mt-1">
                        @if((int)$retencaoDias === 0)
                            Nada será apagado automaticamente.
                        @else
                            Tudo com mais de <strong>{{ $retencaoDias }} dias</strong> pode ser apagado.
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="limpAuto"
                               wire:model.live="limpezaAuto" style="cursor:pointer"
                               @if((int)$retencaoDias === 0) disabled @endif>
                        <label class="form-check-label small" for="limpAuto">
                            Limpeza automática diária
                        </label>
                    </div>
                    <div class="small text-muted mt-1">
                        @if($limpezaAuto && (int)$retencaoDias > 0)
                            Todo dia, de madrugada, o sistema apaga sozinho os logs com mais de {{ $retencaoDias }} dias.
                        @else
                            Se ligada, o sistema apaga sozinho (de madrugada) usando o período acima.
                        @endif
                    </div>
                </div>

                <div class="col-md-4 text-md-end">
                    <button wire:click="salvarRetencao" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-save me-1"></i>Salvar configuração
                    </button>
                    <button wire:click="limparAntigos"
                            wire:confirm="Apagar agora todos os logs com mais de {{ $retencaoDias }} dias? Esta ação não pode ser desfeita."
                            class="btn btn-outline-danger btn-sm mt-1 mt-md-0"
                            @if((int)$retencaoDias === 0) disabled @endif>
                        <i class="bi bi-trash me-1"></i>Apagar antigos agora
                    </button>
                </div>
            </div>
            <div class="text-muted small mt-2">
                <i class="bi bi-info-circle me-1"></i>A limpeza vale para todos os logs: ações do sistema, histórico de e-mail e de WhatsApp. "Salvar" guarda o período e a limpeza automática; "Apagar antigos agora" remove na hora usando o período selecionado.
            </div>
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
                            <th style="width:120px">IP</th>
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
                            <td style="font-size:12px;color:#999">{{ $log->ip ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
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


<x-help-modal titulo="Ajuda — Logs">
<p class="text-muted mb-3">Histórico de todas as alterações feitas no sistema (auditoria).</p>
<h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-list-check me-1"></i>O que cada coluna mostra</h6>
<ul class="list-unstyled mb-3">
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Data / Hora:</strong> quando a ação aconteceu</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Usuário:</strong> quem fez a alteração</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Ação:</strong> criou, editou ou excluiu</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Módulo:</strong> qual tela foi alterada</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Descrição:</strong> detalhe do que mudou</li>
    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>IP:</strong> endereço de rede do dispositivo usado</li>
</ul>
<h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-funnel me-1"></i>Filtros</h6>
<p class="small text-muted mb-3">Use a busca e os filtros de módulo e ação para encontrar registros específicos. O botão "Limpar" zera os filtros (não apaga nada).</p>
<h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-trash me-1"></i>Limpeza de logs antigos</h6>
<ul class="list-unstyled mb-2">
    <li class="mb-2"><i class="bi bi-arrow-right-short text-primary me-1"></i><strong>Manter os logs dos últimos X dias:</strong> escolha o período (15 dias a 1 ano, ou "Nunca apagar"). Tudo mais antigo que isso pode ser apagado.</li>
    <li class="mb-2"><i class="bi bi-arrow-right-short text-primary me-1"></i><strong>Apagar antigos agora:</strong> remove na hora os registros mais antigos que o período escolhido.</li>
    <li class="mb-2"><i class="bi bi-arrow-right-short text-primary me-1"></i><strong>Limpeza automática diária:</strong> quando ligada e salva, o sistema apaga sozinho (de madrugada) usando <em>o mesmo período</em> escolhido acima.</li>
    <li class="mb-2"><i class="bi bi-arrow-right-short text-primary me-1"></i><strong>Salvar configuração:</strong> guarda o período e o liga/desliga da limpeza automática.</li>
</ul>
<div class="alert alert-warning py-2" style="font-size:12px">
    <i class="bi bi-exclamation-triangle me-1"></i>A limpeza vale para os três tipos de log (ações, e-mail e WhatsApp) e é permanente — não dá para desfazer. Mantenha backups do banco.
</div>
</x-help-modal>
</div>