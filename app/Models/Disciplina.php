<?php
// app/Models/Disciplina.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disciplina extends Model
{
    protected $fillable = [
        'curso_id',
        'nome',
        'carga_horaria',
        'semestre_grade',
        'tipo_sala',
        'bloco_preferencial',
        'sala_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function aulas()
    {
        return $this->hasMany(Aula::class);
    }
}
