<?php
// database/migrations/2026_06_02_000001_add_sala_fields_to_disciplinas.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            // Tipo de sala necessária para a disciplina
            // Ex: 'Sala de Aula', 'Laboratório', 'Auditório', 'Sala de Reunião'
            $table->string('tipo_sala', 50)->nullable()->after('semestre_grade');

            // Bloco preferencial para a alocação de sala
            // Ex: 'A', 'B', 'C' — o gerador tentará alocar neste bloco primeiro
            $table->string('bloco_preferencial', 20)->nullable()->after('tipo_sala');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->dropColumn(['tipo_sala', 'bloco_preferencial']);
        });
    }
};
