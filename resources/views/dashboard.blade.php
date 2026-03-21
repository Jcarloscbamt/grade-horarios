{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
    <div class="py-2 px-3">

        {{-- Boas vindas --}}
        <div class="mb-3 d-flex align-items-center gap-2">
            <div>
                <h4 class="fw-bold mb-0">Olá, {{ Auth::user()->name }}! 👋</h4>
                <small class="text-muted">Bem-vindo ao sistema de Grade de Horários — UniSENAI MT</small>
            </div>
        </div>

        {{-- Cards de resumo — 8 cards em 2 linhas de 4 --}}
        <div class="row g-2 mb-3">

            @php
            $cards = [
                ['route' => 'cursos',      'icon' => 'bi-mortarboard-fill',    'cor' => '#E30613', 'bg' => '#fde8e8', 'label' => 'Cursos',           'count' => \App\Models\Curso::count()],
                ['route' => 'turmas',      'icon' => 'bi-people-fill',         'cor' => '#1a1a1a', 'bg' => '#ebebeb', 'label' => 'Turmas',           'count' => \App\Models\Turma::count()],
                ['route' => 'professores', 'icon' => 'bi-person-badge-fill',   'cor' => '#E30613', 'bg' => '#fde8e8', 'label' => 'Professores',      'count' => \App\Models\Professor::count()],
                ['route' => 'disciplinas', 'icon' => 'bi-book-fill',           'cor' => '#1a1a1a', 'bg' => '#ebebeb', 'label' => 'Disciplinas',      'count' => \App\Models\Disciplina::count()],
                ['route' => 'salas',       'icon' => 'bi-door-open-fill',      'cor' => '#E30613', 'bg' => '#fde8e8', 'label' => 'Salas',            'count' => \App\Models\Sala::count()],
                ['route' => 'aulas',       'icon' => 'bi-calendar-week-fill',  'cor' => '#1a1a1a', 'bg' => '#ebebeb', 'label' => 'Aulas cadastradas','count' => \App\Models\Aula::count()],
                ['route' => 'horarios',    'icon' => 'bi-clock-fill',          'cor' => '#E30613', 'bg' => '#fde8e8', 'label' => 'Horários',         'count' => \App\Models\Horario::count()],
                ['route' => 'periodos',    'icon' => 'bi-calendar3',           'cor' => '#1a1a1a', 'bg' => '#ebebeb', 'label' => 'Períodos Letivos', 'count' => \App\Models\PeriodoLetivo::count()],
            ];
            @endphp

            @foreach($cards as $card)
            <div class="col-md-3 col-sm-6">
                <a href="{{ route($card['route']) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm" style="border-left:3px solid {{ $card['cor'] }} !important">
                        <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:40px;height:40px;background:{{ $card['bg'] }}">
                                <i class="bi {{ $card['icon'] }}" style="color:{{ $card['cor'] }};font-size:18px"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size:22px;line-height:1.1">{{ $card['count'] }}</div>
                                <div class="text-muted" style="font-size:12px">{{ $card['label'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach

        </div>

        {{-- Período letivo ativo + atalho para grade --}}
        <div class="row g-2">

            {{-- Período ativo --}}
            <div class="col-md-6">
                @php $periodoAtivo = \App\Models\PeriodoLetivo::where('ativo', true)->first(); @endphp
                @if($periodoAtivo)
                <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #E30613 !important">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-calendar-check-fill" style="color:#E30613"></i>
                            <h6 class="fw-bold mb-0" style="font-size:14px">Período Letivo Ativo</h6>
                            <span class="badge ms-auto" style="background:#E30613">{{ $periodoAtivo->nome }}</span>
                        </div>
                        <div class="row g-2">
                            @if($periodoAtivo->avaliacao1_inicio)
                            <div class="col-6">
                                <div class="p-2 rounded" style="background:#f8f8f8">
                                    <small class="text-muted d-block" style="font-size:11px">Avaliação 1</small>
                                    <span class="fw-semibold" style="font-size:12px">
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
                                    <small class="text-muted d-block" style="font-size:11px">Avaliação 2</small>
                                    <span class="fw-semibold" style="font-size:12px">
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
                    <div class="card-body text-center py-3 text-muted">
                        <i class="bi bi-calendar-x fs-4 d-block mb-2"></i>
                        <small>Nenhum período letivo ativo.</small>
                        <div class="mt-2">
                            <a href="{{ route('periodos') }}" class="btn btn-sm" style="background:#E30613;color:white">
                                Cadastrar Período
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Atalho grade --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100" style="background:#1a1a1a;border-top:3px solid #E30613 !important">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-3">
                        @php $logoPath = public_path('images/logo-unisenai.png'); @endphp
                        @if(file_exists($logoPath))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}"
                                 alt="UniSENAI MT"
                                 style="height:32px;filter:brightness(0) invert(1);margin-bottom:10px;opacity:0.9">
                        @else
                            <span style="font-size:16px;font-weight:900;color:white;margin-bottom:10px">UniSENAI MT</span>
                        @endif
                        <h6 class="fw-bold text-white mb-1">Grade de Horários</h6>
                        <p class="text-white mb-2" style="opacity:0.6;font-size:12px">
                            Visualize e imprima a grade completa por turma
                        </p>
                        <a href="{{ route('grade') }}" class="btn btn-sm fw-semibold px-4"
                           style="background:#E30613;color:white;border:none">
                            <i class="bi bi-eye me-2"></i>Visualizar Grade
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
