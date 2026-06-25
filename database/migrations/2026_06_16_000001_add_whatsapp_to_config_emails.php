<?php
// database/migrations/2026_06_16_000001_add_whatsapp_to_config_emails.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('config_emails', function (Blueprint $table) {
            // Canal WhatsApp por tipo (mesmo horário do e-mail).
            // As colunas envio_diario_ativo / envio_semanal_ativo continuam controlando o E-MAIL.
            if (!Schema::hasColumn('config_emails', 'whatsapp_diario_ativo')) {
                $table->boolean('whatsapp_diario_ativo')->default(false)->after('horario_diario');
            }
            if (!Schema::hasColumn('config_emails', 'whatsapp_semanal_ativo')) {
                $table->boolean('whatsapp_semanal_ativo')->default(false)->after('horario_semanal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('config_emails', function (Blueprint $table) {
            if (Schema::hasColumn('config_emails', 'whatsapp_diario_ativo')) {
                $table->dropColumn('whatsapp_diario_ativo');
            }
            if (Schema::hasColumn('config_emails', 'whatsapp_semanal_ativo')) {
                $table->dropColumn('whatsapp_semanal_ativo');
            }
        });
    }
};
