<?php
// app/Livewire/TurmasCrud.php
namespace App\Livewire;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\Log;
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

    public bool $showModal  = false;
    public bool $showDelete = false;
    public bool $ativo      = true;
    public string $search     = '';
    public string $filtroAtivo = 'todos';
    public string $filtro  = 'todos';
    public string $modalTitle = '';

    protected function rules(): array
    {
        return [
            'curso_id' => 'required|exists:cursos,id',
            'nome'     => 'required|max:50|unique:turmas,nome,' . ($this->turmaId ?? 'NULL'),
            'semestre' => 'required|integer|min:1|max:10',
            'ano'      => 'required|digits:4',
        ];
    }

    protected array $messages = [
        'curso_id.required' => 'Selecione um curso.',
        'nome.required'     => 'O nome da turma é obrigatório.',
        'nome.unique'       => 'Este nome de turma já está cadastrado.',
        'semestre.required' => 'O semestre é obrigatório.',
        'ano.required'      => 'O ano é obrigatório.',
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
        $this->modalTitle = 'Editar Turma';
        $this->ativo       = (bool) $t->ativo;
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = is_null($this->turmaId);
        Turma::updateOrCreate(
            ['id' => $this->turmaId],
            [
                'curso_id' => $this->curso_id,
                'nome'     => $this->nome,
                'semestre' => $this->semestre,
                'ano'      => $this->ano,
            ]
        );
        $this->showModal = false;
        $this->resetForm();
        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Turmas',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $this->nome
        );
        session()->flash('success', $this->turmaId ? 'Turma atualizada com sucesso!' : 'Turma cadastrada com sucesso!');
    }


    public function toggleAtivo(int $id): void
    {
        $item = \App\Models\Turma::findOrFail($id);
        $item->ativo = !$item->ativo;
        $item->save();
        $status = $item->ativo ? 'ativado' : 'desativado';
        session()->flash('success', 'Turma ' . $status . ' com sucesso!');
        \App\Models\Log::registrar('editou', 'Turmas', 'Turma ' . $status . ': ' . $item->nome);
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
        // Log da ação
        Log::registrar('excluiu', 'Turmas', 'Excluiu: ' . $t->nome);
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
        $this->turmaId  = null;
        $this->curso_id = '';
        $this->nome     = '';
        $this->semestre = '';
        $this->ano      = '';
        $this->ativo    = true;
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltro(): void  { $this->resetPage(); $this->search = ''; }

    public function render()
    {
        $turmas = Turma::with('curso')
            ->when($this->filtroAtivo === 'ativos', fn($q) => $q->where('ativo', true))
            ->when($this->filtroAtivo === 'inativos', fn($q) => $q->where('ativo', false))
            ->when($this->search, function($q) {
                $s = $this->search;
                match($this->filtro) {
                    'nome'     => $q->where('nome', 'like', "%$s%"),
                    'curso'    => $q->whereHas('curso', fn($c) => $c->where('nome', 'like', "%$s%")->orWhere('sigla', 'like', "%$s%")),
                    'semestre' => $q->where('semestre', 'like', "%$s%"),
                    default    => $q->where('nome', 'like', "%$s%")
                                    ->orWhereHas('curso', fn($c) => $c->where('nome', 'like', "%$s%")),
                };
            })
            ->orderByDesc('ano')->orderBy('nome')
            ->paginate(20);

        $cursos = Curso::orderBy('nome')->get();

        return view('livewire.turmas-crud', compact('turmas', 'cursos'));
    }
}
