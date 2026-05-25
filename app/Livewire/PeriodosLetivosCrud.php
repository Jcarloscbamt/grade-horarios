<?php
// app/Livewire/PeriodosLetivosCrud.php
namespace App\Livewire;

use App\Models\PeriodoLetivo;
use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;

class PeriodosLetivosCrud extends Component
{
    use WithPagination;

    public ?int   $periodoId         = null;
    public string $nome              = '';
    public string $ano               = '';
    public string $semestre          = '';
    public string $avaliacao1_inicio = '';
    public string $avaliacao1_fim    = '';
    public string $avaliacao2_inicio = '';
    public string $avaliacao2_fim    = '';
    public bool   $ativo             = false;

    public bool   $showModal  = false;
    public bool   $showDelete = false;
    public string $modalTitle = '';
    public string $search     = '';

    protected $queryString = ['search'];

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
            'ativo'             => 'boolean',
        ];
    }

    protected array $messages = [
        'nome.required'     => 'O nome é obrigatório.',
        'ano.required'      => 'O ano é obrigatório.',
        'ano.digits'        => 'O ano deve ter 4 dígitos.',
        'semestre.required' => 'Selecione o semestre.',
        'semestre.in'       => 'Semestre inválido.',
        'avaliacao1_fim.after_or_equal' => 'A data fim deve ser após a data início.',
        'avaliacao2_fim.after_or_equal' => 'A data fim deve ser após a data início.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->ano        = date('Y');
        $this->modalTitle = 'Novo Período Letivo';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $p = PeriodoLetivo::findOrFail($id);
        $this->periodoId         = $p->id;
        $this->nome              = $p->nome;
        $this->ano               = $p->ano;
        $this->semestre          = $p->semestre;
        $this->avaliacao1_inicio = $p->avaliacao1_inicio?->format('Y-m-d') ?? '';
        $this->avaliacao1_fim    = $p->avaliacao1_fim?->format('Y-m-d') ?? '';
        $this->avaliacao2_inicio = $p->avaliacao2_inicio?->format('Y-m-d') ?? '';
        $this->avaliacao2_fim    = $p->avaliacao2_fim?->format('Y-m-d') ?? '';
        $this->ativo             = $p->ativo;
        $this->modalTitle        = 'Editar Período Letivo';
        $this->showModal         = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = is_null($this->periodoId);

        // ── SEM auto-desativação ──────────────────────────────────
        // O usuário controla manualmente quais períodos estão ativos.
        // Removida a lógica que desativava todos os outros ao ativar um.

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

        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'PeriodosLetivos',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $this->nome
        );

        session()->flash('success', $isNovo
            ? 'Período letivo cadastrado com sucesso!'
            : 'Período letivo atualizado com sucesso!');

        $this->showModal = false;
        $this->resetForm();
    }

    // Toggle rápido de ativo/inativo direto na lista
    public function toggleAtivo(int $id): void
    {
        $p = PeriodoLetivo::findOrFail($id);
        $p->update(['ativo' => !$p->ativo]);

        Log::registrar(
            'editou',
            'PeriodosLetivos',
            ($p->ativo ? 'Ativou' : 'Desativou') . ': ' . $p->nome
        );

        session()->flash('success', 'Status do período atualizado.');
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
            session()->flash('error', 'Não é possível excluir: existem aulas vinculadas a este período.');
            $this->showDelete = false;
            return;
        }
        $nome = $p->nome;
        $p->delete();
        Log::registrar('excluiu', 'PeriodosLetivos', 'Excluiu: ' . $nome);
        session()->flash('success', 'Período letivo excluído com sucesso!');
        $this->showDelete = false;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->periodoId         = null;
        $this->nome              = '';
        $this->ano               = '';
        $this->semestre          = '';
        $this->avaliacao1_inicio = '';
        $this->avaliacao1_fim    = '';
        $this->avaliacao2_inicio = '';
        $this->avaliacao2_fim    = '';
        $this->ativo             = false;
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $periodos = PeriodoLetivo::query()
            ->when($this->search, fn($q) =>
                $q->where('nome', 'like', '%' . $this->search . '%')
                  ->orWhere('ano', 'like', '%' . $this->search . '%')
            )
            ->orderByDesc('ano')
            ->orderByDesc('semestre')
            ->paginate(10);

        return view('livewire.periodos-letivos-crud', compact('periodos'));
    }
}
