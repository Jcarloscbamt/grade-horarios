<?php
// app/Models/ProfessorDisciplina.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorDisciplina extends Model
{
    protected $table = 'professor_disciplinas';

    protected $fillable = [
        'professor_id',
        'disciplina_id',
        'turma_id',
        'dias_semana',
    ];

    protected $casts = [
        'dias_semana' => 'array',
    ];

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    // Nomes dos dias da semana formatados para exibição
    public function getDiasFormatadosAttribute(): string
    {
        $nomes = [1 => 'SEG', 2 => 'TER', 3 => 'QUA', 4 => 'QUI', 5 => 'SEX'];
        $dias  = $this->dias_semana ?? [];
        return implode(', ', array_map(fn($d) => $nomes[$d] ?? $d, $dias));
    }
}
