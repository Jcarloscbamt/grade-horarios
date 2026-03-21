<?php
// database/migrations/2026_03_22_000001_create_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 100);       // nome no momento da ação
            $table->string('acao', 20);              // criou, editou, excluiu
            $table->string('modulo', 50);            // Cursos, Turmas, Aulas...
            $table->string('descricao', 255);        // ex: Professor: João Paulo
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
