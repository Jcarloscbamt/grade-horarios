<?php
// app/Models/ProfessorDisciplina.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessorDisciplina extends Model
{
    protected $table = 'professor_disciplinas';

    protected $fillable = [
        'professor_id',
        'disciplina_id',
        'turma_id',
        'dias',
    ];

    // CAST CRÍTICO: garante que 'dias' seja salvo como JSON
    // e lido de volta como array PHP automaticamente
    protected $casts = [
        'dias' => 'array',
    ];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class);
    }
}
