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
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="diarioSwitch" wire:model.live="envio_diario_ativo" style="cursor:pointer">
                            <label class="form-check-label fw-medium" for="diarioSwitch">
                                Lembrete diário (aulas de amanhã)
                            </label>
                        </div>
                        <div class="d-flex align-items-center gap-2 {{ $envio_diario_ativo ? '' : 'opacity-50' }}">
                            <label class="form-label small mb-0">Horário:</label>
                            <input type="time" wire:model="horario_diario" class="form-control form-control-sm"
                                   style="max-width:130px" {{ $envio_diario_ativo ? '' : 'disabled' }}>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-info-circle me-1"></i>Todo dia, no horário escolhido, envia as aulas do dia seguinte.
                        </div>
                    </div>
                </div>

                {{-- Envio semanal --}}
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="semanalSwitch" wire:model.live="envio_semanal_ativo" style="cursor:pointer">
                            <label class="form-check-label fw-medium" for="semanalSwitch">
                                Resumo semanal (todas as aulas da semana)
                            </label>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap {{ $envio_semanal_ativo ? '' : 'opacity-50' }}">
                            <label class="form-label small mb-0">Dia:</label>
                            <select wire:model="dia_semanal" class="form-select form-select-sm" style="max-width:130px"
                                    {{ $envio_semanal_ativo ? '' : 'disabled' }}>
                                @foreach($dias as $num => $nome)
                                <option value="{{ $num }}">{{ $nome }}</option>
                                @endforeach
                            </select>
                            <label class="form-label small mb-0">às</label>
                            <input type="time" wire:model="horario_semanal" class="form-control form-control-sm"
                                   style="max-width:110px" {{ $envio_semanal_ativo ? '' : 'disabled' }}>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-info-circle me-1"></i>No dia e horário escolhidos, envia o resumo da semana.
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
                    <p class="text-muted mb-3">Notifica os professores por e-mail sobre suas aulas.</p>
                    <h6 class="fw-bold mb-2"><i class="bi bi-robot me-1 text-primary"></i>Envio Automático</h6>
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Lembrete diário:</strong> ative e escolha o horário. Todo dia o sistema envia as aulas do dia seguinte.</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Resumo semanal:</strong> ative, escolha o dia e horário. Envia todas as aulas da semana.</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-exclamation-triangle-fill text-warning mt-1 flex-shrink-0" style="font-size:12px"></i><span>O envio automático depende do <strong>agendador (cron)</strong> estar configurado no servidor.</span></li>
                    </ul>
                    <h6 class="fw-bold mb-2"><i class="bi bi-send me-1"></i>Envio Manual</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>Escolha o período (amanhã, semana ou dia específico)</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>Escolha todos os professores ou um específico</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i><span>Clique em "Enviar Agora" — o resultado aparece à direita</span></li>
                    </ul>
                    <div class="alert alert-info py-2 mt-2" style="font-size:12px">
                        <i class="bi bi-info-circle me-1"></i>Só recebem e-mail os professores com endereço cadastrado e que tenham aula no período escolhido.
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

</div>
