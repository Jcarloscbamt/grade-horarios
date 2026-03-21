{{-- resources/views/livewire/trocar-senha.blade.php --}}
<div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        .input-senha { position: relative; }
        .input-senha input { padding-right: 44px; }
        .btn-toggle-senha {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #888; padding: 0; z-index: 5;
        }
        .btn-toggle-senha:hover { color: #E30613; }
        .form-control:focus { border-color: #E30613 !important; box-shadow: 0 0 0 3px rgba(227,6,19,0.1) !important; }
        .form-control.is-invalid:focus { box-shadow: 0 0 0 3px rgba(220,53,69,0.1) !important; }
        .btn-salvar {
            width: 100%; padding: 12px;
            background: #E30613; color: white;
            border: none; border-radius: 8px;
            font-size: 15px; font-weight: 700;
            cursor: pointer; margin-bottom: 16px;
            transition: background 0.2s;
        }
        .btn-salvar:hover { background: #c0050f; }
        .btn-salvar:disabled { opacity: 0.7; cursor: not-allowed; }
    </style>

    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f0f4f8;padding:20px">
        <div style="width:100%;max-width:440px;background:white;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.10);padding:2rem">

            {{-- Logo --}}
            <div class="text-center mb-4">
                @php $logoPath = public_path('images/logo-unisenai.png'); @endphp
                @if(file_exists($logoPath))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}"
                         alt="UniSENAI MT" style="height:48px;margin-bottom:16px;display:block;margin-left:auto;margin-right:auto">
                @else
                    <div style="font-size:22px;font-weight:900;color:#E30613;margin-bottom:16px">UniSENAI MT</div>
                @endif

                <div style="width:64px;height:64px;background:#fde8e8;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                    <i class="bi bi-shield-lock-fill" style="font-size:28px;color:#E30613"></i>
                </div>
                <h4 style="font-weight:700;margin-bottom:6px">Troque sua senha</h4>
                <p style="font-size:14px;color:#666;margin:0">
                    Por segurança, defina uma nova senha antes de continuar.
                </p>
            </div>

            {{-- Alertas --}}
            @if(session()->has('success'))
                <div class="alert alert-success py-2 mb-3">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </div>
            @endif
            @if(session()->has('error'))
                <div class="alert alert-danger py-2 mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2 mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li style="font-size:13px">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Nova Senha --}}
            <div class="mb-3">
                <label class="form-label fw-medium">
                    <i class="bi bi-lock me-1" style="color:#E30613"></i>
                    Nova Senha <span class="text-danger">*</span>
                </label>
                <div class="input-senha">
                    <input
                        type="password"
                        id="campo-senha"
                        wire:model="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Mínimo 8 caracteres"
                        autofocus
                    >
                    <button type="button" class="btn-toggle-senha" onclick="toggleSenha('campo-senha','ico-senha')">
                        <i id="ico-senha" class="bi bi-eye"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Confirmar Senha --}}
            <div class="mb-4">
                <label class="form-label fw-medium">
                    <i class="bi bi-lock-fill me-1" style="color:#E30613"></i>
                    Confirmar Senha <span class="text-danger">*</span>
                </label>
                <div class="input-senha">
                    <input
                        type="password"
                        id="campo-confirma"
                        wire:model="password_confirm"
                        class="form-control @error('password_confirm') is-invalid @enderror"
                        placeholder="Repita a nova senha"
                    >
                    <button type="button" class="btn-toggle-senha" onclick="toggleSenha('campo-confirma','ico-confirma')">
                        <i id="ico-confirma" class="bi bi-eye"></i>
                    </button>
                    @error('password_confirm')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Indicador em tempo real --}}
                @if(strlen($password_confirm) > 0)
                    @if($password === $password_confirm)
                        <div style="color:#198754;font-size:13px;margin-top:6px">
                            <i class="bi bi-check-circle me-1"></i>Senhas coincidem!
                        </div>
                    @else
                        <div style="color:#dc3545;font-size:13px;margin-top:6px">
                            <i class="bi bi-x-circle me-1"></i>As senhas não coincidem.
                        </div>
                    @endif
                @endif
            </div>

            {{-- Botão Salvar --}}
            <button
                wire:click="save"
                wire:loading.attr="disabled"
                class="btn-salvar"
            >
                <span wire:loading wire:target="save">
                    <span class="spinner-border spinner-border-sm me-2"></span>Salvando...
                </span>
                <span wire:loading.remove wire:target="save">
                    <i class="bi bi-check-lg me-2"></i>Salvar nova senha e continuar
                </span>
            </button>

            <div class="text-center">
                <small class="text-muted">
                    Logado como <strong>{{ auth()->user()->name }}</strong> —
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-tc').submit();"
                       style="color:#E30613;text-decoration:none">
                       Sair
                    </a>
                </small>
                <form id="logout-tc" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>

        </div>
    </div>

    <script>
        function toggleSenha(campoId, icoId) {
            const campo = document.getElementById(campoId);
            const ico   = document.getElementById(icoId);
            if (campo.type === 'password') {
                campo.type = 'text';
                ico.className = 'bi bi-eye-slash';
            } else {
                campo.type = 'password';
                ico.className = 'bi bi-eye';
            }
        }
    </script>
</div>
