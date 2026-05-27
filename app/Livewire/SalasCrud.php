<?php
// app/Livewire/SalasCrud.php
namespace App\Livewire;

use App\Models\Sala;
use App\Models\Log;
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
    public bool $ativo      = true;
    public string $search     = '';
    public string $filtroAtivo = 'todos';
    public string $filtro  = 'todos';
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
        $this->ativo       = (bool) $s->ativo;
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = is_null($this->salaId);
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
        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Salas',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $this->nome
        );
        session()->flash('success', $this->salaId ? 'Sala atualizada com sucesso!' : 'Sala cadastrada com sucesso!');
    }


    public function toggleAtivo(int $id): void
    {
        $item = \App\Models\Sala::findOrFail($id);
        $item->ativo = !$item->ativo;
        $item->save();
        $status = $item->ativo ? 'ativado' : 'desativado';
        session()->flash('success', 'Sala ' . $status . ' com sucesso!');
        \App\Models\Log::registrar('editou', 'Salas', 'Sala ' . $status . ': ' . $item->nome);
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
        // Log da ação
        Log::registrar('excluiu', 'Salas', 'Excluiu: ' . $s->nome);
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
    public function updatingFiltro(): void  { $this->resetPage(); $this->search = ''; }

    public function render()
    {
        $salas = Sala::query()
            ->when($this->filtroAtivo === 'ativos', fn($q) => $q->where('ativo', true))
            ->when($this->filtroAtivo === 'inativos', fn($q) => $q->where('ativo', false))
            ->when($this->search, function($q) {
                $s = $this->search;
                match($this->filtro) {
                    'nome'  => $q->where('nome', 'like', "%$s%"),
                    'tipo'  => $q->where('tipo', 'like', "%$s%"),
                    'bloco' => $q->where('bloco', 'like', "%$s%"),
                    default => $q->where('nome', 'like', "%$s%")
                                 ->orWhere('tipo', 'like', "%$s%")
                                 ->orWhere('bloco', 'like', "%$s%"),
                };
            })
            ->orderBy('nome')
            ->paginate(10);

        return view('livewire.salas-crud', compact('salas'));
    }
}
