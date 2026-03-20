<?php
// app/Livewire/ProfessoresCrud.php
namespace App\Livewire;

use App\Models\Professor;
use Livewire\Component;
use Livewire\WithPagination;

class ProfessoresCrud extends Component
{
    use WithPagination;

    public ?int $professorId = null;
    public string $nome      = '';
    public string $email     = '';
    public string $telefone  = '';
    public string $cpf       = '';

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $search   = '';
    public string $modalTitle = '';

    protected function rules(): array
    {
        return [
            'nome'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:100|unique:professores,email,' . ($this->professorId ?? 'NULL'),
            'telefone' => 'nullable|max:20',
            'cpf'      => 'required|digits:11|unique:professores,cpf,' . ($this->professorId ?? 'NULL'),
        ];
    }

    protected array $messages = [
        'nome.required'  => 'O nome é obrigatório.',
        'email.required' => 'O e-mail é obrigatório.',
        'email.unique'   => 'Este e-mail já está cadastrado.',
        'cpf.required'   => 'O CPF é obrigatório.',
        'cpf.digits'     => 'O CPF deve ter 11 dígitos (somente números).',
        'cpf.unique'     => 'Este CPF já está cadastrado.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Novo Professor';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $p = Professor::findOrFail($id);
        $this->professorId = $p->id;
        $this->nome        = $p->nome;
        $this->email       = $p->email;
        $this->telefone    = $p->telefone ?? '';
        $this->cpf         = $p->cpf;
        $this->modalTitle  = 'Editar Professor';
        $this->showModal   = true;
    }

    public function save(): void
    {
        $this->validate();
        Professor::updateOrCreate(
            ['id' => $this->professorId],
            [
                'nome'     => $this->nome,
                'email'    => $this->email,
                'telefone' => $this->telefone ?: null,
                'cpf'      => $this->cpf,
            ]
        );
        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $this->professorId ? 'Professor atualizado com sucesso!' : 'Professor cadastrado com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        $this->professorId = $id;
        $this->showDelete  = true;
    }

    public function delete(): void
    {
        $p = Professor::findOrFail($this->professorId);
        if ($p->aulas()->count() > 0) {
            session()->flash('error', 'Não é possível excluir pois este professor possui aulas vinculadas.');
            $this->showDelete = false;
            return;
        }
        $p->delete();
        $this->showDelete = false;
        $this->resetForm();
        session()->flash('success', 'Professor excluído com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->professorId = null;
        $this->nome = $this->email = $this->telefone = $this->cpf = '';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $professores = Professor::query()
            ->when($this->search, fn($q) =>
                $q->where('nome', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->orderBy('nome')
            ->paginate(10);

        return view('livewire.professores-crud', compact('professores'));
    }
}
