<?php
// app/Livewire/GeradorGrade.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Horario, Sala, PeriodoLetivo, ProfessorDisciplina};
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

    public function mount(): void
    {
        $ativo = PeriodoLetivo::where('ativo', true)->first();
        if ($ativo) {
            $this->periodo_letivo_id = $ativo->id;
        }
    }

    public function updatedCursoId(): void
    {
        $this->turmasSelecionadas = [];
        $this->resetPreview();
    }

    public function updatedPeriodoLetivoId(): void { $this->resetPreview(); }

    public function toggleTodasTurmas(): void
    {
        $query = Turma::where('ativo', true);
        if ($this->curso_id) $query->where('curso_id', $this->curso_id);
        $todos = $query->pluck('id')->toArray();

        if (count($this->turmasSelecionadas) >= count($todos)) {
            $this->turmasSelecionadas = [];
        } else {
            $this->turmasSelecionadas = $todos;
        }
    }

    public function resetPreview(): void
    {
        $this->previewGerado = false;
        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];
    }

    // ── Aceitar sugestão de dia e regerar ─────────────────
    public function aceitarSugestao(int $professorId, int $turmaId, int $disciplinaId, array $diasSugeridos): void
    {
        $vinculo = \App\Models\ProfessorDisciplina::where('professor_id', $professorId)
            ->where('turma_id', $turmaId)
            ->where('disciplina_id', $disciplinaId)
            ->first();

        if ($vinculo) {
            $diasAtuais = is_array($vinculo->dias) ? $vinculo->dias : (json_decode($vinculo->dias ?? '[]', true) ?? []);
            $vinculo->dias = array_values(array_unique(array_merge(
                array_map('intval', $diasAtuais),
                array_map('intval', $diasSugeridos)
            )));
            $vinculo->save();
        }

        $professor = \App\Models\Professor::find($professorId);
        if ($professor) {
            $dispAtual = is_array($professor->disponibilidade)
                ? $professor->disponibilidade
                : (json_decode($professor->disponibilidade ?? '[]', true) ?? []);
            $professor->disponibilidade = array_values(array_unique(array_merge(
                array_map('intval', $dispAtual),
                array_map('intval', $diasSugeridos)
            )));
            $professor->save();
        }

        $this->gerarPrevia();
        session()->flash('success', 'Dias atualizados! Grade regerada automaticamente.');
    }

    // ── Gerar Prévia ───────────────────────────────────────
    public function gerarPrevia(): void
    {
        if (!$this->periodo_letivo_id || empty($this->turmasSelecionadas)) {
            session()->flash('error', 'Selecione um período letivo e ao menos uma turma.');
            return;
        }

        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];

        $horarios  = Horario::where('tipo', '!=', 'intervalo')->orderBy('hora_inicio')->get();
        $periodoId = $this->periodo_letivo_id;

        // Aulas já salvas no período (cross-turma professor + sala)
        $todasAulas = Aula::where('periodo_letivo_id', $periodoId)->get();

        // ── Mapas globais (persistem entre turmas desta geração) ──
        $ocupProfessor = []; // [dia][professor_id] = true
        $ocupSala      = []; // [dia][horario_id][sala_id] = true  ← SET para evitar duplicatas

        foreach ($todasAulas as $aula) {
            $dia = $aula->dia_semana;
            $ocupProfessor[$dia][$aula->professor_id] = true;
            if ($aula->sala_id) {
                // Registra sala como ocupada neste dia+horario
                $ocupSala[$dia][$aula->horario_id][$aula->sala_id] = true;
            }
        }

        // ── Processa cada turma ────────────────────────────────
        foreach ($this->turmasSelecionadas as $turmaId) {
            $turma = Turma::with('curso')->find($turmaId);
            if (!$turma) continue;

            $vinculos = ProfessorDisciplina::where('turma_id', $turmaId)
                ->with(['professor', 'disciplina'])
                ->get()
                ->filter(function($v) use ($turma) {
                    $semDisc = $v->disciplina->semestre_grade ?? null;
                    if (!$semDisc) return true;
                    return (int)$semDisc === (int)$turma->semestre;
                });

            if ($vinculos->isEmpty()) {
                $sem = $turma->semestre;
                $this->conflitos[] = [
                    'tipo'     => 'sem_vinculo',
                    'mensagem' => "Turma {$turma->nome}: nenhum vínculo de disciplina para o {$sem}º semestre.",
                ];
                continue;
            }

            $aulasExistentes     = Aula::where('periodo_letivo_id', $periodoId)->where('turma_id', $turmaId)->get();
            $ocupTurma           = [];
            $disciplinasAlocadas = [];

            foreach ($aulasExistentes as $aula) {
                $ocupTurma[$aula->dia_semana] = true;
                if ($aula->sala_id) {
                    $ocupSala[$aula->dia_semana][$aula->horario_id][$aula->sala_id] = true;
                }
            }

            foreach ($vinculos as $vinculo) {
                $professor  = $vinculo->professor;
                $disciplina = $vinculo->disciplina;
                if (!$professor || !$disciplina) continue;

                if ($aulasExistentes->where('disciplina_id', $disciplina->id)->isNotEmpty()) continue;
                if (isset($disciplinasAlocadas[$disciplina->id])) continue;

                // SEMPRE usa disponibilidade geral atual do professor
                $disp     = $professor->disponibilidade;
                $diasDisp = is_array($disp) ? $disp
                    : (is_string($disp) ? (json_decode($disp, true) ?? []) : []);

                if (empty($diasDisp)) {
                    $this->conflitos[] = [
                        'tipo'        => 'sem_dias',
                        'mensagem'    => "{$turma->nome} — Professor {$professor->nome} sem disponibilidade para {$disciplina->nome}.",
                        'professor_id'=> $professor->id,
                        'professor'   => $professor->nome,
                        'disciplina'  => $disciplina->nome,
                        'dias_livres' => [],
                    ];
                    continue;
                }

                $alocado = false;

                foreach ($diasDisp as $dia) {
                    $dia = (int) $dia;

                    if (isset($ocupTurma[$dia])) continue;

                    if (isset($ocupProfessor[$dia][$professor->id])) continue;

                    // Busca sala — FIX: coleta todas salas ocupadas neste dia (todos os horários)
                    $sala       = null;
                    $salaId     = null;
                    $modalidade = 'presencial';

                    if ($disciplina->tipo_sala === 'Online') {
                        $modalidade = 'online';
                    } elseif ($disciplina->tipo_sala) {
                        // Coleta IDs de salas já ocupadas NESTE DIA (qualquer horário)
                        $salasOcupadasNoDia = [];
                        foreach ($horarios as $h) {
                            if (isset($ocupSala[$dia][$h->id])) {
                                $salasOcupadasNoDia = array_merge(
                                    $salasOcupadasNoDia,
                                    array_keys($ocupSala[$dia][$h->id])
                                );
                            }
                        }
                        $salasOcupadasNoDia = array_unique($salasOcupadasNoDia);

                        // Tenta bloco preferencial
                        if ($disciplina->bloco_preferencial) {
                            $sala = Sala::where('ativo', true)
                                ->where('tipo', $disciplina->tipo_sala)
                                ->where('bloco', $disciplina->bloco_preferencial)
                                ->whereNotIn('id', $salasOcupadasNoDia)
                                ->first();
                        }
                        // Qualquer sala do tipo
                        if (!$sala) {
                            $sala = Sala::where('ativo', true)
                                ->where('tipo', $disciplina->tipo_sala)
                                ->whereNotIn('id', $salasOcupadasNoDia)
                                ->first();
                        }
                        if (!$sala) {
                            $this->avisosSemSala[] = [
                                'mensagem' => "{$turma->nome} — {$disciplina->nome}: sem sala '{$disciplina->tipo_sala}' disponível.",
                            ];
                        }
                        $salaId = $sala?->id;
                    }

                    foreach ($horarios as $h) {
                        $this->preview[] = [
                            'periodo_id'    => $periodoId,
                            'turma_id'      => $turmaId,
                            'turma_nome'    => $turma->nome,
                            'disciplina_id' => $disciplina->id,
                            'disciplina'    => $disciplina->nome,
                            'professor_id'  => $professor->id,
                            'professor'     => $professor->nome,
                            'horario_id'    => $h->id,
                            'horario'       => substr($h->hora_inicio,0,5).' - '.substr($h->hora_fim,0,5),
                            'dia_semana'    => $dia,
                            'dia_nome'      => $this->nomeDia($dia),
                            'sala_id'       => $salaId,
                            'sala'          => $sala?->nome ?? 'Sem sala',
                            'modalidade'    => $modalidade,
                        ];
                        $ocupTurma[$dia]                      = true;
                        $ocupProfessor[$dia][$professor->id]  = true;
                        if ($salaId) {
                            // Registra sala no conjunto deste dia+horario
                            $ocupSala[$dia][$h->id][$salaId] = true;
                        }
                    }

                    $disciplinasAlocadas[$disciplina->id] = $dia;
                    $alocado = true;
                    break;
                }

                if (!$alocado) {
                    // Sugere dia FORA da disponibilidade que esteja livre
                    $diasLivres = [];
                    $nomeDias   = [1=>'SEG',2=>'TER',3=>'QUA',4=>'QUI',5=>'SEX'];
                    $dispAtual  = array_map('intval', $diasDisp);

                    foreach (range(1, 5) as $d) {
                        if (in_array($d, $dispAtual)) continue; // já está na disponibilidade → pula
                        if (isset($ocupProfessor[$d][$professor->id])) continue;
                        if (isset($ocupTurma[$d])) continue;
                        $diasLivres = [['num' => $d, 'nome' => $nomeDias[$d]]];
                        break;
                    }

                    $this->conflitos[] = [
                        'tipo'          => 'sem_dia',
                        'mensagem'      => "{$turma->nome} — {$disciplina->nome} ({$professor->nome}): todos os dias disponíveis têm conflito.",
                        'professor_id'  => $professor->id,
                        'professor'     => $professor->nome,
                        'turma_id'      => $turmaId,
                        'disciplina_id' => $disciplina->id,
                        'disciplina'    => $disciplina->nome,
                        'dias_livres'   => $diasLivres,
                    ];
                }
            }
        }

        // Ordena prévia e conflitos
        usort($this->preview, function ($a, $b) {
            if ($a['turma_nome'] !== $b['turma_nome']) return strcmp($a['turma_nome'], $b['turma_nome']);
            if ($a['dia_semana']  !== $b['dia_semana'])  return $a['dia_semana'] <=> $b['dia_semana'];
            return $a['horario_id'] <=> $b['horario_id'];
        });

        usort($this->conflitos, fn($a, $b) => strcmp($a['mensagem'], $b['mensagem']));

        $this->previewGerado = true;
        session()->flash('success', count($this->preview) . ' aula(s) gerada(s) na prévia.');
    }

    // ── Salvar Grade ───────────────────────────────────────
    public function salvarGrade(): void
    {
        if (empty($this->preview)) {
            session()->flash('error', 'Nenhuma aula na prévia para salvar.');
            return;
        }

        $this->salvando = true;
        $count = 0;

        foreach ($this->preview as $item) {
            Aula::updateOrCreate(
                [
                    'turma_id'          => $item['turma_id'],
                    'disciplina_id'     => $item['disciplina_id'],
                    'horario_id'        => $item['horario_id'],
                    'dia_semana'        => $item['dia_semana'],
                    'periodo_letivo_id' => $item['periodo_id'],
                ],
                [
                    'professor_id' => $item['professor_id'],
                    'sala_id'      => $item['sala_id'],
                    'modalidade'   => $item['modalidade'],
                ]
            );
            $count++;
        }

        Log::registrar('criou', 'Gerador de Grade', "Grade gerada: {$count} aula(s) para ".count($this->turmasSelecionadas)." turma(s)");

        $this->salvando      = false;
        $this->previewGerado = false;
        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];

        session()->flash('success', "{$count} aula(s) salvas! Acesse Grade de Horários para visualizar.");
    }

    public function limpar(): void
    {
        $this->curso_id           = '';
        $this->periodo_letivo_id  = '';
        $this->turmasSelecionadas = [];
        $this->resetPreview();
    }

    private function nomeDia(int $num): string
    {
        return [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta'][$num] ?? "Dia {$num}";
    }

    public function render()
    {
        $cursos          = \App\Models\Curso::where('ativo', true)->orderBy('nome')->get();
        $turmas          = Turma::with('curso')
            ->where('ativo', true)
            ->when($this->curso_id, fn($q) => $q->where('curso_id', $this->curso_id))
            ->orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')->get();
        $dias            = [1=>'SEG',2=>'TER',3=>'QUA',4=>'QUI',5=>'SEX'];

        return view('livewire.gerador-grade', compact('turmas', 'periodosLetivos', 'dias', 'cursos'));
    }
}
