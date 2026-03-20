{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
    <div class="py-4 px-4">

        {{-- Boas vindas --}}
        <div class="mb-4 d-flex align-items-center gap-3">
            <div>
                <h2 class="fw-bold mb-0">Olá, {{ Auth::user()->name }}! 👋</h2>
                <small class="text-muted">Bem-vindo ao sistema de Grade de Horários — UniSENAI MT</small>
            </div>
        </div>

        {{-- Cards de resumo --}}
        <div class="row g-3 mb-4">

            <div class="col-md-3 col-sm-6">
                <a href="{{ route('cursos') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #E30613 !important">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;background:#fde8e8">
                                <i class="bi bi-mortarboard-fill fs-4" style="color:#E30613"></i>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold text-dark">{{ \App\Models\Curso::count() }}</div>
                                <div class="text-muted" style="font-size:13px">Cursos</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="{{ route('turmas') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #1a1a1a !important">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;background:#ebebeb">
                                <i class="bi bi-people-fill fs-4" style="color:#1a1a1a"></i>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold text-dark">{{ \App\Models\Turma::count() }}</div>
                                <div class="text-muted" style="font-size:13px">Turmas</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="{{ route('professores') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #E30613 !important">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;background:#fde8e8">
                                <i class="bi bi-person-badge-fill fs-4" style="color:#E30613"></i>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold text-dark">{{ \App\Models\Professor::count() }}</div>
                                <div class="text-muted" style="font-size:13px">Professores</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="{{ route('disciplinas') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #1a1a1a !important">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;background:#ebebeb">
                                <i class="bi bi-book-fill fs-4" style="color:#1a1a1a"></i>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold text-dark">{{ \App\Models\Disciplina::count() }}</div>
                                <div class="text-muted" style="font-size:13px">Disciplinas</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="{{ route('salas') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #E30613 !important">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;background:#fde8e8">
                                <i class="bi bi-door-open-fill fs-4" style="color:#E30613"></i>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold text-dark">{{ \App\Models\Sala::count() }}</div>
                                <div class="text-muted" style="font-size:13px">Salas</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="{{ route('aulas') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #1a1a1a !important">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;background:#ebebeb">
                                <i class="bi bi-calendar-week-fill fs-4" style="color:#1a1a1a"></i>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold text-dark">{{ \App\Models\Aula::count() }}</div>
                                <div class="text-muted" style="font-size:13px">Aulas cadastradas</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="{{ route('horarios') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #E30613 !important">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;background:#fde8e8">
                                <i class="bi bi-clock-fill fs-4" style="color:#E30613"></i>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold text-dark">{{ \App\Models\Horario::count() }}</div>
                                <div class="text-muted" style="font-size:13px">Horários</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="{{ route('periodos') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #1a1a1a !important">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;background:#ebebeb">
                                <i class="bi bi-calendar3 fs-4" style="color:#1a1a1a"></i>
                            </div>
                            <div>
                                <div class="fs-2 fw-bold text-dark">{{ \App\Models\PeriodoLetivo::count() }}</div>
                                <div class="text-muted" style="font-size:13px">Períodos Letivos</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

        </div>

        {{-- Período letivo ativo + atalho para grade --}}
        <div class="row g-3">

            {{-- Período ativo --}}
            <div class="col-md-6">
                @php $periodoAtivo = \App\Models\PeriodoLetivo::where('ativo', true)->first(); @endphp
                @if($periodoAtivo)
                <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #E30613 !important">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-calendar-check-fill fs-5" style="color:#E30613"></i>
                            <h6 class="fw-bold mb-0">Período Letivo Ativo</h6>
                            <span class="badge ms-auto" style="background:#E30613">{{ $periodoAtivo->nome }}</span>
                        </div>
                        <div class="row g-2">
                            @if($periodoAtivo->avaliacao1_inicio)
                            <div class="col-6">
                                <div class="p-2 rounded" style="background:#f8f8f8">
                                    <small class="text-muted d-block">Avaliação 1</small>
                                    <span class="fw-semibold" style="font-size:13px">
                                        {{ $periodoAtivo->avaliacao1_inicio->format('d/m') }}
                                        a
                                        {{ $periodoAtivo->avaliacao1_fim?->format('d/m/Y') }}
                                    </span>
                                </div>
                            </div>
                            @endif
                            @if($periodoAtivo->avaliacao2_inicio)
                            <div class="col-6">
                                <div class="p-2 rounded" style="background:#f8f8f8">
                                    <small class="text-muted d-block">Avaliação 2</small>
                                    <span class="fw-semibold" style="font-size:13px">
                                        {{ $periodoAtivo->avaliacao2_inicio->format('d/m') }}
                                        a
                                        {{ $periodoAtivo->avaliacao2_fim?->format('d/m/Y') }}
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @else
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-4 text-muted">
                        <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                        Nenhum período letivo ativo.
                        <div class="mt-2">
                            <a href="{{ route('periodos') }}" class="btn btn-sm" style="background:#E30613;color:white">
                                Cadastrar Período
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Atalho rápido para a grade --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100" style="background:#1a1a1a; border-top:4px solid #E30613 !important">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-4">
                        <img src="https://unisenaimt.com.br/img/logo-unisenai.png"
                             alt="UniSENAI MT"
                             style="height:40px; filter:brightness(0) invert(1); margin-bottom:16px; opacity:0.9"
                             onerror="this.style.display='none'">
                        <h5 class="fw-bold text-white mb-1">Grade de Horários</h5>
                        <p class="text-white mb-3" style="opacity:0.6; font-size:13px">
                            Visualize e imprima a grade completa por turma
                        </p>
                        <a href="{{ route('grade') }}" class="btn fw-semibold px-4"
                           style="background:#E30613; color:white; border:none">
                            <i class="bi bi-eye me-2"></i>Visualizar Grade
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
