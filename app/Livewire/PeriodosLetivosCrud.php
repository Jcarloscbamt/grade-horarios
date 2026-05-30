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
    public string $search     = '';

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


        $isNovo = is_null($this->periodoId);
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
        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Períodos',
            ($isNovo ? 'Novo período: ' : 'Editou período: ') . $this->nome
        );
        session()->flash('success', $isNovo ? 'Período letivo cadastrado com sucesso!' : 'Período letivo atualizado com sucesso!');
    }


    public function toggleAtivo(int $id): void
    {
        $item   = \App\Models\PeriodoLetivo::findOrFail($id);
        $item->ativo = !$item->ativo;
        $item->save();
        $status = $item->ativo ? 'ativado' : 'desativado';
        session()->flash('success', 'Período letivo ' . $status . ' com sucesso!');
        \App\Models\Log::registrar('editou', 'Períodos Letivos', 'Período ' . $status . ': ' . $item->nome);
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
        // Log da ação
        Log::registrar('excluiu', 'Períodos', 'Excluiu período: ' . $p->nome);
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


    // ── Avançar Semestre das Turmas ─────────────────
    public bool  $showAvancar    = false;
    public array $previewAvanco  = []; // turmas que serão avançadas
    public array $previewConcluidas = []; // turmas no último semestre

    public function prepararAvanco(): void
    {
        $this->previewAvanco     = [];
        $this->previewConcluidas = [];

        $turmas = \App\Models\Turma::with('curso')->where('ativo', true)->get();

        foreach ($turmas as $t) {
            $maxSem = $t->curso->total_semestres ?? 6;
            if ($t->semestre < $maxSem) {
                $this->previewAvanco[] = [
                    'id'           => $t->id,
                    'nome'         => $t->nome,
                    'curso'        => $t->curso->sigla ?? '',
                    'semestre_atual' => $t->semestre,
                    'semestre_novo'  => $t->semestre + 1,
                    'max'          => $maxSem,
                ];
            } else {
                $this->previewConcluidas[] = [
                    'id'     => $t->id,
                    'nome'   => $t->nome,
                    'curso'  => $t->curso->sigla ?? '',
                    'semestre' => $t->semestre,
                ];
            }
        }

        $this->showAvancar = true;
    }

    public function confirmarAvanco(): void
    {
        $count = 0;
        foreach ($this->previewAvanco as $item) {
            \App\Models\Turma::where('id', $item['id'])
                ->increment('semestre');
            $count++;
        }

        \App\Models\Log::registrar('editou', 'Turmas', "Avançou semestre de {$count} turma(s)");
        $this->showAvancar       = false;
        $this->previewAvanco     = [];
        $this->previewConcluidas = [];
        session()->flash('success', "Semestre avançado em {$count} turma(s) com sucesso!");
    }

    public function cancelarAvanco(): void
    {
        $this->showAvancar       = false;
        $this->previewAvanco     = [];
        $this->previewConcluidas = [];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $periodos = PeriodoLetivo::when($this->search, fn($q) => $q->where('nome','like',"%{$this->search}%")->orWhere('ano','like',"%{$this->search}%"))
            ->orderByDesc('ano')->orderByDesc('semestre')->paginate(20);
        return view('livewire.periodos-letivos-crud', compact('periodos'));
    }
}
