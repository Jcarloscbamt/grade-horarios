<?php
// app/Livewire/DisciplinasCrud.php
namespace App\Livewire;

use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;

class DisciplinasCrud extends Component
{
    use WithPagination;

    public ?int $disciplinaId   = null;
    public string $curso_id      = '';
    public string $nome          = '';
    public string $carga_horaria = '';
    public string $semestre_grade = '';

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $search  = '';
    public string $filtro  = 'todos';
    public string $modalTitle = '';

    protected function rules(): array
    {
        return [
            'curso_id'      => 'required|exists:cursos,id',
            'nome'          => 'required|min:3|max:100',
            'carga_horaria' => 'required|integer|min:1',
            'semestre_grade'=> 'required|integer|min:1|max:10',
        ];
    }

    protected array $messages = [
        'curso_id.required'       => 'Selecione um curso.',
        'nome.required'           => 'O nome da disciplina é obrigatório.',
        'carga_horaria.required'  => 'A carga horária é obrigatória.',
        'semestre_grade.required' => 'O semestre é obrigatório.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Nova Disciplina';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $d = Disciplina::findOrFail($id);
        $this->disciplinaId   = $d->id;
        $this->curso_id       = $d->curso_id;
        $this->nome           = $d->nome;
        $this->carga_horaria  = $d->carga_horaria;
        $this->semestre_grade = $d->semestre_grade;
        $this->modalTitle     = 'Editar Disciplina';
        $this->showModal      = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = is_null($this->disciplinaId);
        Disciplina::updateOrCreate(
            ['id' => $this->disciplinaId],
            [
                'curso_id'      => $this->curso_id,
                'nome'          => $this->nome,
                'carga_horaria' => $this->carga_horaria,
                'semestre_grade'=> $this->semestre_grade,
            ]
        );
        $this->showModal = false;
        $this->resetForm();

        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Disciplinas',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $this->nome
        );
        session()->flash('success', $this->disciplinaId ? 'Disciplina atualizada com sucesso!' : 'Disciplina cadastrada com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        $this->disciplinaId = $id;
        $this->showDelete   = true;
    }

    public function delete(): void
    {
        $d = Disciplina::findOrFail($this->disciplinaId);
        if ($d->aulas()->count() > 0) {
            session()->flash('error', 'Não é possível excluir pois esta disciplina possui aulas vinculadas.');
            $this->showDelete = false;
            return;
        }
        $d->delete();
        $this->showDelete = false;
        $this->resetForm();
        // Log da ação
        Log::registrar('excluiu', 'Disciplinas', 'Excluiu: ' . $d->nome);
        session()->flash('success', 'Disciplina excluída com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->disciplinaId = null;
        $this->curso_id = $this->nome = $this->carga_horaria = $this->semestre_grade = '';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltro(): void  { $this->resetPage(); $this->search = ''; }

    public function render()
    {
        $disciplinas = Disciplina::with('curso')
            ->when($this->search, function($q) {
                $s = $this->search;
                match($this->filtro) {
                    'nome'     => $q->where('nome', 'like', "%$s%"),
                    'curso'    => $q->whereHas('curso', fn($c) => $c->where('nome', 'like', "%$s%")->orWhere('sigla', 'like', "%$s%")),
                    'semestre' => $q->where('semestre_grade', 'like', "%$s%"),
                    default    => $q->where('nome', 'like', "%$s%")
                                    ->orWhereHas('curso', fn($c) => $c->where('nome', 'like', "%$s%")->orWhere('sigla', 'like', "%$s%")),
                };
            })
            ->orderBy('nome')
            ->paginate(10);

        $cursos = Curso::orderBy('nome')->get();

        return view('livewire.disciplinas-crud', compact('disciplinas', 'cursos'));
    }
}
