<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha — UniSENAI MT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; font-family: Arial, Helvetica, sans-serif; }
        .login-wrapper { min-height: 100vh; display: flex; }
        .login-left {
            width: 45%; background: #1a1a1a; border-right: 5px solid #E30613;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; padding: 48px 40px; position: relative; overflow: hidden;
        }
        .login-left::before { content: ''; position: absolute; bottom: -80px; left: -80px; width: 320px; height: 320px; border-radius: 50%; background: rgba(227,6,19,0.06); }
        .login-left::after { content: ''; position: absolute; top: -60px; right: -60px; width: 240px; height: 240px; border-radius: 50%; background: rgba(227,6,19,0.04); }
        .logo-wrap { margin-bottom: 32px; text-align: center; position: relative; z-index: 1; }
        .logo-wrap img { height: 52px; filter: brightness(0) invert(1); }
        .sistema-titulo { font-size: 24px; font-weight: 900; color: white; text-align: center; letter-spacing: 1px; line-height: 1.2; margin-bottom: 12px; position: relative; z-index: 1; }
        .sistema-titulo span { color: #E30613; }
        .divisor { width: 48px; height: 3px; background: #E30613; margin: 20px auto; border-radius: 2px; position: relative; z-index: 1; }
        .sistema-desc { font-size: 13px; color: rgba(255,255,255,0.5); text-align: center; line-height: 1.7; position: relative; z-index: 1; }
        .login-right { flex: 1; background: #f5f5f5; display: flex; align-items: center; justify-content: center; padding: 40px 32px; }
        .form-wrap { width: 100%; max-width: 400px; }
        .form-wrap .form-control { border: 1.5px solid #ddd; border-radius: 8px; padding: 10px 14px; font-size: 14px; }
        .form-wrap .form-control:focus { border-color: #E30613; box-shadow: 0 0 0 3px rgba(227,6,19,0.1); }
        .btn-primary-uni { background: #E30613; color: white; border: none; border-radius: 8px; padding: 11px; font-size: 15px; font-weight: 700; width: 100%; }
        .btn-primary-uni:hover { background: #c0050f; color: white; }
        .back-link { color: #888; font-size: 13px; text-decoration: none; display: flex; align-items: center; gap: 6px; margin-top: 20px; }
        .back-link:hover { color: #E30613; }
        @media (max-width: 768px) { .login-left { display: none; } .login-right { background: white; } }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-left">
        <div class="logo-wrap">
            <img src="{{ asset('images/logo-unisenai.png') }}" alt="UniSENAI MT"
                 onerror="this.outerHTML='<div style=\'font-size:26px;font-weight:900;color:white\'>Uni<span style=\'color:#E30613\'>SENAI</span></div>'">
        </div>
        <div class="sistema-titulo">Grade de<br><span>Horários</span></div>
        <div class="divisor"></div>
        <div class="sistema-desc">Sistema de gestão e visualização<br>de grades de horários acadêmicos<br><strong style="color:rgba(255,255,255,0.75)">UniSENAI Mato Grosso</strong></div>
    </div>

    <div class="login-right">
        <div class="form-wrap">

            <div style="font-size:22px;font-weight:800;color:#1a1a1a;margin-bottom:4px">Nova Senha</div>
            <div style="font-size:13px;color:#888;margin-bottom:24px">
                Defina sua nova senha de acesso ao sistema.
            </div>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;color:#444">
                        <i class="bi bi-envelope me-1" style="color:#E30613"></i>E-mail
                    </label>
                    <input type="email" name="email" value="{{ old('email', $request->email) }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="seu@email.com.br" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;color:#444">
                        <i class="bi bi-lock me-1" style="color:#E30613"></i>Nova Senha
                    </label>
                    <div class="position-relative">
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Mínimo 8 caracteres" required>
                        <button type="button" onclick="toggle('password','ico1')"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888">
                            <i id="ico1" class="bi bi-eye"></i>
                        </button>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size:13px;color:#444">
                        <i class="bi bi-lock-fill me-1" style="color:#E30613"></i>Confirmar Nova Senha
                    </label>
                    <div class="position-relative">
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control @error('password_confirmation') is-invalid @enderror"
                               placeholder="Repita a nova senha" required>
                        <button type="button" onclick="toggle('password_confirmation','ico2')"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888">
                            <i id="ico2" class="bi bi-eye"></i>
                        </button>
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-uni">
                    <i class="bi bi-check-lg me-2"></i>Redefinir Senha
                </button>
            </form>

            <a href="{{ route('login') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Voltar para o login
            </a>

            <div style="text-align:center;margin-top:32px;font-size:11px;color:#bbb">
                &copy; {{ date('Y') }} UniSENAI MT — Todos os direitos reservados
            </div>

        </div>
    </div>
</div>
<script>
    function toggle(id, ico) {
        const i = document.getElementById(id);
        const ic = document.getElementById(ico);
        i.type = i.type === 'password' ? 'text' : 'password';
        ic.className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
</script>
</body>
</html>
