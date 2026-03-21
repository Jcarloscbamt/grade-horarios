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

    public ?int $usuarioId  = null;
    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public string $role     = '';

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $search   = '';
    public string $modalTitle = '';

    protected function rules(): array
    {
        $passwordRule = $this->usuarioId
            ? 'nullable|min:8'
            : 'required|min:8';

        return [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:100|unique:users,email,' . ($this->usuarioId ?? 'NULL'),
            'password' => $passwordRule,
            'role'     => 'required|in:admin,coordenador',
        ];
    }

    protected array $messages = [
        'name.required'     => 'O nome é obrigatório.',
        'email.required'    => 'O e-mail é obrigatório.',
        'email.unique'      => 'Este e-mail já está cadastrado.',
        'password.required' => 'A senha é obrigatória.',
        'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
        'role.required'     => 'Selecione um perfil.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Novo Usuário';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->usuarioId = $user->id;
        $this->name      = $user->name;
        $this->email     = $user->email;
        $this->password  = '';
        $this->role      = $user->getRoleNames()->first() ?? '';
        $this->modalTitle = 'Editar Usuário';
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'  => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $isNovo = is_null($this->usuarioId);
        $user = User::updateOrCreate(['id' => $this->usuarioId], $data);

        // Atualiza o perfil
        $user->syncRoles([$this->role]);

        $this->showModal = false;
        $this->resetForm();
        // Log da ação
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Usuários',
            ($isNovo ? 'Novo usuário: ' : 'Editou usuário: ') . $this->name
        );
        session()->flash('success', $isNovo ? 'Usuário cadastrado com sucesso!' : 'Usuário atualizado com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        // Impede que o admin exclua a si mesmo
        if ($id === auth()->id()) {
            session()->flash('error', 'Você não pode excluir seu próprio usuário.');
            return;
        }
        $this->usuarioId  = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        User::findOrFail($this->usuarioId)->delete();
        $this->showDelete = false;
        $this->resetForm();
        // Log da ação
        Log::registrar('excluiu', 'Usuários', 'Excluiu usuário: ' . $u->name);
        session()->flash('success', 'Usuário excluído com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->usuarioId = null;
        $this->name = $this->email = $this->password = $this->role = '';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $usuarios = User::with('roles')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->orderBy('name')
            ->paginate(10);

        $roles = Role::orderBy('name')->get();

        return view('livewire.usuarios-crud', compact('usuarios', 'roles'));
    }
}
