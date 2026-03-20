<?php
// app/Models/Curso.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    protected $fillable = [
        'nome',
        'sigla',
        'nivel',
        'coordenador',
        'email_coord',
        'telefone_coord',
        'cor_grade',
    ];

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    public function disciplinas(): HasMany
    {
        return $this->hasMany(Disciplina::class);
    }

    // Retorna a cor do curso ou o vermelho UniSENAI como padrão
    public function getCorGradeAttribute($value): string
    {
        return $value ?? '#E30613';
    }
}
