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

    public ?int $disciplinaId    = null;
    public string $curso_id      = '';
    public string $nome          = '';
    public string $carga_horaria = '';
    public string $semestre_grade    = '';
    public string $tipo_sala          = '';
    public string $bloco_preferencial = '';

    public bool $showModal  = false;
    public bool $showDelete = false;
    public bool $ativo      = true;
    public string $search      = '';
    public string $filtro      = 'todos';
    public string $filtroAtivo = 'todos';
    public string $modalTitle = '';

    protected function rules(): array
    {
        return [
            'curso_id'       => 'required|exists:cursos,id',
            'nome'           => 'required|min:3|max:100',
            'carga_horaria'  => 'required|integer|min:1',
            'semestre_grade' => 'required|integer|min:1|max:10',
            'tipo_sala'      => 'required|in:Sala de Aula,Laboratório,Online',
        ];
    }

    protected array $messages = [
        'curso_id.required'       => 'Selecione um curso.',
        'nome.required'           => 'O nome da disciplina é obrigatório.',
        'nome.min'                => 'O nome deve ter no mínimo 3 caracteres.',
        'carga_horaria.required'  => 'A carga horária é obrigatória.',
        'carga_horaria.integer'   => 'A carga horária deve ser um número.',
        'carga_horaria.min'       => 'A carga horária deve ser maior que zero.',
        'semestre_grade.required' => 'O semestre é obrigatório.',
        'tipo_sala.required'      => 'Selecione o tipo de sala.',
        'tipo_sala.in'            => 'Tipo de sala inválido.',
        'semestre_grade.integer'  => 'O semestre deve ser um número.',
        'semestre_grade.min'      => 'O semestre deve ser no mínimo 1.',
        'semestre_grade.max'      => 'O semestre deve ser no máximo 10.',
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
        $this->ativo              = (bool) $d->ativo;
        $this->tipo_sala          = $d->tipo_sala ?? '';
        $this->bloco_preferencial = $d->bloco_preferencial ?? '';
        $this->showModal      = true;
    }

    public function save(): void
    {
        $this->validate();

        // Verificar duplicidade: mesmo nome + mesmo curso
        $duplicada = Disciplina::where('curso_id', $this->curso_id)
            ->whereRaw('LOWER(nome) = ?', [strtolower(trim($this->nome))])
            ->when($this->disciplinaId, fn($q) => $q->where('id', '!=', $this->disciplinaId))
            ->exists();

        if ($duplicada) {
            $this->addError('nome', 'Já existe uma disciplina com este nome neste curso.');
            return;
        }

        $isNovo = is_null($this->disciplinaId);

        Disciplina::updateOrCreate(
            ['id' => $this->disciplinaId],
            [
                'curso_id'           => $this->curso_id,
                'nome'               => trim($this->nome),
                'carga_horaria'      => $this->carga_horaria,
                'semestre_grade'     => $this->semestre_grade,
                'tipo_sala'          => $this->tipo_sala ?: null,
                'bloco_preferencial' => ($this->tipo_sala === 'Online') ? null : ($this->bloco_preferencial ?: null),
                'ativo'              => $this->ativo,
            ]
        );

        // Log
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Disciplinas',
            ($isNovo ? 'Nova disciplina: ' : 'Editou disciplina: ') . $this->nome
        );

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $isNovo ? 'Disciplina cadastrada com sucesso!' : 'Disciplina atualizada com sucesso!');
    }


    public function toggleAtivo(int $id): void
    {
        $item = \App\Models\Disciplina::findOrFail($id);
        $item->ativo = !$item->ativo;
        $item->save();
        $status = $item->ativo ? 'ativado' : 'desativado';
        session()->flash('success', 'Disciplina ' . $status . ' com sucesso!');
        \App\Models\Log::registrar('editou', 'Disciplinas', 'Disciplina ' . $status . ': ' . $item->nome);
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

        $nome = $d->nome;
        $d->delete();

        // Log
        Log::registrar('excluiu', 'Disciplinas', "Excluiu disciplina: {$nome}");

        $this->showDelete = false;
        $this->resetForm();
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
        $this->disciplinaId  = null;
        $this->curso_id      = '';
        $this->nome          = '';
        $this->carga_horaria = '';
        $this->semestre_grade = '';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltro(): void  { $this->resetPage(); $this->search = ''; }

    public function render()
    {
        $disciplinas = Disciplina::with('curso')
            ->when($this->filtroAtivo === 'ativos', fn($q) => $q->where('ativo', true))
            ->when($this->filtroAtivo === 'inativos', fn($q) => $q->where('ativo', false))
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
            ->paginate(20);

        $cursos = Curso::orderBy('nome')->get();

        // Tipos de sala disponíveis
        $tiposSala = ['Sala de Aula', 'Laboratório', 'Online'];

        // Blocos disponíveis (busca os existentes no banco)
        $blocos = \App\Models\Sala::whereNotNull('bloco')
            ->distinct()->orderBy('bloco')->pluck('bloco')->toArray();
        if (empty($blocos)) {
            $blocos = ['A', 'B', 'C', 'D'];
        }

        return view('livewire.disciplinas-crud', compact('disciplinas', 'cursos', 'tiposSala', 'blocos'));
    }
}
