<?php
// app/Livewire/GeradorGrade.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Horario, Sala, PeriodoLetivo, ProfessorDisciplina};
use App\Models\Log;
use Livewire\Component;

class GeradorGrade extends Component
{
    public string $turma_id           = '';
    public array  $periodos_selecionados = [];
    public bool   $previewGerado      = false;
    public bool   $salvando           = false;
    public array  $preview            = [];
    public array  $conflitos          = [];
    public array  $avisosSemSala      = [];

    public function mount(): void
    {
        $ativo = PeriodoLetivo::where('ativo', true)->first();
        if ($ativo) {
            $this->periodos_selecionados = [$ativo->id];
        }
    }

    public function togglePeriodo(int $id): void
    {
        if (in_array($id, $this->periodos_selecionados)) {
            $this->periodos_selecionados = array_values(
                array_filter($this->periodos_selecionados, fn($p) => $p != $id)
            );
        } else {
            $this->periodos_selecionados[] = $id;
        }
        $this->resetPreview();
    }

    public function resetPreview(): void
    {
        $this->previewGerado   = false;
        $this->preview         = [];
        $this->conflitos       = [];
        $this->avisosSemSala   = [];
    }

    public function updatedTurmaId(): void
    {
        $this->resetPreview();
    }

    // ────────────────────────────────────────────────────────
    //  GERAR PRÉVIA
    // ────────────────────────────────────────────────────────
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

        // Vínculos professor-disciplina para esta turma
        $vinculos = ProfessorDisciplina::where('turma_id', $this->turma_id)
            ->with(['professor', 'disciplina'])
            ->get();

        if ($vinculos->isEmpty()) {
            session()->flash('error', 'Nenhum vínculo professor-disciplina cadastrado para esta turma.');
            return;
        }

        // Aulas da turma selecionada (para evitar duplicidade)
        $aulasExistentes = Aula::whereIn('periodo_letivo_id', $this->periodos_selecionados)
            ->where('turma_id', $this->turma_id)
            ->get();

        // Todas as aulas do período (para detectar conflito de professor entre turmas)
        $todasAulas = Aula::whereIn('periodo_letivo_id', $this->periodos_selecionados)->get();

        // Mapas de ocupação por período
        $ocupTurma    = []; // [periodoId][dia] = true  (apenas turma selecionada)
        $ocupProfessor = []; // [periodoId][dia] = professor_id  (TODAS as turmas)
        $ocupSala      = []; // [periodoId][dia][horarioId] = sala_id

        foreach ($aulasExistentes as $aula) {
            $pid = $aula->periodo_letivo_id;
            $dia = $aula->dia_semana;
            $hid = $aula->horario_id;
            $ocupTurma[$pid][$dia] = true;
            if ($aula->sala_id) {
                $ocupSala[$pid][$dia][$hid] = $aula->sala_id;
            }
        }

        // Mapeia ocupação de professores em TODAS as turmas (regra: mesmo dia = conflito)
        foreach ($todasAulas as $aula) {
            $pid = $aula->periodo_letivo_id;
            $dia = $aula->dia_semana;
            // Se professor já tem aula em qualquer turma neste dia, marca como ocupado
            if (!isset($ocupProfessor[$pid][$dia])) {
                $ocupProfessor[$pid][$dia] = $aula->professor_id;
            }
            if ($aula->sala_id) {
                $hid = $aula->horario_id;
                $ocupSala[$pid][$dia][$hid] = $aula->sala_id;
            }
        }

        foreach ($this->periodos_selecionados as $periodoId) {
            foreach ($vinculos as $vinculo) {
                $professor  = $vinculo->professor;
                $disciplina = $vinculo->disciplina;

                if (!$professor || !$disciplina) continue;

                // Obtém dias disponíveis — garante que seja sempre array
                $rawDias = $vinculo->dias;
                if (is_string($rawDias)) {
                    $diasDisp = json_decode($rawDias, true) ?? [];
                } elseif (is_array($rawDias)) {
                    $diasDisp = $rawDias;
                } else {
                    $diasDisp = [];
                }

                if (empty($diasDisp)) {
                    $this->conflitos[] = [
                        'tipo'       => 'sem_dias',
                        'mensagem'   => "Professor {$professor->nome} não tem dias configurados para {$disciplina->nome}.",
                        'professor'  => $professor->nome,
                        'disciplina' => $disciplina->nome,
                    ];
                    continue;
                }

                // Verifica se já existe aula para esta disciplina neste período
                $jaExiste = $aulasExistentes->where('disciplina_id', $disciplina->id)
                    ->where('periodo_letivo_id', $periodoId)
                    ->isNotEmpty();

                if ($jaExiste) continue; // já gerado, pula

                $alocado = false;

                foreach ($diasDisp as $dia) {
                    $dia = (int) $dia;

                    // Verifica se a turma já tem disciplina neste dia neste período
                    if (isset($ocupTurma[$periodoId][$dia])) {
                        continue; // turma já ocupada neste dia
                    }

                    // Verifica conflito do professor NO DIA (qualquer turma)
                    // Regra: professor não pode dar aula no mesmo dia para turmas diferentes
                    $professorLivre = true;
                    if (isset($ocupProfessor[$periodoId][$dia]) &&
                        $ocupProfessor[$periodoId][$dia] != $professor->id) {
                        $professorLivre = false;
                    }

                    if (!$professorLivre) {
                        $this->conflitos[] = [
                            'tipo'       => 'professor_ocupado',
                            'mensagem'   => "Professor {$professor->nome} já tem aula no dia {$this->nomeDia($dia)} (período {$periodoId}).",
                            'professor'  => $professor->nome,
                            'disciplina' => $disciplina->nome,
                            'dia'        => $this->nomeDia($dia),
                        ];
                        continue;
                    }

                    // Busca sala disponível
                    $sala   = null;
                    $salaId = null;

                    if ($disciplina->tipo_sala) {
                        $salaQuery = Sala::where('ativo', true)
                            ->where('tipo', $disciplina->tipo_sala);

                        if ($disciplina->bloco_preferencial) {
                            // Tenta bloco preferencial primeiro
                            $salaPreferencial = (clone $salaQuery)
                                ->where('bloco', $disciplina->bloco_preferencial)
                                ->whereNotIn('id', array_values(
                                    array_map(fn($h) => $ocupSala[$periodoId][$dia][$h->id] ?? 0, $horarios->all())
                                ))
                                ->first();
                            $sala = $salaPreferencial;
                        }

                        if (!$sala) {
                            // Qualquer sala do tipo
                            $sala = $salaQuery
                                ->whereNotIn('id', array_filter(array_map(
                                    fn($h) => $ocupSala[$periodoId][$dia][$h->id] ?? null,
                                    $horarios->all()
                                )))
                                ->first();
                        }
                    }

                    if (!$sala && $disciplina->tipo_sala) {
                        $this->avisosSemSala[] = [
                            'disciplina' => $disciplina->nome,
                            'dia'        => $this->nomeDia($dia),
                            'mensagem'   => "Nenhuma sala do tipo '{$disciplina->tipo_sala}' disponível.",
                        ];
                    }

                    $salaId = $sala?->id;

                    // Adiciona na prévia — uma entrada por horário do dia
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

                        // Marca como ocupado (dia inteiro para este professor e turma)
                        $ocupTurma[$periodoId][$dia]      = true;
                        $ocupProfessor[$periodoId][$dia]  = $professor->id;
                        if ($salaId) {
                            $ocupSala[$periodoId][$dia][$h->id] = $salaId;
                        }
                    }

                    $alocado = true;
                    break; // alocou neste dia, vai para próxima disciplina
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

    // ────────────────────────────────────────────────────────
    //  SALVAR GRADE
    // ────────────────────────────────────────────────────────
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
                    'turma_id'        => $item['turma_id'],
                    'disciplina_id'   => $item['disciplina_id'],
                    'horario_id'      => $item['horario_id'],
                    'dia_semana'      => $item['dia_semana'],
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

        Log::registrar('criou', 'Gerador de Grade', "Grade gerada: {$count} aula(s) criadas para turma {$item['turma_nome']}");

        $this->salvando      = false;
        $this->previewGerado = false;
        $this->preview       = [];
        $this->conflitos     = [];
        $this->avisosSemSala = [];

        session()->flash('success', "{$count} aula(s) salvas com sucesso! Acesse Grade de Horários para visualizar.");
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

        // Dias para exibição na prévia
        $dias = [1=>'SEG',2=>'TER',3=>'QUA',4=>'QUI',5=>'SEX'];

        return view('livewire.gerador-grade', compact('turmas', 'periodosLetivos', 'dias'));
    }
}
