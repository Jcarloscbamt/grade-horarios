<?php
// app/Models/ProfessorCompetencia.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessorCompetencia extends Model
{
    protected $table = 'professor_competencias';

    protected $fillable = [
        'professor_id',
        'curso_id',
        'disciplina_id',
    ];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class);
    }
}
