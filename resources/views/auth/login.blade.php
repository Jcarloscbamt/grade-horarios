<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — UniSENAI MT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: 'Arial', Helvetica, sans-serif;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
        }

        /* ── Lado esquerdo — identidade UniSENAI ── */
        .login-left {
            width: 45%;
            background: #1a1a1a;
            border-right: 5px solid #E30613;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
        }

        /* Detalhe decorativo de fundo */
        .login-left::before {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(227, 6, 19, 0.06);
        }
        .login-left::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(227, 6, 19, 0.04);
        }

        .login-left .logo-wrap {
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .login-left .logo-wrap img {
            height: 56px;
            filter: brightness(0) invert(1);
        }

        .login-left .logo-fallback {
            font-size: 28px;
            font-weight: 900;
            color: white;
        }
        .login-left .logo-fallback span {
            color: #E30613;
        }

        .login-left .sistema-titulo {
            font-size: 26px;
            font-weight: 900;
            color: white;
            text-align: center;
            letter-spacing: 1px;
            line-height: 1.2;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .login-left .sistema-titulo span {
            color: #E30613;
        }

        .login-left .sistema-desc {
            font-size: 14px;
            color: rgba(255,255,255,0.55);
            text-align: center;
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }

        .login-left .divisor {
            width: 48px;
            height: 3px;
            background: #E30613;
            margin: 24px auto;
            border-radius: 2px;
            position: relative;
            z-index: 1;
        }

        .login-left .badges-wrap {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 32px;
            position: relative;
            z-index: 1;
        }

        .login-left .badge-info {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.65);
            font-size: 11px;
            padding: 5px 12px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Lado direito — formulário ── */
        .login-right {
            flex: 1;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
        }

        .login-form-wrap {
            width: 100%;
            max-width: 400px;
        }

        .login-form-wrap .form-title {
            font-size: 22px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .login-form-wrap .form-subtitle {
            font-size: 13px;
            color: #888;
            margin-bottom: 32px;
        }

        .login-form-wrap .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #444;
            margin-bottom: 6px;
        }

        .login-form-wrap .form-control {
            border: 1.5px solid #ddd;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            background: white;
            transition: border-color 0.2s;
        }

        .login-form-wrap .form-control:focus {
            border-color: #E30613;
            box-shadow: 0 0 0 3px rgba(227,6,19,0.1);
        }

        .login-form-wrap .form-control.is-invalid {
            border-color: #dc3545;
        }

        .btn-entrar {
            background: #E30613;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            letter-spacing: 0.5px;
            transition: background 0.2s, transform 0.1s;
            cursor: pointer;
        }

        .btn-entrar:hover {
            background: #c0050f;
        }

        .btn-entrar:active {
            transform: scale(0.99);
        }

        .forgot-link {
            color: #888;
            font-size: 12px;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #E30613;
        }

        .remember-label {
            font-size: 13px;
            color: #555;
            cursor: pointer;
            user-select: none;
        }

        .form-check-input:checked {
            background-color: #E30613;
            border-color: #E30613;
        }

        /* ── Rodapé --  */
        .login-footer {
            text-align: center;
            margin-top: 32px;
            font-size: 11px;
            color: #bbb;
        }

        /* ── Responsivo — telas pequenas ── */
        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
            .login-right {
                background: white;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">

    {{-- ── LADO ESQUERDO ── --}}
    <div class="login-left">

        <div class="logo-wrap">
            <img src="{{ asset('images/logo-unisenai.png') }}"
                 alt="UniSENAI MT"
                 onerror="this.style.display='none'; document.getElementById('logo-text').style.display='block'">
            <div id="logo-text" style="display:none" class="logo-fallback">Uni<span>SENAI</span></div>
        </div>

        <div class="sistema-titulo">
            Grade de<br><span>Horários</span>
        </div>

        <div class="divisor"></div>

        <div class="sistema-desc">
            Sistema de gestão e visualização<br>
            de grades de horários acadêmicos<br>
            <strong style="color:rgba(255,255,255,0.75)">UniSENAI Mato Grosso</strong>
        </div>

        <div class="badges-wrap">
            <div class="badge-info">
                <i class="bi bi-mortarboard-fill" style="color:#E30613"></i>
                Cursos
            </div>
            <div class="badge-info">
                <i class="bi bi-people-fill" style="color:#E30613"></i>
                Turmas
            </div>
            <div class="badge-info">
                <i class="bi bi-grid-3x3-gap-fill" style="color:#E30613"></i>
                Grades
            </div>
            <div class="badge-info">
                <i class="bi bi-printer-fill" style="color:#E30613"></i>
                Impressão
            </div>
        </div>

    </div>

    {{-- ── LADO DIREITO — FORMULÁRIO ── --}}
    <div class="login-right">
        <div class="login-form-wrap">

            <div class="form-title">Bem-vindo!</div>
            <div class="form-subtitle">Faça login para acessar o sistema</div>

            {{-- Erros de sessão --}}
            @if (session('status'))
                <div class="alert alert-success mb-3" style="font-size:13px">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- E-mail --}}
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope me-1" style="color:#E30613"></i>E-mail
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="seu@email.com.br"
                           required
                           autofocus
                           autocomplete="username">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Senha --}}
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock me-1" style="color:#E30613"></i>Senha
                    </label>
                    <div class="mt-1 position-relative">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="••••••••"
                               required
                               autocomplete="current-password">
                        <button type="button"
                                onclick="toggleSenha()"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;padding:0">
                            <i id="ico-senha" class="bi bi-eye"></i>
                        </button>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Lembrar --}}
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="remember-label" for="remember">Lembrar de mim</label>
                    </div>
                </div>

                {{-- Botão entrar --}}
                <button type="submit" class="btn-entrar">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </button>

            </form>

            <div class="login-footer">
                &copy; {{ date('Y') }} UniSENAI MT — Todos os direitos reservados
            </div>

        </div>
    </div>

</div>

<script>
    function toggleSenha() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('ico-senha');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
</script>

</body>
</html>
