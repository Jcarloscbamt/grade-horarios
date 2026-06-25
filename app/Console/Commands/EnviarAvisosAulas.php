<?php
// app/Console/Commands/EnviarAvisosAulas.php
namespace App\Console\Commands;

use App\Services\EnviarAvisoAulas;
use App\Services\EnviarAvisoWhatsapp;
use App\Models\ConfigEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnviarAvisosAulas extends Command
{
    protected $signature = 'avisos:aulas
        {--semanal : Envia o resumo semanal em vez do diário}
        {--forcar : Ignora a verificação de ativo/horário (envio manual via terminal)}
        {--whatsapp : Força incluir o WhatsApp neste envio}
        {--so-whatsapp : Envia SOMENTE por WhatsApp (não envia e-mail)}';
    protected $description = 'Avisa os professores das aulas por e-mail e/ou WhatsApp, conforme os canais ativos na configuração';

    public function handle(EnviarAvisoAulas $service, EnviarAvisoWhatsapp $whatsapp): int
    {
        $cfg       = ConfigEmail::atual();
        $semanal   = $this->option('semanal');
        $forcar    = $this->option('forcar');
        $soWhats   = $this->option('so-whatsapp');
        $flagWhats = $this->option('whatsapp');
        $tipo      = $semanal ? 'semanal' : 'diario';

        // Toggles de cada canal para o tipo escolhido
        [$emailAtivo, $whatsAtivo] = $semanal
            ? [$cfg->envio_semanal_ativo, $cfg->whatsapp_semanal_ativo]
            : [$cfg->envio_diario_ativo, $cfg->whatsapp_diario_ativo];

        // Define quais canais enviar:
        //  - e-mail: a menos que --so-whatsapp; manda se forçado (--forcar) ou se o canal está ativo
        //  - whatsapp: manda se --so-whatsapp/--whatsapp, ou (em agendamento) se o canal está ativo
        $enviarEmail    = !$soWhats && ($forcar || $emailAtivo);
        $enviarWhatsapp = $soWhats || $flagWhats || (!$forcar && $whatsAtivo);

        // Se não é envio forçado e nenhum canal está ativo, não faz nada
        if (!$forcar && !$soWhats && !$flagWhats && !$emailAtivo && !$whatsAtivo) {
            $this->info("Envio {$tipo} está desativado para e-mail e WhatsApp. Use --forcar para enviar mesmo assim.");
            return self::SUCCESS;
        }

        // Determina o dia alvo e a regra de fim de semana (apenas para o diário)
        $diaAlvo = null;
        if (!$semanal) {
            $amanha = Carbon::tomorrow()->dayOfWeekIso;
            if ($amanha > 5) {
                $this->info('Amanhã é fim de semana — nenhum aviso enviado.');
                return self::SUCCESS;
            }
            $diaAlvo = $amanha;
            $this->info("Enviando avisos das aulas de amanhã (dia {$amanha})...");
        } else {
            $this->info('Enviando resumo SEMANAL...');
        }

        // ── E-MAIL ──
        if ($enviarEmail) {
            $r = $service->enviarParaTodos($semanal, $diaAlvo, $tipo);
            $this->info("✓ {$r['enviados']} e-mail(s) enviado(s).");
            if (!empty($r['detalhes'])) {
                foreach ($r['detalhes'] as $d) { $this->line('  ' . $d); }
            }
        } else {
            $this->line('— E-mail: não enviado (canal inativo ou --so-whatsapp).');
        }

        // ── WHATSAPP ──
        if ($enviarWhatsapp) {
            $rw = $whatsapp->enviarParaTodos($semanal, $diaAlvo, $tipo);
            $this->info("✓ {$rw['enviados']} WhatsApp enviado(s)." .
                ($rw['falhas'] ? " ({$rw['falhas']} falha(s))" : '') .
                ($rw['sem_telefone'] ? " — {$rw['sem_telefone']} sem telefone" : ''));
        } else {
            $this->line('— WhatsApp: não enviado (canal inativo).');
        }

        return self::SUCCESS;
    }
}
