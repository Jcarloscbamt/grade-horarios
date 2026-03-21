<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GradeImpressaoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrocarSenhaController;

Route::get('/', function () {
    return view('welcome');
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

// Rotas do sistema
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/grade',       \App\Livewire\GradeHorarios::class)->name('grade');
    Route::get('/cursos',      \App\Livewire\CursosCrud::class)->name('cursos');
    Route::get('/turmas',      \App\Livewire\TurmasCrud::class)->name('turmas');
    Route::get('/disciplinas', \App\Livewire\DisciplinasCrud::class)->name('disciplinas');
    Route::get('/professores', \App\Livewire\ProfessoresCrud::class)->name('professores');
    Route::get('/salas',       \App\Livewire\SalasCrud::class)->name('salas');
    Route::get('/horarios',    \App\Livewire\HorariosCrud::class)->name('horarios');
    Route::get('/periodos',    \App\Livewire\PeriodosLetivosCrud::class)->name('periodos');
    Route::get('/aulas',       \App\Livewire\AulasCrud::class)->name('aulas');
    Route::get('/usuarios',    \App\Livewire\UsuariosCrud::class)->middleware('role:admin')->name('usuarios');
    Route::get('/logs',        \App\Livewire\LogsCrud::class)->middleware('role:admin')->name('logs');

    // Página de impressão da grade
    Route::get('/grade/imprimir', GradeImpressaoController::class)->name('grade.imprimir');
});

require __DIR__.'/auth.php';