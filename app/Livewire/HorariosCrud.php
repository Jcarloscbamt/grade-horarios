<?php
// app/Livewire/HorariosCrud.php
namespace App\Livewire;

use App\Models\Horario;
use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;

class HorariosCrud extends Component
{
    use WithPagination;

    public ?int $horarioId    = null;
    public string $hora_inicio = '';
    public string $hora_fim    = '';
    public string $tipo        = '';

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $modalTitle = '';

    public array $tipos = ['Aula', 'Intervalo'];

    protected function rules(): array
    {
        return [
            'hora_inicio' => 'required',
            'hora_fim'    => 'required|after:hora_inicio',
            'tipo'        => 'required',
        ];
    }

    protected array $messages = [
        'hora_inicio.required' => 'A hora de início é obrigatória.',
        'hora_fim.required'    => 'A hora de fim é obrigatória.',
        'hora_fim.after'       => 'A hora de fim deve ser após a hora de início.',
        'tipo.required'        => 'O tipo é obrigatório.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Novo Horário';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $h = Horario::findOrFail($id);
        $this->horarioId   = $h->id;
        $this->hora_inicio = $h->hora_inicio;
        $this->hora_fim    = $h->hora_fim;
        $this->tipo        = $h->tipo;
        $this->modalTitle  = 'Editar Horário';
        $this->showModal   = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = is_null($this->horarioId);
        Horario::updateOrCreate(
            ['id' => $this->horarioId],
            [
                'hora_inicio' => $this->hora_inicio,
                'hora_fim'    => $this->hora_fim,
                'tipo'        => $this->tipo,
            ]
        );
        $this->showModal = false;
        $this->resetForm();
        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Horários',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $this->hora_inicio
        );
        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Horários',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $this->hora_inicio
        );
        session()->flash('success', $this->horarioId ? 'Horário atualizado com sucesso!' : 'Horário cadastrado com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        $this->horarioId  = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $h = Horario::findOrFail($this->horarioId);
        if ($h->aulas()->count() > 0) {
            session()->flash('error', 'Não é possível excluir pois este horário possui aulas vinculadas.');
            $this->showDelete = false;
            return;
        }
        $h->delete();
        $this->showDelete = false;
        $this->resetForm();
        // Log da ação
        Log::registrar('excluiu', 'Horários', 'Excluiu: ' . $h->nome);
        session()->flash('success', 'Horário excluído com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->horarioId = null;
        $this->hora_inicio = $this->hora_fim = $this->tipo = '';
        $this->resetValidation();
    }

    public function render()
    {
        $horarios = Horario::orderBy('hora_inicio')->paginate(20);
        return view('livewire.horarios-crud', compact('horarios'));
    }
}
