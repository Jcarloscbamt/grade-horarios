<?php
// database/migrations/2026_06_10_000001_create_log_emails_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('log_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->nullable()->constrained('professores')->nullOnDelete();
            $table->string('professor_nome');
            $table->string('email');
            $table->string('tipo')->default('manual');   // diario | semanal | manual
            $table->boolean('sucesso')->default(false);
            $table->text('erro')->nullable();             // mensagem de erro, se houver
            $table->unsignedSmallInteger('qtd_aulas')->default(0);
            $table->timestamp('enviado_em')->nullable();
            $table->timestamps();

            $table->index(['sucesso', 'created_at']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_emails');
    }
};
