<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha — UniSENAI MT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; font-family: Arial, Helvetica, sans-serif; background: #f0f4f8; }
        .form-control:focus { border-color: #E30613 !important; box-shadow: 0 0 0 3px rgba(227,6,19,0.1) !important; }
        .btn-salvar { background: #E30613; color: white; border: none; border-radius: 8px; padding: 12px; font-size: 15px; font-weight: 700; width: 100%; transition: background 0.2s; }
        .btn-salvar:hover { background: #c0050f; }
        .toggle-senha { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #888; padding: 0; }
        .toggle-senha:hover { color: #E30613; }
    </style>
</head>
<body>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px">
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
        @if(session('success'))
            <div class="alert alert-success py-2 mb-3">
                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
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

        {{-- Formulário HTML puro — sem Livewire --}}
        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf

            {{-- Nova Senha --}}
            <div class="mb-3">
                <label class="form-label fw-medium">
                    <i class="bi bi-lock me-1" style="color:#E30613"></i>
                    Nova Senha <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                    <input type="password"
                           id="campo-senha"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Mínimo 8 caracteres"
                           style="padding-right:44px"
                           autofocus>
                    <button type="button" class="toggle-senha" onclick="toggle('campo-senha','ico1')">
                        <i id="ico1" class="bi bi-eye"></i>
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
                <div class="position-relative">
                    <input type="password"
                           id="campo-confirma"
                           name="password_confirm"
                           class="form-control @error('password_confirm') is-invalid @enderror"
                           placeholder="Repita a nova senha"
                           style="padding-right:44px">
                    <button type="button" class="toggle-senha" onclick="toggle('campo-confirma','ico2')">
                        <i id="ico2" class="bi bi-eye"></i>
                    </button>
                    @error('password_confirm')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn-salvar">
                <i class="bi bi-check-lg me-2"></i>Salvar nova senha e continuar
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                Logado como <strong>{{ auth()->user()->name }}</strong> —
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   style="color:#E30613;text-decoration:none">Sair</a>
            </small>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>

    </div>
</div>

<script>
    function toggle(id, ico) {
        const c = document.getElementById(id);
        const i = document.getElementById(ico);
        c.type  = c.type === 'password' ? 'text' : 'password';
        i.className = c.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
</script>

</body>
</html>
