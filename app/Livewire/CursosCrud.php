<?php
// app/Livewire/CursosCrud.php
namespace App\Livewire;

use App\Models\Curso;
use App\Models\Log;
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
    public string $cor_grade     = '#E30613';
    public int    $total_semestres = 6;

    public bool $showModal  = false;
    public bool $showDelete = false;
    public bool $ativo      = true;
    public string $search     = '';
    public string $filtroAtivo = 'todos';
    public string $filtro  = 'todos';
    public string $modalTitle = '';

    public array $niveis = ['Tecnólogo', 'Bacharelado', 'Licenciatura', 'Técnico'];

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

    protected $queryString = ['search', 'filtro'];

    protected function rules(): array
    {
        return [
            'nome'           => 'required|min:3|max:100',
            'sigla'          => 'required|max:20|unique:cursos,sigla,' . ($this->cursoId ?? 'NULL'),
            'nivel'          => 'required',
            'coordenador'    => 'required|min:3|max:100',
            'email_coord'    => 'required|email|max:100',
            'telefone_coord' => 'nullable|min:13|max:15',
            'total_semestres' => 'required|integer|min:1|max:10',
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
        'telefone_coord.min'   => 'Telefone incompleto. Use o formato (00) 0000-0000.',
    ];

    private function formatarTelefone(string $tel): string
    {
        $tel = preg_replace('/\D/', '', $tel);
        if (strlen($tel) <= 2)  return $tel ? '(' . $tel : $tel;
        if (strlen($tel) <= 6)  return '(' . substr($tel,0,2) . ') ' . substr($tel,2);
        if (strlen($tel) <= 10) return '(' . substr($tel,0,2) . ') ' . substr($tel,2,4) . '-' . substr($tel,6);
        return '(' . substr($tel,0,2) . ') ' . substr($tel,2,5) . '-' . substr($tel,7,4);
    }

    public function updatedTelefoneCoord(string $value): void
    {
        $this->telefone_coord = $this->formatarTelefone($value);
    }

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
        $this->ativo       = (bool) $curso->ativo;
        $this->showModal      = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = is_null($this->cursoId);

        Curso::updateOrCreate(
            ['id' => $this->cursoId],
            [
                'nome'           => $this->nome,
                'sigla'          => strtoupper($this->sigla),
                'nivel'          => $this->nivel,
                'coordenador'    => $this->coordenador,
                'email_coord'    => $this->email_coord,
                'telefone_coord' => $this->telefone_coord ?: null,
                'total_semestres' => 'required|integer|min:1|max:10',
            'cor_grade'      => $this->cor_grade,
            ]
        );

        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Cursos',
            ($isNovo ? 'Novo curso: ' : 'Editou curso: ') . $this->nome
        );

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $isNovo ? 'Curso cadastrado com sucesso!' : 'Curso atualizado com sucesso!');
    }


    public function toggleAtivo(int $id): void
    {
        $item = \App\Models\Curso::findOrFail($id);
        $item->ativo = !$item->ativo;
        $item->save();
        $status = $item->ativo ? 'ativado' : 'desativado';
        session()->flash('success', 'Curso ' . $status . ' com sucesso!');
        \App\Models\Log::registrar('editou', 'Cursos', 'Curso ' . $status . ': ' . $item->nome);
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

        $nome = $curso->nome;
        $curso->delete();

        // Log da ação
        Log::registrar('excluiu', 'Cursos', "Excluiu curso: {$nome}");

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
    public function updatingFiltro(): void  { $this->resetPage(); $this->search = ''; }

    public function render()
    {
        $cursos = Curso::query()
            ->when($this->filtroAtivo === 'ativos', fn($q) => $q->where('ativo', true))
            ->when($this->filtroAtivo === 'inativos', fn($q) => $q->where('ativo', false))
            ->when($this->search, function($q) {
                $s = $this->search;
                match($this->filtro) {
                    'nome'        => $q->where('nome', 'like', "%$s%"),
                    'sigla'       => $q->where('sigla', 'like', "%$s%"),
                    'coordenador' => $q->where('coordenador', 'like', "%$s%"),
                    default       => $q->where('nome', 'like', "%$s%")
                                       ->orWhere('sigla', 'like', "%$s%")
                                       ->orWhere('coordenador', 'like', "%$s%"),
                };
            })
            ->orderBy('nome')
            ->paginate(20);

        return view('livewire.cursos-crud', compact('cursos'));
    }
}
