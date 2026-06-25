<?php
// app/Services/WhatsappManager.php
namespace App\Services;

use App\Models\ConfigWhatsapp;

/**
 * Decide qual provedor de WhatsApp usar (Meta ou Twilio), conforme a configuração.
 * O resto do sistema pede o provedor ativo aqui e não precisa saber qual é.
 */
class WhatsappManager
{
    /** Retorna a instância do provedor ativo */
    public function ativo(): WhatsappProvider
    {
        $provider = ConfigWhatsapp::atual()->provider ?: 'meta';
        return $this->provedor($provider);
    }

    /** Retorna um provedor específico pelo nome ('meta' | 'twilio') */
    public function provedor(string $nome): WhatsappProvider
    {
        return match ($nome) {
            'twilio' => app(TwilioWhatsappService::class),
            default  => app(WhatsappService::class), // meta
        };
    }

    /** Modo de envio configurado: 'text' | 'template' */
    public function modo(): string
    {
        return ConfigWhatsapp::atual()->mode ?: 'text';
    }
}
