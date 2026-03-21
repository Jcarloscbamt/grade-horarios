<x-app-layout>
    <div class="py-4 px-4">

        {{-- Cabeçalho --}}
        <div class="mb-4">
            <h2 class="fw-bold mb-0">Meu Perfil</h2>
            <small class="text-muted">Gerencie suas informações de acesso</small>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">

                {{-- Card informações (somente leitura) --}}
                <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #E30613 !important">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                 style="width:52px;height:52px;font-size:22px;background:#E30613;flex-shrink:0">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:18px">{{ auth()->user()->name }}</div>
                                <div class="text-muted" style="font-size:13px">{{ auth()->user()->email }}</div>
                            </div>
                            <div class="ms-auto">
                                @if(auth()->user()->hasRole('admin'))
                                    <span class="badge" style="background:#E30613">Admin</span>
                                @elseif(auth()->user()->hasRole('coordenador'))
                                    <span class="badge bg-secondary">Coordenador</span>
                                @else
                                    <span class="badge bg-secondary">Consulta</span>
                                @endif
                            </div>
                        </div>
                        <div class="py-2 px-3 rounded" style="background:#f8f8f8;font-size:13px;color:#666">
                            <i class="bi bi-info-circle me-2" style="color:#E30613"></i>
                            Para alterar nome ou e-mail, solicite ao administrador do sistema.
                        </div>
                    </div>
                </div>

                {{-- Card alterar senha --}}
                <div class="card border-0 shadow-sm" style="border-left:4px solid #1a1a1a !important">
                    <div class="card-body">

                        <div class="d-flex align-items-center gap-2 mb-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:40px;height:40px;background:#fde8e8;flex-shrink:0">
                                <i class="bi bi-lock-fill" style="color:#E30613;font-size:18px"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Alterar Senha</div>
                                <div class="text-muted" style="font-size:12px">Use uma senha forte com no mínimo 8 caracteres</div>
                            </div>
                        </div>

                        {{-- Alertas --}}
                        @if(session('status') === 'password-updated')
                            <div class="alert alert-success py-2 mb-3">
                                <i class="bi bi-check-circle me-2"></i>Senha alterada com sucesso!
                            </div>
                        @endif

                        @if($errors->updatePassword->any())
                            <div class="alert alert-danger py-2 mb-3">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->updatePassword->all() as $error)
                                        <li style="font-size:13px">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('put')

                            {{-- Senha atual --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium" style="font-size:13px">
                                    <i class="bi bi-lock me-1" style="color:#E30613"></i>
                                    Senha Atual <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           id="current_password"
                                           name="current_password"
                                           class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif"
                                           placeholder="Digite sua senha atual"
                                           style="padding-right:44px;border:1.5px solid #ddd;border-radius:8px">
                                    <button type="button" onclick="toggleSenha('current_password','ico1')"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888">
                                        <i id="ico1" class="bi bi-eye"></i>
                                    </button>
                                    @if($errors->updatePassword->has('current_password'))
                                        <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Nova senha --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium" style="font-size:13px">
                                    <i class="bi bi-lock-fill me-1" style="color:#E30613"></i>
                                    Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           id="password"
                                           name="password"
                                           class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif"
                                           placeholder="Mínimo 8 caracteres"
                                           style="padding-right:44px;border:1.5px solid #ddd;border-radius:8px">
                                    <button type="button" onclick="toggleSenha('password','ico2')"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888">
                                        <i id="ico2" class="bi bi-eye"></i>
                                    </button>
                                    @if($errors->updatePassword->has('password'))
                                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Confirmar senha --}}
                            <div class="mb-4">
                                <label class="form-label fw-medium" style="font-size:13px">
                                    <i class="bi bi-shield-lock me-1" style="color:#E30613"></i>
                                    Confirmar Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           id="password_confirmation"
                                           name="password_confirmation"
                                           class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
                                           placeholder="Repita a nova senha"
                                           style="padding-right:44px;border:1.5px solid #ddd;border-radius:8px">
                                    <button type="button" onclick="toggleSenha('password_confirmation','ico3')"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888">
                                        <i id="ico3" class="bi bi-eye"></i>
                                    </button>
                                    @if($errors->updatePassword->has('password_confirmation'))
                                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                                    @endif
                                </div>
                            </div>

                            <button type="submit"
                                    style="background:#E30613;color:white;border:none;border-radius:8px;padding:11px 24px;font-size:15px;font-weight:700;cursor:pointer">
                                <i class="bi bi-check-lg me-2"></i>Salvar Nova Senha
                            </button>

                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script>
        function toggleSenha(id, ico) {
            const c = document.getElementById(id);
            const i = document.getElementById(ico);
            c.type      = c.type === 'password' ? 'text' : 'password';
            i.className = c.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        }
    </script>
</x-app-layout>
