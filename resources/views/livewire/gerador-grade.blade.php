{{-- resources/views/livewire/gerador-grade.blade.php --}}
<div>
    {{-- Cabeçalho --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-magic me-2" style="color:#E30613"></i>Gerador de Grade
            </h4>
            <button type="button" data-bs-toggle="modal" data-bs-target="#helpModalGerador"
                class="btn btn-outline-secondary btn-sm rounded-circle"
                style="width:24px;height:24px;padding:0;font-size:12px;line-height:1" title="Ajuda">?</button>
        </div>
        <small class="text-muted">Gera automaticamente as aulas com base nos vínculos professor-disciplina</small>
    </div>

    {{-- Alertas --}}
    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-2">

                {{-- Coluna esquerda: Curso + Período empilhados --}}
                <div class="col-md-3">
                    <div class="mb-2">
                        <label class="form-label fw-medium small mb-1">Curso</label>
                        <select wire:model.live="curso_id" class="form-select form-select-sm">
                            <option value="">Todos os cursos</option>
                            @foreach($cursos as $c)
                            <option value="{{ $c->id }}">{{ $c->sigla }} — {{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-medium small mb-1">Período Letivo <span class="text-danger">*</span></label>
                        <select wire:model.live="periodo_letivo_id" class="form-select form-select-sm">
                            <option value="">Selecione...</option>
                            @foreach($periodosLetivos as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }}{{ $p->ativo ? ' ●' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Coluna centro: Turmas horizontais --}}
                <div class="col-md-7">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-medium small mb-0">Turma(s) <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            @if(count($turmasSelecionadas) > 0)
                            <span class="badge bg-primary" style="font-size:10px">{{ count($turmasSelecionadas) }} selecionada(s)</span>
                            @endif
                            <button type="button" wire:click="toggleTodasTurmas"
                                class="btn btn-outline-secondary" style="font-size:10px;padding:1px 7px">
                                {{ count($turmasSelecionadas) >= count($turmas) && count($turmas) > 0 ? 'Desmarcar' : 'Todas' }}
                            </button>
                        </div>
                    </div>
                    <div class="border rounded p-2 d-flex flex-wrap gap-1" style="min-height:70px;background:#fafafa">
                        @forelse($turmas as $t)
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox"
                                wire:model.live="turmasSelecionadas"
                                value="{{ $t->id }}" id="t_{{ $t->id }}">
                            <label class="form-check-label" for="t_{{ $t->id }}" style="cursor:pointer">
                                <span class="badge {{ in_array($t->id, $turmasSelecionadas) ? 'bg-primary' : 'bg-light text-dark border' }}"
                                      style="font-size:11px;white-space:nowrap">
                                    {{ $t->nome }}
                                    <span class="opacity-75">{{ $t->semestre }}º</span>
                                </span>
                            </label>
                        </div>
                        @empty
                        <span class="text-muted small align-self-center">
                            <i class="bi bi-info-circle me-1"></i>Nenhuma turma encontrada
                        </span>
                        @endforelse
                    </div>
                    <div class="form-text mt-1">
                        <i class="bi bi-check-square me-1"></i>Clique nos badges para selecionar as turmas
                    </div>
                </div>

                {{-- Botões Gerar + Cancelar --}}
                <div class="col-md-2 d-flex align-items-center gap-2">
                    <button wire:click="gerarPrevia"
                        wire:loading.attr="disabled"
                        class="btn btn-primary w-100"
                        {{ !$periodo_letivo_id || empty($turmasSelecionadas) ? 'disabled' : '' }}>
                        <span wire:loading wire:target="gerarPrevia" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="gerarPrevia" class="bi bi-play-circle me-1"></i>
                        Gerar Prévia
                    </button>
                    @if($previewGerado || count($conflitos) > 0)
                    <button wire:click="resetPreview" class="btn btn-outline-secondary" title="Cancelar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Aviso turmas já geradas --}}
    @if(count($turmasJaGeradas) > 0)
    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
        <div>
            <strong>Atenção:</strong> As seguintes turmas já têm grade gerada para este período:
            <strong>{{ implode(', ', $turmasJaGeradas) }}</strong>
            <br><small>Para regerar, exclua as aulas existentes na tela <a href="{{ route('aulas') }}">Aulas</a> primeiro.</small>
        </div>
    </div>
    @endif

    {{-- Seleção de professor quando há múltiplos --}}
    @if($aguardandoSelecao)
    <div class="card border-0 shadow-sm mb-4 border-start border-primary border-4">
        <div class="card-body">
            <h6 class="fw-bold text-primary mb-3">
                <i class="bi bi-people me-1"></i>Selecione o professor para cada disciplina com múltiplos vínculos
            </h6>
            <div class="alert alert-info py-2" style="font-size:12px">
                <i class="bi bi-info-circle me-1"></i>
                Ao lado de cada professor aparece <strong>vínculos / dias</strong>. Se os vínculos forem mais que os dias (⚠️), escolher esse professor tende a gerar conflito — prefira distribuir entre professores com folga.
            </div>
            <div class="d-flex flex-column gap-3">
                @foreach($pendentesSelecao as $pend)
                @php $key = $pend['disciplina_id'].'_'.$pend['turma_id']; @endphp
                <div class="border rounded p-3" style="background:#f8f9fa">
                    <div class="fw-medium mb-2" style="font-size:13px">
                        <i class="bi bi-book me-1 text-primary"></i>
                        {{ $pend['disciplina_nome'] }}
                        <span class="badge bg-secondary ms-1">{{ $pend['turma_nome'] }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($pend['professores'] as $prof)
                        <div class="form-check">
                            <input class="form-check-input" type="radio"
                                wire:model="escolhasProfessores.{{ $key }}"
                                value="{{ $prof['id'] }}"
                                id="prof_{{ $key }}_{{ $prof['id'] }}">
                            <label class="form-check-label badge {{ isset($escolhasProfessores[$key]) && $escolhasProfessores[$key] == $prof['id'] ? 'bg-primary' : 'bg-light text-dark border' }}"
                                for="prof_{{ $key }}_{{ $prof['id'] }}"
                                style="font-size:12px;cursor:pointer;font-weight:500">
                                <i class="bi bi-person me-1"></i>{{ $prof['nome'] }}
                                @isset($prof['total_disc'])
                                <span class="ms-1 {{ ($prof['sobrecarregado'] ?? false) ? 'text-danger fw-bold' : 'text-muted' }}">
                                    ({{ $prof['total_disc'] }} vínc / {{ $prof['dias'] }}d{{ ($prof['sobrecarregado'] ?? false) ? ' ⚠️' : '' }})
                                </span>
                                @endisset
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            <div class="d-flex gap-2 mt-3">
                <button wire:click="cancelarSelecao" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x me-1"></i>Cancelar
                </button>
                <button wire:click="confirmarSelecao" class="btn btn-primary btn-sm">
                    <i class="bi bi-check2-circle me-1"></i>Confirmar e Gerar
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($previewGerado)
    {{-- Indicador da estratégia usada --}}
    @if($estrategiaUsada)
    <div class="alert {{ str_contains($estrategiaUsada, '✅') ? 'alert-success' : 'alert-info' }} py-2 mb-3" style="font-size:12px">
        <i class="bi bi-cpu me-1"></i><strong>Varredura automática:</strong> {{ $estrategiaUsada }}
    </div>
    @endif

    {{-- Conflitos --}}
    @if(count($conflitos) > 0)
    <div class="card border-0 shadow-sm mb-3 border-start border-warning border-4 position-relative">
        {{-- Overlay de carregamento: cobre a área enquanto regenera (evita botões piscando) --}}
        <div wire:loading.flex wire:target="aceitarSugestao"
             class="position-absolute top-0 start-0 w-100 h-100 align-items-center justify-content-center flex-column"
             style="background:rgba(255,255,255,0.85);z-index:10;border-radius:.375rem">
            <div class="spinner-border text-warning mb-2" role="status"></div>
            <div class="fw-medium text-muted">Recalculando a grade...</div>
        </div>
        <div class="card-body">
            <h6 class="fw-bold text-warning mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ count($conflitos) }} conflito(s) detectado(s)
            </h6>
            <div class="d-flex flex-column gap-3">
                @foreach($conflitos as $c)
                <div class="border rounded p-2" style="background:#fffbeb;font-size:13px">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-x-circle-fill text-danger mt-1 flex-shrink-0"></i>
                        <span class="fw-medium">{{ $c['mensagem'] }}</span>
                    </div>
                    {{-- Diagnóstico por dia --}}
                    @if(!empty($c['diagnostico']))
                    <div class="ms-4 mb-2">
                        <div class="text-muted fw-medium mb-1" style="font-size:12px">
                            <i class="bi bi-search me-1"></i>Por que cada dia falhou:
                        </div>
                        <div class="d-flex flex-column gap-1">
                            @foreach($c['diagnostico'] as $diag)
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger d-inline-block text-start" style="font-size:11px;font-weight:400;max-width:fit-content">
                                <i class="bi bi-x-circle me-1"></i>{{ $diag }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Sugestão de troca (swap) --}}
                    @if(!empty($c['sugestao_troca']))
                    <div class="ms-4 mb-2">
                        <div class="alert alert-info py-2 mb-0" style="font-size:12px">
                            <i class="bi bi-arrow-left-right me-1"></i>
                            <strong>Sugestão de reorganização:</strong><br>
                            {{ $c['sugestao_troca'] }}
                        </div>
                    </div>
                    @endif

                    @if(!empty($c['dias_livres']))
                    <div class="ms-4">
                        <div class="text-success fw-medium mb-1" style="font-size:12px">
                            <i class="bi bi-lightbulb me-1"></i>Sugestão: ADICIONAR este dia à disponibilidade de {{ $c['professor'] ?? '' }} (hoje ele NÃO atende neste dia):
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @foreach($c['dias_livres'] as $dia)
                            <span class="badge bg-success" style="font-size:11px">+ {{ $dia['nome'] }}</span>
                            @endforeach
                            @if(isset($c['professor_id']))
                            <button type="button"
                                wire:click="aceitarSugestao({{ $c['professor_id'] }}, {{ $c['turma_id'] }}, {{ $c['disciplina_id'] }}, {{ json_encode(array_column($c['dias_livres'], 'num')) }})"
                                wire:loading.attr="disabled"
                                class="btn btn-success btn-sm py-0 ms-1" style="font-size:11px">
                                <i class="bi bi-check2-circle me-1"></i>Adicionar dia e regerar
                            </button>
                            @endif
                        </div>
                        <div class="text-muted mt-1" style="font-size:11px">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <strong>Atenção:</strong> ao confirmar, este dia será gravado permanentemente na disponibilidade do professor (cadastro), e a grade será regerada.
                        </div>
                    </div>
                    @elseif(isset($c['professor_id']))
                    <div class="ms-4 text-muted" style="font-size:12px">
                        <i class="bi bi-calendar-x me-1"></i>
                        Nenhum dia livre fora da disponibilidade — revise a distribuição dos professores ou adicione mais dias ao professor.
                    </div>
                    @endif

                    @if(!empty($c['aviso_alocacao']))
                    <div class="ms-4 mt-2 alert alert-warning py-2 mb-0" style="font-size:12px">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $c['aviso_alocacao'] }}
                    </div>
                    @endif
                    @if(isset($c['professor_id']))
                    <div class="ms-4 mt-2">
                        <a href="{{ route('professores') }}?editar={{ $c['professor_id'] }}"
                            target="_blank"
                            class="btn btn-outline-secondary btn-sm py-0" style="font-size:11px">
                            <i class="bi bi-person-gear me-1"></i>
                            Abrir cadastro de {{ $c['professor'] ?? 'Professor' }} (nova aba)
                        </a>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Avisos sem sala --}}
    @if(count($avisosSemSala) > 0)
    <div class="card border-0 shadow-sm mb-3 border-start border-info border-4">
        <div class="card-body py-3">
            <h6 class="fw-bold text-info mb-2"><i class="bi bi-building me-1"></i>{{ count($avisosSemSala) }} aviso(s) de sala</h6>
            @foreach($avisosSemSala as $a)
            <div style="font-size:13px"><i class="bi bi-dash me-1"></i>{{ $a['mensagem'] }}</div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Prévia --}}
    @if(count($preview) > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0"><i class="bi bi-table me-1 text-success"></i>Prévia — {{ count($preview) }} aula(s)</h6>
                <small class="text-muted">Revise antes de salvar</small>
            </div>
            <div class="d-flex gap-2">
                <button wire:click="resetPreview" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x me-1"></i>Cancelar
                </button>
                <button wire:click="salvarGrade" wire:loading.attr="disabled" class="btn btn-success btn-sm">
                    <span wire:loading wire:target="salvarGrade" class="spinner-border spinner-border-sm me-1"></span>
                    <i wire:loading.remove wire:target="salvarGrade" class="bi bi-floppy me-1"></i>
                    Salvar Grade
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:calc(100vh - 220px);overflow-y:auto">
                <table class="table table-hover align-middle mb-0" style="font-size:13px">
                    <thead class="table-light" style="position:sticky;top:0;z-index:10">
                        <tr>
                            <th class="ps-3">Turma</th>
                            <th>Dia</th>
                            <th>Horário</th>
                            <th>Disciplina</th>
                            <th>Professor</th>
                            <th>Sala</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $turmaAtual = ''; @endphp
                        @foreach($preview as $item)
                        @if($item['turma_nome'] !== $turmaAtual)
                        @php $turmaAtual = $item['turma_nome']; @endphp
                        <tr class="table-secondary">
                            <td colspan="6" class="ps-3 fw-bold py-1" style="font-size:12px;letter-spacing:.5px">
                                <i class="bi bi-people me-1"></i>{{ $turmaAtual }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td class="ps-3 text-muted" style="font-size:11px"></td>
                            <td><span class="badge bg-secondary">{{ $dias[$item['dia_semana']] ?? $item['dia_semana'] }}</span></td>
                            <td style="font-family:monospace">{{ $item['horario'] }}</td>
                            <td class="fw-medium">{{ $item['disciplina'] }}</td>
                            <td>{{ $item['professor'] }}</td>
                            <td>
                                @if($item['modalidade'] === 'online')
                                <span class="badge bg-primary" style="font-size:11px"><i class="bi bi-wifi me-1"></i>Online</span>
                                @elseif($item['sala_id'])
                                <i class="bi bi-building me-1 text-muted"></i>{{ $item['sala'] }}
                                @else
                                <span class="text-warning small"><i class="bi bi-exclamation-triangle me-1"></i>Sem sala</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="text-center text-muted py-4">
        <i class="bi bi-calendar-x fs-3 d-block mb-2 opacity-50"></i>
        Nenhuma aula nova para gerar — todas já estão cadastradas ou há conflitos.
    </div>
    @endif

    @else
    <div class="text-center text-muted py-5">
        <i class="bi bi-magic fs-1 d-block mb-3 opacity-25"></i>
        <p>Selecione o <strong>período letivo</strong> e as <strong>turmas</strong> para gerar a grade.</p>
    </div>
    @endif

    {{-- Modal de Ajuda --}}
    <div class="modal fade" id="helpModalGerador" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background:#1a1a1a;color:white;border-bottom:3px solid #E30613">
                    <h5 class="modal-title fw-bold"><i class="bi bi-question-circle me-2"></i>Ajuda — Gerador de Grade</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="font-size:14px">
                    <p class="text-muted mb-3">O Gerador monta a grade automaticamente a partir dos <strong>vínculos</strong> (professor + disciplina + turma) cadastrados. Esta ajuda explica o passo a passo, as regras, e — principalmente — <strong>o que acontece quando dá conflito</strong>.</p>

                    {{-- COMO USAR --}}
                    <h6 class="fw-bold mt-3"><i class="bi bi-1-circle-fill text-danger me-1"></i>Como usar</h6>
                    <ol class="mb-3">
                        <li class="mb-1">Selecione o <strong>Curso</strong> (opcional, filtra as turmas)</li>
                        <li class="mb-1">Selecione o <strong>Período Letivo</strong></li>
                        <li class="mb-1">Selecione as <strong>Turmas</strong></li>
                        <li class="mb-1">Clique em <strong>Gerar Prévia</strong> e revise</li>
                        <li class="mb-1">Se houver mais de um professor para a mesma disciplina, aparece uma <strong>tela de seleção</strong> para você escolher qual leciona</li>
                        <li class="mb-1">Clique em <strong>Salvar Grade</strong></li>
                    </ol>

                    <hr>

                    {{-- A REGRA DE OURO --}}
                    <h6 class="fw-bold"><i class="bi bi-key-fill text-warning me-1"></i>A regra que explica tudo</h6>
                    <div class="alert alert-light border py-2" style="font-size:13px">
                        Cada <strong>vínculo</strong> (uma disciplina numa turma) vira <strong>1 aula</strong>, e cada aula ocupa <strong>1 dia</strong> da semana do professor.
                        Como a semana tem <strong>5 dias úteis</strong> e o professor dá no máximo 1 aula por dia, um professor pode ter no máximo <strong>5 vínculos</strong>.
                        <div class="mt-2 mb-0">Exemplo: se um professor tem 4 disciplinas para dar, ele precisa de pelo menos 4 dias livres na disponibilidade. Com só 2 dias, é fisicamente impossível — faltam dias.</div>
                    </div>

                    <hr>

                    {{-- COMO O GERADOR PENSA --}}
                    <h6 class="fw-bold"><i class="bi bi-gear-fill text-secondary me-1"></i>Como o gerador monta a grade (em etapas)</h6>
                    <p class="mb-2" style="font-size:13px">Ele não tenta de qualquer jeito — segue uma ordem inteligente:</p>
                    <ul style="font-size:13px">
                        <li class="mb-2"><strong>1. Começa pelos mais difíceis (Escassez / MRV):</strong> professores com menos dias disponíveis são alocados primeiro, porque têm menos opções. É como montar um quebra-cabeça começando pelas peças que só encaixam num lugar.</li>
                        <li class="mb-2"><strong>2. Distribui as aulas de cada professor (1 por dia):</strong> garante que o professor nunca fique em duas turmas no mesmo dia.</li>
                        <li class="mb-2"><strong>3. Conserta conflitos automaticamente (reparo global):</strong> se duas aulas brigam pelo mesmo dia, o gerador tenta <em>mover</em> uma delas para outro dia livre — e faz isso em várias rodadas, porque resolver um conflito costuma liberar espaço para resolver outro.</li>
                        <li class="mb-2"><strong>4. Confere se não há duplicidade:</strong> uma verificação final garante as 3 regras invioláveis (abaixo).</li>
                        <li class="mb-2"><strong>5. Sugere soluções para o que sobrou:</strong> para cada conflito que não deu para resolver sozinho, ele sugere um dia específico para adicionar à disponibilidade do professor.</li>
                    </ul>
                    <p class="text-muted" style="font-size:12px">Obs.: o gerador faz várias tentativas com estratégias diferentes e fica com a melhor (a que tem menos conflitos).</p>

                    <hr>

                    {{-- AS 3 REGRAS --}}
                    <h6 class="fw-bold"><i class="bi bi-shield-check text-success me-1"></i>As 3 regras que nunca são quebradas</h6>
                    <ul class="list-unstyled" style="font-size:13px">
                        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i>Um <strong>professor</strong> não pode estar em duas turmas no mesmo dia</li>
                        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i>Uma <strong>turma</strong> não pode ter duas disciplinas no mesmo dia</li>
                        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i>Uma <strong>sala</strong> não pode ser usada por duas turmas ao mesmo tempo</li>
                    </ul>
                    <p class="text-muted" style="font-size:12px">Disciplinas são filtradas pelo semestre da turma; salas são alocadas por tipo e bloco preferencial.</p>

                    <hr>

                    {{-- QUANDO DÁ ERRO --}}
                    <h6 class="fw-bold"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Quando aparece conflito — o que significa</h6>
                    <p class="mb-2" style="font-size:13px">A mensagem mostra <strong>por que cada dia falhou</strong>. Os casos mais comuns:</p>

                    <div class="border-start border-3 border-warning ps-2 mb-2" style="font-size:13px">
                        <strong>"Faltam N dias — adicione mais dias ou redistribua"</strong><br>
                        O professor tem mais disciplinas do que dias disponíveis. Ex.: 4 disciplinas, 2 dias → faltam 2 dias.
                        <div class="text-muted">Solução: adicione dias na disponibilidade dele (cadastro) ou passe alguma disciplina para outro professor.</div>
                    </div>

                    <div class="border-start border-3 border-danger ps-2 mb-2" style="font-size:13px">
                        <strong>"IMPOSSÍVEL — a semana só tem 5 dias úteis"</strong><br>
                        O professor tem mais de 5 vínculos. Não há 6º dia na semana.
                        <div class="text-muted">Solução: a única saída é redistribuir disciplinas para outro professor.</div>
                    </div>

                    <div class="border-start border-3 border-secondary ps-2 mb-2" style="font-size:13px">
                        <strong>"Não há combinação de dias possível"</strong><br>
                        Os dias do professor estão todos tomados por outras turmas naquele momento.
                        <div class="text-muted">Solução: o gerador normalmente sugere um dia novo para adicionar (botão abaixo do conflito).</div>
                    </div>

                    <hr>

                    {{-- SUGESTÕES --}}
                    <h6 class="fw-bold"><i class="bi bi-lightbulb-fill text-warning me-1"></i>Aceitar sugestão de dia</h6>
                    <p style="font-size:13px">Quando o gerador sugere <strong>"+ TER"</strong> (por exemplo), significa: "se este professor passar a atender às terças, o conflito some". Ao clicar em <strong>Adicionar dia e regerar</strong>:</p>
                    <ul style="font-size:13px">
                        <li class="mb-1">O dia é <strong>gravado permanentemente</strong> na disponibilidade do professor (no cadastro)</li>
                        <li class="mb-1">A grade é recalculada automaticamente</li>
                    </ul>
                    <div class="alert alert-info py-2" style="font-size:12px">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Dica importante:</strong> às vezes um conflito não mostra botão de sugestão na hora, mas aparece depois que você aceita os outros — porque cada ajuste libera espaço novo. Se um professor está muito sobrecarregado, o melhor é <strong>configurar a disponibilidade correta ANTES de gerar</strong> (dias ≥ número de disciplinas), em vez de aceitar sugestões uma a uma.
                    </div>

                    <hr>

                    {{-- PRE-REQUISITOS --}}
                    <div class="alert alert-warning py-2 mt-2" style="font-size:12px">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Pré-requisitos:</strong> professores com vínculos cadastrados, disponibilidade definida, horários e salas cadastrados, e um período letivo selecionado.
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

</div>
