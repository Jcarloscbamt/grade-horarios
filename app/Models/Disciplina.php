<?php
// app/Models/Disciplina.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disciplina extends Model
{
    protected $table = 'disciplinas';

    protected $fillable = [
        'curso_id',
        'nome',
        'carga_horaria',
        'semestre_grade',
        'tipo_sala',
        'bloco_preferencial',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }

    // Retorna a descrição concatenada do tipo de sala + bloco
    // Ex: "Laboratório - Bloco B" ou "Sala de Aula" (sem bloco)
    public function getSalaPreferencialAttribute(): string
    {
        if (!$this->tipo_sala) return '—';
        if ($this->bloco_preferencial) {
            return $this->tipo_sala . ' - Bloco ' . $this->bloco_preferencial;
        }
        return $this->tipo_sala;
    }
}
