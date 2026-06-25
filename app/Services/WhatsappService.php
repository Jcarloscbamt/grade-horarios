<?php
// app/Services/WhatsappService.php
namespace App\Services;

use App\Models\ConfigWhatsapp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provedor: Meta Cloud API (WhatsApp Business oficial).
 * Lê as credenciais da configuração no banco (tela) e, se vazias, do .env (config/whatsapp.php).
 */
class WhatsappService implements WhatsappProvider
{
    private string $token;
    private string $phoneId;
    private string $version;
    private string $country;

    public function __construct()
    {
        $cfg = ConfigWhatsapp::atual();
        $this->token   = (string) ($cfg->meta_token        ?: config('whatsapp.token'));
        $this->phoneId = (string) ($cfg->meta_phone_id     ?: config('whatsapp.phone_number_id'));
        $this->version = (string) ($cfg->meta_api_version  ?: config('whatsapp.api_version', 'v25.0'));
        $this->country = (string) ($cfg->default_country   ?: config('whatsapp.default_country', '55'));
    }

    public function nome(): string { return 'Meta Cloud API'; }

    public function configurado(): bool
    {
        return $this->token !== '' && $this->phoneId !== '';
    }

    public function normalizarTelefone(?string $telefone): ?string
    {
        if (empty($telefone)) return null;
        $num = preg_replace('/\D/', '', $telefone);
        if ($num === '') return null;
        if (strpos($num, $this->country) !== 0) {
            $num = $this->country . $num;
        }
        return $num;
    }

    private function endpoint(): string
    {
        return "https://graph.facebook.com/{$this->version}/{$this->phoneId}/messages";
    }

    public function enviarTexto(string $telefone, string $texto): array
    {
        $para = $this->normalizarTelefone($telefone);
        if (!$para) return ['ok' => false, 'erro' => 'Telefone inválido'];
        if (!$this->configurado()) return ['ok' => false, 'erro' => 'Meta não configurado (token/phone_id)'];

        return $this->postar([
            'messaging_product' => 'whatsapp',
            'to'                => $para,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $texto],
        ]);
    }

    public function enviarTemplate(string $telefone, string $template, string $idioma, array $parametros = []): array
    {
        $para = $this->normalizarTelefone($telefone);
        if (!$para) return ['ok' => false, 'erro' => 'Telefone inválido'];
        if (!$this->configurado()) return ['ok' => false, 'erro' => 'Meta não configurado (token/phone_id)'];

        $componentes = [];
        if (!empty($parametros)) {
            $componentes[] = [
                'type'       => 'body',
                'parameters' => array_map(fn($p) => ['type' => 'text', 'text' => (string) $p], $parametros),
            ];
        }

        return $this->postar([
            'messaging_product' => 'whatsapp',
            'to'                => $para,
            'type'              => 'template',
            'template'          => [
                'name'       => $template,
                'language'   => ['code' => $idioma],
                'components' => $componentes,
            ],
        ]);
    }

    private function postar(array $payload): array
    {
        try {
            $resp = Http::withToken($this->token)->acceptJson()->timeout(20)
                ->post($this->endpoint(), $payload);

            if ($resp->successful()) {
                return ['ok' => true, 'id' => $resp->json('messages.0.id')];
            }
            $erro = $resp->json('error.message') ?? ('HTTP ' . $resp->status());
            Log::warning('Meta WhatsApp falhou: ' . $erro, ['resp' => $resp->body()]);
            return ['ok' => false, 'erro' => $erro];
        } catch (\Throwable $e) {
            Log::error('Meta WhatsApp exceção: ' . $e->getMessage());
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }
}
