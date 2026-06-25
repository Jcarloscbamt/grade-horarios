<?php
// database/migrations/2026_06_15_000001_create_log_whatsapps_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('log_whatsapps')) {
            Schema::create('log_whatsapps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('professor_id')->nullable()->constrained('professores')->nullOnDelete();
                $table->string('professor_nome')->nullable();
                $table->string('telefone')->nullable();
                $table->string('tipo')->default('manual'); // diario | semanal | manual
                $table->boolean('sucesso')->default(false);
                $table->text('erro')->nullable();
                $table->integer('qtd_aulas')->default(0);
                $table->string('message_id')->nullable(); // id retornado pela Meta
                $table->timestamp('enviado_em')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('log_whatsapps');
    }
};
