<?php
// app/Models/ConfigEmail.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigEmail extends Model
{
    protected $table = 'config_emails';

    protected $fillable = [
        'envio_diario_ativo', 'horario_diario',
        'envio_semanal_ativo', 'dia_semanal', 'horario_semanal',
        'whatsapp_diario_ativo', 'whatsapp_semanal_ativo',
        'log_retencao_dias', 'log_limpeza_auto',
    ];

    protected $casts = [
        'envio_diario_ativo'     => 'boolean',
        'envio_semanal_ativo'    => 'boolean',
        'whatsapp_diario_ativo'  => 'boolean',
        'whatsapp_semanal_ativo' => 'boolean',
        'dia_semanal'            => 'integer',
        'log_retencao_dias'      => 'integer',
        'log_limpeza_auto'       => 'boolean',
    ];

    // Retorna o registro único de configuração (cria se não existir)
    public static function atual(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
