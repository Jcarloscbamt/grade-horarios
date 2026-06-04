<?php
// app/Console/Commands/EnviarAvisosAulas.php
namespace App\Console\Commands;

use App\Services\EnviarAvisoAulas;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnviarAvisosAulas extends Command
{
    protected $signature = 'avisos:aulas {--semanal : Envia o resumo semanal em vez do diário}';
    protected $description = 'Envia e-mail aos professores avisando das aulas do dia seguinte (ou da semana)';

    public function handle(EnviarAvisoAulas $service): int
    {
        $semanal = $this->option('semanal');

        if ($semanal) {
            $this->info('Enviando resumo SEMANAL para os professores...');
            $resultado = $service->enviarParaTodos(true);
        } else {
            // Dia de AMANHÃ (1=Seg ... 5=Sex)
            $amanha = Carbon::tomorrow()->dayOfWeekIso; // 1=Seg ... 7=Dom
            if ($amanha > 5) {
                $this->info('Amanhã é fim de semana — nenhum aviso enviado.');
                return self::SUCCESS;
            }
            $this->info("Enviando avisos das aulas de amanhã (dia {$amanha})...");
            $resultado = $service->enviarParaTodos(false, $amanha);
        }

        $this->info("✓ {$resultado['enviados']} e-mail(s) enviado(s).");
        foreach ($resultado['detalhes'] as $d) {
            $this->line('  ' . $d);
        }

        return self::SUCCESS;
    }
}
