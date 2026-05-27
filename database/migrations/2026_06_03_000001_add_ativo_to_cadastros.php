<?php
// database/migrations/2026_06_03_000001_add_ativo_to_cadastros.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cursos
        Schema::table('cursos', function (Blueprint $table) {
            $table->boolean('ativo')->default(true)->after('cor_grade');
        });

        // Turmas
        Schema::table('turmas', function (Blueprint $table) {
            $table->boolean('ativo')->default(true)->after('periodo');
        });

        // Disciplinas
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->boolean('ativo')->default(true)->after('bloco_preferencial');
        });

        // Professores
        Schema::table('professores', function (Blueprint $table) {
            $table->boolean('ativo')->default(true)->after('disponibilidade');
        });

        // Salas
        Schema::table('salas', function (Blueprint $table) {
            $table->boolean('ativo')->default(true)->after('bloco');
        });
    }

    public function down(): void
    {
        Schema::table('cursos',      fn($t) => $t->dropColumn('ativo'));
        Schema::table('turmas',      fn($t) => $t->dropColumn('ativo'));
        Schema::table('disciplinas', fn($t) => $t->dropColumn('ativo'));
        Schema::table('professores', fn($t) => $t->dropColumn('ativo'));
        Schema::table('salas',       fn($t) => $t->dropColumn('ativo'));
    }
};
