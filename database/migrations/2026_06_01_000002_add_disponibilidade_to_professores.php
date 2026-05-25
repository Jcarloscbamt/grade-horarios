<?php
// database/migrations/2026_06_01_000002_add_disponibilidade_to_professores.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professores', function (Blueprint $table) {
            // Armazena os dias disponíveis do professor, ex: [1,2,3,4,5]
            // 1=SEG, 2=TER, 3=QUA, 4=QUI, 5=SEX
            $table->json('disponibilidade')->nullable()->after('cpf');
        });
    }

    public function down(): void
    {
        Schema::table('professores', function (Blueprint $table) {
            $table->dropColumn('disponibilidade');
        });
    }
};
