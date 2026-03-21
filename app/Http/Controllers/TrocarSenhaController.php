<?php
// app/Http/Controllers/TrocarSenhaController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TrocarSenhaController extends Controller
{
    public function show()
    {
        return view('auth.trocar-senha');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password'         => 'required|min:8',
            'password_confirm' => 'required|min:8|same:password',
        ], [
            'password.required'         => 'A nova senha é obrigatória.',
            'password.min'              => 'A senha deve ter no mínimo 8 caracteres.',
            'password_confirm.required' => 'A confirmação de senha é obrigatória.',
            'password_confirm.min'      => 'A senha deve ter no mínimo 8 caracteres.',
            'password_confirm.same'     => 'As senhas não coincidem. Digite novamente.',
        ]);

        DB::table('users')
            ->where('id', auth()->id())
            ->update([
                'password'                 => Hash::make($request->password),
                'password_change_required' => false,
                'updated_at'               => now(),
            ]);

        return redirect()->route('dashboard')
            ->with('success', 'Senha alterada com sucesso!');
    }
}
