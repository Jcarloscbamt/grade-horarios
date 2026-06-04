<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Todo dia às 18h envia aviso das aulas de amanhã
    Schedule::command('avisos:aulas')->dailyAt('18:00');

// Toda segunda às 7h envia o resumo semanal (opcional)
    Schedule::command('avisos:aulas --semanal')->weeklyOn(1, '07:00');    