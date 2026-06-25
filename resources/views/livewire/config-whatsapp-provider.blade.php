<div class="container-fluid py-3" style="max-width:900px">

    <div class="d-flex align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-whatsapp text-success me-2"></i>Provedor de WhatsApp</h4>
        <button type="button" data-bs-toggle="modal" data-bs-target="#helpModal"
                class="btn btn-outline-secondary btn-sm rounded-circle ms-2"
                style="width:24px;height:24px;padding:0;font-size:12px;line-height:1" title="Ajuda">?</button>
    </div>

    @if(session('cfgwa_ok'))
    <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i>{{ session('cfgwa_ok') }}</div>
    @endif

    {{-- Escolha do provedor --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold mb-2">Qual serviço enviar o WhatsApp?</label>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="card h-100 p-3 {{ $provider === 'meta' ? 'border-success border-2' : '' }}" style="cursor:pointer">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.live="provider" value="meta" id="prov_meta">
                            <label class="form-check-label fw-medium" for="prov_meta">
                                <i class="bi bi-meta me-1 text-primary"></i>Meta Cloud API
                            </label>
                        </div>
                        <div class="small text-muted mt-1">Oficial da Meta. Exige verificação da empresa (CNPJ) para entregar no Brasil.</div>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="card h-100 p-3 {{ $provider === 'twilio' ? 'border-success border-2' : '' }}" style="cursor:pointer">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" wire:model.live="provider" value="twilio" id="prov_twilio">
                            <label class="form-check-label fw-medium" for="prov_twilio">
                                <i class="bi bi-telephone-outbound me-1 text-danger"></i>Twilio
                            </label>
                        </div>
                        <div class="small text-muted mt-1">BSP oficial. Tem Sandbox para testar a entrega no Brasil sem CNPJ.</div>
                    </label>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Modo de envio</label>
                    <select wire:model.live="mode" class="form-select form-select-sm">
                        <option value="text">Texto livre (teste / janela de 24h)</option>
                        <option value="template">Template aprovado (produção)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Código do país (DDI)</label>
                    <input type="text" wire:model="default_country" class="form-control form-control-sm" style="max-width:120px" placeholder="55">
                </div>
            </div>
        </div>
    </div>

    {{-- Configuração Meta --}}
    <div class="card border-0 shadow-sm mb-3 {{ $provider === 'meta' ? '' : 'opacity-75' }}">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-meta me-1 text-primary"></i>Credenciais — Meta Cloud API
            @if($provider === 'meta')<span class="badge bg-success ms-1">Ativo</span>@endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Phone Number ID</label>
                    <input type="text" wire:model="meta_phone_id" class="form-control form-control-sm" placeholder="Identificador do número">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium mb-1">Versão da API</label>
                    <input type="text" wire:model="meta_api_version" class="form-control form-control-sm" placeholder="v25.0">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium mb-1">Token de acesso</label>
                    <input type="password" wire:model="meta_token_novo" class="form-control form-control-sm"
                           placeholder="{{ $metaTokenSalvo ? '•••••• (salvo)' : 'colar token' }}">
                    @if($metaTokenSalvo)<div class="small text-success mt-1"><i class="bi bi-check-circle"></i> Token salvo</div>@endif
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Nome do template</label>
                    <input type="text" wire:model="meta_template" class="form-control form-control-sm" placeholder="aviso_aula">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Idioma do template</label>
                    <input type="text" wire:model="meta_template_lang" class="form-control form-control-sm" placeholder="pt_BR">
                </div>
            </div>
            <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>O token fica criptografado no banco. Deixe o campo de token em branco para manter o atual.</div>
        </div>
    </div>

    {{-- Configuração Twilio --}}
    <div class="card border-0 shadow-sm mb-3 {{ $provider === 'twilio' ? '' : 'opacity-75' }}">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-telephone-outbound me-1 text-danger"></i>Credenciais — Twilio
            @if($provider === 'twilio')<span class="badge bg-success ms-1">Ativo</span>@endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Account SID</label>
                    <input type="text" wire:model="twilio_sid" class="form-control form-control-sm" placeholder="ACxxxxxxxx...">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Auth Token</label>
                    <input type="password" wire:model="twilio_token_novo" class="form-control form-control-sm"
                           placeholder="{{ $twilioTokenSalvo ? '•••••• (salvo)' : 'colar auth token' }}">
                    @if($twilioTokenSalvo)<div class="small text-success mt-1"><i class="bi bi-check-circle"></i> Token salvo</div>@endif
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Número remetente (From)</label>
                    <input type="text" wire:model="twilio_from" class="form-control form-control-sm" placeholder="+14155238886 (sandbox)">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium mb-1">Content SID (template de produção)</label>
                    <input type="text" wire:model="twilio_content_sid" class="form-control form-control-sm" placeholder="HXxxxxxxxx... (opcional)">
                </div>
            </div>
            <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>No Sandbox, o destinatário precisa enviar <code>join &lt;código&gt;</code> para o número do Twilio antes de receber. O Content SID só é necessário no modo template (produção).</div>
        </div>
    </div>

    {{-- Salvar + Teste --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button wire:click="salvar" class="btn btn-success">
                    <i class="bi bi-save me-1"></i>Salvar configuração
                </button>
                <span class="text-muted small">Provedor ativo: <strong>{{ $provider === 'twilio' ? 'Twilio' : 'Meta Cloud API' }}</strong> · Modo: <strong>{{ $mode === 'template' ? 'Template' : 'Texto livre' }}</strong></span>
            </div>

            <hr>

            <label class="form-label fw-semibold mb-1"><i class="bi bi-send-check me-1"></i>Enviar teste</label>
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" wire:model="telefoneTeste" class="form-control form-control-sm" placeholder="Telefone com DDD, ex: (65) 99999-1234">
                </div>
                <div class="col-md-3">
                    <button wire:click="testar" wire:loading.attr="disabled" class="btn btn-outline-primary btn-sm w-100">
                        <span wire:loading wire:target="testar" class="spinner-border spinner-border-sm me-1"></span>
                        Enviar teste
                    </button>
                </div>
            </div>

            @if(!empty($resultadoTeste))
            <div class="mt-3">
                @if($resultadoTeste['ok'])
                <div class="alert alert-success py-2 mb-0">
                    <i class="bi bi-check-circle me-1"></i>Mensagem aceita pelo provedor. ID: <code>{{ $resultadoTeste['id'] ?? '—' }}</code>
                    <div class="small mt-1">Se não chegar no celular, verifique a janela de 24h (texto livre), o cadastro no Sandbox (Twilio) ou a verificação da empresa (Meta).</div>
                </div>
                @else
                <div class="alert alert-danger py-2 mb-0">
                    <i class="bi bi-x-circle me-1"></i>Falhou: {{ $resultadoTeste['erro'] ?? 'erro desconhecido' }}
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    <x-help-modal titulo="Ajuda — Provedor de WhatsApp">
    <p class="text-muted mb-3">Escolha e configure por qual serviço o sistema envia as mensagens de WhatsApp.</p>
    <h6 class="fw-bold mb-2" style="font-size:13px">Meta Cloud API</h6>
    <p class="small mb-3">Serviço oficial da Meta. É o mais barato, mas exige a <strong>verificação da empresa (CNPJ)</strong> para entregar no Brasil. Use os campos Phone Number ID e Token obtidos no painel de desenvolvedores da Meta.</p>
    <h6 class="fw-bold mb-2" style="font-size:13px">Twilio</h6>
    <p class="small mb-3">Parceiro oficial (BSP). Tem um <strong>Sandbox</strong> que permite testar a entrega real no Brasil sem CNPJ — o destinatário só precisa enviar <code>join &lt;código&gt;</code> uma vez para o número do Twilio. Use Account SID, Auth Token e o número From do Sandbox (ex.: +14155238886).</p>
    <h6 class="fw-bold mb-2" style="font-size:13px">Modo de envio</h6>
    <ul class="list-unstyled small mb-3">
        <li class="mb-1"><i class="bi bi-arrow-right-short text-primary"></i><strong>Texto livre:</strong> para testes e dentro da janela de 24h.</li>
        <li class="mb-1"><i class="bi bi-arrow-right-short text-primary"></i><strong>Template:</strong> para produção (mensagens proativas). Na Meta usa o nome do template; no Twilio usa o Content SID.</li>
    </ul>
    <div class="alert alert-info py-2 small mb-0"><i class="bi bi-info-circle me-1"></i>Os tokens ficam criptografados no banco. O botão "Enviar teste" usa o provedor ativo e o modo texto livre.</div>
    </x-help-modal>

</div>
