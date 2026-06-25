<?php
// database/migrations/2026_06_17_000001_add_log_retencao_to_config_emails.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('config_emails', function (Blueprint $table) {
            // Retenção de logs: dias a manter. 0 = nunca apagar automaticamente.
            if (!Schema::hasColumn('config_emails', 'log_retencao_dias')) {
                $table->unsignedSmallInteger('log_retencao_dias')->default(60)->after('horario_semanal');
            }
            // Liga/desliga a limpeza automática diária
            if (!Schema::hasColumn('config_emails', 'log_limpeza_auto')) {
                $table->boolean('log_limpeza_auto')->default(false)->after('log_retencao_dias');
            }
        });
    }

    public function down(): void
    {
        Schema::table('config_emails', function (Blueprint $table) {
            foreach (['log_retencao_dias', 'log_limpeza_auto'] as $col) {
                if (Schema::hasColumn('config_emails', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
