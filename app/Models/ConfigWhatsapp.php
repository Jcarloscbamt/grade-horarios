<?php
// app/Models/ConfigWhatsapp.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigWhatsapp extends Model
{
    protected $table = 'config_whatsapps';

    protected $fillable = [
        'provider', 'mode', 'default_country',
        'meta_token', 'meta_phone_id', 'meta_api_version', 'meta_template', 'meta_template_lang',
        'twilio_sid', 'twilio_token', 'twilio_from', 'twilio_content_sid',
    ];

    // Tokens guardados criptografados no banco
    protected $casts = [
        'meta_token'   => 'encrypted',
        'twilio_token' => 'encrypted',
    ];

    /** Registro único de configuração (cria se não existir) */
    public static function atual(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
