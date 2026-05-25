<?php
// database/migrations/2026_06_01_000001_create_professor_disciplinas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela pivot: vínculo professor → disciplina → turma
        Schema::create('professor_disciplinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')
                  ->constrained('professores')
                  ->cascadeOnDelete();
            $table->foreignId('disciplina_id')
                  ->constrained('disciplinas')
                  ->cascadeOnDelete();
            $table->foreignId('turma_id')
                  ->constrained('turmas')
                  ->cascadeOnDelete();

            // Dias da semana disponíveis armazenados como JSON, ex: [1,3,5]
            // 1=SEG, 2=TER, 3=QUA, 4=QUI, 5=SEX
            $table->json('dias_semana')->nullable();

            $table->timestamps();

            // Um professor pode dar a mesma disciplina para a mesma turma uma vez
            $table->unique(['professor_id', 'disciplina_id', 'turma_id'], 'uniq_prof_disc_turma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professor_disciplinas');
    }
};
