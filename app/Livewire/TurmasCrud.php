<?php
// app/Livewire/TurmasCrud.php
namespace App\Livewire;

use App\Models\Curso;
use App\Models\Turma;
use Livewire\Component;
use Livewire\WithPagination;

class TurmasCrud extends Component
{
    use WithPagination;

    public ?int $turmaId   = null;
    public string $curso_id  = '';
    public string $nome      = '';
    public string $semestre  = '';
    public string $ano       = '';
    public string $periodo   = '';

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $search   = '';
    public string $modalTitle = '';

    protected function rules(): array
    {
        return [
            'curso_id' => 'required|exists:cursos,id',
            'nome'     => 'required|max:50|unique:turmas,nome,' . ($this->turmaId ?? 'NULL'),
            'semestre' => 'required|integer|min:1|max:10',
            'ano'      => 'required|digits:4',
            'periodo'  => 'required|in:1,2',
        ];
    }

    protected array $messages = [
        'curso_id.required' => 'Selecione um curso.',
        'nome.required'     => 'O nome da turma é obrigatório.',
        'nome.unique'       => 'Este nome de turma já está cadastrado.',
        'semestre.required' => 'O semestre é obrigatório.',
        'ano.required'      => 'O ano é obrigatório.',
        'periodo.required'  => 'O período é obrigatório.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Nova Turma';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $t = Turma::findOrFail($id);
        $this->turmaId  = $t->id;
        $this->curso_id = $t->curso_id;
        $this->nome     = $t->nome;
        $this->semestre = $t->semestre;
        $this->ano      = $t->ano;
        $this->periodo  = $t->periodo;
        $this->modalTitle = 'Editar Turma';
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();
        Turma::updateOrCreate(
            ['id' => $this->turmaId],
            [
                'curso_id' => $this->curso_id,
                'nome'     => $this->nome,
                'semestre' => $this->semestre,
                'ano'      => $this->ano,
                'periodo'  => $this->periodo,
            ]
        );
        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $this->turmaId ? 'Turma atualizada com sucesso!' : 'Turma cadastrada com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        $this->turmaId    = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $t = Turma::findOrFail($this->turmaId);
        if ($t->aulas()->count() > 0) {
            session()->flash('error', 'Não é possível excluir pois esta turma possui aulas vinculadas.');
            $this->showDelete = false;
            return;
        }
        $t->delete();
        $this->showDelete = false;
        $this->resetForm();
        session()->flash('success', 'Turma excluída com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->turmaId = null;
        $this->curso_id = $this->nome = $this->semestre = $this->ano = $this->periodo = '';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $turmas = Turma::with('curso')
            ->when($this->search, fn($q) =>
                $q->where('nome', 'like', "%{$this->search}%")
                  ->orWhereHas('curso', fn($q) => $q->where('nome', 'like', "%{$this->search}%"))
            )
            ->orderByDesc('ano')->orderBy('nome')
            ->paginate(10);

        $cursos = Curso::orderBy('nome')->get();

        return view('livewire.turmas-crud', compact('turmas', 'cursos'));
    }
}
