<?php
// app/Livewire/UsuariosCrud.php
namespace App\Livewire;

use App\Models\User;
use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsuariosCrud extends Component
{
    use WithPagination;

    public ?int   $usuarioId   = null;
    public string $name        = '';
    public string $email       = '';
    public string $password    = '';
    public string $perfil      = '';   // renomeado de 'role' para evitar conflito com @foreach no blade
    public bool   $ativo       = true;

    public bool   $showModal   = false;
    public bool   $showDelete  = false;
    public string $search      = '';
    public string $filtroAtivo = 'todos';
    public string $modalTitle  = '';

    protected function rules(): array
    {
        return [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:100|unique:users,email,' . ($this->usuarioId ?? 'NULL'),
            'password' => $this->usuarioId ? 'nullable|min:8' : 'required|min:8',
            'perfil'   => 'required|in:admin,coordenador,consulta',
        ];
    }

    protected array $messages = [
        'name.required'     => 'O nome é obrigatório.',
        'name.min'          => 'O nome deve ter no mínimo 3 caracteres.',
        'email.required'    => 'O e-mail é obrigatório.',
        'email.unique'      => 'Este e-mail já está cadastrado.',
        'password.required' => 'A senha é obrigatória.',
        'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
        'perfil.required'   => 'Selecione um perfil.',
        'perfil.in'         => 'Perfil inválido.',
    ];

    public function create(): void
    {
        $this->limparFormulario();
        $this->ativo = true;
        $this->modalTitle = 'Novo Usuário';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->usuarioId  = $user->id;
        $this->name       = $user->name;
        $this->email      = $user->email;
        $this->password   = '';
        $this->perfil     = $user->getRoleNames()->first() ?? '';
        $this->ativo      = (bool) ($user->ativo ?? true);
        $this->modalTitle = 'Editar Usuário';
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = is_null($this->usuarioId);
        $nomeLog = $this->name;

        $data = [
            'name'  => $this->name,
            'email' => $this->email,
            'ativo' => $this->ativo,
        ];

        if ($this->password) {
            $data['password']                 = Hash::make($this->password);
            $data['password_change_required'] = false;
        }

        if ($isNovo) {
            $data['password_change_required'] = true;
        }

        $user = User::updateOrCreate(['id' => $this->usuarioId], $data);
        $user->syncRoles([$this->perfil]);

        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Usuários',
            ($isNovo ? 'Novo usuário: ' : 'Editou usuário: ') . $nomeLog
        );

        $this->showModal = false;
        $this->limparFormulario();
        session()->flash('success', $isNovo ? 'Usuário cadastrado com sucesso!' : 'Usuário atualizado com sucesso!');
    }


    public function toggleAtivo(int $id): void
    {
        // Não permite desativar o próprio usuário logado
        if ($id === auth()->id()) {
            session()->flash('error', 'Você não pode desativar seu próprio usuário.');
            return;
        }

        $user   = \App\Models\User::findOrFail($id);
        $novoStatus = !((bool) $user->ativo);

        // Usa DB direto para evitar problemas de cast
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $id)
            ->update(['ativo' => $novoStatus, 'updated_at' => now()]);

        $status = $novoStatus ? 'ativado' : 'desativado';
        session()->flash('success', 'Usuário ' . $status . ' com sucesso!');
        \App\Models\Log::registrar('editou', 'Usuários', 'Usuário ' . $status . ': ' . $user->name);
    }

    public function confirmDelete(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'Você não pode excluir seu próprio usuário.');
            return;
        }
        $this->usuarioId  = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $user = User::findOrFail($this->usuarioId);
        $nome = $user->name;
        $user->delete();

        Log::registrar('excluiu', 'Usuários', "Excluiu usuário: {$nome}");

        $this->showDelete = false;
        $this->limparFormulario();
        session()->flash('success', 'Usuário excluído com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->limparFormulario();
    }

    // Renomeado para não conflitar com resetForm do Livewire
    private function limparFormulario(): void
    {
        $this->usuarioId = null;
        $this->name      = '';
        $this->email     = '';
        $this->password  = '';
        $this->perfil    = '';
        $this->ativo     = true;
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $busca = $this->search;

        $usuarios = User::with('roles')
            ->when($this->filtroAtivo === 'ativos', fn($q) => $q->where('ativo', true))
            ->when($this->filtroAtivo === 'inativos', fn($q) => $q->where('ativo', false))
            ->when($busca, fn($q) =>
                $q->where('name', 'like', "%{$busca}%")
                  ->orWhere('email', 'like', "%{$busca}%")
            )
            ->orderBy('name')
            ->paginate(20);

        $roles = Role::orderBy('name')->get();

        return view('livewire.usuarios-crud', compact('usuarios', 'roles'));
    }
}
