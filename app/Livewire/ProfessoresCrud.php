<?php
// app/Livewire/ProfessoresCrud.php
namespace App\Livewire;

use App\Models\Professor;
use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;

class ProfessoresCrud extends Component
{
    use WithPagination;

    public ?int   $professorId = null;
    public string $nome        = '';
    public string $email       = '';
    public string $telefone    = '';
    public string $cpf         = '';

    public bool   $showModal   = false;
    public bool   $showDelete  = false;
    public string $search      = '';
    public string $filtro      = 'todos';
    public string $modalTitle  = '';

    protected $queryString = ['search', 'filtro'];

    protected function rules(): array
    {
        return [
            'nome'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:100|unique:professores,email,' . ($this->professorId ?? 'NULL'),
            'telefone' => 'nullable|min:13|max:15',
            'cpf'      => [
                'required', 'min:14', 'max:14',
                function ($attribute, $value, $fail) {
                    // Valida formato
                    if (!$this->validarCPF($value)) {
                        $fail('CPF inválido. Verifique o número digitado.');
                        return;
                    }
                    // Verifica duplicidade normalizando (remove pontuação para comparar)
                    $cpfNumeros = preg_replace('/\D/', '', $value);
                    $existe = \App\Models\Professor::whereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$cpfNumeros])
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
        'cpf.unique'        => 'Este CPF já está cadastrado.',
        'telefone.min'      => 'Telefone incompleto. Use o formato (00) 00000-0000.',
    ];

    // ── Formatadores ─────────────────────────────────────────────

    private function formatarCPF(string $v): string
    {
        $v = preg_replace('/\D/', '', $v);
        $v = substr($v, 0, 11); // limita 11 dígitos
        $len = strlen($v);
        if ($len <= 3)  return $v;
        if ($len <= 6)  return substr($v,0,3).'.'.substr($v,3);
        if ($len <= 9)  return substr($v,0,3).'.'.substr($v,3,3).'.'.substr($v,6);
        return substr($v,0,3).'.'.substr($v,3,3).'.'.substr($v,6,3).'-'.substr($v,9,2);
    }

    private function formatarTelefone(string $v): string
    {
        $v = preg_replace('/\D/', '', $v);
        $v = substr($v, 0, 11); // limita 11 dígitos
        $len = strlen($v);
        if ($len <= 2)  return $len ? '('.$v : $v;
        if ($len <= 6)  return '('.substr($v,0,2).') '.substr($v,2);
        if ($len <= 10) return '('.substr($v,0,2).') '.substr($v,2,4).'-'.substr($v,6);
        return '('.substr($v,0,2).') '.substr($v,2,5).'-'.substr($v,7,4);
    }

    private function validarCPF(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t+1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }

    // ── Hooks de atualização em tempo real ───────────────────────

    public function updatedCpf(string $value): void
    {
        $this->cpf = $this->formatarCPF($value);
    }

    public function updatedTelefone(string $value): void
    {
        $this->telefone = $this->formatarTelefone($value);
    }

    // ── CRUD ─────────────────────────────────────────────────────

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Novo Professor';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $p = Professor::findOrFail($id);
        $this->professorId = $p->id;
        $this->nome        = $p->nome;
        $this->email       = $p->email;
        $this->telefone    = $p->telefone ?? '';
        $this->cpf         = $p->cpf;
        $this->modalTitle  = 'Editar Professor';
        $this->showModal   = true;
    }

    public function save(): void
    {
        $this->validate();


        $isNovo = is_null($this->professorId);
        // Garante que CPF é salvo sempre no formato 000.000.000-00
        $cpfFormatado = $this->formatarCPF($this->cpf);

        Professor::updateOrCreate(
            ['id' => $this->professorId],
            [
                'nome'     => $this->nome,
                'email'    => $this->email,
                'telefone' => $this->telefone ?: null,
                'cpf'      => $cpfFormatado,
            ]
        );

        $this->showModal = false;
        $this->resetForm();


        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Professores',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $this->nome
        );
        session()->flash('success', $this->professorId
            ? 'Professor atualizado com sucesso!'
            : 'Professor cadastrado com sucesso!');
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
        $p->delete();
        $this->showDelete = false;
        $this->resetForm();
        // Log da ação
        Log::registrar('excluiu', 'Professores', 'Excluiu: ' . $p->nome);
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
        $this->professorId = null;
        $this->nome = $this->email = $this->telefone = $this->cpf = '';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltro(): void  { $this->resetPage(); $this->search = ''; }

    public function render()
    {
        $professores = Professor::query()
            ->when($this->search, function($q) {
                $s = $this->search;
                match($this->filtro) {
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

        return view('livewire.professores-crud', compact('professores'));
    }
}
