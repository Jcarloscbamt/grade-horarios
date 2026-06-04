<?php
// app/Console/Commands/EnviarAvisosAulas.php
namespace App\Console\Commands;

use App\Services\EnviarAvisoAulas;
use App\Models\ConfigEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnviarAvisosAulas extends Command
{
    protected $signature = 'avisos:aulas
        {--semanal : Envia o resumo semanal em vez do diário}
        {--forcar : Ignora a verificação de ativo/horário (envio manual via terminal)}';
    protected $description = 'Envia e-mail aos professores avisando das aulas (respeita a configuração de envio automático)';

    public function handle(EnviarAvisoAulas $service): int
    {
        $cfg     = ConfigEmail::atual();
        $semanal = $this->option('semanal');
        $forcar  = $this->option('forcar');

        if ($semanal) {
            // Verifica se o envio semanal está ativo (a menos que --forcar)
            if (!$forcar && !$cfg->envio_semanal_ativo) {
                $this->info('Envio semanal está DESATIVADO na configuração. Use --forcar para enviar mesmo assim.');
                return self::SUCCESS;
            }
            $this->info('Enviando resumo SEMANAL para os professores...');
            $resultado = $service->enviarParaTodos(true, null, 'semanal');
        } else {
            if (!$forcar && !$cfg->envio_diario_ativo) {
                $this->info('Envio diário está DESATIVADO na configuração. Use --forcar para enviar mesmo assim.');
                return self::SUCCESS;
            }
            $amanha = Carbon::tomorrow()->dayOfWeekIso;
            if ($amanha > 5) {
                $this->info('Amanhã é fim de semana — nenhum aviso enviado.');
                return self::SUCCESS;
            }
            $this->info("Enviando avisos das aulas de amanhã (dia {$amanha})...");
            $resultado = $service->enviarParaTodos(false, $amanha, 'diario');
        }

        $this->info("✓ {$resultado['enviados']} e-mail(s) enviado(s).");
        foreach ($resultado['detalhes'] as $d) {
            $this->line('  ' . $d);
        }

        return self::SUCCESS;
    }
}
