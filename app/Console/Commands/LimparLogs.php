<?php
// app/Console/Commands/LimparLogs.php
namespace App\Console\Commands;

use App\Services\LimpezaLogs;
use App\Models\ConfigEmail;
use Illuminate\Console\Command;

class LimparLogs extends Command
{
    protected $signature = 'logs:limpar
        {--dias= : Quantos dias manter (sobrepõe a configuração)}
        {--forcar : Executa mesmo se a limpeza automática estiver desligada}';
    protected $description = 'Apaga logs antigos (sistema, e-mail e WhatsApp) conforme a retenção configurada';

    public function handle(LimpezaLogs $limpeza): int
    {
        $cfg = ConfigEmail::atual();

        // Sem --forcar, só roda se a limpeza automática estiver ligada
        if (!$this->option('forcar') && !$cfg->log_limpeza_auto) {
            return self::SUCCESS;
        }

        $dias = $this->option('dias') !== null
            ? (int) $this->option('dias')
            : (int) ($cfg->log_retencao_dias ?? 0);

        if ($dias <= 0) {
            $this->info('Retenção definida como 0 (nunca apagar) — nada foi removido.');
            return self::SUCCESS;
        }

        $r = $limpeza->limpar($dias);
        $this->info("Limpeza concluída (mantendo {$r['dias']} dias): "
            . "{$r['logs']} log(s), {$r['emails']} e-mail(s), {$r['whatsapp']} WhatsApp — total {$r['total']}.");

        return self::SUCCESS;
    }
}
