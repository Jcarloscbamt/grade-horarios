{{-- resources/views/layouts/navigation.blade.php --}}

{{-- Variáveis de cor UniSENAI --}}
@php
    $corPrimaria = '#E30613'; // Vermelho UniSENAI
    $corEscura   = '#1a1a1a'; // Quase preto para navbar
@endphp

<style>
    .navbar-unisenai {
        background-color: #1a1a1a !important;
        border-bottom: 3px solid #E30613 !important;
    }
    .navbar-unisenai .nav-link {
        color: #d0d0d0 !important;
        font-size: 14px;
        transition: color 0.2s;
    }
    .navbar-unisenai .nav-link:hover,
    .navbar-unisenai .nav-link.active {
        color: #ffffff !important;
    }
    .navbar-unisenai .nav-link.active {
        border-bottom: 2px solid #E30613;
    }
    .navbar-unisenai .dropdown-menu {
        background: #2a2a2a;
        border: none;
        border-top: 2px solid #E30613;
    }
    .navbar-unisenai .dropdown-item {
        color: #d0d0d0;
        font-size: 13px;
    }
    .navbar-unisenai .dropdown-item:hover {
        background: #3a3a3a;
        color: #ffffff;
    }
    .navbar-unisenai .dropdown-item.active {
        background: #E30613;
        color: #ffffff;
    }
    .navbar-unisenai .dropdown-divider {
        border-color: #3a3a3a;
    }
    .navbar-unisenai .navbar-toggler {
        border-color: #555;
    }
    .navbar-unisenai .navbar-toggler-icon {
        filter: invert(1);
    }
    .badge-admin-unisenai {
        background: #E30613 !important;
        color: white !important;
        font-size: 10px;
    }
    .badge-coord-unisenai {
        background: #555 !important;
        color: white !important;
        font-size: 10px;
    }
    .avatar-unisenai {
        background: #E30613 !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-unisenai px-3">

    {{-- Logo UniSENAI --}}
    <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('grade') }}">
        <img src="https://unisenaimt.com.br/img/logo-unisenai.png"
             alt="UniSENAI MT"
             style="height:36px; filter: brightness(0) invert(1);"
             onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='flex'">
        {{-- Fallback caso a imagem não carregue --}}
        <span id="logo-fallback" class="d-none align-items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                 fill="none" stroke="#E30613" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span class="fw-bold text-white">Grade<span style="color:#E30613">Horários</span></span>
        </span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-3">

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   href="{{ route('dashboard') }}">
                    <i class="bi bi-house me-1"></i>Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('grade') ? 'active' : '' }}"
                   href="{{ route('grade') }}">
                    <i class="bi bi-grid-3x3-gap me-1"></i>Grade de Horários
                </a>
            </li>

            {{-- Cadastros --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('cursos','turmas','disciplinas','professores','salas','aulas') ? 'active' : '' }}"
                   href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-collection me-1"></i>Cadastros
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ request()->routeIs('cursos') ? 'active' : '' }}" href="{{ route('cursos') }}"><i class="bi bi-mortarboard me-2" style="color:#E30613"></i>Cursos</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('turmas') ? 'active' : '' }}" href="{{ route('turmas') }}"><i class="bi bi-people me-2" style="color:#E30613"></i>Turmas</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('disciplinas') ? 'active' : '' }}" href="{{ route('disciplinas') }}"><i class="bi bi-book me-2" style="color:#E30613"></i>Disciplinas</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item {{ request()->routeIs('professores') ? 'active' : '' }}" href="{{ route('professores') }}"><i class="bi bi-person-badge me-2" style="color:#E30613"></i>Professores</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('salas') ? 'active' : '' }}" href="{{ route('salas') }}"><i class="bi bi-door-open me-2" style="color:#E30613"></i>Salas</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item {{ request()->routeIs('aulas') ? 'active' : '' }}" href="{{ route('aulas') }}"><i class="bi bi-calendar-week me-2" style="color:#E30613"></i>Aulas</a></li>
                </ul>
            </li>

            {{-- Configurações --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('horarios','periodos') ? 'active' : '' }}"
                   href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-gear me-1"></i>Configurações
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ request()->routeIs('horarios') ? 'active' : '' }}" href="{{ route('horarios') }}"><i class="bi bi-clock me-2" style="color:#E30613"></i>Horários</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('periodos') ? 'active' : '' }}" href="{{ route('periodos') }}"><i class="bi bi-calendar3 me-2" style="color:#E30613"></i>Períodos Letivos</a></li>
                </ul>
            </li>

            {{-- Usuários — somente Admin --}}
            @hasrole('admin')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('usuarios') ? 'active' : '' }}"
                   href="{{ route('usuarios') }}">
                    <i class="bi bi-people-fill me-1"></i>Usuários
                </a>
            </li>
            @endhasrole

        </ul>

        {{-- Usuário logado --}}
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                   href="#" role="button" data-bs-toggle="dropdown">
                    <span class="avatar-unisenai rounded-circle text-white d-flex align-items-center justify-content-center fw-bold"
                          style="width:32px;height:32px;font-size:14px;background:#E30613">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="text-white">{{ Auth::user()->name }}</span>
                    @if(Auth::user()->hasRole('admin'))
                        <span class="badge badge-admin-unisenai">Admin</span>
                    @elseif(Auth::user()->hasRole('coordenador'))
                        <span class="badge badge-coord-unisenai">Coord.</span>
                    @elseif(Auth::user()->hasRole('consulta'))
                        <span class="badge badge-coord-unisenai">Consulta</span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2" style="color:#E30613"></i>Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item" style="color:#E30613">
                                <i class="bi bi-box-arrow-right me-2"></i>Sair
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
