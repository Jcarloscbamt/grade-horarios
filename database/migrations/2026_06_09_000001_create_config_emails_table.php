<?php
// database/migrations/2026_06_09_000001_create_config_emails_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('config_emails', function (Blueprint $table) {
            $table->id();
            $table->boolean('envio_diario_ativo')->default(false);
            $table->time('horario_diario')->default('18:00');
            $table->boolean('envio_semanal_ativo')->default(false);
            $table->unsignedTinyInteger('dia_semanal')->default(1); // 1=Seg ... 7=Dom
            $table->time('horario_semanal')->default('07:00');
            $table->timestamps();
        });

        // Cria o registro único de configuração
        \Illuminate\Support\Facades\DB::table('config_emails')->insert([
            'envio_diario_ativo'  => false,
            'horario_diario'      => '18:00',
            'envio_semanal_ativo' => false,
            'dia_semanal'         => 1,
            'horario_semanal'     => '07:00',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('config_emails');
    }
};
