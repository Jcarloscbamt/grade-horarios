<?php
// app/Livewire/UsuariosCrud.php
namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
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

    // Evita reset de paginação ao abrir/fechar modal
    protected $queryString = ['search'];

    protected function rules(): array
    {
        $passwordRule = $this->usuarioId
            ? 'nullable|min:8'
            : 'required|min:8';

        return [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:100|unique:users,email,' . ($this->usuarioId ?? 'NULL'),
            'password' => $passwordRule,
            'role'     => 'required|in:admin,coordenador,consulta',
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
        // Busca o usuário SEM resetar a paginação
        $user = User::findOrFail($id);
        $this->usuarioId  = $user->id;
        $this->name       = $user->name;
        $this->email      = $user->email;
        $this->password   = '';
        $this->role       = $user->getRoleNames()->first() ?? '';
        $this->modalTitle = 'Editar Usuário';
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNovo = !$this->usuarioId;

        $data = [
            'name'  => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
            if ($isNovo || $this->usuarioId !== auth()->id()) {
                $data['password_change_required'] = true;
            }
        }

        if ($isNovo) {
            $data['password_change_required'] = true;
        }

        $user = User::updateOrCreate(['id' => $this->usuarioId], $data);
        $user->syncRoles([$this->role]);

        // Fecha modal e limpa form SEM resetar paginação
        $this->showModal = false;
        $this->resetForm();

        session()->flash('success', $isNovo
            ? 'Usuário cadastrado! Ele será solicitado a trocar a senha no primeiro login.'
            : 'Usuário atualizado com sucesso!'
        );
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
        User::findOrFail($this->usuarioId)->delete();
        $this->showDelete = false;
        $this->resetForm();
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
        $this->name      = '';
        $this->email     = '';
        $this->password  = '';
        $this->role      = '';
        $this->resetValidation();
    }

    // Só reseta página ao pesquisar — não ao abrir modal
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $usuarios = User::with('roles')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.usuarios-crud', compact('usuarios'));
    }
}
