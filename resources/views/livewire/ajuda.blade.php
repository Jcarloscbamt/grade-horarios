{{-- resources/views/livewire/ajuda.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-question-circle me-2" style="color:#E30613"></i>Manual do Sistema</h4>
            <small class="text-muted">Grade de Horários — UniSENAI MT</small>
        </div>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Imprimir / PDF
        </button>
    </div>

    <style>
        @media print {
            .sidebar, .topbar, .btn, .no-print { display: none !important; }
            .main-wrapper { margin: 0 !important; padding: 10px !important; }
            .accordion-collapse { display: block !important; }
            .accordion-button::after { display: none; }
        }
        .ajuda-icon { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .accordion-button:not(.collapsed) { background: #f8f9fa; color: #1a1a1a; font-weight: 600; box-shadow: none; }
        .accordion-button:focus { box-shadow: none; }
        .step-badge { width: 22px; height: 22px; border-radius: 50%; background: #E30613; color: white; font-size: 11px; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    </style>

    {{-- Introdução --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <div style="width:56px;height:56px;background:#E30613;border-radius:12px;display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-grid-3x3-gap text-white" style="font-size:28px"></i>
                    </div>
                </div>
                <div class="col">
                    <h5 class="fw-bold mb-1">Sistema de Grade de Horários</h5>
                    <p class="text-muted mb-0">Este manual explica como usar cada tela do sistema. Clique nas seções para expandir.</p>
                </div>
                <div class="col-auto">
                    <div class="text-center text-muted" style="font-size:12px">
                        <div class="fw-bold" style="font-size:20px;color:#E30613">{{ \App\Models\Turma::count() }}</div>
                        Turmas
                    </div>
                </div>
                <div class="col-auto">
                    <div class="text-center text-muted" style="font-size:12px">
                        <div class="fw-bold" style="font-size:20px;color:#E30613">{{ \App\Models\Professor::count() }}</div>
                        Professores
                    </div>
                </div>
                <div class="col-auto">
                    <div class="text-center text-muted" style="font-size:12px">
                        <div class="fw-bold" style="font-size:20px;color:#E30613">{{ \App\Models\Disciplina::count() }}</div>
                        Disciplinas
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fluxo de Uso --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-arrow-right-circle me-2" style="color:#E30613"></i>Fluxo recomendado para gerar a grade</h6>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @foreach([
                    ['Criar Período Letivo', 'bi-calendar3'],
                    ['Cadastrar Cursos', 'bi-mortarboard'],
                    ['Cadastrar Turmas', 'bi-people'],
                    ['Cadastrar Disciplinas', 'bi-book'],
                    ['Cadastrar Salas', 'bi-door-open'],
                    ['Cadastrar Professores + Vínculos', 'bi-person-badge'],
                    ['Gerar Grade', 'bi-magic'],
                    ['Imprimir', 'bi-printer'],
                ] as $i => $step)
                <div class="d-flex align-items-center gap-2">
                    @if($i > 0)<i class="bi bi-chevron-right text-muted"></i>@endif
                    <span class="badge d-flex align-items-center gap-1 py-2 px-2"
                          style="background:#f8f9fa;color:#333;border:1px solid #dee2e6;font-size:12px;font-weight:500">
                        <i class="bi {{ $step[1] }}" style="color:#E30613"></i>
                        {{ $step[0] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Accordion com seções --}}
    <div class="accordion" id="manualAccordion">

        {{-- ── CADASTROS ── --}}
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white border-0 py-1">
                <span class="fw-bold text-muted" style="font-size:11px;letter-spacing:1px;text-transform:uppercase">Cadastros</span>
            </div>
        </div>

        @foreach([
        [
            'id' => 'cursos',
            'icon' => 'bi-mortarboard',
            'title' => 'Cursos',
            'desc' => 'Gerenciamento dos cursos oferecidos',
            'color' => '#6366f1',
            'campos' => [
                ['Nome e Sigla', 'Ex: Análise e Desenvolvimento de Sistemas / ADS'],
                ['Total de Semestres', 'Define a duração do curso. Ex: 6 semestres = 3 anos. Usado para controlar o avanço das turmas'],
                ['Coordenador', 'Aparece no rodapé da grade impressa para contato dos alunos'],
                ['Cor da Grade', 'Cor de destaque usada na grade impressa colorida'],
            ],
            'dicas' => ['Cadastre todos os cursos antes de criar turmas e disciplinas', 'O total de semestres é obrigatório para o botão "Avançar Semestre" funcionar corretamente'],
        ],
        [
            'id' => 'turmas',
            'icon' => 'bi-people',
            'title' => 'Turmas',
            'desc' => 'Grupos de alunos por curso e ano de entrada',
            'color' => '#0ea5e9',
            'campos' => [
                ['Nome', 'Ex: ADS26/1 — identifica o curso, ano de entrada e semestre'],
                ['Semestre', 'Indica em qual semestre a turma está AGORA. Deve ser atualizado a cada período letivo'],
                ['Ano', 'Ano letivo atual da turma'],
            ],
            'dicas' => ['Toda turma nova começa sempre no 1º semestre', 'Use o botão "Avançar Semestre das Turmas" em Períodos Letivos para atualizar todas de uma vez', 'O semestre atual da turma determina quais disciplinas o Gerador vai alocar'],
        ],
        [
            'id' => 'disciplinas',
            'icon' => 'bi-book',
            'title' => 'Disciplinas',
            'desc' => 'Matérias que compõem cada semestre do curso',
            'color' => '#f59e0b',
            'campos' => [
                ['Semestre na Grade', 'Em qual semestre do curso esta disciplina é ministrada. O Gerador usa isso para filtrar automaticamente'],
                ['Tipo de Sala', 'Obrigatório: Sala de Aula, Laboratório ou Online. O Gerador aloca a sala conforme este tipo'],
                ['Bloco Preferencial', 'O Gerador tenta alocar neste bloco primeiro. Se não encontrar, usa qualquer bloco do tipo'],
                ['Online', 'Disciplinas online não precisam de sala física — o Gerador não aloca sala e marca como Online na grade'],
            ],
            'dicas' => ['Cadastre todas as disciplinas de todos os semestres antes de vincular professores', 'O semestre da disciplina deve coincidir com o semestre atual da turma para o Gerador funcionar'],
        ],
        [
            'id' => 'salas',
            'icon' => 'bi-door-open',
            'title' => 'Salas',
            'desc' => 'Salas físicas disponíveis para alocação',
            'color' => '#10b981',
            'campos' => [
                ['Tipo', 'Sala de Aula ou Laboratório — deve corresponder ao tipo definido na disciplina'],
                ['Bloco', 'O Gerador tenta o bloco preferencial da disciplina primeiro'],
                ['Capacidade', 'Informativo — não interfere na alocação automática'],
            ],
            'dicas' => ['Salas inativas não são consideradas pelo Gerador', 'Cadastre salas suficientes para evitar conflitos — cada turma+horário precisa de uma sala diferente'],
        ],
        [
            'id' => 'professores',
            'icon' => 'bi-person-badge',
            'title' => 'Professores',
            'desc' => 'Cadastro com disponibilidade e vínculos de disciplinas',
            'color' => '#E30613',
            'campos' => [
                ['Disponibilidade Geral', 'Dias da semana em que o professor pode lecionar. OBRIGATÓRIO'],
                ['Vínculos', 'Define quais disciplinas/turmas o professor leciona. Filtro: Curso → Turma → Disciplinas do semestre atual'],
            ],
            'dicas' => [
                'Regra fundamental: um professor NÃO pode dar aula em turmas diferentes no mesmo dia',
                'Mínimo de dias disponíveis = número de turmas distintas vinculadas. Ex: 4 turmas = mínimo 4 dias',
                'Ao salvar, o sistema valida automaticamente se há dias suficientes',
                'Ao alterar a disponibilidade e salvar, todos os vínculos são atualizados automaticamente',
            ],
            'alerta' => 'Se o professor tiver menos dias disponíveis que turmas vinculadas, o Gerador não conseguirá alocar todas as disciplinas.',
        ],
        ] as $sec)
        <div class="accordion-item border-0 shadow-sm mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded" type="button"
                    data-bs-toggle="collapse" data-bs-target="#acc_{{ $sec['id'] }}">
                    <div class="ajuda-icon me-3" style="background:{{ $sec['color'] }}22">
                        <i class="bi {{ $sec['icon'] }}" style="color:{{ $sec['color'] }}"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $sec['title'] }}</div>
                        <div class="text-muted fw-normal" style="font-size:12px">{{ $sec['desc'] }}</div>
                    </div>
                </button>
            </h2>
            <div id="acc_{{ $sec['id'] }}" class="accordion-collapse collapse">
                <div class="accordion-body pt-0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-list-check me-1" style="color:#E30613"></i>Campos</h6>
                            <ul class="list-unstyled">
                                @foreach($sec['campos'] as $campo)
                                <li class="mb-2 d-flex gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i>
                                    <span style="font-size:13px"><strong>{{ $campo[0] }}:</strong> {{ $campo[1] }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-lightbulb me-1" style="color:#f59e0b"></i>Dicas</h6>
                            <ul class="list-unstyled">
                                @foreach($sec['dicas'] as $dica)
                                <li class="mb-2 d-flex gap-2">
                                    <i class="bi bi-arrow-right-short text-primary mt-1 flex-shrink-0"></i>
                                    <span style="font-size:13px">{{ $dica }}</span>
                                </li>
                                @endforeach
                            </ul>
                            @if(isset($sec['alerta']))
                            <div class="alert alert-warning py-2 mt-2" style="font-size:12px">
                                <i class="bi bi-exclamation-triangle me-1"></i>{{ $sec['alerta'] }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        {{-- ── GERAÇÃO ── --}}
        <div class="card border-0 shadow-sm mb-2 mt-3">
            <div class="card-header bg-white border-0 py-1">
                <span class="fw-bold text-muted" style="font-size:11px;letter-spacing:1px;text-transform:uppercase">Geração e Visualização</span>
            </div>
        </div>

        {{-- Períodos Letivos --}}
        <div class="accordion-item border-0 shadow-sm mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded" type="button"
                    data-bs-toggle="collapse" data-bs-target="#acc_periodos">
                    <div class="ajuda-icon me-3" style="background:#8b5cf622">
                        <i class="bi bi-calendar3" style="color:#8b5cf6"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Períodos Letivos</div>
                        <div class="text-muted fw-normal" style="font-size:12px">Semestres letivos com datas de avaliação</div>
                    </div>
                </button>
            </h2>
            <div id="acc_periodos" class="accordion-collapse collapse">
                <div class="accordion-body pt-0" style="font-size:13px">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-list-check me-1" style="color:#E30613"></i>Campos</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Período Ativo:</strong> Apenas um ativo por vez. O Gerador usa o ativo por padrão</span></li>
                                <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Datas de Avaliação:</strong> Aparecem no rodapé da grade impressa</span></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-lightbulb me-1" style="color:#f59e0b"></i>Fluxo ao iniciar novo semestre</h6>
                            <ol class="ps-3" style="font-size:13px">
                                <li class="mb-1">Criar novo período letivo</li>
                                <li class="mb-1">Ativar o novo período</li>
                                <li class="mb-1">Clicar em <strong>"Avançar Semestre das Turmas"</strong></li>
                                <li class="mb-1">Conferir os semestres das turmas</li>
                                <li class="mb-1">Gerar as novas grades</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gerador de Grade --}}
        <div class="accordion-item border-0 shadow-sm mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded" type="button"
                    data-bs-toggle="collapse" data-bs-target="#acc_gerador">
                    <div class="ajuda-icon me-3" style="background:#E3061322">
                        <i class="bi bi-magic" style="color:#E30613"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Gerador de Grade</div>
                        <div class="text-muted fw-normal" style="font-size:12px">Alocação automática de aulas</div>
                    </div>
                </button>
            </h2>
            <div id="acc_gerador" class="accordion-collapse collapse">
                <div class="accordion-body pt-0" style="font-size:13px">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-list-ol me-1" style="color:#E30613"></i>Passo a passo</h6>
                            <ol class="ps-3">
                                <li class="mb-2">Selecione o <strong>Curso</strong> (opcional)</li>
                                <li class="mb-2">Selecione o <strong>Período Letivo</strong></li>
                                <li class="mb-2">Marque as <strong>Turmas</strong></li>
                                <li class="mb-2">Clique em <strong>Gerar Prévia</strong></li>
                                <li class="mb-2">Revise e clique em <strong>Salvar Grade</strong></li>
                            </ol>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-shield-check me-1 text-success"></i>Regras automáticas</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>Professor: 1 turma por dia</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>Sala: sem sobreposição de horário</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>Disciplinas filtradas por semestre</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>1 disciplina por semana por turma</span></li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="fw-bold mb-2" style="font-size:13px"><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Pré-requisitos</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-dot text-muted flex-shrink-0"></i><span>Período ativo</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-dot text-muted flex-shrink-0"></i><span>Horários cadastrados</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-dot text-muted flex-shrink-0"></i><span>Salas cadastradas</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-dot text-muted flex-shrink-0"></i><span>Professores com vínculos</span></li>
                            </ul>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 mt-2" style="font-size:12px">
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Conflitos:</strong> O sistema sugere dias alternativos. Se aparecer "Nenhum dia livre", verifique se o professor tem dias suficientes: mínimo 1 dia por turma vinculada.
                    </div>
                </div>
            </div>
        </div>

        {{-- Grade de Horários --}}
        <div class="accordion-item border-0 shadow-sm mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded" type="button"
                    data-bs-toggle="collapse" data-bs-target="#acc_grade">
                    <div class="ajuda-icon me-3" style="background:#06b6d422">
                        <i class="bi bi-grid-3x3-gap" style="color:#06b6d4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Grade de Horários</div>
                        <div class="text-muted fw-normal" style="font-size:12px">Visualização e impressão da grade</div>
                    </div>
                </button>
            </h2>
            <div id="acc_grade" class="accordion-collapse collapse">
                <div class="accordion-body pt-0" style="font-size:13px">
                    <ul class="list-unstyled">
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Filtros:</strong> Curso, turmas e período letivo. Selecione uma turma para imprimir individualmente</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Imprimir:</strong> Gera PDF colorido com logo UniSENAI, professor, sala e informações do período</span></li>
                        <li class="mb-2 d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0" style="font-size:12px"></i><span><strong>Online:</strong> Disciplinas online aparecem com ícone 🌐 sem sala física</span></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ── RELATÓRIOS ── --}}
        <div class="card border-0 shadow-sm mb-2 mt-3">
            <div class="card-header bg-white border-0 py-1">
                <span class="fw-bold text-muted" style="font-size:11px;letter-spacing:1px;text-transform:uppercase">Relatórios e Administração</span>
            </div>
        </div>

        <div class="accordion-item border-0 shadow-sm mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded" type="button"
                    data-bs-toggle="collapse" data-bs-target="#acc_relatorios">
                    <div class="ajuda-icon me-3" style="background:#84cc1622">
                        <i class="bi bi-bar-chart" style="color:#84cc16"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Relatórios</div>
                        <div class="text-muted fw-normal" style="font-size:12px">Grade e Professores por Disciplina</div>
                    </div>
                </button>
            </h2>
            <div id="acc_relatorios" class="accordion-collapse collapse">
                <div class="accordion-body pt-0" style="font-size:13px">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2" style="font-size:13px">Relatório de Grade</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>Filtre por curso, turma e período</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>Exporte para CSV ou imprima em PDF</span></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2" style="font-size:13px">Relatório de Professores</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>Lista professores com disciplinas e turmas</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span><strong>Identificar Duplicados:</strong> encontra disciplinas com mais de 1 professor por turma</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item border-0 shadow-sm mb-2">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded" type="button"
                    data-bs-toggle="collapse" data-bs-target="#acc_admin">
                    <div class="ajuda-icon me-3" style="background:#64748b22">
                        <i class="bi bi-shield-lock" style="color:#64748b"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Usuários e Logs</div>
                        <div class="text-muted fw-normal" style="font-size:12px">Administração do sistema</div>
                    </div>
                </button>
            </h2>
            <div id="acc_admin" class="accordion-collapse collapse">
                <div class="accordion-body pt-0" style="font-size:13px">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2" style="font-size:13px">Perfis de Acesso</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1 d-flex gap-2"><span class="badge" style="background:#E30613;font-size:10px">Admin</span><span class="ms-1">Acesso total, usuários e logs</span></li>
                                <li class="mb-1 d-flex gap-2"><span class="badge bg-secondary" style="font-size:10px">Coordenador</span><span class="ms-1">Cadastros, gerador e relatórios</span></li>
                                <li class="mb-1 d-flex gap-2"><span class="badge bg-light text-dark border" style="font-size:10px">Consulta</span><span class="ms-1">Apenas visualização</span></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2" style="font-size:13px">Log de Alterações</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>Registra todas as ações: criou, editou, excluiu</span></li>
                                <li class="mb-1 d-flex gap-2"><i class="bi bi-check text-success mt-1 flex-shrink-0"></i><span>Mostra usuário, módulo, IP e data/hora</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- fim accordion --}}

</div>
