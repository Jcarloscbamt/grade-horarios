<?php
// app/Livewire/ProfessoresCrud.php
namespace App\Livewire;

use App\Models\Professor;
use App\Models\ProfessorDisciplina;
use App\Models\Disciplina;
use App\Models\Turma;
use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;

class ProfessoresCrud extends Component
{
    use WithPagination;

    // ── Dados básicos do professor ────────────────────────────────
    public ?int   $professorId = null;
    public string $nome        = '';
    public string $email       = '';
    public string $telefone    = '';
    public string $cpf         = '';

    // ── Controle de modais ────────────────────────────────────────
    public bool   $showModal   = false;
    public bool   $showDelete  = false;
    public string $search      = '';
    public string $filtro      = 'todos';
    public string $modalTitle  = '';

    // ── Seleção de disciplina/turma ───────────────────────────────
    // Agora: disciplina → turma (sem select de curso no meio)
    public string $sel_disciplina_id   = '';
    public string $sel_disciplina_nome = '';
    public string $sel_curso_nome      = '';   // apenas para exibição
    public string $sel_turma_id        = '';
    public array  $sel_dias            = [];
    public array  $turmasDoVinculo     = [];

    public array  $vinculos            = [];

    // ── Busca de disciplinas ──────────────────────────────────────
    public string $buscaDisciplina     = '';
    public bool   $mostrarLista        = false;

    protected $queryString = ['search', 'filtro'];

    // ─────────────────────────────────────────────────────────────
    // Constante dias da semana (SEG–SAB)
    // ─────────────────────────────────────────────────────────────
    private function diasNomes(): array
    {
        return [1 => 'SEG', 2 => 'TER', 3 => 'QUA', 4 => 'QUI', 5 => 'SEX', 6 => 'SAB'];
    }

    // ─────────────────────────────────────────────────────────────
    // Regras de validação
    // ─────────────────────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'nome'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:100|unique:professores,email,' . ($this->professorId ?? 'NULL'),
            'telefone' => 'nullable|min:13|max:15',
            'cpf'      => [
                'required', 'min:14', 'max:14',
                function ($attribute, $value, $fail) {
                    if (!$this->validarCPF($value)) {
                        $fail('CPF inválido. Verifique o número digitado.');
                        return;
                    }
                    $cpfNumeros = preg_replace('/\D/', '', $value);
                    $existe = Professor::whereRaw(
                        "REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?",
                        [$cpfNumeros]
                    )
                    ->when($this->professorId, fn($q) => $q->where('id', '!=', $this->professorId))
                    ->exists();
                    if ($existe) {
                        $fail('Este CPF já está cadastrado para outro professor.');
                    }
                }
            ],
        ];
    }

    protected array $messages = [
        'nome.required'     => 'O nome é obrigatório.',
        'email.required'    => 'O e-mail é obrigatório.',
        'email.unique'      => 'Este e-mail já está cadastrado.',
        'cpf.required'      => 'O CPF é obrigatório.',
        'cpf.min'           => 'CPF incompleto. Use o formato 000.000.000-00.',
        'telefone.min'      => 'Telefone incompleto. Use o formato (00) 00000-0000.',
    ];

    // ─────────────────────────────────────────────────────────────
    // Formatadores / Validadores
    // ─────────────────────────────────────────────────────────────
    private function formatarCPF(string $v): string
    {
        $v = preg_replace('/\D/', '', $v);
        $v = substr($v, 0, 11);
        $len = strlen($v);
        if ($len <= 3) return $v;
        if ($len <= 6) return substr($v, 0, 3) . '.' . substr($v, 3);
        if ($len <= 9) return substr($v, 0, 3) . '.' . substr($v, 3, 3) . '.' . substr($v, 6);
        return substr($v, 0, 3) . '.' . substr($v, 3, 3) . '.' . substr($v, 6, 3) . '-' . substr($v, 9, 2);
    }

    private function formatarTelefone(string $v): string
    {
        $v = preg_replace('/\D/', '', $v);
        $v = substr($v, 0, 11);
        $len = strlen($v);
        if ($len <= 2)  return $len ? '(' . $v : $v;
        if ($len <= 6)  return '(' . substr($v, 0, 2) . ') ' . substr($v, 2);
        if ($len <= 10) return '(' . substr($v, 0, 2) . ') ' . substr($v, 2, 4) . '-' . substr($v, 6);
        return '(' . substr($v, 0, 2) . ') ' . substr($v, 2, 5) . '-' . substr($v, 7, 4);
    }

    private function validarCPF(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }

    // ─────────────────────────────────────────────────────────────
    // Hooks
    // ─────────────────────────────────────────────────────────────
    public function updatedCpf(string $value): void
    {
        $this->cpf = $this->formatarCPF($value);
    }

    public function updatedTelefone(string $value): void
    {
        $this->telefone = $this->formatarTelefone($value);
    }

    public function updatedBuscaDisciplina(): void
    {
        $this->mostrarLista = strlen($this->buscaDisciplina) >= 2;
    }

    // Ao selecionar disciplina: carrega turmas do curso dela diretamente
    public function selecionarDisciplina(int $id, string $nome, string $cursoNome, int $cursoId): void
    {
        $this->sel_disciplina_id   = $id;
        $this->sel_disciplina_nome = $nome;
        $this->sel_curso_nome      = $cursoNome;
        $this->sel_turma_id        = '';
        $this->sel_dias            = [];
        $this->mostrarLista        = false;
        $this->buscaDisciplina     = '';
        $this->resetValidation('vinculo');

        $this->turmasDoVinculo = Turma::where('curso_id', $cursoId)
            ->orderBy('nome')
            ->get(['id', 'nome', 'semestre'])
            ->toArray();
    }

    // Cancela a seleção atual para escolher outra disciplina
    public function cancelarSelecao(): void
    {
        $this->sel_disciplina_id   = '';
        $this->sel_disciplina_nome = '';
        $this->sel_curso_nome      = '';
        $this->sel_turma_id        = '';
        $this->sel_dias            = [];
        $this->turmasDoVinculo     = [];
        $this->buscaDisciplina     = '';
        $this->mostrarLista        = false;
        $this->resetValidation('vinculo');
    }

    // Toggle todos os dias (SEG-SAB = 6 dias)
    public function toggleTodosDias(): void
    {
        if (count($this->sel_dias) === 6) {
            $this->sel_dias = [];
        } else {
            $this->sel_dias = [1, 2, 3, 4, 5, 6];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Adicionar / Remover vínculo
    // ─────────────────────────────────────────────────────────────
    public function adicionarVinculo(): void
    {
        if (!$this->sel_disciplina_id || !$this->sel_turma_id) {
            $this->addError('vinculo', 'Selecione a disciplina e a turma.');
            return;
        }
        if (empty($this->sel_dias)) {
            $this->addError('vinculo', 'Selecione pelo menos um dia da semana.');
            return;
        }

        foreach ($this->vinculos as $v) {
            if ($v['disciplina_id'] == $this->sel_disciplina_id && $v['turma_id'] == $this->sel_turma_id) {
                $this->addError('vinculo', 'Este vínculo (disciplina + turma) já foi adicionado.');
                return;
            }
        }

        $turma = Turma::find($this->sel_turma_id);

        $this->vinculos[] = [
            'disciplina_id'   => $this->sel_disciplina_id,
            'disciplina_nome' => $this->sel_disciplina_nome,
            'curso_nome'      => $this->sel_curso_nome,
            'turma_id'        => $this->sel_turma_id,
            'turma_nome'      => $turma->nome,
            'dias'            => array_values($this->sel_dias),
        ];

        $this->cancelarSelecao();
    }

    public function removerVinculo(int $index): void
    {
        array_splice($this->vinculos, $index, 1);
        $this->vinculos = array_values($this->vinculos);
    }

    // ─────────────────────────────────────────────────────────────
    // CRUD principal
    // ─────────────────────────────────────────────────────────────
    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Novo Professor';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $p = Professor::with('disciplinasTurmas.disciplina.curso', 'disciplinasTurmas.turma')->findOrFail($id);
        $this->professorId = $p->id;
        $this->nome        = $p->nome;
        $this->email       = $p->email;
        $this->telefone    = $p->telefone ?? '';
        $this->cpf         = $p->cpf;
        $this->modalTitle  = 'Editar Professor';

        $this->vinculos = $p->disciplinasTurmas->map(fn($pd) => [
            'disciplina_id'   => $pd->disciplina_id,
            'disciplina_nome' => $pd->disciplina->nome,
            'curso_nome'      => $pd->disciplina->curso->nome ?? '',
            'turma_id'        => $pd->turma_id,
            'turma_nome'      => $pd->turma->nome,
            'dias'            => $pd->dias_semana ?? [],
        ])->toArray();

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = is_null($this->professorId);
        $cpfFormatado = $this->formatarCPF($this->cpf);

        $professor = Professor::updateOrCreate(
            ['id' => $this->professorId],
            [
                'nome'     => $this->nome,
                'email'    => $this->email,
                'telefone' => $this->telefone ?: null,
                'cpf'      => $cpfFormatado,
            ]
        );

        ProfessorDisciplina::where('professor_id', $professor->id)->delete();

        foreach ($this->vinculos as $v) {
            ProfessorDisciplina::create([
                'professor_id'  => $professor->id,
                'disciplina_id' => $v['disciplina_id'],
                'turma_id'      => $v['turma_id'],
                'dias_semana'   => $v['dias'],
            ]);
        }

        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Professores',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $professor->nome
        );

        session()->flash('success', $isNovo
            ? 'Professor cadastrado com sucesso!'
            : 'Professor atualizado com sucesso!');

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->professorId = $id;
        $this->showDelete  = true;
    }

    public function delete(): void
    {
        $p = Professor::findOrFail($this->professorId);
        if ($p->aulas()->count() > 0) {
            session()->flash('error', 'Não é possível excluir pois este professor possui aulas vinculadas.');
            $this->showDelete = false;
            return;
        }
        $nomeProfessor = $p->nome;
        $p->disciplinasTurmas()->delete();
        $p->delete();
        $this->showDelete = false;
        $this->resetForm();
        Log::registrar('excluiu', 'Professores', 'Excluiu: ' . $nomeProfessor);
        session()->flash('success', 'Professor excluído com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->professorId         = null;
        $this->nome                = '';
        $this->email               = '';
        $this->telefone            = '';
        $this->cpf                 = '';
        $this->vinculos            = [];
        $this->buscaDisciplina     = '';
        $this->mostrarLista        = false;
        $this->sel_disciplina_id   = '';
        $this->sel_disciplina_nome = '';
        $this->sel_curso_nome      = '';
        $this->sel_turma_id        = '';
        $this->sel_dias            = [];
        $this->turmasDoVinculo     = [];
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltro(): void  { $this->resetPage(); $this->search = ''; }

    // ─────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────
    public function render()
    {
        $professores = Professor::query()
            ->when($this->search, function ($q) {
                $s = $this->search;
                match ($this->filtro) {
                    'nome'     => $q->where('nome', 'like', "%$s%"),
                    'email'    => $q->where('email', 'like', "%$s%"),
                    'cpf'      => $q->where('cpf', 'like', "%$s%"),
                    'telefone' => $q->where('telefone', 'like', "%$s%"),
                    default    => $q->where('nome', 'like', "%$s%")
                                    ->orWhere('email', 'like', "%$s%")
                                    ->orWhere('cpf', 'like', "%$s%"),
                };
            })
            ->orderBy('nome')
            ->paginate(10);

        $disciplinasDisponiveis = [];
        if ($this->showModal && $this->mostrarLista) {
            $disciplinasDisponiveis = Disciplina::with('curso')
                ->where('nome', 'like', '%' . $this->buscaDisciplina . '%')
                ->orderBy('nome')
                ->get()
                ->map(fn($d) => [
                    'id'            => $d->id,
                    'nome'          => $d->nome,
                    'curso_id'      => $d->curso_id,
                    'curso_nome'    => $d->curso->nome ?? '—',
                    'semestre_grade'=> $d->semestre_grade,
                ])
                ->toArray();
        }

        $diasNomes = $this->diasNomes();

        return view('livewire.professores-crud', compact(
            'professores',
            'disciplinasDisponiveis',
            'diasNomes'
        ));
    }
}
