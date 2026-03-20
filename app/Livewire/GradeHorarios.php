<?php
// app/Livewire/GradeHorarios.php
namespace App\Livewire;

use App\Models\Aula;
use App\Models\Turma;
use App\Models\PeriodoLetivo;
use Livewire\Component;

class GradeHorarios extends Component
{
    public string $turma_id        = '';
    public string $periodo_letivo_id = '';

    public array $dias = [
        1 => 'SEG',
        2 => 'TER',
        3 => 'QUA',
        4 => 'QUI',
        5 => 'SEX',
    ];

    public function mount(): void
    {
        // Pré-seleciona o período letivo ativo
        $periodoAtivo = PeriodoLetivo::where('ativo', true)->first();
        if ($periodoAtivo) {
            $this->periodo_letivo_id = $periodoAtivo->id;
        }
    }

    public function render()
    {
        $turmas          = Turma::orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->get();
        $grade           = [];
        $horarios        = [];
        $periodo         = null;

        if ($this->turma_id && $this->periodo_letivo_id) {
            $periodo = PeriodoLetivo::find($this->periodo_letivo_id);

            // Busca todas as aulas da turma no período
            $aulas = Aula::with(['disciplina', 'professor', 'sala', 'horario'])
                ->where('turma_id', $this->turma_id)
                ->where('periodo_letivo_id', $this->periodo_letivo_id)
                ->get();

            // Monta lista de horários únicos ordenados
            $horarios = $aulas->map(fn($a) => $a->horario)
                ->unique('id')
                ->sortBy('hora_inicio')
                ->values();

            // Monta a grade: [horario_id][dia_semana] = aula
            foreach ($aulas as $aula) {
                $grade[$aula->horario_id][$aula->dia_semana] = $aula;
            }
        }

        return view('livewire.grade-horarios', compact(
            'turmas', 'periodosLetivos', 'grade', 'horarios', 'periodo'
        ));
    }
}
