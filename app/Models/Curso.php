<?php
// app/Models/Curso.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $fillable = [
        'nome', 'sigla', 'nivel', 'coordenador',
        'email_coord', 'telefone_coord', 'cor_grade',
        'total_semestres', 'ativo',
    ];

    protected $casts = [
        'ativo'            => 'boolean',
        'total_semestres'  => 'integer',
    ];

    public function turmas()
    {
        return $this->hasMany(Turma::class);
    }

    public function disciplinas()
    {
        return $this->hasMany(Disciplina::class);
    }
}
