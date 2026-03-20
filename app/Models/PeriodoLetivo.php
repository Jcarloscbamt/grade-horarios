<?php
// app/Models/PeriodoLetivo.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoLetivo extends Model
{
    protected $table = 'periodo_letivos';

    protected $fillable = [
        'nome', 'ano', 'semestre',
        'avaliacao1_inicio', 'avaliacao1_fim',
        'avaliacao2_inicio', 'avaliacao2_fim',
        'ativo',
    ];

    protected $casts = [
        'avaliacao1_inicio' => 'date',
        'avaliacao1_fim'    => 'date',
        'avaliacao2_inicio' => 'date',
        'avaliacao2_fim'    => 'date',
        'ativo'             => 'boolean',
    ];

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }
}
