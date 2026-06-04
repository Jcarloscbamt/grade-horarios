{{-- resources/views/livewire/envio-emails.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0"><i class="bi bi-envelope-paper me-2" style="color:#E30613"></i>Envio de Avisos por E-mail</h4>
        </div>
        <small class="text-muted">Notifica os professores sobre suas aulas</small>
    </div>

    @if(!$periodoAtivo)
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Nenhum período letivo ativo. Ative um período antes de enviar avisos.</div>
    @else

    <div class="row g-3">
        {{-- Configuração do envio --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-1"></i>Configurar envio</h6>

                    {{-- Tipo de período --}}
                    <label class="form-label fw-medium small mb-1">O que enviar?</label>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.live="tipo" value="amanha" id="t_amanha">
                            <label class="form-check-label" for="t_amanha">
                                <strong>Aulas de amanhã</strong>
                                <div class="text-muted small">Lembrete do dia seguinte</div>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.live="tipo" value="semana" id="t_semana">
                            <label class="form-check-label" for="t_semana">
                                <strong>Resumo da semana</strong>
                                <div class="text-muted small">Todas as aulas da semana</div>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.live="tipo" value="dia" id="t_dia">
                            <label class="form-check-label" for="t_dia">
                                <strong>Dia específico</strong>
                                <div class="text-muted small">Escolha o dia da semana</div>
                            </label>
                        </div>
                    </div>

                    @if($tipo === 'dia')
                    <div class="mb-3">
                        <label class="form-label fw-medium small mb-1">Dia da semana</label>
                        <select wire:model.live="diaEspecifico" class="form-select form-select-sm">
                            @foreach($dias as $num => $nome)
                            <option value="{{ $num }}">{{ $nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Destinatário --}}
                    <label class="form-label fw-medium small mb-1">Enviar para</label>
                    <select wire:model.live="professorFiltro" class="form-select form-select-sm mb-3">
                        <option value="">Todos os professores</option>
                        @foreach($professores as $p)
                        <option value="{{ $p->id }}">{{ $p->nome }}</option>
                        @endforeach
                    </select>

                    <button wire:click="enviar" wire:loading.attr="disabled" class="btn btn-primary w-100">
                        <span wire:loading wire:target="enviar" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="enviar" class="bi bi-send me-1"></i>
                        Enviar Avisos
                    </button>
                </div>
            </div>
        </div>

        {{-- Resultado --}}
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-card-checklist me-1"></i>Resultado do envio</h6>

                    @if(empty($resultado))
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-envelope fs-1 d-block mb-2 opacity-25"></i>
                        Configure e clique em "Enviar Avisos" para notificar os professores.
                    </div>
                    @elseif(isset($resultado['erro']))
                    <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i>{{ $resultado['erro'] }}</div>
                    @else
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-1"></i>
                        <strong>{{ $resultado['enviados'] }}</strong> e-mail(s) enviado(s) com sucesso!
                    </div>
                    @if(isset($resultado['aviso']))
                    <div class="alert alert-warning py-2"><i class="bi bi-info-circle me-1"></i>{{ $resultado['aviso'] }}</div>
                    @endif
                    @if(!empty($resultado['detalhes']))
                    <div class="border rounded p-2" style="max-height:300px;overflow-y:auto;font-size:13px">
                        @foreach($resultado['detalhes'] as $d)
                        <div class="text-success py-1">{{ $d }}</div>
                        @endforeach
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-3 py-2" style="font-size:13px">
        <i class="bi bi-robot me-1"></i>
        <strong>Envio automático:</strong> além do envio manual aqui, o sistema pode enviar os avisos de amanhã
        automaticamente todo dia. Veja as instruções de configuração do agendador (scheduler).
    </div>

    @endif
</div>
