<?php
// app/Services/LimpezaLogs.php
namespace App\Services;

use App\Models\{Log, LogEmail, LogWhatsapp, ConfigEmail};
use Illuminate\Support\Carbon;

/**
 * Limpa logs antigos de TODAS as tabelas de log do sistema:
 *  - logs          (auditoria de ações)
 *  - log_emails    (histórico de e-mails)
 *  - log_whatsapps (histórico de WhatsApp)
 */
class LimpezaLogs
{
    /**
     * Apaga registros mais antigos que $dias.
     * @param int $dias  0 = não apaga nada (segurança)
     * @return array{logs:int, emails:int, whatsapp:int, total:int, dias:int}
     */
    public function limpar(int $dias): array
    {
        $dias = max(0, $dias);
        if ($dias === 0) {
            return ['logs' => 0, 'emails' => 0, 'whatsapp' => 0, 'total' => 0, 'dias' => 0];
        }

        $limite = Carbon::now()->subDays($dias);

        $qLogs   = Log::where('created_at', '<', $limite)->count();
        Log::where('created_at', '<', $limite)->delete();

        $qEmails = LogEmail::where('created_at', '<', $limite)->count();
        LogEmail::where('created_at', '<', $limite)->delete();

        $qWhats  = 0;
        if (\Schema::hasTable('log_whatsapps')) {
            $qWhats = LogWhatsapp::where('created_at', '<', $limite)->count();
            LogWhatsapp::where('created_at', '<', $limite)->delete();
        }

        return [
            'logs'     => $qLogs,
            'emails'   => $qEmails,
            'whatsapp' => $qWhats,
            'total'    => $qLogs + $qEmails + $qWhats,
            'dias'     => $dias,
        ];
    }

    /** Usa a retenção salva na configuração */
    public function limparPelaConfig(): array
    {
        $cfg = ConfigEmail::atual();
        return $this->limpar((int) ($cfg->log_retencao_dias ?? 0));
    }
}
