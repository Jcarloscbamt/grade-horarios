{{-- resources/views/livewire/envio-emails.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0"><i class="bi bi-envelope-paper me-2" style="color:#E30613"></i>Envio de Avisos por E-mail</h4>
            <button type="button" data-bs-toggle="modal" data-bs-target="#helpEmail"
                class="btn btn-outline-secondary btn-sm rounded-circle"
                style="width:24px;height:24px;padding:0;font-size:12px;line-height:1" title="Ajuda">?</button>
        </div>
        <small class="text-muted">Notifica os professores sobre suas aulas</small>
    </div>

    @if(!$periodoAtivo)
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Nenhum período letivo ativo. Ative um período antes de enviar avisos.</div>
    @else

    {{-- ════════ CONFIGURAÇÃO AUTOMÁTICA ════════ --}}
    <div class="card border-0 shadow-sm mb-3 border-start border-primary border-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-robot me-1 text-primary"></i>Envio Automático</h6>

            @if(session('config_ok'))
            <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i>{{ session('config_ok') }}</div>
            @endif

            <div class="row g-3">
                {{-- Envio diário --}}
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-medium mb-2"><i class="bi bi-calendar-day me-1"></i>Lembrete diário (aulas de amanhã)</div>
                        <div class="mb-2 ps-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="diarioEmail" wire:model.live="envio_diario_ativo" style="cursor:pointer">
                                <label class="form-check-label" for="diarioEmail"><i class="bi bi-envelope me-1"></i>Enviar por E-mail</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="diarioWhats" wire:model.live="whatsapp_diario_ativo" style="cursor:pointer">
                                <label class="form-check-label" for="diarioWhats"><i class="bi bi-whatsapp me-1 text-success"></i>Enviar por WhatsApp</label>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 {{ ($envio_diario_ativo || $whatsapp_diario_ativo) ? '' : 'opacity-50' }}">
                            <label class="form-label small mb-0">Horário:</label>
                            <input type="time" wire:model="horario_diario" class="form-control form-control-sm"
                                   style="max-width:130px" {{ ($envio_diario_ativo || $whatsapp_diario_ativo) ? '' : 'disabled' }}>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-info-circle me-1"></i>Todo dia, no horário escolhido, envia as aulas do dia seguinte pelos canais ativos.
                        </div>
                    </div>
                </div>

                {{-- Envio semanal --}}
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-medium mb-2"><i class="bi bi-calendar-week me-1"></i>Resumo semanal (todas as aulas da semana)</div>
                        <div class="mb-2 ps-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="semanalEmail" wire:model.live="envio_semanal_ativo" style="cursor:pointer">
                                <label class="form-check-label" for="semanalEmail"><i class="bi bi-envelope me-1"></i>Enviar por E-mail</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="semanalWhats" wire:model.live="whatsapp_semanal_ativo" style="cursor:pointer">
                                <label class="form-check-label" for="semanalWhats"><i class="bi bi-whatsapp me-1 text-success"></i>Enviar por WhatsApp</label>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap {{ ($envio_semanal_ativo || $whatsapp_semanal_ativo) ? '' : 'opacity-50' }}">
                            <label class="form-label small mb-0">Dia:</label>
                            <select wire:model="dia_semanal" class="form-select form-select-sm" style="max-width:130px"
                                    {{ ($envio_semanal_ativo || $whatsapp_semanal_ativo) ? '' : 'disabled' }}>
                                @foreach($dias as $num => $nome)
                                <option value="{{ $num }}">{{ $nome }}</option>
                                @endforeach
                            </select>
                            <label class="form-label small mb-0">às</label>
                            <input type="time" wire:model="horario_semanal" class="form-control form-control-sm"
                                   style="max-width:110px" {{ ($envio_semanal_ativo || $whatsapp_semanal_ativo) ? '' : 'disabled' }}>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-info-circle me-1"></i>No dia e horário escolhidos, envia o resumo da semana pelos canais ativos.
                        </div>
                    </div>
                </div>
            </div>

            <button wire:click="salvarConfig" class="btn btn-primary btn-sm mt-3">
                <i class="bi bi-floppy me-1"></i>Salvar Configuração
            </button>
        </div>
    </div>

    {{-- ════════ ENVIO MANUAL ════════ --}}
    <div class="row g-3">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-send me-1"></i>Envio Manual (agora)</h6>

                    <label class="form-label fw-medium small mb-1">O que enviar?</label>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.live="tipo" value="amanha" id="t_amanha">
                            <label class="form-check-label" for="t_amanha"><strong>Aulas de amanhã</strong></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.live="tipo" value="semana" id="t_semana">
                            <label class="form-check-label" for="t_semana"><strong>Resumo da semana</strong></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.live="tipo" value="dia" id="t_dia">
                            <label class="form-check-label" for="t_dia"><strong>Dia específico</strong></label>
                        </div>
                    </div>

                    @if($tipo === 'dia')
                    <div class="mb-3">
                        <label class="form-label fw-medium small mb-1">Dia da semana</label>
                        <select wire:model.live="diaEspecifico" class="form-select form-select-sm">
                            @foreach($dias as $num => $nome)<option value="{{ $num }}">{{ $nome }}</option>@endforeach
                        </select>
                    </div>
                    @endif

                    <label class="form-label fw-medium small mb-1">Enviar para</label>
                    <select wire:model.live="professorFiltro" class="form-select form-select-sm mb-3">
                        <option value="">Todos os professores</option>
                        @foreach($professores as $p)<option value="{{ $p->id }}">{{ $p->nome }}</option>@endforeach
                    </select>

                    <label class="form-label fw-medium small mb-1">Por qual canal?</label>
                    <div class="btn-group w-100 mb-3" role="group">
                        <input type="radio" class="btn-check" wire:model.live="canalManual" value="email" id="c_email" autocomplete="off">
                        <label class="btn btn-outline-primary btn-sm" for="c_email"><i class="bi bi-envelope me-1"></i>E-mail</label>

                        <input type="radio" class="btn-check" wire:model.live="canalManual" value="whatsapp" id="c_whats" autocomplete="off">
                        <label class="btn btn-outline-success btn-sm" for="c_whats"><i class="bi bi-whatsapp me-1"></i>WhatsApp</label>

                        <input type="radio" class="btn-check" wire:model.live="canalManual" value="ambos" id="c_ambos" autocomplete="off">
                        <label class="btn btn-outline-secondary btn-sm" for="c_ambos"><i class="bi bi-broadcast me-1"></i>Ambos</label>
                    </div>

                    @if($canalManual === 'whatsapp' || $canalManual === 'ambos')
                    <div class="small text-muted mb-2" style="line-height:1.3">
                        <i class="bi bi-info-circle me-1"></i>O WhatsApp só envia se o provedor estiver configurado (tela "Provedor de WhatsApp") e a conta/sandbox liberada.
                    </div>
                    @endif

                    <button wire:click="enviar" wire:loading.attr="disabled" class="btn btn-success w-100">
                        <span wire:loading wire:target="enviar" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="enviar" class="bi bi-send me-1"></i>
                        Enviar Agora
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-card-checklist me-1"></i>Resultado do envio</h6>
                    @if(empty($resultado))
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-envelope fs-1 d-block mb-2 opacity-25"></i>
                        Configure e clique em "Enviar Agora".
                    </div>
                    @elseif(isset($resultado['erro']))
                    <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i>{{ $resultado['erro'] }}</div>
                    @else
                    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><strong>{{ $resultado['enviados'] }}</strong> e-mail(s) enviado(s)!</div>
                    @if(isset($resultado['aviso']))<div class="alert alert-warning py-2"><i class="bi bi-info-circle me-1"></i>{{ $resultado['aviso'] }}</div>@endif
                    @if(!empty($resultado['detalhes']))
                    <div class="border rounded p-2" style="max-height:280px;overflow-y:auto;font-size:13px">
                        @foreach($resultado['detalhes'] as $d)<div class="text-success py-1">{{ $d }}</div>@endforeach
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ HISTÓRICO DE ENVIOS ════════ --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-1"></i>Histórico de Envios</h6>
                <div class="d-flex align-items-center gap-2">
                    @if($abaHistorico === 'whatsapp')
                    <span class="badge bg-success">{{ $totalSucessoWhats }} enviados</span>
                    <span class="badge bg-danger">{{ $totalFalhaWhats }} falhas</span>
                    @else
                    <span class="badge bg-success">{{ $totalSucesso }} enviados</span>
                    <span class="badge bg-danger">{{ $totalFalha }} falhas</span>
                    @endif
                    <select wire:model.live="filtroHistorico" class="form-select form-select-sm" style="width:auto">
                        <option value="todos">Todos</option>
                        <option value="sucesso">Só sucessos</option>
                        <option value="falha">Só falhas</option>
                    </select>
                    <button wire:click="limparHistorico"
                            wire:confirm="Tem certeza que deseja limpar todo o histórico desta aba?"
                            class="btn btn-outline-danger btn-sm" title="Limpar histórico desta aba">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>

            {{-- Abas E-mail / WhatsApp --}}
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $abaHistorico === 'email' ? 'active' : '' }}" href="#"
                       wire:click.prevent="$set('abaHistorico', 'email')">
                        <i class="bi bi-envelope me-1"></i>E-mail
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $abaHistorico === 'whatsapp' ? 'active' : '' }}" href="#"
                       wire:click.prevent="$set('abaHistorico', 'whatsapp')">
                        <i class="bi bi-whatsapp me-1 text-success"></i>WhatsApp
                    </a>
                </li>
            </ul>

            {{-- ===== Histórico de E-MAIL ===== --}}
            @if($abaHistorico === 'email')
            @if($historico->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                Nenhum envio de e-mail registrado ainda.
            </div>
            @else
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead style="position:sticky;top:0;background:#fff;z-index:1">
                        <tr>
                            <th class="small text-muted text-uppercase">Status</th>
                            <th class="small text-muted text-uppercase">Data/Hora</th>
                            <th class="small text-muted text-uppercase">Professor</th>
                            <th class="small text-muted text-uppercase">E-mail</th>
                            <th class="small text-muted text-uppercase">Tipo</th>
                            <th class="small text-muted text-uppercase text-center">Aulas</th>
                            <th class="small text-muted text-uppercase">Detalhe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historico as $log)
                        <tr>
                            <td>
                                @if($log->sucesso)
                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> OK</span>
                                @else
                                <span class="badge bg-danger"><i class="bi bi-x-lg"></i> Falha</span>
                                @endif
                            </td>
                            <td class="small">{{ $log->enviado_em?->format('d/m/Y H:i') ?? $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="small fw-medium">{{ $log->professor_nome }}</td>
                            <td class="small text-muted">{{ $log->email }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $log->tipo_label }}</span></td>
                            <td class="text-center small">{{ $log->qtd_aulas }}</td>
                            <td class="small">
                                @if($log->sucesso)
                                <span class="text-success">Aceito pelo servidor</span>
                                @else
                                <span class="text-danger" title="{{ $log->erro }}">{{ Str::limit($log->erro, 50) }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-muted small mt-2">
                <i class="bi bi-info-circle me-1"></i>
                "Aceito pelo servidor" = o provedor de e-mail (ex: Gmail) recebeu e aceitou a mensagem sem erro.
                Mostrando os últimos 100 registros.
            </div>
            @endif
            @endif

            {{-- ===== Histórico de WHATSAPP ===== --}}
            @if($abaHistorico === 'whatsapp')
            @if($historicoWhats->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-whatsapp fs-2 d-block mb-2 opacity-25"></i>
                Nenhum envio de WhatsApp registrado ainda.
            </div>
            @else
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead style="position:sticky;top:0;background:#fff;z-index:1">
                        <tr>
                            <th class="small text-muted text-uppercase">Status</th>
                            <th class="small text-muted text-uppercase">Data/Hora</th>
                            <th class="small text-muted text-uppercase">Professor</th>
                            <th class="small text-muted text-uppercase">Telefone</th>
                            <th class="small text-muted text-uppercase">Tipo</th>
                            <th class="small text-muted text-uppercase text-center">Aulas</th>
                            <th class="small text-muted text-uppercase">Detalhe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historicoWhats as $log)
                        <tr>
                            <td>
                                @if($log->sucesso)
                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> OK</span>
                                @else
                                <span class="badge bg-danger"><i class="bi bi-x-lg"></i> Falha</span>
                                @endif
                            </td>
                            <td class="small">{{ $log->enviado_em?->format('d/m/Y H:i') ?? $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="small fw-medium">{{ $log->professor_nome }}</td>
                            <td class="small text-muted">{{ $log->telefone }}</td>
                            <td><span class="badge bg-light text-dark border">{{ ucfirst($log->tipo) }}</span></td>
                            <td class="text-center small">{{ $log->qtd_aulas }}</td>
                            <td class="small">
                                @if($log->sucesso)
                                <span class="text-success">Aceito pelo provedor</span>
                                @else
                                <span class="text-danger" title="{{ $log->erro }}">{{ Str::limit($log->erro, 50) }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-muted small mt-2">
                <i class="bi bi-info-circle me-1"></i>
                "Aceito pelo provedor" = a API de WhatsApp (Meta ou Twilio) recebeu a mensagem. A entrega final depende da conta/sandbox estar liberada.
                Mostrando os últimos 100 registros.
            </div>
            @endif
            @endif
        </div>
    </div>

    @endif

    {{-- Modal de Ajuda --}}
    <div class="modal fade" id="helpEmail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background:#1a1a1a;color:white;border-bottom:3px solid #E30613">
                    <h5 class="modal-title fw-bold"><i class="bi bi-question-circle me-2"></i>Ajuda — Envio de E-mails</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="font-size:14px">
                    <p class="text-muted mb-3">Notifica os professores sobre suas aulas por <strong>e-mail</strong> e/ou <strong>WhatsApp</strong>.</p>
                    <h6 class="fw-bold mb-2"><i class="bi bi-robot me-1 text-primary"></i>Envio Automático</h6>
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Lembrete diário:</strong> escolha o horário e ligue os canais desejados (E-mail e/ou WhatsApp). Todo dia o sistema envia as aulas do dia seguinte.</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Resumo semanal:</strong> escolha o dia, o horário e os canais. Envia todas as aulas da semana.</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-whatsapp text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span>Cada canal liga/desliga de forma independente, mas os dois usam o <strong>mesmo horário</strong>.</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-exclamation-triangle-fill text-warning mt-1 flex-shrink-0" style="font-size:12px"></i><span>O envio automático depende do <strong>agendador (cron)</strong> configurado no servidor. O WhatsApp exige um provedor configurado (Meta ou Twilio) na tela "Provedor de WhatsApp".</span></li>
                    </ul>
                    <h6 class="fw-bold mb-2"><i class="bi bi-send me-1"></i>Envio Manual</h6>
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>Escolha o período (amanhã, semana ou dia específico)</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>Escolha todos os professores ou um específico</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>Escolha o <strong>canal</strong>: E-mail, WhatsApp ou Ambos</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>Clique em "Enviar Agora" — o resultado aparece à direita</span></li>
                    </ul>
                    <h6 class="fw-bold mb-2"><i class="bi bi-clock-history me-1"></i>Histórico</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>Abas separadas para <strong>E-mail</strong> e <strong>WhatsApp</strong>, com status (OK/falha), data, professor e detalhe do erro.</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>A limpeza de registros antigos por período fica na tela de <strong>Logs</strong>.</span></li>
                    </ul>
                    <div class="alert alert-info py-2 mt-2" style="font-size:12px">
                        <i class="bi bi-info-circle me-1"></i>Recebem e-mail os professores com e-mail cadastrado; recebem WhatsApp os que têm telefone cadastrado — sempre que tiverem aula no período escolhido.
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

</div>
