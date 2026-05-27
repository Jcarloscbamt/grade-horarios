{{-- resources/views/livewire/relatorio-professores.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-person-lines-fill me-2" style="color:#E30613"></i>Relatório — Professores por Disciplina</h2>
            <small class="text-muted">Consulte os vínculos de professores, disciplinas e turmas</small>
        </div>
        <div class="d-flex gap-2 d-print-none">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Imprimir / PDF
            </button>
            <button wire:click="exportarCsv" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar CSV
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4 d-print-none">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="form-control" placeholder="Buscar professor ou e-mail...">
                </div>
                <div class="col-md-3">
                    <select wire:model.live="curso_id" class="form-select">
                        <option value="">Todos os cursos</option>
                        @foreach($cursos as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="turma_id" class="form-select">
                        <option value="">Todas as turmas</option>
                        @foreach($turmas as $t)<option value="{{ $t->id }}">{{ $t->nome }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="filtroAtivo" class="form-select">
                        <option value="todos">Todos os status</option>
                        <option value="ativos">Somente Ativos</option>
                        <option value="inativos">Somente Inativos</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:13px">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Professor</th>
                            <th>Contato</th>
                            <th>Disponibilidade</th>
                            <th>Disciplina</th>
                            <th>Curso</th>
                            <th>Turma</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($professores as $professor)
                        @php
                            $vinculos = $professor->disciplinasTurmas()->with(['disciplina.curso', 'turma'])->get();
                            $disp = $professor->disponibilidade;
                            $dispArr = is_array($disp) ? $disp : (is_string($disp) ? json_decode($disp, true) : []);
                            $dispGeral = collect($dispArr ?? [])->map(fn($d) => $dias[$d] ?? $d);
                        @endphp

                        @if($vinculos->isEmpty())
                        {{-- Professor sem vínculos --}}
                        <tr class="{{ !$professor->ativo ? 'opacity-50' : '' }}">
                            <td class="ps-3 fw-medium">{{ $professor->nome }}</td>
                            <td>
                                <div style="font-size:12px">{{ $professor->email }}</div>
                                @if($professor->telefone)<div class="text-muted" style="font-size:11px">{{ $professor->telefone }}</div>@endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @foreach($dispGeral as $d)
                                    <span class="badge bg-secondary" style="font-size:10px">{{ $d }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td colspan="3"><span class="text-muted small fst-italic">Sem disciplinas vinculadas</span></td>
                            <td class="text-center">
                                <span class="badge {{ $professor->ativo ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $professor->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                        </tr>

                        @else
                        {{-- Professor com vínculos --}}
                        @foreach($vinculos as $idx => $v)
                        <tr class="{{ !$professor->ativo ? 'opacity-50' : '' }}">
                            @if($idx === 0)
                            <td class="ps-3 fw-medium" rowspan="{{ $vinculos->count() }}">
                                <div>{{ $professor->nome }}</div>
                                <div class="text-muted" style="font-size:11px">{{ $vinculos->count() }} disciplina(s)</div>
                            </td>
                            <td rowspan="{{ $vinculos->count() }}">
                                <div style="font-size:12px">{{ $professor->email }}</div>
                                @if($professor->telefone)<div class="text-muted" style="font-size:11px">{{ $professor->telefone }}</div>@endif
                            </td>
                            <td rowspan="{{ $vinculos->count() }}">
                                <div class="d-flex gap-1 flex-wrap">
                                    @foreach($dispGeral as $d)
                                    <span class="badge bg-success" style="font-size:10px">{{ $d }}</span>
                                    @endforeach
                                </div>
                            </td>
                            @endif

                            <td class="fw-medium">{{ $v->disciplina->nome ?? '—' }}</td>
                            <td>
                                @if($v->disciplina?->curso)
                                <span class="badge fw-semibold"
                                    style="background:{{ $v->disciplina->curso->cor_grade ?? '#6c757d' }};color:white">
                                    {{ $v->disciplina->curso->sigla }}
                                </span>
                                @else —
                                @endif
                            </td>
                            <td>{{ $v->turma->nome ?? '—' }}</td>

                            @if($idx === 0)
                            <td class="text-center" rowspan="{{ $vinculos->count() }}">
                                <span class="badge {{ $professor->ativo ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $professor->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                        @endif

                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum professor encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($professores->hasPages())
        <div class="card-footer bg-white border-top-0 d-print-none">{{ $professores->links() }}</div>
        @endif
    </div>

    <style>
        @media print {
            .d-print-none { display: none !important; }
            .card { box-shadow: none !important; }
        }
    </style>
</div>
