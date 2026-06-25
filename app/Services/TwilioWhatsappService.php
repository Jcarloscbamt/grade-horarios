<?php
// app/Services/TwilioWhatsappService.php
namespace App\Services;

use App\Models\ConfigWhatsapp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provedor: Twilio (BSP oficial da Meta).
 * Texto livre funciona no Sandbox (números que enviaram "join <código>").
 * Em produção, usa Content Templates (ContentSid).
 */
class TwilioWhatsappService implements WhatsappProvider
{
    private string $sid;
    private string $token;
    private string $from;       // número remetente, ex.: +14155238886
    private string $contentSid; // template de produção (opcional)
    private string $country;

    public function __construct()
    {
        $cfg = ConfigWhatsapp::atual();
        $this->sid        = (string) ($cfg->twilio_sid         ?: config('whatsapp.twilio_sid', ''));
        $this->token      = (string) ($cfg->twilio_token       ?: config('whatsapp.twilio_token', ''));
        $this->from       = (string) ($cfg->twilio_from        ?: config('whatsapp.twilio_from', ''));
        $this->contentSid = (string) ($cfg->twilio_content_sid ?: '');
        $this->country    = (string) ($cfg->default_country    ?: '55');
    }

    public function nome(): string { return 'Twilio'; }

    public function configurado(): bool
    {
        return $this->sid !== '' && $this->token !== '' && $this->from !== '';
    }

    /** Twilio exige o prefixo whatsapp:+<DDI><numero> */
    public function normalizarTelefone(?string $telefone): ?string
    {
        if (empty($telefone)) return null;
        $num = preg_replace('/\D/', '', $telefone);
        if ($num === '') return null;
        if (strpos($num, $this->country) !== 0) {
            $num = $this->country . $num;
        }

        // Regra do 9º dígito no Brasil (DDI 55):
        // O WhatsApp registra contas SEM o 9 para DDDs >= 28.
        // Formato com 9: 55 + DD + 9 + 8 dígitos = 13 chars.
        // Removemos o 9 apenas quando DDD >= 28.
        if ($this->country === '55' && strlen($num) === 13) {
            $ddd = (int) substr($num, 2, 2);
            $nono = substr($num, 4, 1);
            if ($ddd >= 28 && $nono === '9') {
                $num = substr($num, 0, 4) . substr($num, 5); // remove o 9
            }
        }

        return 'whatsapp:+' . $num;
    }

    /** Garante o prefixo whatsapp: no remetente */
    private function fromFormatado(): string
    {
        $f = trim($this->from);
        if (str_starts_with($f, 'whatsapp:')) return $f;
        $f = ltrim($f, '+');
        return 'whatsapp:+' . $f;
    }

    private function endpoint(): string
    {
        return "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
    }

    public function enviarTexto(string $telefone, string $texto): array
    {
        $para = $this->normalizarTelefone($telefone);
        if (!$para) return ['ok' => false, 'erro' => 'Telefone inválido'];
        if (!$this->configurado()) return ['ok' => false, 'erro' => 'Twilio não configurado (sid/token/from)'];

        return $this->postar([
            'From' => $this->fromFormatado(),
            'To'   => $para,
            'Body' => $texto,
        ]);
    }

    /**
     * No Twilio, o "template" é um Content Template identificado por ContentSid.
     * Os parâmetros viram ContentVariables {"1":"...","2":"..."}.
     * O nome/idioma do template são ignorados aqui (o ContentSid já os define).
     */
    public function enviarTemplate(string $telefone, string $template, string $idioma, array $parametros = []): array
    {
        $para = $this->normalizarTelefone($telefone);
        if (!$para) return ['ok' => false, 'erro' => 'Telefone inválido'];
        if (!$this->configurado()) return ['ok' => false, 'erro' => 'Twilio não configurado (sid/token/from)'];
        if ($this->contentSid === '') {
            return ['ok' => false, 'erro' => 'Twilio sem ContentSid (template de produção não configurado)'];
        }

        $vars = [];
        foreach (array_values($parametros) as $i => $valor) {
            $vars[(string) ($i + 1)] = (string) $valor;
        }

        return $this->postar([
            'From'             => $this->fromFormatado(),
            'To'               => $para,
            'ContentSid'       => $this->contentSid,
            'ContentVariables' => json_encode($vars, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function postar(array $form): array
    {
        try {
            $resp = Http::asForm()
                ->withBasicAuth($this->sid, $this->token)
                ->timeout(20)
                ->post($this->endpoint(), $form);

            if ($resp->successful()) {
                return ['ok' => true, 'id' => $resp->json('sid')];
            }
            $erro = $resp->json('message') ?? ('HTTP ' . $resp->status());
            Log::warning('Twilio WhatsApp falhou: ' . $erro, ['resp' => $resp->body()]);
            return ['ok' => false, 'erro' => $erro];
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp exceção: ' . $e->getMessage());
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }
}
