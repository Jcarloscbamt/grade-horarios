{{-- resources/views/livewire/trocar-senha.blade.php --}}
<div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

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

                <div style="width:64px;height:64px;background:#fff3cd;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                    <i class="bi bi-shield-lock-fill text-warning" style="font-size:28px"></i>
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
                    Nova Senha <span class="text-danger">*</span>
                </label>
                <input
                    type="password"
                    wire:model="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Mínimo 8 caracteres"
                    autofocus
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Confirmar Senha --}}
            <div class="mb-3">
                <label class="form-label fw-medium">
                    Confirmar Senha <span class="text-danger">*</span>
                </label>
                <input
                    type="password"
                    wire:model="password_confirm"
                    class="form-control @error('password_confirm') is-invalid @enderror"
                    placeholder="Repita a nova senha"
                >
                @error('password_confirm')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if(strlen($password_confirm) > 0)
                    @if($password === $password_confirm)
                        <div style="color:#198754;font-size:13px;margin-top:4px">
                            <i class="bi bi-check-circle me-1"></i>Senhas coincidem!
                        </div>
                    @else
                        <div style="color:#dc3545;font-size:13px;margin-top:4px">
                            <i class="bi bi-x-circle me-1"></i>As senhas não coincidem.
                        </div>
                    @endif
                @endif
            </div>

            {{-- Botão --}}
            <button
                wire:click="save"
                wire:loading.attr="disabled"
                style="width:100%;padding:12px;background:#0d6efd;color:white;border:none;border-radius:6px;font-size:15px;font-weight:600;cursor:pointer;margin-bottom:16px"
            >
                <span wire:loading wire:target="save">Salvando...</span>
                <span wire:loading.remove wire:target="save">
                    ✓ Salvar nova senha e continuar
                </span>
            </button>

            <div class="text-center">
                <small class="text-muted">
                    Logado como <strong>{{ auth()->user()->name }}</strong> —
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-tc').submit();">
                       Sair
                    </a>
                </small>
                <form id="logout-tc" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>

        </div>
    </div>
</div>
