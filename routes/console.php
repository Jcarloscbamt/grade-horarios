<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ConfigEmail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Todo dia às 18h envia aviso das aulas de amanhã
    Schedule::command('avisos:aulas')->dailyAt('18:00');

// Toda segunda às 7h envia o resumo semanal (opcional)
    Schedule::command('avisos:aulas --semanal')->weeklyOn(1, '07:00');  
    
    
// routes/console.php — adicione ESTE bloco (mantenha o que já existe no topo)



// ── Agendamento dinâmico dos avisos de aula ──
// Roda a cada minuto e verifica se é a hora configurada pelo usuário.
Schedule::call(function () {
    $cfg = ConfigEmail::atual();
    $agora = now()->format('H:i');

    // Envio diário (aulas de amanhã)
    if ($cfg->envio_diario_ativo && substr($cfg->horario_diario, 0, 5) === $agora) {
        \Illuminate\Support\Facades\Artisan::call('avisos:aulas', ['--forcar' => true]);
    }

    // Envio semanal (resumo da semana) no dia configurado
    if ($cfg->envio_semanal_ativo
        && (int) now()->dayOfWeekIso === (int) $cfg->dia_semanal
        && substr($cfg->horario_semanal, 0, 5) === $agora) {
        \Illuminate\Support\Facades\Artisan::call('avisos:aulas', ['--semanal' => true, '--forcar' => true]);
    }
})->everyMinute();