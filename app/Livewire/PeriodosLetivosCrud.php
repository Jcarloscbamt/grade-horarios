<?php
// app/Livewire/PeriodosLetivosCrud.php
namespace App\Livewire;

use App\Models\PeriodoLetivo;
use Livewire\Component;
use Livewire\WithPagination;

class PeriodosLetivosCrud extends Component
{
    use WithPagination;

    public ?int $periodoId          = null;
    public string $nome              = '';
    public string $ano               = '';
    public string $semestre          = '';
    public string $avaliacao1_inicio = '';
    public string $avaliacao1_fim    = '';
    public string $avaliacao2_inicio = '';
    public string $avaliacao2_fim    = '';
    public bool   $ativo             = false;

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $modalTitle = '';

    protected function rules(): array
    {
        return [
            'nome'              => 'required|max:20',
            'ano'               => 'required|digits:4',
            'semestre'          => 'required|in:1,2',
            'avaliacao1_inicio' => 'nullable|date',
            'avaliacao1_fim'    => 'nullable|date|after_or_equal:avaliacao1_inicio',
            'avaliacao2_inicio' => 'nullable|date',
            'avaliacao2_fim'    => 'nullable|date|after_or_equal:avaliacao2_inicio',
        ];
    }

    protected array $messages = [
        'nome.required'     => 'O nome é obrigatório.',
        'ano.required'      => 'O ano é obrigatório.',
        'ano.digits'        => 'O ano deve ter 4 dígitos.',
        'semestre.required' => 'O semestre é obrigatório.',
        'semestre.in'       => 'O semestre deve ser 1 ou 2.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Novo Período Letivo';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $p = PeriodoLetivo::findOrFail($id);
        $this->periodoId          = $p->id;
        $this->nome               = $p->nome;
        $this->ano                = $p->ano;
        $this->semestre           = $p->semestre;
        $this->avaliacao1_inicio  = $p->avaliacao1_inicio?->format('Y-m-d') ?? '';
        $this->avaliacao1_fim     = $p->avaliacao1_fim?->format('Y-m-d') ?? '';
        $this->avaliacao2_inicio  = $p->avaliacao2_inicio?->format('Y-m-d') ?? '';
        $this->avaliacao2_fim     = $p->avaliacao2_fim?->format('Y-m-d') ?? '';
        $this->ativo              = $p->ativo;
        $this->modalTitle         = 'Editar Período Letivo';
        $this->showModal          = true;
    }

    public function save(): void
    {
        $this->validate();

        // Se ativo, desativa todos os outros
        if ($this->ativo) {
            PeriodoLetivo::where('id', '!=', $this->periodoId ?? 0)->update(['ativo' => false]);
        }

        PeriodoLetivo::updateOrCreate(
            ['id' => $this->periodoId],
            [
                'nome'              => $this->nome,
                'ano'               => $this->ano,
                'semestre'          => $this->semestre,
                'avaliacao1_inicio' => $this->avaliacao1_inicio ?: null,
                'avaliacao1_fim'    => $this->avaliacao1_fim ?: null,
                'avaliacao2_inicio' => $this->avaliacao2_inicio ?: null,
                'avaliacao2_fim'    => $this->avaliacao2_fim ?: null,
                'ativo'             => $this->ativo,
            ]
        );
        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $this->periodoId ? 'Período letivo atualizado com sucesso!' : 'Período letivo cadastrado com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        $this->periodoId  = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $p = PeriodoLetivo::findOrFail($this->periodoId);
        if ($p->aulas()->count() > 0) {
            session()->flash('error', 'Não é possível excluir pois este período possui aulas vinculadas.');
            $this->showDelete = false;
            return;
        }
        $p->delete();
        $this->showDelete = false;
        $this->resetForm();
        session()->flash('success', 'Período letivo excluído com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->periodoId = null;
        $this->nome = $this->ano = $this->semestre = '';
        $this->avaliacao1_inicio = $this->avaliacao1_fim = '';
        $this->avaliacao2_inicio = $this->avaliacao2_fim = '';
        $this->ativo = false;
        $this->resetValidation();
    }

    public function render()
    {
        $periodos = PeriodoLetivo::orderByDesc('ano')->orderByDesc('semestre')->paginate(10);
        return view('livewire.periodos-letivos-crud', compact('periodos'));
    }
}
