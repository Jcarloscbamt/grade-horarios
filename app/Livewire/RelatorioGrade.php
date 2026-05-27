<?php
// app/Livewire/RelatorioGrade.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Horario, PeriodoLetivo};
use Livewire\Component;

class RelatorioGrade extends Component
{
    public array $turmasSelecionadas    = [];
    public array $periodosSelecionados  = [];

    public array $dias = [1=>'SEG', 2=>'TER', 3=>'QUA', 4=>'QUI', 5=>'SEX'];

    public function mount(): void
    {
        $periodo = PeriodoLetivo::where('ativo', true)->first();
        if ($periodo) {
            $this->periodosSelecionados = [$periodo->id];
        }
    }

    public function toggleTodasTurmas(): void
    {
        $todos = Turma::where('ativo', true)->pluck('id')->toArray();
        if (count($this->turmasSelecionadas) >= count($todos)) {
            $this->turmasSelecionadas = [];
        } else {
            $this->turmasSelecionadas = $todos;
        }
    }

    public function toggleTodosPeriodos(): void
    {
        $todos = PeriodoLetivo::pluck('id')->toArray();
        if (count($this->periodosSelecionados) >= count($todos)) {
            $this->periodosSelecionados = [];
        } else {
            $this->periodosSelecionados = $todos;
        }
    }

    public function exportarCsv()
    {
        if (empty($this->turmasSelecionadas) || empty($this->periodosSelecionados)) return;

        $dias = [1=>'Segunda', 2=>'Terça', 3=>'Quarta', 4=>'Quinta', 5=>'Sexta'];
        $linhas = [['Turma', 'Período', 'Dia', 'Horário', 'Disciplina', 'Professor', 'Sala', 'Modalidade']];

        $aulas = Aula::with(['disciplina', 'professor', 'sala', 'horario', 'periodoLetivo', 'turma'])
            ->whereIn('turma_id', $this->turmasSelecionadas)
            ->whereIn('periodo_letivo_id', $this->periodosSelecionados)
            ->orderBy('turma_id')->orderBy('periodo_letivo_id')->orderBy('dia_semana')
            ->get();

        foreach ($aulas as $aula) {
            $linhas[] = [
                $aula->turma->nome,
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
        }, 'relatorio_grade.csv', ['Content-Type' => 'text/csv;charset=UTF-8']);
    }

    public function render()
    {
        $turmas          = Turma::with('curso')->where('ativo', true)->orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')->get();

        $grades         = [];  // [turma_id][periodo_id][horario_id][dia] = aula
        $horarios       = collect();
        $turmasAtivas   = collect();
        $periodosAtivos = collect();
        $totalAulas     = 0;

        if (!empty($this->turmasSelecionadas) && !empty($this->periodosSelecionados)) {
            $aulas = Aula::with(['disciplina', 'professor', 'sala', 'horario'])
                ->whereIn('turma_id', $this->turmasSelecionadas)
                ->whereIn('periodo_letivo_id', $this->periodosSelecionados)
                ->get();

            $totalAulas = $aulas->count();

            $horarios = $aulas->map(fn($a) => $a->horario)
                ->unique('id')->sortBy('hora_inicio')->values();

            foreach ($aulas as $aula) {
                $grades[$aula->turma_id][$aula->periodo_letivo_id][$aula->horario_id][$aula->dia_semana] = $aula;
            }

            $turmasAtivas = Turma::with('curso')
                ->whereIn('id', $this->turmasSelecionadas)
                ->orderBy('nome')->get();

            $periodosAtivos = PeriodoLetivo::whereIn('id', $this->periodosSelecionados)
                ->orderByDesc('ano')->orderByDesc('semestre')->get();
        }

        return view('livewire.relatorio-grade', compact(
            'turmas', 'periodosLetivos', 'grades', 'horarios',
            'turmasAtivas', 'periodosAtivos', 'totalAulas'
        ));
    }
}
