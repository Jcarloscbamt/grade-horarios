<?php
// app/Livewire/CursosCrud.php
namespace App\Livewire;

use App\Models\Curso;
use Livewire\Component;
use Livewire\WithPagination;

class CursosCrud extends Component
{
    use WithPagination;

    public ?int $cursoId       = null;
    public string $nome          = '';
    public string $sigla         = '';
    public string $nivel         = '';
    public string $coordenador   = '';
    public string $email_coord   = '';
    public string $telefone_coord = '';
    public string $cor_grade     = '#E30613'; // padrão vermelho UniSENAI

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $search   = '';
    public string $modalTitle = '';

    public array $niveis = ['Tecnólogo', 'Bacharelado', 'Licenciatura', 'Técnico'];

    // Sugestões de cores para facilitar
    public array $coresSugeridas = [
        '#E30613' => 'Vermelho UniSENAI',
        '#1565C0' => 'Azul',
        '#2E7D32' => 'Verde',
        '#E65100' => 'Laranja',
        '#6A1B9A' => 'Roxo',
        '#00838F' => 'Ciano',
        '#4E342E' => 'Marrom',
        '#37474F' => 'Cinza escuro',
    ];

    protected $queryString = ['search'];

    protected function rules(): array
    {
        return [
            'nome'           => 'required|min:3|max:100',
            'sigla'          => 'required|max:20|unique:cursos,sigla,' . ($this->cursoId ?? 'NULL'),
            'nivel'          => 'required',
            'coordenador'    => 'required|min:3|max:100',
            'email_coord'    => 'required|email|max:100',
            'telefone_coord' => 'nullable|max:20',
            'cor_grade'      => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    protected array $messages = [
        'nome.required'        => 'O nome do curso é obrigatório.',
        'nome.min'             => 'O nome deve ter no mínimo 3 caracteres.',
        'sigla.required'       => 'A sigla é obrigatória.',
        'sigla.unique'         => 'Esta sigla já está em uso.',
        'nivel.required'       => 'O nível é obrigatório.',
        'coordenador.required' => 'O nome do coordenador é obrigatório.',
        'email_coord.required' => 'O e-mail do coordenador é obrigatório.',
        'email_coord.email'    => 'Informe um e-mail válido.',
        'cor_grade.required'   => 'Informe uma cor para a grade.',
        'cor_grade.regex'      => 'A cor deve estar no formato hexadecimal. Ex: #E30613',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Novo Curso';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $curso = Curso::findOrFail($id);
        $this->cursoId        = $curso->id;
        $this->nome           = $curso->nome;
        $this->sigla          = $curso->sigla;
        $this->nivel          = $curso->nivel;
        $this->coordenador    = $curso->coordenador;
        $this->email_coord    = $curso->email_coord;
        $this->telefone_coord = $curso->telefone_coord ?? '';
        $this->cor_grade      = $curso->cor_grade ?? '#E30613';
        $this->modalTitle     = 'Editar Curso';
        $this->showModal      = true;
    }

    public function save(): void
    {
        $this->validate();

        Curso::updateOrCreate(
            ['id' => $this->cursoId],
            [
                'nome'           => $this->nome,
                'sigla'          => strtoupper($this->sigla),
                'nivel'          => $this->nivel,
                'coordenador'    => $this->coordenador,
                'email_coord'    => $this->email_coord,
                'telefone_coord' => $this->telefone_coord ?: null,
                'cor_grade'      => $this->cor_grade,
            ]
        );

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $this->cursoId ? 'Curso atualizado com sucesso!' : 'Curso cadastrado com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        $this->cursoId    = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $curso = Curso::findOrFail($this->cursoId);
        if ($curso->turmas()->count() > 0 || $curso->disciplinas()->count() > 0) {
            session()->flash('error', 'Não é possível excluir pois este curso possui turmas ou disciplinas vinculadas.');
            $this->showDelete = false;
            return;
        }
        $curso->delete();
        $this->showDelete = false;
        $this->resetForm();
        session()->flash('success', 'Curso excluído com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->cursoId        = null;
        $this->nome           = '';
        $this->sigla          = '';
        $this->nivel          = '';
        $this->coordenador    = '';
        $this->email_coord    = '';
        $this->telefone_coord = '';
        $this->cor_grade      = '#E30613';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $cursos = Curso::query()
            ->when($this->search, fn($q) =>
                $q->where('nome', 'like', "%{$this->search}%")
                  ->orWhere('sigla', 'like', "%{$this->search}%")
                  ->orWhere('coordenador', 'like', "%{$this->search}%")
            )
            ->orderBy('nome')
            ->paginate(10);

        return view('livewire.cursos-crud', compact('cursos'));
    }
}
