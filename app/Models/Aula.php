<?php
// app/Models/Aula.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aula extends Model
{
    protected $table = 'aulas';
    
    protected $fillable = [
        'turma_id', 'disciplina_id', 'professor_id',
        'sala_id', 'horario_id', 'periodo_letivo_id',
        'dia_semana', 'modalidade',
    ];

    public function turma(): BelongsTo { return $this->belongsTo(Turma::class); }
    public function disciplina(): BelongsTo { return $this->belongsTo(Disciplina::class); }
    public function professor(): BelongsTo { return $this->belongsTo(Professor::class); }
    public function sala(): BelongsTo { return $this->belongsTo(Sala::class); }
    public function horario(): BelongsTo { return $this->belongsTo(Horario::class); }
    public function periodoLetivo(): BelongsTo { return $this->belongsTo(PeriodoLetivo::class); }
}
