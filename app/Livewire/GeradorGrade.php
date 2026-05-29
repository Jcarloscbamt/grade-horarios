<?php
// app/Livewire/GeradorGrade.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Horario, Sala, PeriodoLetivo, ProfessorDisciplina};
use App\Models\Log;
use Livewire\Component;

class GeradorGrade extends Component
{
    public string $turma_id              = '';
    public array  $periodos_selecionados = [];
    public bool   $previewGerado         = false;
    public bool   $salvando              = false;
    public array  $preview               = [];
    public array  $conflitos             = [];
    public array  $avisosSemSala         = [];

    public function mount(): void
    {
        $ativo = PeriodoLetivo::where('ativo', true)->first();
        if ($ativo) {
            $this->periodos_selecionados = [$ativo->id];
        }
    }

    public function updatedTurmaId(): void { $this->resetPreview(); }

    public function resetPreview(): void
    {
        $this->previewGerado = false;
        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];
    }

    public function gerarPrevia(): void
    {
        if (!$this->turma_id || empty($this->periodos_selecionados)) {
            session()->flash('error', 'Selecione uma turma e ao menos um período letivo.');
            return;
        }

        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];

        $turma    = Turma::with('curso')->findOrFail($this->turma_id);
        $horarios = Horario::where('tipo', '!=', 'intervalo')
            ->orderBy('hora_inicio')->get();

        $vinculos = ProfessorDisciplina::where('turma_id', $this->turma_id)
            ->with(['professor', 'disciplina'])
            ->get();

        if ($vinculos->isEmpty()) {
            session()->flash('error', 'Nenhum vínculo professor-disciplina cadastrado para esta turma.');
            return;
        }

        // Aulas da turma (para evitar duplicidade)
        $aulasExistentes = Aula::whereIn('periodo_letivo_id', $this->periodos_selecionados)
            ->where('turma_id', $this->turma_id)
            ->get();

        // Todas as aulas do período (para conflito de professor entre turmas)
        $todasAulas = Aula::whereIn('periodo_letivo_id', $this->periodos_selecionados)->get();

        $ocupTurma    = []; // [periodoId][dia] = true
        $ocupProfessor = []; // [periodoId][dia] = professor_id (TODAS turmas)
        $ocupSala      = []; // [periodoId][dia][horarioId] = sala_id

        foreach ($aulasExistentes as $aula) {
            $ocupTurma[$aula->periodo_letivo_id][$aula->dia_semana] = true;
            if ($aula->sala_id) {
                $ocupSala[$aula->periodo_letivo_id][$aula->dia_semana][$aula->horario_id] = $aula->sala_id;
            }
        }

        foreach ($todasAulas as $aula) {
            $pid = $aula->periodo_letivo_id;
            $dia = $aula->dia_semana;
            if (!isset($ocupProfessor[$pid][$dia])) {
                $ocupProfessor[$pid][$dia] = $aula->professor_id;
            }
            if ($aula->sala_id) {
                $ocupSala[$pid][$dia][$aula->horario_id] = $aula->sala_id;
            }
        }

        foreach ($this->periodos_selecionados as $periodoId) {
            foreach ($vinculos as $vinculo) {
                $professor  = $vinculo->professor;
                $disciplina = $vinculo->disciplina;

                if (!$professor || !$disciplina) continue;

                // Obtém dias — garante array
                $rawDias = $vinculo->dias;
                if (is_string($rawDias)) {
                    $diasDisp = json_decode($rawDias, true) ?? [];
                } elseif (is_array($rawDias)) {
                    $diasDisp = $rawDias;
                } else {
                    $diasDisp = [];
                }

                // Fallback: usa disponibilidade geral do professor
                if (empty($diasDisp)) {
                    $dispGeral = $professor->disponibilidade;
                    if (is_string($dispGeral)) {
                        $diasDisp = json_decode($dispGeral, true) ?? [];
                    } elseif (is_array($dispGeral)) {
                        $diasDisp = $dispGeral;
                    }
                }

                if (empty($diasDisp)) {
                    $this->conflitos[] = [
                        'tipo'       => 'sem_dias',
                        'mensagem'   => "Professor {$professor->nome} não tem disponibilidade configurada.",
                        'professor'  => $professor->nome,
                        'disciplina' => $disciplina->nome,
                    ];
                    continue;
                }

                // Já existe aula para esta disciplina neste período?
                $jaExiste = $aulasExistentes
                    ->where('disciplina_id', $disciplina->id)
                    ->where('periodo_letivo_id', $periodoId)
                    ->isNotEmpty();

                if ($jaExiste) continue;

                $alocado = false;

                foreach ($diasDisp as $dia) {
                    $dia = (int) $dia;

                    // Turma já ocupada neste dia?
                    if (isset($ocupTurma[$periodoId][$dia])) continue;

                    // Professor já tem aula em outra turma neste dia?
                    if (isset($ocupProfessor[$periodoId][$dia]) &&
                        $ocupProfessor[$periodoId][$dia] != $professor->id) {
                        $this->conflitos[] = [
                            'tipo'       => 'professor_ocupado',
                            'mensagem'   => "Professor {$professor->nome} já tem aula no dia {$this->nomeDia($dia)} (outra turma).",
                            'professor'  => $professor->nome,
                            'disciplina' => $disciplina->nome,
                            'dia'        => $this->nomeDia($dia),
                        ];
                        continue;
                    }

                    // Busca sala
                    $sala   = null;
                    $salaId = null;

                    if ($disciplina->tipo_sala) {
                        $ocupadosNoDia = array_values(array_filter(
                            array_map(fn($h) => $ocupSala[$periodoId][$dia][$h->id] ?? null, $horarios->all())
                        ));

                        if ($disciplina->bloco_preferencial) {
                            $sala = Sala::where('ativo', true)
                                ->where('tipo', $disciplina->tipo_sala)
                                ->where('bloco', $disciplina->bloco_preferencial)
                                ->whereNotIn('id', $ocupadosNoDia)
                                ->first();
                        }

                        if (!$sala) {
                            $sala = Sala::where('ativo', true)
                                ->where('tipo', $disciplina->tipo_sala)
                                ->whereNotIn('id', $ocupadosNoDia)
                                ->first();
                        }
                    }

                    if (!$sala && $disciplina->tipo_sala) {
                        $this->avisosSemSala[] = [
                            'disciplina' => $disciplina->nome,
                            'dia'        => $this->nomeDia($dia),
                            'mensagem'   => "Sem sala do tipo '{$disciplina->tipo_sala}' disponível — aula criada sem sala.",
                        ];
                    }

                    $salaId = $sala?->id;

                    foreach ($horarios as $h) {
                        $this->preview[] = [
                            'periodo_id'    => $periodoId,
                            'turma_id'      => (int) $this->turma_id,
                            'turma_nome'    => $turma->nome,
                            'disciplina_id' => $disciplina->id,
                            'disciplina'    => $disciplina->nome,
                            'professor_id'  => $professor->id,
                            'professor'     => $professor->nome,
                            'horario_id'    => $h->id,
                            'horario'       => substr($h->hora_inicio,0,5) . ' - ' . substr($h->hora_fim,0,5),
                            'dia_semana'    => $dia,
                            'dia_nome'      => $this->nomeDia($dia),
                            'sala_id'       => $salaId,
                            'sala'          => $sala?->nome ?? 'Sem sala',
                            'modalidade'    => 'presencial',
                        ];

                        $ocupTurma[$periodoId][$dia]      = true;
                        $ocupProfessor[$periodoId][$dia]  = $professor->id;
                        if ($salaId) {
                            $ocupSala[$periodoId][$dia][$h->id] = $salaId;
                        }
                    }

                    $alocado = true;
                    break;
                }

                if (!$alocado) {
                    $this->conflitos[] = [
                        'tipo'       => 'sem_dia_disponivel',
                        'mensagem'   => "Não foi possível alocar {$disciplina->nome} ({$professor->nome}): todos os dias têm conflito.",
                        'professor'  => $professor->nome,
                        'disciplina' => $disciplina->nome,
                    ];
                }
            }
        }

        // Ordena por dia da semana (1=SEG → 5=SEX) e depois por horário
        usort($this->preview, function ($a, $b) {
            if ($a['dia_semana'] !== $b['dia_semana']) {
                return $a['dia_semana'] <=> $b['dia_semana'];
            }
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
        $count          = 0;

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

        Log::registrar('criou', 'Gerador de Grade', "Grade gerada: {$count} aula(s) para turma {$this->preview[0]['turma_nome']}");

        $this->salvando      = false;
        $this->previewGerado = false;
        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];

        session()->flash('success', "{$count} aula(s) salvas! Acesse Grade de Horários para visualizar.");
    }

    public function limpar(): void
    {
        $this->turma_id             = '';
        $this->periodos_selecionados = [];
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
