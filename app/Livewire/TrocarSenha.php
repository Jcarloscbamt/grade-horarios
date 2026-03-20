<?php
// app/Livewire/TrocarSenha.php
namespace App\Livewire;

use Livewire\Component;
use App\Models\User;

class TrocarSenha extends Component
{
    public string $password         = '';
    public string $password_confirm = '';

    protected function rules(): array
    {
        return [
            'password'         => 'required|min:8',
            'password_confirm' => 'required|min:8|same:password',
        ];
    }

    protected array $messages = [
        'password.required'         => 'A nova senha é obrigatória.',
        'password.min'              => 'A senha deve ter no mínimo 8 caracteres.',
        'password_confirm.required' => 'A confirmação de senha é obrigatória.',
        'password_confirm.min'      => 'A senha deve ter no mínimo 8 caracteres.',
        'password_confirm.same'     => 'As senhas não coincidem. Digite novamente.',
    ];

    public function save(): void
    {
        $this->validate();

        $user = User::find(auth()->id());

        if (!$user) {
            session()->flash('error', 'Usuário não encontrado.');
            return;
        }

        $user->password                 = $this->password;
        $user->password_change_required = false;
        $user->save();

        session()->flash('success', 'Senha alterada com sucesso!');

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        // Layout limpo sem navbar — usuário ainda não está "dentro" do sistema
        return view('livewire.trocar-senha')
            ->layout('layouts.guest');
    }
}
