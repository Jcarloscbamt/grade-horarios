<?php
// app/Livewire/GeradorGrade.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Horario, Sala, PeriodoLetivo, ProfessorDisciplina};
use App\Models\Log;
use Livewire\Component;

class GeradorGrade extends Component
{
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

    public function updatedPeriodoLetivoId(): void { $this->resetPreview(); }

    public function toggleTodasTurmas(): void
    {
        $todos = Turma::where('ativo', true)->pluck('id')->toArray();
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

    public function gerarPrevia(): void
    {
        if (!$this->periodo_letivo_id || empty($this->turmasSelecionadas)) {
            session()->flash('error', 'Selecione um período letivo e ao menos uma turma.');
            return;
        }

        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];

        $horarios = Horario::where('tipo', '!=', 'intervalo')
            ->orderBy('hora_inicio')->get();

        $periodoId = $this->periodo_letivo_id;

        // Todas as aulas do período (conflito de professor entre turmas)
        $todasAulas = Aula::where('periodo_letivo_id', $periodoId)->get();

        // Mapa global de ocupação (persiste entre turmas desta geração)
        // Armazena CONJUNTO de professores por dia para verificar conflito corretamente
        $ocupProfessor = []; // [dia][professor_id] = true
        $ocupSala      = []; // [dia][horarioId] = sala_id

        foreach ($todasAulas as $aula) {
            $dia = $aula->dia_semana;
            $ocupProfessor[$dia][$aula->professor_id] = true; // marca professor no dia
            if ($aula->sala_id) {
                $ocupSala[$dia][$aula->horario_id] = $aula->sala_id;
            }
        }

        // Processa cada turma selecionada
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
                $this->conflitos[] = [
                    'tipo'     => 'sem_vinculo',
                    'mensagem' => "Turma {$turma->nome}: nenhum vínculo professor-disciplina cadastrado.",
                ];
                continue;
            }

            // Aulas já existentes desta turma (para evitar duplicidade)
            $aulasExistentes = Aula::where('periodo_letivo_id', $periodoId)
                ->where('turma_id', $turmaId)
                ->get();

            $ocupTurma           = []; // [dia] = true
            $disciplinasAlocadas = []; // [disciplinaId] = dia

            foreach ($aulasExistentes as $aula) {
                $ocupTurma[$aula->dia_semana] = true;
                if ($aula->sala_id) {
                    $ocupSala[$aula->dia_semana][$aula->horario_id] = $aula->sala_id;
                }
            }

            foreach ($vinculos as $vinculo) {
                $professor  = $vinculo->professor;
                $disciplina = $vinculo->disciplina;
                if (!$professor || !$disciplina) continue;

                // Disciplina já alocada no DB ou nesta geração?
                $jaExisteDb      = $aulasExistentes->where('disciplina_id', $disciplina->id)->isNotEmpty();
                $jaAlocadaPreview = isset($disciplinasAlocadas[$disciplina->id]);
                if ($jaExisteDb || $jaAlocadaPreview) continue;

                // Obtém dias disponíveis
                $rawDias  = $vinculo->dias;
                $diasDisp = is_array($rawDias) ? $rawDias
                    : (is_string($rawDias) ? (json_decode($rawDias, true) ?? []) : []);

                if (empty($diasDisp)) {
                    $disp = $professor->disponibilidade;
                    $diasDisp = is_array($disp) ? $disp
                        : (is_string($disp) ? (json_decode($disp, true) ?? []) : []);
                }

                if (empty($diasDisp)) {
                    $this->conflitos[] = [
                        'tipo'       => 'sem_dias',
                        'mensagem'   => "{$turma->nome} — Professor {$professor->nome} sem disponibilidade para {$disciplina->nome}.",
                    ];
                    continue;
                }

                $alocado = false;

                foreach ($diasDisp as $dia) {
                    $dia = (int) $dia;

                    if (isset($ocupTurma[$dia])) continue; // turma ocupada

                    // Verifica se este professor já está alocado neste dia (qualquer turma)
                    if (isset($ocupProfessor[$dia][$professor->id])) {
                        continue; // professor já leciona neste dia para outra turma
                    }

                    // Busca sala (Online não precisa de sala física)
                    $sala       = null;
                    $salaId     = null;
                    $modalidade = 'presencial';

                    if ($disciplina->tipo_sala === 'Online') {
                        // Aula online — sem sala física, modalidade = online
                        $modalidade = 'online';
                    } elseif ($disciplina->tipo_sala) {
                        $ocupadosNoDia = array_values(array_filter(
                            array_map(fn($h) => $ocupSala[$dia][$h->id] ?? null, $horarios->all())
                        ));

                        if ($disciplina->bloco_preferencial) {
                            $sala = Sala::where('ativo', true)
                                ->where('tipo', $disciplina->tipo_sala)
                                ->where('bloco', $disciplina->bloco_preferencial)
                                ->whereNotIn('id', $ocupadosNoDia)->first();
                        }
                        if (!$sala) {
                            $sala = Sala::where('ativo', true)
                                ->where('tipo', $disciplina->tipo_sala)
                                ->whereNotIn('id', $ocupadosNoDia)->first();
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
                        $ocupTurma[$dia]                       = true;
                        $ocupProfessor[$dia][$professor->id]  = true; // marca professor no dia
                        if ($salaId) $ocupSala[$dia][$h->id] = $salaId;
                    }

                    $disciplinasAlocadas[$disciplina->id] = $dia;
                    $alocado = true;
                    break;
                }

                if (!$alocado) {
                    $this->conflitos[] = [
                        'tipo'     => 'sem_dia',
                        'mensagem' => "{$turma->nome} — Não foi possível alocar {$disciplina->nome} ({$professor->nome}): todos os dias têm conflito.",
                    ];
                }
            }
        }

        // Ordena por turma, depois dia, depois horário
        usort($this->preview, function ($a, $b) {
            if ($a['turma_nome'] !== $b['turma_nome']) return strcmp($a['turma_nome'], $b['turma_nome']);
            if ($a['dia_semana']  !== $b['dia_semana'])  return $a['dia_semana'] <=> $b['dia_semana'];
            return $a['horario_id'] <=> $b['horario_id'];
        });

        $this->previewGerado = true;
        session()->flash('success', count($this->preview) . ' aula(s) gerada(s) na prévia.');
    }

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
        $turmas          = Turma::with('curso')->where('ativo', true)->orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')->get();
        $dias            = [1=>'SEG',2=>'TER',3=>'QUA',4=>'QUI',5=>'SEX'];

        return view('livewire.gerador-grade', compact('turmas', 'periodosLetivos', 'dias'));
    }
}
