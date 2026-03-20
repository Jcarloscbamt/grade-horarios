<?php
// app/Livewire/SalasCrud.php
namespace App\Livewire;

use App\Models\Sala;
use Livewire\Component;
use Livewire\WithPagination;

class SalasCrud extends Component
{
    use WithPagination;

    public ?int $salaId     = null;
    public string $nome       = '';
    public string $tipo       = '';
    public string $capacidade = '';
    public string $bloco      = '';

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $search   = '';
    public string $modalTitle = '';

    public array $tipos = ['Sala de Aula', 'Laboratório', 'Auditório', 'Sala de Reunião'];

    protected function rules(): array
    {
        return [
            'nome'       => 'required|max:50|unique:salas,nome,' . ($this->salaId ?? 'NULL'),
            'tipo'       => 'required',
            'capacidade' => 'nullable|integer|min:1',
            'bloco'      => 'nullable|max:20',
        ];
    }

    protected array $messages = [
        'nome.required' => 'O nome da sala é obrigatório.',
        'nome.unique'   => 'Este nome de sala já está cadastrado.',
        'tipo.required' => 'O tipo é obrigatório.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Nova Sala';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $s = Sala::findOrFail($id);
        $this->salaId     = $s->id;
        $this->nome       = $s->nome;
        $this->tipo       = $s->tipo;
        $this->capacidade = $s->capacidade ?? '';
        $this->bloco      = $s->bloco ?? '';
        $this->modalTitle = 'Editar Sala';
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();
        Sala::updateOrCreate(
            ['id' => $this->salaId],
            [
                'nome'       => $this->nome,
                'tipo'       => $this->tipo,
                'capacidade' => $this->capacidade ?: null,
                'bloco'      => $this->bloco ?: null,
            ]
        );
        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $this->salaId ? 'Sala atualizada com sucesso!' : 'Sala cadastrada com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        $this->salaId    = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $s = Sala::findOrFail($this->salaId);
        if ($s->aulas()->count() > 0) {
            session()->flash('error', 'Não é possível excluir pois esta sala possui aulas vinculadas.');
            $this->showDelete = false;
            return;
        }
        $s->delete();
        $this->showDelete = false;
        $this->resetForm();
        session()->flash('success', 'Sala excluída com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->salaId = null;
        $this->nome = $this->tipo = $this->capacidade = $this->bloco = '';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $salas = Sala::query()
            ->when($this->search, fn($q) =>
                $q->where('nome', 'like', "%{$this->search}%")
                  ->orWhere('tipo', 'like', "%{$this->search}%")
                  ->orWhere('bloco', 'like', "%{$this->search}%")
            )
            ->orderBy('nome')
            ->paginate(10);

        return view('livewire.salas-crud', compact('salas'));
    }
}
