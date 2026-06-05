<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GradeImpressaoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrocarSenhaController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Troca de senha obrigatória
    Route::middleware(['auth'])->group(function () {
    Route::get('/trocar-senha',  [TrocarSenhaController::class, 'show'])
        ->name('password.change');
    Route::post('/trocar-senha', [TrocarSenhaController::class, 'update'])
        ->name('password.change.update');
});

// Rotas do sistema — todas protegidas por auth + verified
Route::middleware(['auth', 'verified'])->group(function () {

    // Grade e Gerador
    Route::get('/grade',         \App\Livewire\GradeHorarios::class)->name('grade');
    Route::get('/gerador-grade', \App\Livewire\GeradorGrade::class)->name('gerador-grade');

    // Cadastros
    Route::get('/cursos',      \App\Livewire\CursosCrud::class)->name('cursos');
    Route::get('/turmas',      \App\Livewire\TurmasCrud::class)->name('turmas');
    Route::get('/disciplinas', \App\Livewire\DisciplinasCrud::class)->name('disciplinas');
    Route::get('/professores', \App\Livewire\ProfessoresCrud::class)->name('professores');
    Route::get('/salas',       \App\Livewire\SalasCrud::class)->name('salas');
    Route::get('/aulas',       \App\Livewire\AulasCrud::class)->name('aulas');

    // Configurações
    Route::get('/horarios', \App\Livewire\HorariosCrud::class)->name('horarios');
    Route::get('/periodos', \App\Livewire\PeriodosLetivosCrud::class)->name('periodos');

    // Relatórios
    Route::get('/relatorios/grade',       \App\Livewire\RelatorioGrade::class)->name('relatorio.grade');
    Route::get('/relatorios/professores', \App\Livewire\RelatorioProfessores::class)->name('relatorio.professores');

    // Página de impressão da grade
    Route::get('/grade/imprimir', GradeImpressaoController::class)->name('grade.imprimir');

    // Somente Admin
    Route::get('/usuarios', \App\Livewire\UsuariosCrud::class)->middleware('role:admin')->name('usuarios');
    Route::get('/logs',     \App\Livewire\LogsCrud::class)->middleware('role:admin')->name('logs');
    Route::get('/envio-emails', \App\Livewire\EnvioEmails::class)->middleware('role:admin')->name('envio-emails');
    
    Route::get('/ajuda', \App\Livewire\Ajuda::class)->name('ajuda');

});

Route::get('/teste-print', fn() => view('teste-impressao'));

require __DIR__ . '/auth.php';