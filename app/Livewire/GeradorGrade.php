<?php
// app/Livewire/GeradorGrade.php
namespace App\Livewire;

use App\Models\{Turma, PeriodoLetivo, Disciplina, Professor, Sala, Horario, Aula};
use App\Models\ProfessorDisciplina;
use App\Models\Log;
use Livewire\Component;

class GeradorGrade extends Component
{
    // ── Seleção inicial ───────────────────────────────────────────
    public string $turma_id          = '';
    public array  $periodos_selecionados = [];  // IDs dos períodos letivos

    // ── Estado do gerador ─────────────────────────────────────────
    public bool   $previewGerado     = false;
    public bool   $salvando          = false;

    // ── Prévia da grade: array[dia][horario_id] = dados da aula
    public array  $preview           = [];

    // ── Conflitos detectados
    public array  $conflitos         = [];

    // ── Aulas sem sala disponível (aviso, não bloqueante se aceito)
    public array  $avisosSemSala     = [];

    // ── Dias disponíveis para geração
    private array $diasNomes = [
        1 => 'SEG', 2 => 'TER', 3 => 'QUA', 4 => 'QUI', 5 => 'SEX',
    ];

    // ─────────────────────────────────────────────────────────────
    // Gerar prévia
    // ─────────────────────────────────────────────────────────────
    public function gerarPrevia(): void
    {
        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];
        $this->previewGerado = false;

        if (!$this->turma_id) {
            $this->addError('geral', 'Selecione uma turma.');
            return;
        }
        if (empty($this->periodos_selecionados)) {
            $this->addError('geral', 'Selecione pelo menos um período letivo.');
            return;
        }

        $turma    = Turma::with('curso')->find($this->turma_id);
        $horarios = Horario::where('tipo', '!=', 'intervalo')
            ->orderBy('hora_inicio')
            ->get();

        if ($horarios->isEmpty()) {
            $this->addError('geral', 'Nenhum horário de aula cadastrado.');
            return;
        }

        // Busca todos os vínculos professor→disciplina para esta turma
        $vinculos = ProfessorDisciplina::with(['professor', 'disciplina'])
            ->where('turma_id', $this->turma_id)
            ->get();

        if ($vinculos->isEmpty()) {
            $this->addError('geral', 'Nenhum professor vinculado a disciplinas desta turma. Cadastre os vínculos primeiro.');
            return;
        }

        // ── Algoritmo de alocação ─────────────────────────────────
        // Para cada período letivo selecionado, para cada vínculo,
        // tenta alocar a disciplina em um dia disponível do professor
        // sem conflitos.

        // grade_alocada[periodo_id][dia][horario_id] = dados
        $gradeAlocada   = [];
        // rastreamento de ocupação nesta geração
        $ocupProfessor  = []; // [periodo][dia][horario_id] = professor_id
        $ocupSala       = []; // [periodo][dia][horario_id] = sala_id
        $ocupTurma      = []; // [periodo][dia] = disciplina_nome (1 por dia)

        // Carrega aulas já existentes no banco para evitar conflitos com outros períodos/turmas
        $aulasExistentes = Aula::whereIn('periodo_letivo_id', $this->periodos_selecionados)->get();
        foreach ($aulasExistentes as $ae) {
            $pid = $ae->periodo_letivo_id;
            $dia = $ae->dia_semana;
            $hid = $ae->horario_id;
            if ($ae->professor_id) $ocupProfessor[$pid][$dia][$hid] = $ae->professor_id;
            if ($ae->sala_id)      $ocupSala[$pid][$dia][$hid]      = $ae->sala_id;
        }

        foreach ($this->periodos_selecionados as $periodoId) {
            $gradeAlocada[$periodoId] = [];

            foreach ($vinculos as $vinculo) {
                $professor  = $vinculo->professor;
                $disciplina = $vinculo->disciplina;
                $diasDisp   = $vinculo->dias_semana ?? [];

                if (empty($diasDisp)) {
                    $this->conflitos[] = [
                        'tipo'      => 'sem_dia',
                        'mensagem'  => "Professor <strong>{$professor->nome}</strong> não tem dias disponíveis para <strong>{$disciplina->nome}</strong>.",
                        'vinculo_id'=> $vinculo->id,
                    ];
                    continue;
                }

                $alocado = false;

                foreach ($diasDisp as $dia) {
                    // Verifica se a turma já tem disciplina neste dia neste período
                    if (isset($ocupTurma[$periodoId][$dia])) {
                        continue; // turma já ocupada neste dia
                    }

                    // Verifica conflito do professor em TODOS os horários do dia
                    $professorLivre = true;
                    foreach ($horarios as $h) {
                        if (isset($ocupProfessor[$periodoId][$dia][$h->id]) &&
                            $ocupProfessor[$periodoId][$dia][$h->id] != $professor->id) {
                            // outro professor já está aqui — mas pode ser o mesmo professor
                        }
                        if (isset($ocupProfessor[$periodoId][$dia][$h->id]) &&
                            $ocupProfessor[$periodoId][$dia][$h->id] == $professor->id) {
                            $professorLivre = false;
                            break;
                        }
                    }
                    if (!$professorLivre) {
                        continue;
                    }

                    // Tenta alocar sala
                    $salaAlocada = null;
                    $salaAviso   = false;

                    if ($disciplina->tipo_sala) {
                        $queryS = Sala::where('tipo', $disciplina->tipo_sala);
                        if ($disciplina->bloco_preferencial) {
                            // Tenta primeiro o bloco preferencial
                            $salaBloco = (clone $queryS)
                                ->where('bloco', $disciplina->bloco_preferencial)
                                ->get();
                            $todasSalas = $salaBloco->isNotEmpty()
                                ? $salaBloco->merge($queryS->get())
                                : $queryS->get();
                        } else {
                            $todasSalas = $queryS->get();
                        }

                        foreach ($todasSalas as $sala) {
                            $ocupada = false;
                            foreach ($horarios as $h) {
                                if (isset($ocupSala[$periodoId][$dia][$h->id]) &&
                                    $ocupSala[$periodoId][$dia][$h->id] == $sala->id) {
                                    $ocupada = true;
                                    break;
                                }
                            }
                            if (!$ocupada) {
                                $salaAlocada = $sala;
                                break;
                            }
                        }

                        if (!$salaAlocada) {
                            $salaAviso = true;
                        }
                    }

                    // Aloca em todos os horários de aula deste dia
                    foreach ($horarios as $h) {
                        $gradeAlocada[$periodoId][$dia][$h->id] = [
                            'disciplina_id'   => $disciplina->id,
                            'disciplina_nome' => $disciplina->nome,
                            'professor_id'    => $professor->id,
                            'professor_nome'  => $professor->nome,
                            'sala_id'         => $salaAlocada?->id,
                            'sala_nome'       => $salaAlocada
                                ? ($salaAlocada->nome . ' — ' . $salaAlocada->tipo . ($salaAlocada->bloco ? ' / Bloco ' . $salaAlocada->bloco : ''))
                                : null,
                            'horario_id'      => $h->id,
                            'dia'             => $dia,
                            'periodo_id'      => $periodoId,
                        ];
                        $ocupProfessor[$periodoId][$dia][$h->id] = $professor->id;
                        if ($salaAlocada) {
                            $ocupSala[$periodoId][$dia][$h->id] = $salaAlocada->id;
                        }
                    }

                    $ocupTurma[$periodoId][$dia] = $disciplina->nome;

                    if ($salaAviso) {
                        $this->avisosSemSala[] = [
                            'mensagem' => "Disciplina <strong>{$disciplina->nome}</strong> no dia {$this->diasNomes[$dia]}: "
                                . "nenhuma sala do tipo <em>{$disciplina->tipo_sala}</em>"
                                . ($disciplina->bloco_preferencial ? " / Bloco {$disciplina->bloco_preferencial}" : '')
                                . " disponível. Aula será salva sem sala.",
                        ];
                    }

                    $alocado = true;
                    break;
                }

                if (!$alocado) {
                    $diasStr = implode(', ', array_map(fn($d) => $this->diasNomes[$d] ?? $d, $diasDisp));
                    $this->conflitos[] = [
                        'tipo'      => 'sem_slot',
                        'mensagem'  => "Não foi possível alocar <strong>{$disciplina->nome}</strong> "
                            . "(Prof. {$professor->nome}) — todos os dias disponíveis ({$diasStr}) "
                            . "estão ocupados no período.",
                        'vinculo_id'=> $vinculo->id,
                    ];
                }
            }
        }

        $this->preview       = $gradeAlocada;
        $this->previewGerado = true;
        $this->resetValidation();
    }

    // ─────────────────────────────────────────────────────────────
    // Confirmar e Salvar
    // ─────────────────────────────────────────────────────────────
    public function confirmarSalvar(): void
    {
        if (!empty($this->conflitos)) {
            $this->addError('geral', 'Resolva os conflitos antes de salvar.');
            return;
        }
        if (empty($this->preview)) {
            $this->addError('geral', 'Nenhuma grade gerada para salvar.');
            return;
        }

        $horariosTodos = Horario::orderBy('hora_inicio')->get();
        $criadas = 0;

        foreach ($this->preview as $periodoId => $diasGrade) {
            foreach ($diasGrade as $dia => $horarios) {
                foreach ($horarios as $horarioId => $dados) {
                    // Inclui também o horário de intervalo para o mesmo dia/disciplina
                    Aula::firstOrCreate(
                        [
                            'turma_id'          => $this->turma_id,
                            'disciplina_id'     => $dados['disciplina_id'],
                            'horario_id'        => $horarioId,
                            'dia_semana'        => $dia,
                            'periodo_letivo_id' => $periodoId,
                        ],
                        [
                            'professor_id' => $dados['professor_id'],
                            'sala_id'      => $dados['sala_id'],
                            'modalidade'   => 'presencial',
                        ]
                    );
                    $criadas++;
                }

                // Gera também os horários de intervalo para este dia (sem sala/professor obrigatório)
                $intervalos = $horariosTodos->where('tipo', 'intervalo');
                foreach ($intervalos as $h) {
                    // Pega o primeiro dado do dia para disciplina/professor
                    $primeiroDado = reset($horarios);
                    Aula::firstOrCreate(
                        [
                            'turma_id'          => $this->turma_id,
                            'disciplina_id'     => $primeiroDado['disciplina_id'],
                            'horario_id'        => $h->id,
                            'dia_semana'        => $dia,
                            'periodo_letivo_id' => $periodoId,
                        ],
                        [
                            'professor_id' => $primeiroDado['professor_id'],
                            'sala_id'      => null,
                            'modalidade'   => 'presencial',
                        ]
                    );
                }
            }
        }

        $turma = Turma::find($this->turma_id);
        Log::registrar('criou', 'GeradorGrade', "Grade automática: {$criadas} aulas para turma {$turma->nome}");

        session()->flash('success', "Grade gerada com sucesso! {$criadas} aulas criadas.");

        $this->turma_id              = '';
        $this->periodos_selecionados = [];
        $this->preview               = [];
        $this->conflitos             = [];
        $this->avisosSemSala         = [];
        $this->previewGerado         = false;
    }

    // ─────────────────────────────────────────────────────────────
    // Limpar
    // ─────────────────────────────────────────────────────────────
    public function limpar(): void
    {
        $this->turma_id              = '';
        $this->periodos_selecionados = [];
        $this->preview               = [];
        $this->conflitos             = [];
        $this->avisosSemSala         = [];
        $this->previewGerado         = false;
        $this->resetValidation();
    }

    // ─────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────
    public function render()
    {
        $turmas          = Turma::with('curso')->orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')->get();
        $horarios        = Horario::orderBy('hora_inicio')->get();
        $diasNomes       = $this->diasNomes;

        return view('livewire.gerador-grade', compact(
            'turmas', 'periodosLetivos', 'horarios', 'diasNomes'
        ));
    }
}
