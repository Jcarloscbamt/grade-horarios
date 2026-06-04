<?php
// app/Models/LogEmail.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogEmail extends Model
{
    protected $table = 'log_emails';

    protected $fillable = [
        'professor_id', 'professor_nome', 'email', 'tipo',
        'sucesso', 'erro', 'qtd_aulas', 'enviado_em',
    ];

    protected $casts = [
        'sucesso'    => 'boolean',
        'enviado_em' => 'datetime',
    ];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    // Rótulo amigável do tipo
    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'diario'  => 'Diário (amanhã)',
            'semanal' => 'Resumo semanal',
            default   => 'Manual',
        };
    }
}
