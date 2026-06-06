<?php
// app/Models/Professor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Professor extends Model
{
    protected $table = 'professores';

    protected $fillable = ['nome', 'email', 'telefone', 'cpf', 'disponibilidade', 'ativo'];

    protected $casts = [
        'disponibilidade' => 'array',
        'ativo'           => 'boolean',
    ];

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }

    public function disciplinasTurmas(): HasMany
    {
        return $this->hasMany(ProfessorDisciplina::class);
    }

    // NÍVEL 1: competências (curso + disciplina que sabe lecionar, sem limite)
    public function competencias(): HasMany
    {
        return $this->hasMany(ProfessorCompetencia::class);
    }

    // Helper: retorna disponibilidade sempre como array
    public function getDisponibilidadeArrayAttribute(): array
    {
        $d = $this->disponibilidade;
        if (is_array($d)) return $d;
        if (is_string($d)) return json_decode($d, true) ?? [];
        return [];
    }
}
