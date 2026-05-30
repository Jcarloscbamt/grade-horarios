{{-- resources/views/layouts/navigation.blade.php --}}
<style>
/* ═══════════════════════════════════════════════════
   TOPBAR FIXO
═══════════════════════════════════════════════════ */
.topbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: #1a1a1a;
    border-bottom: 3px solid #E30613;
    z-index: 1040;
    display: flex;
    align-items: center;
    padding: 0 16px;
    gap: 12px;
}
.topbar-brand {
    display: flex; align-items: center; gap: 8px;
    text-decoration: none; color: white;
    font-weight: 900; font-size: 16px;
    white-space: nowrap;
}
.topbar-brand img { height: 32px; filter: brightness(0) invert(1); }
.topbar-brand span { color: #E30613; }

.topbar-toggle {
    background: none; border: none; color: #aaa;
    font-size: 20px; cursor: pointer; padding: 4px 8px;
    border-radius: 4px; transition: all .2s;
    flex-shrink: 0;
}
.topbar-toggle:hover { background: #333; color: white; }

.topbar-right {
    margin-left: auto;
    display: flex; align-items: center; gap: 8px;
}
.topbar-user {
    display: flex; align-items: center; gap: 8px;
    color: white; text-decoration: none; font-size: 14px;
    padding: 4px 8px; border-radius: 6px;
    position: relative;
}
.topbar-user:hover { background: #333; color: white; }
.topbar-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: #E30613; color: white;
    display: flex; align-items: center; justify-content: center;
    font-weight: bold; font-size: 14px; flex-shrink: 0;
}
.topbar-dropdown {
    position: absolute; top: calc(100% + 8px); right: 0;
    background: #2a2a2a; border-radius: 8px; min-width: 180px;
    box-shadow: 0 4px 20px rgba(0,0,0,.4);
    border-top: 2px solid #E30613;
    display: none; z-index: 1050;
}
.topbar-dropdown.show { display: block; }
.topbar-dropdown a, .topbar-dropdown button {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 16px; color: #ccc; text-decoration: none;
    font-size: 13px; width: 100%; background: none; border: none;
    cursor: pointer; transition: background .15s;
}
.topbar-dropdown a:hover, .topbar-dropdown button:hover { background: #3a3a3a; color: white; }
.topbar-dropdown hr { border-color: #3a3a3a; margin: 4px 0; }

/* ═══════════════════════════════════════════════════
   SIDEBAR LATERAL
═══════════════════════════════════════════════════ */
.sidebar {
    position: fixed;
    top: 56px; left: 0; bottom: 0;
    width: 240px;
    background: #1f1f1f;
    border-right: 1px solid #2a2a2a;
    z-index: 1030;
    overflow-y: auto;
    overflow-x: hidden;
    transition: width .25s ease, transform .25s ease;
    scrollbar-width: thin;
    scrollbar-color: #333 transparent;
}
.sidebar.collapsed { width: 60px; }

/* Mobile: sidebar esconde e desliza */
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); width: 240px; }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar-overlay { display: block !important; }
}

.sidebar-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 1029;
}

.sidebar-section {
    padding: 8px 0;
    border-bottom: 1px solid #2a2a2a;
}
.sidebar-label {
    font-size: 10px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: #555;
    padding: 8px 16px 4px;
    white-space: nowrap; overflow: hidden;
    transition: opacity .2s;
}
.sidebar.collapsed .sidebar-label { opacity: 0; }

.sidebar-link {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 16px;
    color: #aaa; text-decoration: none; font-size: 13px;
    transition: background .15s, color .15s;
    white-space: nowrap; overflow: hidden;
    position: relative;
}
.sidebar-link i { font-size: 16px; flex-shrink: 0; min-width: 20px; text-align: center; }
.sidebar-link span { transition: opacity .2s; }
.sidebar.collapsed .sidebar-link span { opacity: 0; width: 0; overflow: hidden; }
.sidebar-link:hover { background: #2a2a2a; color: white; }
.sidebar-link.active { background: #E30613 !important; color: white !important; }
.sidebar-link.active i { color: white !important; }

/* Tooltip quando collapsed */
.sidebar.collapsed .sidebar-link::after {
    content: attr(data-tooltip);
    position: absolute; left: 64px;
    background: #333; color: white;
    padding: 4px 10px; border-radius: 4px;
    font-size: 12px; white-space: nowrap;
    pointer-events: none; opacity: 0;
    transition: opacity .15s;
}
.sidebar.collapsed .sidebar-link:hover::after { opacity: 1; }

/* Submenu */
.sidebar-submenu { padding-left: 0; }
.sidebar-submenu .sidebar-link {
    padding-left: 46px;
    font-size: 12px;
}
.sidebar.collapsed .sidebar-submenu { display: none; }
.sidebar-group-toggle {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 16px;
    color: #aaa; font-size: 13px;
    cursor: pointer; white-space: nowrap; overflow: hidden;
    transition: background .15s, color .15s;
    user-select: none;
}
.sidebar-group-toggle:hover { background: #2a2a2a; color: white; }
.sidebar-group-toggle i.icon { font-size: 16px; flex-shrink: 0; min-width: 20px; text-align: center; }
.sidebar-group-toggle .chevron { margin-left: auto; transition: transform .2s; font-size: 12px; }
.sidebar-group-toggle.open .chevron { transform: rotate(180deg); }
.sidebar-group-toggle span { transition: opacity .2s; }
.sidebar.collapsed .sidebar-group-toggle span,
.sidebar.collapsed .sidebar-group-toggle .chevron { opacity: 0; width: 0; }

/* ═══════════════════════════════════════════════════
   MAIN CONTENT OFFSET
═══════════════════════════════════════════════════ */
.main-wrapper {
    margin-top: 56px;
    margin-left: 240px;
    min-height: calc(100vh - 56px);
    transition: margin-left .25s ease;
    padding: 24px;
}
/* Controla via classe no body — mais confiável */
body.sidebar-collapsed .main-wrapper { margin-left: 60px; }
@media (max-width: 768px) {
    .main-wrapper { margin-left: 0 !important; padding: 16px; }
    body.sidebar-collapsed .main-wrapper { margin-left: 0 !important; }
}
</style>

{{-- ═══ TOPBAR ═══ --}}
<div class="topbar">
    <button class="topbar-toggle" id="sidebarToggle" title="Recolher menu">
        <i class="bi bi-chevron-double-left" id="sidebarToggleIcon"></i>
    </button>
    <a class="topbar-brand" href="{{ route('grade') }}">
        <img src="https://unisenaimt.com.br/img/logo-unisenai.png" alt="UniSENAI"
             onerror="this.style.display='none'">
        Uni<span>SENAI</span>
    </a>

    <div class="topbar-right">
        <div class="topbar-user" id="userMenuToggle">
            <div class="topbar-avatar">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
            @if(Auth::user()->hasRole('admin'))
                <span class="badge" style="background:#E30613;font-size:10px">Admin</span>
            @elseif(Auth::user()->hasRole('coordenador'))
                <span class="badge bg-secondary" style="font-size:10px">Coord.</span>
            @elseif(Auth::user()->hasRole('consulta'))
                <span class="badge bg-secondary" style="font-size:10px">Consulta</span>
            @endif
            <i class="bi bi-chevron-down" style="font-size:11px;color:#aaa"></i>

            <div class="topbar-dropdown" id="userDropdown">
                <a href="{{ route('profile.edit') }}">
                    <i class="bi bi-person" style="color:#E30613"></i>Meu Perfil
                </a>
                <hr>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">
                        <i class="bi bi-box-arrow-right" style="color:#E30613"></i>Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══ OVERLAY MOBILE ═══ --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

{{-- ═══ SIDEBAR ═══ --}}
<nav class="sidebar" id="sidebar">

    {{-- Principal --}}
    <div class="sidebar-section">
        <div class="sidebar-label">Principal</div>
        <a href="{{ route('dashboard') }}" data-tooltip="Dashboard"
           class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-house-door"></i><span>Dashboard</span>
        </a>
        <a href="{{ route('grade') }}" data-tooltip="Grade de Horários"
           class="sidebar-link {{ request()->routeIs('grade') ? 'active' : '' }}">
            <i class="bi bi-grid-3x3-gap"></i><span>Grade de Horários</span>
        </a>
        <a href="{{ route('gerador-grade') }}" data-tooltip="Gerador de Grade"
           class="sidebar-link {{ request()->routeIs('gerador-grade') ? 'active' : '' }}">
            <i class="bi bi-magic"></i><span>Gerador de Grade</span>
        </a>
    </div>

    {{-- Cadastros --}}
    <div class="sidebar-section">
        <div class="sidebar-label">Cadastros</div>
        <div class="sidebar-group-toggle {{ request()->routeIs('cursos','turmas','disciplinas','professores','salas','aulas') ? 'open' : '' }}"
             onclick="toggleGroup(this)">
            <i class="bi bi-collection icon"></i>
            <span>Cadastros</span>
            <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="sidebar-submenu" style="{{ request()->routeIs('cursos','turmas','disciplinas','professores','salas','aulas') ? '' : 'display:none' }}">
            <a href="{{ route('cursos') }}" class="sidebar-link {{ request()->routeIs('cursos') ? 'active' : '' }}">
                <i class="bi bi-mortarboard" style="color:#E30613"></i><span>Cursos</span>
            </a>
            <a href="{{ route('turmas') }}" class="sidebar-link {{ request()->routeIs('turmas') ? 'active' : '' }}">
                <i class="bi bi-people" style="color:#E30613"></i><span>Turmas</span>
            </a>
            <a href="{{ route('disciplinas') }}" class="sidebar-link {{ request()->routeIs('disciplinas') ? 'active' : '' }}">
                <i class="bi bi-book" style="color:#E30613"></i><span>Disciplinas</span>
            </a>
            <a href="{{ route('professores') }}" class="sidebar-link {{ request()->routeIs('professores') ? 'active' : '' }}">
                <i class="bi bi-person-badge" style="color:#E30613"></i><span>Professores</span>
            </a>
            <a href="{{ route('salas') }}" class="sidebar-link {{ request()->routeIs('salas') ? 'active' : '' }}">
                <i class="bi bi-door-open" style="color:#E30613"></i><span>Salas</span>
            </a>
            <a href="{{ route('aulas') }}" class="sidebar-link {{ request()->routeIs('aulas') ? 'active' : '' }}">
                <i class="bi bi-calendar-week" style="color:#E30613"></i><span>Aulas</span>
            </a>
        </div>
    </div>

    {{-- Relatórios --}}
    <div class="sidebar-section">
        <div class="sidebar-label">Relatórios</div>
        <div class="sidebar-group-toggle {{ request()->routeIs('relatorio.*') ? 'open' : '' }}"
             onclick="toggleGroup(this)">
            <i class="bi bi-bar-chart icon"></i>
            <span>Relatórios</span>
            <i class="bi bi-chevron-down chevron"></i>
        </div>
        <div class="sidebar-submenu" style="{{ request()->routeIs('relatorio.*') ? '' : 'display:none' }}">
            <a href="{{ route('relatorio.grade') }}" class="sidebar-link {{ request()->routeIs('relatorio.grade') ? 'active' : '' }}">
                <i class="bi bi-grid-3x3-gap" style="color:#E30613"></i><span>Grade</span>
            </a>
            <a href="{{ route('relatorio.professores') }}" class="sidebar-link {{ request()->routeIs('relatorio.professores') ? 'active' : '' }}">
                <i class="bi bi-person-lines-fill" style="color:#E30613"></i><span>Professores</span>
            </a>
        </div>
    </div>

    {{-- Configurações --}}
    <div class="sidebar-section">
        <div class="sidebar-label">Configurações</div>
        <a href="{{ route('horarios') }}" data-tooltip="Horários"
           class="sidebar-link {{ request()->routeIs('horarios') ? 'active' : '' }}">
            <i class="bi bi-clock"></i><span>Horários</span>
        </a>
        <a href="{{ route('periodos') }}" data-tooltip="Períodos Letivos"
           class="sidebar-link {{ request()->routeIs('periodos') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i><span>Períodos Letivos</span>
        </a>
        @hasrole('admin')
        <a href="{{ route('logs') }}" data-tooltip="Logs"
           class="sidebar-link {{ request()->routeIs('logs') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i><span>Log de Alterações</span>
        </a>
        @endhasrole
    </div>

    {{-- Admin --}}
    @hasrole('admin')
    <div class="sidebar-section">
        <div class="sidebar-label">Administração</div>
        <a href="{{ route('usuarios') }}" data-tooltip="Usuários"
           class="sidebar-link {{ request()->routeIs('usuarios') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i><span>Usuários</span>
        </a>
    </div>
    @endhasrole

</nav>

<script>
// ── Sidebar toggle ──────────────────────────────
var sidebar  = document.getElementById('sidebar');
var isMobile = window.innerWidth <= 768;

// Restaura estado salvo via classe no body
var savedState = localStorage.getItem('sidebar_collapsed');
if (!isMobile && savedState === 'true') {
    sidebar.classList.add('collapsed');
    document.body.classList.add('sidebar-collapsed');
}

function updateToggleIcon() {
    var icon = document.getElementById('sidebarToggleIcon');
    if (!icon) return;
    var collapsed = sidebar.classList.contains('collapsed');
    icon.className = collapsed ? 'bi bi-chevron-double-right' : 'bi bi-chevron-double-left';
    document.getElementById('sidebarToggle').title = collapsed ? 'Expandir menu' : 'Recolher menu';
}

// Atualiza ícone no carregamento
updateToggleIcon();

document.getElementById('sidebarToggle').addEventListener('click', function() {
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
        document.getElementById('sidebarOverlay').style.display =
            sidebar.classList.contains('mobile-open') ? 'block' : 'none';
    } else {
        sidebar.classList.toggle('collapsed');
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
        updateToggleIcon();
    }
});

function closeSidebar() {
    sidebar.classList.remove('mobile-open');
    document.getElementById('sidebarOverlay').style.display = 'none';
}

// ── Submenu groups ──────────────────────────────
function toggleGroup(el) {
    el.classList.toggle('open');
    var sub = el.nextElementSibling;
    if (sub) {
        sub.style.display = sub.style.display === 'none' ? 'block' : 'none';
    }
}

// ── User dropdown ───────────────────────────────
document.getElementById('userMenuToggle').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('userDropdown').classList.toggle('show');
});
document.addEventListener('click', function() {
    document.getElementById('userDropdown').classList.remove('show');
});
</script>
