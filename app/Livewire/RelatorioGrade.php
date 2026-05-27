<?php
// app/Livewire/RelatorioGrade.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Horario, PeriodoLetivo};
use Livewire\Component;

class RelatorioGrade extends Component
{
    public string $turma_id            = '';
    public array  $periodosSelecionados = [];

    public array $dias = [1=>'SEG', 2=>'TER', 3=>'QUA', 4=>'QUI', 5=>'SEX'];

    public function mount(): void
    {
        $periodo = PeriodoLetivo::where('ativo', true)->first();
        if ($periodo) {
            $this->periodosSelecionados = [$periodo->id];
        }
    }

    public function toggleTodosPeriodos(): void
    {
        $todos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')->pluck('id')->toArray();
        if (count($this->periodosSelecionados) >= count($todos)) {
            $this->periodosSelecionados = [];
        } else {
            $this->periodosSelecionados = $todos;
        }
    }

    public function exportarCsv()
    {
        if (!$this->turma_id || empty($this->periodosSelecionados)) return;

        $turma = Turma::with('curso')->findOrFail($this->turma_id);
        $dias  = [1=>'Segunda', 2=>'Terça', 3=>'Quarta', 4=>'Quinta', 5=>'Sexta'];

        $linhas = [['Período', 'Dia', 'Horário', 'Disciplina', 'Professor', 'Sala', 'Modalidade']];

        $aulas = Aula::with(['disciplina', 'professor', 'sala', 'horario', 'periodoLetivo'])
            ->where('turma_id', $this->turma_id)
            ->whereIn('periodo_letivo_id', $this->periodosSelecionados)
            ->orderBy('periodo_letivo_id')
            ->orderBy('dia_semana')
            ->get();

        foreach ($aulas as $aula) {
            $linhas[] = [
                $aula->periodoLetivo->nome,
                $dias[$aula->dia_semana] ?? $aula->dia_semana,
                substr($aula->horario->hora_inicio,0,5) . ' - ' . substr($aula->horario->hora_fim,0,5),
                $aula->disciplina->nome,
                $aula->professor->nome,
                $aula->sala?->nome ?? 'Online',
                $aula->modalidade,
            ];
        }

        $csv = '';
        foreach ($linhas as $linha) {
            $csv .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $linha)) . "\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF" . $csv;
        }, 'grade_' . $turma->nome . '.csv', ['Content-Type' => 'text/csv;charset=UTF-8']);
    }

    public function render()
    {
        $turmas          = Turma::with('curso')->where('ativo', true)->orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')->get();
        $grade           = [];
        $horarios        = collect();
        $turma           = null;
        $periodosAtivos  = collect();
        $totalAulas      = 0;

        if ($this->turma_id && !empty($this->periodosSelecionados)) {
            $turma = Turma::with('curso')->find($this->turma_id);

            $aulas = Aula::with(['disciplina', 'professor', 'sala', 'horario'])
                ->where('turma_id', $this->turma_id)
                ->whereIn('periodo_letivo_id', $this->periodosSelecionados)
                ->get();

            $totalAulas = $aulas->count();

            $horarios = $aulas->map(fn($a) => $a->horario)
                ->unique('id')
                ->sortBy('hora_inicio')
                ->values();

            // Indexa grade por [periodo_id][horario_id][dia_semana]
            foreach ($aulas as $aula) {
                $grade[$aula->periodo_letivo_id][$aula->horario_id][$aula->dia_semana] = $aula;
            }

            $periodosAtivos = PeriodoLetivo::whereIn('id', $this->periodosSelecionados)
                ->orderByDesc('ano')->orderByDesc('semestre')->get();
        }

        return view('livewire.relatorio-grade', compact(
            'turmas', 'periodosLetivos', 'grade', 'horarios',
            'turma', 'periodosAtivos', 'totalAulas'
        ));
    }
}
