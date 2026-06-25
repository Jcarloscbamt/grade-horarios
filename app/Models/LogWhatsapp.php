<?php
// app/Models/LogWhatsapp.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogWhatsapp extends Model
{
    protected $table = 'log_whatsapps';

    protected $fillable = [
        'professor_id', 'professor_nome', 'telefone',
        'tipo', 'sucesso', 'erro', 'qtd_aulas', 'message_id', 'enviado_em',
    ];

    protected $casts = [
        'sucesso'    => 'boolean',
        'enviado_em' => 'datetime',
    ];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }
}
