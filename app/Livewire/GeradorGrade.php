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

    // Seleção de professor quando há múltiplos
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
        $vinculo = ProfessorDisciplina::where('professor_id', $professorId)
            ->where('turma_id', $turmaId)->where('disciplina_id', $disciplinaId)->first();
        if ($vinculo) {
            $dias = is_array($vinculo->dias) ? $vinculo->dias : (json_decode($vinculo->dias ?? '[]', true) ?? []);
            $vinculo->dias = array_values(array_unique(array_merge(array_map('intval', $dias), array_map('intval', $diasSugeridos))));
            $vinculo->save();
        }
        $prof = Professor::find($professorId);
        if ($prof) {
            $disp = is_array($prof->disponibilidade) ? $prof->disponibilidade : (json_decode($prof->disponibilidade ?? '[]', true) ?? []);
            $prof->disponibilidade = array_values(array_unique(array_merge(array_map('intval', $disp), array_map('intval', $diasSugeridos))));
            $prof->save();
        }
        $this->gerarPrevia();
        session()->flash('success', 'Dias atualizados! Grade regerada.');
    }

    // ═══════════════════════════════════════════════════════
    //  GERAÇÃO COM MÚLTIPLAS VARREDURAS
    // ═══════════════════════════════════════════════════════
    public function gerarPrevia(bool $comSelecao = false): void
    {
        if (!$this->periodo_letivo_id || empty($this->turmasSelecionadas)) {
            session()->flash('error', 'Selecione um período letivo e ao menos uma turma.');
            return;
        }

        $horarios  = Horario::where('tipo', '!=', 'intervalo')->orderBy('hora_inicio')->get();
        $periodoId = $this->periodo_letivo_id;

        // Aulas já salvas (cross-turma)
        $todasAulas     = Aula::where('periodo_letivo_id', $periodoId)->get();
        $ocupBase       = $this->construirOcupBase($todasAulas);

        // ── 5 estratégias de ordenação ──────────────────────
        $estrategias = [
            'Escassez (menos dias primeiro)',
            'Mais turmas primeiro',
            'Varredura aleatória A',
            'Varredura aleatória B',
            'Varredura aleatória C',
        ];

        $melhorPreview     = [];
        $melhorConflitos   = [];
        $melhorAvisos      = [];
        $melhorNome        = '';
        $menorConflitos    = PHP_INT_MAX;
        $pendentes         = [];

        foreach ($estrategias as $idx => $nomeEstrategia) {
            $resultado = $this->executarVarredura(
                $periodoId, $horarios, $ocupBase, $idx, $comSelecao
            );

            // Coleta pendentes de seleção (múltiplos professores)
            if (!empty($resultado['pendentes'])) {
                $pendentes = $resultado['pendentes'];
            }

            $qtdConflitos = count($resultado['conflitos']);
            if ($qtdConflitos < $menorConflitos) {
                $menorConflitos  = $qtdConflitos;
                $melhorPreview   = $resultado['preview'];
                $melhorConflitos = $resultado['conflitos'];
                $melhorAvisos    = $resultado['avisos'];
                $melhorNome      = $nomeEstrategia;
            }

            if ($menorConflitos === 0) break; // solução perfeita!
        }

        // Há disciplinas com múltiplos professores para selecionar?
        if (!empty($pendentes) && !$comSelecao) {
            $this->pendentesSelecao  = $pendentes;
            $this->aguardandoSelecao = true;
            return;
        }

        // Ordena preview
        usort($melhorPreview, function ($a, $b) {
            if ($a['turma_nome'] !== $b['turma_nome']) return strcmp($a['turma_nome'], $b['turma_nome']);
            return $a['dia_semana'] <=> $b['dia_semana'];
        });
        usort($melhorConflitos, fn($a, $b) => strcmp($a['mensagem'], $b['mensagem']));

        $this->preview         = $melhorPreview;
        $this->conflitos       = $melhorConflitos;
        $this->avisosSemSala   = $melhorAvisos;
        $this->estrategiaUsada = $menorConflitos === 0
            ? "✅ Gerado sem conflitos — {$melhorNome}"
            : "⚠️ Melhor resultado de 5 varreduras — {$melhorNome} ({$menorConflitos} conflito(s))";
        $this->previewGerado   = true;

        session()->flash('success', count($this->preview) . ' aula(s) gerada(s) na prévia.');
    }

    // ── Executa uma varredura com uma estratégia ───────────
    private function executarVarredura(
        string $periodoId, $horarios, array $ocupBase, int $estrategia, bool $comSelecao
    ): array {
        $preview   = [];
        $conflitos = [];
        $avisos    = [];
        $pendentes = [];

        // Clona mapas de ocupação da base (aulas já salvas)
        $ocupProfessor = $ocupBase['professor'];
        $ocupSala      = $ocupBase['sala'];

        $primeiroH = $horarios->first();
        $ultimoH   = $horarios->last();
        $nomeDias  = [1=>'SEG',2=>'TER',3=>'QUA',4=>'QUI',5=>'SEX'];

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

            $porDisciplina       = $vinculos->groupBy('disciplina_id');
            $aulasExistentes     = Aula::where('periodo_letivo_id', $periodoId)->where('turma_id', $turmaId)->get();
            $ocupTurma           = [];
            $disciplinasAlocadas = [];

            foreach ($aulasExistentes as $aula) {
                $ocupTurma[$aula->dia_semana] = true;
                if ($aula->sala_id) $ocupSala[$aula->dia_semana][$aula->horario_id][$aula->sala_id] = true;
            }

            // Ordena por estratégia
            $porDisciplinaOrdenado = $this->ordenarPorEstrategia($porDisciplina, $estrategia);

            foreach ($porDisciplinaOrdenado as $discId => $grupoVinculos) {
                $disciplina = $grupoVinculos->first()->disciplina;
                if (!$disciplina) continue;
                if ($aulasExistentes->where('disciplina_id', $discId)->isNotEmpty()) continue;
                if (isset($disciplinasAlocadas[$discId])) continue;

                // Múltiplos professores?
                if ($grupoVinculos->count() > 1 && !$comSelecao) {
                    $key = $discId.'_'.$turmaId;
                    if (empty($this->escolhasProfessores[$key])) {
                        $pendentes[] = ['disciplina_id' => $discId, 'disciplina_nome' => $disciplina->nome, 'turma_id' => $turmaId, 'turma_nome' => $turma->nome, 'professores' => $grupoVinculos->map(fn($v) => ['id' => $v->professor_id, 'nome' => $v->professor->nome ?? '?'])->values()->toArray()];
                        continue;
                    }
                    $profId  = (int) $this->escolhasProfessores[$key];
                    $vinculo = $grupoVinculos->firstWhere('professor_id', $profId) ?? $grupoVinculos->first();
                } else {
                    $vinculo = $grupoVinculos->first();
                }

                $professor = $vinculo->professor;
                if (!$professor) continue;

                $disp     = $professor->disponibilidade;
                $diasDisp = is_array($disp) ? $disp : (is_string($disp) ? (json_decode($disp, true) ?? []) : []);

                if (empty($diasDisp)) {
                    $conflitos[] = ['tipo' => 'sem_dias', 'mensagem' => "{$turma->nome} — {$disciplina->nome} ({$professor->nome}): sem disponibilidade.", 'professor_id' => $professor->id, 'professor' => $professor->nome, 'turma_id' => $turmaId, 'disciplina_id' => $discId, 'disciplina' => $disciplina->nome, 'dias_livres' => [], 'diagnostico' => []];
                    continue;
                }

                $alocado = false;
                foreach ($diasDisp as $dia) {
                    $dia = (int) $dia;
                    if (isset($ocupTurma[$dia])) continue;
                    if (isset($ocupProfessor[$dia][$professor->id])) continue;

                    // Busca sala
                    $sala = null; $salaId = null; $modalidade = 'presencial';
                    if ($disciplina->tipo_sala === 'Online') {
                        $modalidade = 'online';
                    } elseif ($disciplina->tipo_sala) {
                        $salasOcupadas = [];
                        foreach ($horarios as $h) {
                            if (isset($ocupSala[$dia][$h->id])) $salasOcupadas = array_merge($salasOcupadas, array_keys($ocupSala[$dia][$h->id]));
                        }
                        $salasOcupadas = array_unique($salasOcupadas);

                        if (!empty($disciplina->sala_id)) {
                            if (!in_array($disciplina->sala_id, $salasOcupadas)) $sala = Sala::find($disciplina->sala_id);
                        } elseif ($disciplina->bloco_preferencial) {
                            $sala = Sala::where('ativo', true)->where('tipo', $disciplina->tipo_sala)->where('bloco', $disciplina->bloco_preferencial)->whereNotIn('id', $salasOcupadas)->first();
                        }
                        if (!$sala) $sala = Sala::where('ativo', true)->where('tipo', $disciplina->tipo_sala)->whereNotIn('id', $salasOcupadas)->first();
                        if (!$sala) $avisos[] = ['mensagem' => "{$turma->nome} — {$disciplina->nome}: sem sala '{$disciplina->tipo_sala}'."];
                        $salaId = $sala?->id;
                    }

                    $horarioStr  = ($primeiroH ? substr($primeiroH->hora_inicio,0,5) : '--:--') . ' - ' . ($ultimoH ? substr($ultimoH->hora_fim,0,5) : '--:--');
                    $horariosIds = $horarios->pluck('id')->toArray();

                    $preview[] = ['periodo_id' => $periodoId, 'turma_id' => $turmaId, 'turma_nome' => $turma->nome, 'disciplina_id' => $discId, 'disciplina' => $disciplina->nome, 'professor_id' => $professor->id, 'professor' => $professor->nome, 'horarios_ids' => $horariosIds, 'horario' => $horarioStr, 'dia_semana' => $dia, 'dia_nome' => $this->nomeDia($dia), 'sala_id' => $salaId, 'sala' => $sala?->nome ?? 'Sem sala', 'modalidade' => $modalidade];

                    $ocupTurma[$dia]                     = true;
                    $ocupProfessor[$dia][$professor->id] = true;
                    foreach ($horarios as $h) { if ($salaId) $ocupSala[$dia][$h->id][$salaId] = true; }
                    $disciplinasAlocadas[$discId] = $dia;
                    $alocado = true;
                    break;
                }

                if (!$alocado) {
                    $diagnostico = [];
                    $diasLivres  = [];
                    $dispAtual   = array_map('intval', $diasDisp);

                    foreach ($dispAtual as $d) {
                        $d = (int)$d;
                        if (isset($ocupTurma[$d])) {
                            $discNoDia = collect($preview)->where('turma_id', $turmaId)->where('dia_semana', $d)->first();
                            $diagnostico[] = "{$nomeDias[$d]}: turma: " . ($discNoDia ? $discNoDia['disciplina'] : 'ocupada');
                        } elseif (isset($ocupProfessor[$d][$professor->id])) {
                            $aulaProf = collect($preview)->where('professor_id', $professor->id)->where('dia_semana', $d)->first();
                            $diagnostico[] = "{$nomeDias[$d]}: " . ($aulaProf ? "{$aulaProf['disciplina']} / {$aulaProf['turma_nome']}" : 'outra turma');
                        }
                    }
                    foreach (range(1, 5) as $d) {
                        if (in_array($d, $dispAtual) || isset($ocupProfessor[$d][$professor->id]) || isset($ocupTurma[$d])) continue;
                        $diasLivres = [['num' => $d, 'nome' => $nomeDias[$d]]];
                        break;
                    }
                    $conflitos[] = ['tipo' => 'sem_dia', 'mensagem' => "{$turma->nome} — {$disciplina->nome} ({$professor->nome}): todos os dias têm conflito.", 'professor_id' => $professor->id, 'professor' => $professor->nome, 'turma_id' => $turmaId, 'disciplina_id' => $discId, 'disciplina' => $disciplina->nome, 'dias_livres' => $diasLivres, 'diagnostico' => $diagnostico];
                }
            }
        }

        return compact('preview', 'conflitos', 'avisos', 'pendentes');
    }

    // ── Ordena vinculos por estratégia ─────────────────────
    private function ordenarPorEstrategia($porDisciplina, int $estrategia)
    {
        return $porDisciplina->map(function($grupoVinculos) use ($estrategia) {
            $vinculo = $grupoVinculos->first();
            $disp    = $vinculo->professor->disponibilidade ?? [];
            $dias    = is_array($disp) ? count($disp) : count(json_decode($disp ?? '[]', true) ?? []);

            return match($estrategia) {
                0 => $dias,          // escassez (menos dias = primeiro)
                1 => -$dias,         // mais flexível primeiro (inverso)
                default => rand(),   // aleatório
            };
        })->sortKeys()->map(fn($score, $discId) => $porDisciplina[$discId]);
    }

    // ── Constrói mapa base de ocupação (aulas já salvas) ──
    private function construirOcupBase($todasAulas): array
    {
        $professor = [];
        $sala      = [];
        foreach ($todasAulas as $aula) {
            $dia = $aula->dia_semana;
            $professor[$dia][$aula->professor_id] = true;
            if ($aula->sala_id) $sala[$dia][$aula->horario_id][$aula->sala_id] = true;
        }
        return compact('professor', 'sala');
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
        $this->verificarTurmasJaGeradas();
        session()->flash('success', "{$count} disciplina(s) alocada(s)!");
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
