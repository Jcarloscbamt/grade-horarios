<?php
// app/Livewire/TrocarSenha.php
namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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

        $userId = auth()->id();

        if (!$userId) {
            $this->addError('password', 'Sessão expirada. Faça login novamente.');
            return;
        }

        // DB direto — bypassa o cast 'hashed' do Model evitando hash duplo
        DB::table('users')
            ->where('id', $userId)
            ->update([
                'password'                 => Hash::make($this->password),
                'password_change_required' => false,
                'updated_at'               => now(),
            ]);

        // Redirect direto sem JS
        redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.trocar-senha')
            ->layout('layouts.guest');
    }
}
