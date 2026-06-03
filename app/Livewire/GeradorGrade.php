<?php
// app/Livewire/GeradorGrade.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Horario, Sala, PeriodoLetivo, ProfessorDisciplina, Professor};
use App\Models\Log;
use Livewire\Component;

class GeradorGrade extends Component
{
    public string $curso_id             = '';
    public string $periodo_letivo_id    = '';
    public array  $turmasSelecionadas   = [];
    public bool   $previewGerado        = false;
    public bool   $salvando             = false;
    public array  $preview              = [];
    public array  $conflitos            = [];
    public array  $avisosSemSala        = [];
    public string $estrategiaUsada      = '';

    public bool  $aguardandoSelecao    = false;
    public array $pendentesSelecao     = [];
    public array $escolhasProfessores  = [];
    public array $turmasJaGeradas      = [];

    public function mount(): void
    {
        $ativo = PeriodoLetivo::where('ativo', true)->first();
        if ($ativo) $this->periodo_letivo_id = $ativo->id;
    }

    public function updatedCursoId(): void { $this->turmasSelecionadas = []; $this->resetPreview(); }
    public function updatedPeriodoLetivoId(): void { $this->verificarTurmasJaGeradas(); $this->resetPreview(); }
    public function updatedTurmasSelecionadas(): void { $this->verificarTurmasJaGeradas(); $this->resetPreview(); }

    private function verificarTurmasJaGeradas(): void
    {
        $this->turmasJaGeradas = [];
        if (empty($this->turmasSelecionadas) || !$this->periodo_letivo_id) return;
        foreach ($this->turmasSelecionadas as $turmaId) {
            if (Aula::where('turma_id', $turmaId)->where('periodo_letivo_id', $this->periodo_letivo_id)->exists()) {
                $turma = Turma::find($turmaId);
                $this->turmasJaGeradas[] = $turma->nome ?? "#$turmaId";
            }
        }
    }

    public function toggleTodasTurmas(): void
    {
        $query = Turma::where('ativo', true);
        if ($this->curso_id) $query->where('curso_id', $this->curso_id);
        $todos = $query->pluck('id')->toArray();
        $this->turmasSelecionadas = count($this->turmasSelecionadas) >= count($todos) ? [] : $todos;
        $this->verificarTurmasJaGeradas();
    }

    public function resetPreview(): void
    {
        $this->previewGerado       = false;
        $this->preview             = $this->conflitos = $this->avisosSemSala = [];
        $this->aguardandoSelecao   = false;
        $this->pendentesSelecao    = $this->escolhasProfessores = [];
        $this->estrategiaUsada     = '';
    }

    public function confirmarSelecao(): void
    {
        foreach ($this->pendentesSelecao as $p) {
            $key = $p['disciplina_id'].'_'.$p['turma_id'];
            if (empty($this->escolhasProfessores[$key])) {
                session()->flash('error', "Selecione um professor para {$p['disciplina_nome']}.");
                return;
            }
        }
        $this->aguardandoSelecao = false;
        $this->gerarPrevia(true);
    }

    public function cancelarSelecao(): void
    {
        $this->aguardandoSelecao = false;
        $this->pendentesSelecao  = $this->escolhasProfessores = [];
    }

    public function aceitarSugestao(int $professorId, int $turmaId, int $disciplinaId, array $diasSugeridos): void
    {
        $prof = Professor::find($professorId);
        if ($prof) {
            $disp = is_array($prof->disponibilidade) ? $prof->disponibilidade : (json_decode($prof->disponibilidade ?? '[]', true) ?? []);
            $prof->disponibilidade = array_values(array_unique(array_merge(array_map('intval', $disp), array_map('intval', $diasSugeridos))));
            $prof->save();
        }
        $this->gerarPrevia();
        session()->flash('success', 'Dia adicionado! Grade regerada.');
    }

    // ═══════════════════════════════════════════════════════
    //  GERAÇÃO — múltiplas varreduras + matching bipartido
    // ═══════════════════════════════════════════════════════
    public function gerarPrevia(bool $comSelecao = false): void
    {
        if (!$this->periodo_letivo_id || empty($this->turmasSelecionadas)) {
            session()->flash('error', 'Selecione um período letivo e ao menos uma turma.');
            return;
        }

        $horarios  = Horario::where('tipo', '!=', 'intervalo')->orderBy('hora_inicio')->get();
        $periodoId = $this->periodo_letivo_id;

        // Monta lista de tarefas (disciplina+turma+professor) + detecta múltiplos professores
        $montagem = $this->montarTarefas($periodoId, $comSelecao);
        if (!empty($montagem['pendentes']) && !$comSelecao) {
            $this->pendentesSelecao  = $montagem['pendentes'];
            $this->aguardandoSelecao = true;
            return;
        }
        $tarefas       = $montagem['tarefas'];
        $conflitosBase = $montagem['conflitos']; // turmas sem vínculo etc.

        $estrategias = [
            'Escassez (MRV)',
            'Mais disciplinas primeiro',
            'Varredura aleatória A',
            'Varredura aleatória B',
            'Varredura aleatória C',
        ];

        $melhorPreview = $melhorConflitos = $melhorAvisos = [];
        $melhorNome    = '';
        $menorConf     = PHP_INT_MAX;

        foreach ($estrategias as $idx => $nome) {
            $res = $this->executarVarreduraMatching($tarefas, $horarios, $periodoId, $idx);
            $totalConf = count($res['conflitos']);
            if ($totalConf < $menorConf) {
                $menorConf     = $totalConf;
                $melhorPreview = $res['preview'];
                $melhorConflitos = $res['conflitos'];
                $melhorAvisos  = $res['avisos'];
                $melhorNome    = $nome;
            }
            if ($menorConf === 0) break;
        }

        // ── REPARO GLOBAL: backtracking cross-professor ──
        if (!empty($melhorConflitos)) {
            $reparo = $this->repararGlobal($melhorPreview, $melhorConflitos, $horarios, $periodoId);
            $melhorPreview   = $reparo['preview'];
            $melhorConflitos = $reparo['conflitos'];
            if ($reparo['resolvidos'] > 0) {
                $melhorNome .= " + {$reparo['resolvidos']} resolvido(s) por reparo global";
            }
        }

        // Adiciona conflitos base (turmas sem vínculo)
        $melhorConflitos = array_merge($conflitosBase, $melhorConflitos);

        usort($melhorPreview, function ($a, $b) {
            if ($a['turma_nome'] !== $b['turma_nome']) return strcmp($a['turma_nome'], $b['turma_nome']);
            return $a['dia_semana'] <=> $b['dia_semana'];
        });
        usort($melhorConflitos, fn($a, $b) => strcmp($a['mensagem'], $b['mensagem']));

        $this->preview         = $melhorPreview;
        $this->conflitos       = $melhorConflitos;
        $this->avisosSemSala   = $melhorAvisos;
        $confFinal = count(array_filter($melhorConflitos, fn($c) => ($c['tipo'] ?? '') !== 'sem_vinculo'));
        $this->estrategiaUsada = $confFinal === 0
            ? "✅ Gerado sem conflitos — {$melhorNome}"
            : "⚠️ {$melhorNome} — {$confFinal} conflito(s) restante(s)";
        $this->previewGerado   = true;

        session()->flash('success', count($this->preview) . ' aula(s) gerada(s) na prévia.');
    }

    // ── Monta tarefas e detecta múltiplos professores ─────
    private function montarTarefas(string $periodoId, bool $comSelecao): array
    {
        $tarefas   = [];
        $pendentes = [];
        $conflitos = [];

        foreach ($this->turmasSelecionadas as $turmaId) {
            $turma = Turma::with('curso')->find($turmaId);
            if (!$turma) continue;

            $vinculos = ProfessorDisciplina::where('turma_id', $turmaId)
                ->with(['professor', 'disciplina'])->get()
                ->filter(fn($v) => !$v->disciplina->semestre_grade || (int)$v->disciplina->semestre_grade === (int)$turma->semestre);

            if ($vinculos->isEmpty()) {
                $conflitos[] = ['tipo' => 'sem_vinculo', 'mensagem' => "Turma {$turma->nome}: nenhum vínculo para o {$turma->semestre}º semestre.", 'dias_livres' => [], 'diagnostico' => []];
                continue;
            }

            $aulasExistentes = Aula::where('periodo_letivo_id', $periodoId)->where('turma_id', $turmaId)->get();
            $porDisciplina   = $vinculos->groupBy('disciplina_id');

            foreach ($porDisciplina as $discId => $grupo) {
                $disciplina = $grupo->first()->disciplina;
                if (!$disciplina) continue;
                if ($aulasExistentes->where('disciplina_id', $discId)->isNotEmpty()) continue;

                if ($grupo->count() > 1 && !$comSelecao) {
                    $key = $discId.'_'.$turmaId;
                    if (empty($this->escolhasProfessores[$key])) {
                        $pendentes[] = ['disciplina_id' => $discId, 'disciplina_nome' => $disciplina->nome, 'turma_id' => $turmaId, 'turma_nome' => $turma->nome, 'professores' => $grupo->map(fn($v) => ['id' => $v->professor_id, 'nome' => $v->professor->nome ?? '?'])->values()->toArray()];
                        continue;
                    }
                    $profId  = (int) $this->escolhasProfessores[$key];
                    $vinculo = $grupo->firstWhere('professor_id', $profId) ?? $grupo->first();
                } else {
                    $vinculo = $grupo->first();
                }

                if (!$vinculo->professor) continue;
                $tarefas[] = [
                    'disciplina_id' => $discId,
                    'disciplina'    => $disciplina->nome,
                    'tipo_sala'     => $disciplina->tipo_sala,
                    'bloco_pref'    => $disciplina->bloco_preferencial,
                    'sala_fixa'     => $disciplina->sala_id,
                    'turma_id'      => $turmaId,
                    'turma_nome'    => $turma->nome,
                    'professor_id'  => $vinculo->professor_id,
                    'professor'     => $vinculo->professor->nome,
                ];
            }
        }

        return compact('tarefas', 'pendentes', 'conflitos');
    }

    // ── Uma varredura: matching bipartido por professor ───
    private function executarVarreduraMatching(array $tarefas, $horarios, string $periodoId, int $estrategia): array
    {
        $preview = []; $conflitos = []; $avisos = [];
        $primeiroH = $horarios->first();
        $ultimoH   = $horarios->last();
        $nomeDias  = [1=>'SEG',2=>'TER',3=>'QUA',4=>'QUI',5=>'SEX'];

        // Mapas de ocupação (incluindo aulas já salvas no período)
        $ocupTurma = []; $ocupProfessor = []; $ocupSala = [];
        $todasAulas = Aula::where('periodo_letivo_id', $periodoId)->get();
        foreach ($todasAulas as $a) {
            $ocupTurma[$a->turma_id][$a->dia_semana] = true;
            $ocupProfessor[$a->dia_semana][$a->professor_id] = true;
            if ($a->sala_id) $ocupSala[$a->dia_semana][$a->horario_id][$a->sala_id] = true;
        }

        // Agrupa tarefas por professor
        $porProfessor = collect($tarefas)->groupBy('professor_id');

        // Ordena professores conforme estratégia
        $ordemProf = $this->ordenarProfessores($porProfessor, $estrategia);

        foreach ($ordemProf as $profId) {
            $tarefasProf = $porProfessor[$profId]->values()->toArray();
            $professor   = Professor::find($profId);
            $disp        = $professor->disponibilidade ?? [];
            $diasProf    = is_array($disp) ? $disp : (json_decode($disp ?? '[]', true) ?? []);
            $diasProf    = array_map('intval', $diasProf);

            // Monta dias possíveis para cada tarefa: dias do prof ∩ turma livre ∩ prof livre
            $availDays = [];
            foreach ($tarefasProf as $i => $t) {
                $availDays[$i] = [];
                foreach ($diasProf as $d) {
                    if (isset($ocupTurma[$t['turma_id']][$d])) continue;   // turma ocupada
                    if (isset($ocupProfessor[$d][$profId]))     continue;   // prof ocupado (aulas salvas)
                    $availDays[$i][] = $d;
                }
            }

            // ── MATCHING BIPARTIDO (caminhos aumentantes) ──
            $matching = $this->bipartiteMatch(count($tarefasProf), $availDays);

            // Aloca cada tarefa conforme o matching
            foreach ($tarefasProf as $i => $t) {
                $dia = $matching[$i] ?? null;

                if ($dia === null) {
                    // Sem dia → conflito com diagnóstico
                    $diag = [];
                    foreach ($diasProf as $d) {
                        if (isset($ocupTurma[$t['turma_id']][$d])) {
                            $discNoDia = collect($preview)->where('turma_id', $t['turma_id'])->where('dia_semana', $d)->first();
                            $diag[] = "{$nomeDias[$d]}: turma: " . ($discNoDia ? $discNoDia['disciplina'] : 'ocupada');
                        } elseif (isset($ocupProfessor[$d][$profId])) {
                            $ap = collect($preview)->where('professor_id', $profId)->where('dia_semana', $d)->first();
                            $diag[] = "{$nomeDias[$d]}: " . ($ap ? "{$ap['disciplina']} / {$ap['turma_nome']}" : 'outra turma');
                        }
                    }
                    // Dia fora da disponibilidade que esteja livre
                    $diasLivres = [];
                    foreach (range(1,5) as $d) {
                        if (in_array($d, $diasProf)) continue;
                        if (isset($ocupProfessor[$d][$profId]) || isset($ocupTurma[$t['turma_id']][$d])) continue;
                        $diasLivres = [['num' => $d, 'nome' => $nomeDias[$d]]];
                        break;
                    }
                    $conflitos[] = ['tipo' => 'sem_dia', 'mensagem' => "{$t['turma_nome']} — {$t['disciplina']} ({$t['professor']}): não há combinação de dias possível.", 'professor_id' => $profId, 'professor' => $t['professor'], 'turma_id' => $t['turma_id'], 'disciplina_id' => $t['disciplina_id'], 'disciplina' => $t['disciplina'], 'dias_livres' => $diasLivres, 'diagnostico' => $diag];
                    continue;
                }

                // Busca sala
                $sala = null; $salaId = null; $modalidade = 'presencial';
                if ($t['tipo_sala'] === 'Online') {
                    $modalidade = 'online';
                } elseif ($t['tipo_sala']) {
                    $salasOcup = [];
                    foreach ($horarios as $h) {
                        if (isset($ocupSala[$dia][$h->id])) $salasOcup = array_merge($salasOcup, array_keys($ocupSala[$dia][$h->id]));
                    }
                    $salasOcup = array_unique($salasOcup);

                    if (!empty($t['sala_fixa'])) {
                        if (!in_array($t['sala_fixa'], $salasOcup)) $sala = Sala::find($t['sala_fixa']);
                    } elseif ($t['bloco_pref']) {
                        $sala = Sala::where('ativo', true)->where('tipo', $t['tipo_sala'])->where('bloco', $t['bloco_pref'])->whereNotIn('id', $salasOcup)->first();
                    }
                    if (!$sala) $sala = Sala::where('ativo', true)->where('tipo', $t['tipo_sala'])->whereNotIn('id', $salasOcup)->first();
                    if (!$sala) $avisos[] = ['mensagem' => "{$t['turma_nome']} — {$t['disciplina']}: sem sala '{$t['tipo_sala']}'."];
                    $salaId = $sala?->id;
                }

                $horarioStr  = ($primeiroH ? substr($primeiroH->hora_inicio,0,5) : '--:--') . ' - ' . ($ultimoH ? substr($ultimoH->hora_fim,0,5) : '--:--');
                $preview[] = [
                    'periodo_id' => $periodoId, 'turma_id' => $t['turma_id'], 'turma_nome' => $t['turma_nome'],
                    'disciplina_id' => $t['disciplina_id'], 'disciplina' => $t['disciplina'],
                    'professor_id' => $profId, 'professor' => $t['professor'],
                    'horarios_ids' => $horarios->pluck('id')->toArray(), 'horario' => $horarioStr,
                    'dia_semana' => $dia, 'dia_nome' => $this->nomeDia($dia),
                    'sala_id' => $salaId, 'sala' => $sala?->nome ?? 'Sem sala', 'modalidade' => $modalidade,
                ];

                $ocupTurma[$t['turma_id']][$dia]   = true;
                $ocupProfessor[$dia][$profId]      = true;
                foreach ($horarios as $h) { if ($salaId) $ocupSala[$dia][$h->id][$salaId] = true; }
            }
        }

        return compact('preview', 'conflitos', 'avisos');
    }

    // ── Matching bipartido: tarefas ↔ dias (augmenting paths) ──
    private function bipartiteMatch(int $numTarefas, array $availDays): array
    {
        $matchDay = []; // dia => indice da tarefa
        $result   = array_fill(0, $numTarefas, null);

        for ($t = 0; $t < $numTarefas; $t++) {
            $visited = [];
            $this->tryAugment($t, $availDays, $matchDay, $visited);
        }

        foreach ($matchDay as $dia => $tarefaIdx) {
            $result[$tarefaIdx] = $dia;
        }
        return $result;
    }

    private function tryAugment(int $t, array $availDays, array &$matchDay, array &$visited): bool
    {
        foreach (($availDays[$t] ?? []) as $dia) {
            if (isset($visited[$dia])) continue;
            $visited[$dia] = true;
            // Dia livre OU o ocupante atual consegue outro dia
            if (!isset($matchDay[$dia]) || $this->tryAugment($matchDay[$dia], $availDays, $matchDay, $visited)) {
                $matchDay[$dia] = $t;
                return true;
            }
        }
        return false;
    }

    // ── Ordena professores por estratégia ──────────────────
    private function ordenarProfessores($porProfessor, int $estrategia): array
    {
        $profs = [];
        foreach ($porProfessor as $profId => $tarefas) {
            $p    = Professor::find($profId);
            $disp = $p->disponibilidade ?? [];
            $dias = is_array($disp) ? count($disp) : count(json_decode($disp ?? '[]', true) ?? []);
            $profs[$profId] = ['dias' => $dias, 'tarefas' => count($tarefas)];
        }

        $ids = array_keys($profs);
        usort($ids, function($a, $b) use ($profs, $estrategia) {
            return match($estrategia) {
                0 => $profs[$a]['dias'] <=> $profs[$b]['dias'],          // menos dias primeiro (MRV)
                1 => $profs[$b]['tarefas'] <=> $profs[$a]['tarefas'],    // mais disciplinas primeiro
                default => rand(-1, 1),                                  // aleatório
            };
        });
        return $ids;
    }


    // ═══════════════════════════════════════════════════════
    //  REPARO GLOBAL — backtracking recursivo cross-professor
    //  Move aulas de QUALQUER professor em cascata para
    //  resolver conflitos (CSP timetabling completo)
    // ═══════════════════════════════════════════════════════
    private array $gAssign;    // [turma_id][dia] = índice no gPreview
    private array $gProfBusy;  // [dia][professor_id] = turma_id
    private array $gPreview;
    private $gHorarios;
    private string $gPeriodo;

    private function repararGlobal(array $preview, array $conflitos, $horarios, string $periodoId): array
    {
        $this->gHorarios = $horarios;
        $this->gPeriodo  = $periodoId;
        $this->gPreview  = $preview;
        $this->gAssign   = [];
        $this->gProfBusy = [];

        foreach ($preview as $i => $item) {
            $this->gAssign[$item['turma_id']][$item['dia_semana']]   = $i;
            $this->gProfBusy[$item['dia_semana']][$item['professor_id']] = $item['turma_id'];
        }

        $resolvidos = 0;
        $restantes  = [];

        foreach ($conflitos as $conflito) {
            if (($conflito['tipo'] ?? '') !== 'sem_dia' || empty($conflito['professor_id'])) {
                $restantes[] = $conflito;
                continue;
            }

            $ok = $this->colocarGlobal(
                (int)$conflito['disciplina_id'],
                (int)$conflito['professor_id'],
                (int)$conflito['turma_id'],
                0, []
            );

            if ($ok) {
                $resolvidos++;
            } else {
                $restantes[] = $conflito;
            }
        }

        // Limpa itens removidos durante o reparo
        $previewLimpo = array_values(array_filter($this->gPreview, fn($it) => empty($it['_removido'])));

        return ['preview' => $previewLimpo, 'conflitos' => $restantes, 'resolvidos' => $resolvidos];
    }

    // ── Coloca uma disciplina movendo outras em cascata ────
    private function colocarGlobal(int $discId, int $profId, int $turmaId, int $depth, array $visitados): bool
    {
        if ($depth > 8) return false; // limite de profundidade

        $chave = "$turmaId-$discId";
        if (in_array($chave, $visitados)) return false;
        $visitados[] = $chave;

        $professor = Professor::find($profId);
        $disp      = $professor->disponibilidade ?? [];
        $diasProf  = is_array($disp) ? $disp : (json_decode($disp ?? '[]', true) ?? []);
        $diasProf  = array_map('intval', $diasProf);

        // CASO A: dia onde turma E professor estão livres → aloca direto
        foreach ($diasProf as $dia) {
            $dia = (int)$dia;
            if (!isset($this->gAssign[$turmaId][$dia]) && !isset($this->gProfBusy[$dia][$profId])) {
                $this->inserirGlobal($discId, $profId, $turmaId, $dia);
                return true;
            }
        }

        // CASO B: turma livre mas PROFESSOR ocupado → move aula do próprio professor
        foreach ($diasProf as $dia) {
            $dia = (int)$dia;
            if (isset($this->gAssign[$turmaId][$dia])) continue;     // turma ocupada, pula
            if (!isset($this->gProfBusy[$dia][$profId])) continue;   // prof livre, já tratado no caso A

            $turma2 = $this->gProfBusy[$dia][$profId];
            if ($turma2 == $turmaId) continue;
            $idx2 = $this->gAssign[$turma2][$dia] ?? null;
            if ($idx2 === null) continue;

            $item2 = $this->gPreview[$idx2];
            // Remove temporariamente
            $this->removerGlobal($idx2, $turma2, $dia, $item2['professor_id']);

            if ($this->colocarGlobal($item2['disciplina_id'], $item2['professor_id'], $turma2, $depth+1, $visitados)) {
                $this->inserirGlobal($discId, $profId, $turmaId, $dia);
                return true;
            }
            // Restaura
            $this->restaurarGlobal($idx2, $turma2, $dia, $item2['professor_id']);
        }

        // CASO C: professor livre mas TURMA ocupada → move aula de OUTRO professor
        foreach ($diasProf as $dia) {
            $dia = (int)$dia;
            if (isset($this->gProfBusy[$dia][$profId])) continue;    // prof ocupado, tratado no caso B
            if (!isset($this->gAssign[$turmaId][$dia])) continue;    // turma livre, tratado no caso A

            $idx3 = $this->gAssign[$turmaId][$dia];
            $item3 = $this->gPreview[$idx3];
            $prof3 = $item3['professor_id'];

            // Remove temporariamente a aula do outro professor
            $this->removerGlobal($idx3, $turmaId, $dia, $prof3);

            // Tenta realocar a disciplina do outro professor em outro lugar
            if ($this->colocarGlobal($item3['disciplina_id'], $prof3, $turmaId, $depth+1, $visitados)) {
                $this->inserirGlobal($discId, $profId, $turmaId, $dia);
                return true;
            }
            // Restaura
            $this->restaurarGlobal($idx3, $turmaId, $dia, $prof3);
        }

        return false;
    }

    private function inserirGlobal(int $discId, int $profId, int $turmaId, int $dia): void
    {
        $disciplina = \App\Models\Disciplina::find($discId);
        $professor  = Professor::find($profId);
        $turma      = Turma::find($turmaId);
        $primeiroH  = $this->gHorarios->first();
        $ultimoH    = $this->gHorarios->last();
        $horarioStr = ($primeiroH ? substr($primeiroH->hora_inicio,0,5) : '--:--') . ' - ' . ($ultimoH ? substr($ultimoH->hora_fim,0,5) : '--:--');

        $modalidade = ($disciplina && $disciplina->tipo_sala === 'Online') ? 'online' : 'presencial';
        $salaId = null; $salaNome = 'Sem sala';
        if ($disciplina && $disciplina->tipo_sala && $disciplina->tipo_sala !== 'Online') {
            $salasUsadas = collect($this->gPreview)->where('dia_semana', $dia)->where('_removido', '!=', true)->pluck('sala_id')->filter()->unique()->toArray();
            $q = Sala::where('ativo', true)->where('tipo', $disciplina->tipo_sala)->whereNotIn('id', $salasUsadas);
            if (!empty($disciplina->sala_id) && !in_array($disciplina->sala_id, $salasUsadas)) {
                $sala = Sala::find($disciplina->sala_id);
            } elseif ($disciplina->bloco_preferencial) {
                $sala = (clone $q)->where('bloco', $disciplina->bloco_preferencial)->first() ?? $q->first();
            } else {
                $sala = $q->first();
            }
            if ($sala) { $salaId = $sala->id; $salaNome = $sala->nome; }
        }

        $this->gPreview[] = [
            'periodo_id' => $this->gPeriodo, 'turma_id' => $turmaId, 'turma_nome' => $turma->nome ?? '',
            'disciplina_id' => $discId, 'disciplina' => $disciplina->nome ?? '',
            'professor_id' => $profId, 'professor' => $professor->nome ?? '',
            'horarios_ids' => $this->gHorarios->pluck('id')->toArray(), 'horario' => $horarioStr,
            'dia_semana' => $dia, 'dia_nome' => $this->nomeDia($dia),
            'sala_id' => $salaId, 'sala' => $salaNome, 'modalidade' => $modalidade,
        ];
        $novoIdx = count($this->gPreview) - 1;
        $this->gAssign[$turmaId][$dia]   = $novoIdx;
        $this->gProfBusy[$dia][$profId]  = $turmaId;
    }

    private function removerGlobal(int $idx, int $turmaId, int $dia, int $profId): void
    {
        $this->gPreview[$idx]['_removido'] = true;
        unset($this->gAssign[$turmaId][$dia]);
        unset($this->gProfBusy[$dia][$profId]);
    }

    private function restaurarGlobal(int $idx, int $turmaId, int $dia, int $profId): void
    {
        unset($this->gPreview[$idx]['_removido']);
        $this->gAssign[$turmaId][$dia]  = $idx;
        $this->gProfBusy[$dia][$profId] = $turmaId;
    }

    // ── Salvar Grade ───────────────────────────────────────
    public function salvarGrade(): void
    {
        if (empty($this->preview)) { session()->flash('error', 'Nenhuma aula para salvar.'); return; }
        $this->salvando = true;
        $count = 0;
        foreach ($this->preview as $item) {
            foreach ($item['horarios_ids'] as $horarioId) {
                Aula::updateOrCreate(
                    ['turma_id' => $item['turma_id'], 'disciplina_id' => $item['disciplina_id'], 'horario_id' => $horarioId, 'dia_semana' => $item['dia_semana'], 'periodo_letivo_id' => $item['periodo_id']],
                    ['professor_id' => $item['professor_id'], 'sala_id' => $item['sala_id'], 'modalidade' => $item['modalidade']]
                );
            }
            $count++;
        }
        Log::registrar('criou', 'Gerador de Grade', "Grade: {$count} slot(s) para ".count($this->turmasSelecionadas)." turma(s)");
        $this->salvando = false;
        $this->resetPreview();
        $this->turmasSelecionadas = [];   // limpa seleção após salvar
        $this->turmasJaGeradas    = [];   // remove o aviso
        session()->flash('success', "{$count} disciplina(s) alocada(s)! Acesse Grade de Horários para visualizar.");
    }

    public function limpar(): void
    {
        $this->curso_id = ''; $this->periodo_letivo_id = ''; $this->turmasSelecionadas = [];
        $this->turmasJaGeradas = [];
        $this->resetPreview();
    }

    private function nomeDia(int $num): string
    {
        return [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta'][$num] ?? "Dia $num";
    }

    public function render()
    {
        $cursos          = \App\Models\Curso::where('ativo', true)->orderBy('nome')->get();
        $turmas          = Turma::with('curso')->where('ativo', true)
            ->when($this->curso_id, fn($q) => $q->where('curso_id', $this->curso_id))
            ->orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')->get();
        $dias            = [1=>'SEG',2=>'TER',3=>'QUA',4=>'QUI',5=>'SEX'];
        return view('livewire.gerador-grade', compact('turmas', 'periodosLetivos', 'dias', 'cursos'));
    }
}
