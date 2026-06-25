<?php
// routes/console.php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ConfigEmail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Agendamento dinâmico dos avisos (e-mail e/ou WhatsApp) ──
// Roda a cada minuto e verifica se é a hora configurada na tela /envio-emails.
// O comando avisos:aulas decide, internamente, quais canais enviar conforme os toggles.
Schedule::call(function () {
    $cfg   = ConfigEmail::atual();
    $agora = now()->format('H:i');

    // DIÁRIO: dispara se o e-mail OU o WhatsApp diário estiver ativo, no horário configurado
    $diarioAtivo = $cfg->envio_diario_ativo || $cfg->whatsapp_diario_ativo;
    if ($diarioAtivo && substr($cfg->horario_diario, 0, 5) === $agora) {
        Artisan::call('avisos:aulas');
    }

    // SEMANAL: dispara no dia e horário configurados, se algum canal semanal estiver ativo
    $semanalAtivo = $cfg->envio_semanal_ativo || $cfg->whatsapp_semanal_ativo;
    if ($semanalAtivo
        && (int) now()->dayOfWeekIso === (int) $cfg->dia_semanal
        && substr($cfg->horario_semanal, 0, 5) === $agora) {
        Artisan::call('avisos:aulas', ['--semanal' => true]);
    }
})->everyMinute();

// ── Limpeza automática de logs antigos (uma vez por dia, de madrugada) ──
// Só age se a limpeza automática estiver ligada na tela (log_limpeza_auto).
Schedule::command('logs:limpar')->dailyAt('03:30');
