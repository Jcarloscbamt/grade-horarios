<?php
// app/Livewire/RelatorioProfessores.php
namespace App\Livewire;

use App\Models\{Professor, Curso, Turma};
use Livewire\Component;
use Livewire\WithPagination;

class RelatorioProfessores extends Component
{
    use WithPagination;

    public string $search    = '';
    public string $curso_id  = '';
    public string $turma_id  = '';
    public string $filtroAtivo   = 'ativos';
    public bool   $showDuplicados = false;

    public function updatingSearch():   void { $this->resetPage(); }
    public function updatingCursoId():  void { $this->resetPage(); $this->turma_id = ''; }
    public function updatingTurmaId():  void { $this->resetPage(); }

    public function exportarCsv()
    {
        $professores = $this->queryProfessores()->get();

        $dias = [1=>'Segunda', 2=>'Terça', 3=>'Quarta', 4=>'Quinta', 5=>'Sexta'];

        $linhas = [['Professor', 'CPF', 'E-mail', 'Telefone', 'Disponibilidade', 'Disciplina', 'Curso', 'Turma', 'Dias da Disciplina', 'Status']];

        foreach ($professores as $prof) {
            $vinculos = $prof->disciplinasTurmas()->with(['disciplina.curso', 'turma'])->get();

            // Disponibilidade geral
            $dispDias = collect(is_array($prof->disponibilidade) ? $prof->disponibilidade : json_decode($prof->disponibilidade ?? '[]', true))
                ->map(fn($d) => $dias[$d] ?? $d)->implode(', ');

            if ($vinculos->isEmpty()) {
                $linhas[] = [
                    $prof->nome,
                    $prof->cpf,
                    $prof->email,
                    $prof->telefone ?? '',
                    $dispDias,
                    '—', '—', '—', '—',
                    $prof->ativo ? 'Ativo' : 'Inativo',
                ];
            } else {
                foreach ($vinculos as $v) {
                    $diasVinculo = collect(json_decode($v->dias ?? '[]', true))
                        ->map(fn($d) => $dias[$d] ?? $d)->implode(', ');
                    $linhas[] = [
                        $prof->nome,
                        $prof->cpf,
                        $prof->email,
                        $prof->telefone ?? '',
                        $dispDias,
                        $v->disciplina->nome ?? '—',
                        $v->disciplina->curso->nome ?? '—',
                        $v->turma->nome ?? '—',
                        $diasVinculo,
                        $prof->ativo ? 'Ativo' : 'Inativo',
                    ];
                }
            }
        }

        $csv = '';
        foreach ($linhas as $linha) {
            $csv .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $linha)) . "\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF" . $csv;
        }, 'relatorio_professores.csv', ['Content-Type' => 'text/csv;charset=UTF-8']);
    }

    private function queryProfessores()
    {
        return Professor::with(['disciplinasTurmas.disciplina.curso', 'disciplinasTurmas.turma'])
            ->when($this->search, fn($q) =>
                $q->where(function ($sub) {
                    $sub->where('nome', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhereHas('disciplinasTurmas.disciplina', fn($q2) =>
                            $q2->where('nome', 'like', "%{$this->search}%"));
                })
            )
            ->when($this->filtroAtivo === 'ativos', fn($q) => $q->where('ativo', true))
            ->when($this->filtroAtivo === 'inativos', fn($q) => $q->where('ativo', false))
            ->when($this->curso_id, fn($q) =>
                $q->whereHas('disciplinasTurmas.disciplina', fn($q2) =>
                    $q2->where('curso_id', $this->curso_id))
            )
            ->when($this->turma_id, fn($q) =>
                $q->whereHas('disciplinasTurmas', fn($q2) =>
                    $q2->where('turma_id', $this->turma_id))
            )
            ->orderBy('nome');
    }


    // Retorna disciplinas+turma com mais de 1 professor vinculado
    public function getDuplicados(): array
    {
        $duplicados = \App\Models\ProfessorDisciplina::with([
                'disciplina.curso', 'turma', 'professor'
            ])
            ->when($this->curso_id, fn($q) =>
                $q->whereHas('disciplina', fn($q2) =>
                    $q2->where('curso_id', $this->curso_id))
            )
            ->when($this->turma_id, fn($q) =>
                $q->where('turma_id', $this->turma_id)
            )
            ->get()
            ->groupBy(fn($v) => $v->disciplina_id . '_' . $v->turma_id)
            ->filter(fn($group) => $group->count() > 1)
            ->map(fn($group) => [
                'disciplina'  => $group->first()->disciplina->nome ?? '?',
                'curso'       => $group->first()->disciplina->curso->sigla ?? '?',
                'turma'       => $group->first()->turma->nome ?? '?',
                'semestre'    => $group->first()->disciplina->semestre_grade ?? '?',
                'professores' => $group->map(fn($v) => $v->professor->nome ?? '?')->values()->toArray(),
                'count'       => $group->count(),
            ])
            ->values()
            ->toArray();

        return $duplicados;
    }

    public function render()
    {
        $professores = $this->queryProfessores()->paginate(20);
        $cursos      = Curso::where('ativo', true)->orderBy('nome')->get();
        $turmas      = $this->curso_id
            ? Turma::where('curso_id', $this->curso_id)->where('ativo', true)->orderBy('nome')->get()
            : Turma::where('ativo', true)->orderBy('nome')->get();
        $dias        = [1=>'SEG', 2=>'TER', 3=>'QUA', 4=>'QUI', 5=>'SEX'];

        $duplicados = $this->showDuplicados ? $this->getDuplicados() : [];

        return view('livewire.relatorio-professores', compact('professores', 'cursos', 'turmas', 'dias', 'duplicados'));
    }
}
