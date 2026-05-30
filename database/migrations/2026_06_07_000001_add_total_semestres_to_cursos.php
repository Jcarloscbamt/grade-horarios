<?php
// database/migrations/2026_06_07_000001_add_total_semestres_to_cursos.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            if (!Schema::hasColumn('cursos', 'total_semestres')) {
                $table->integer('total_semestres')->default(6)->after('cor_grade');
            }
        });
        // Atualiza cursos existentes com valor padrão 6
        \Illuminate\Support\Facades\DB::table('cursos')
            ->whereNull('total_semestres')
            ->orWhere('total_semestres', 0)
            ->update(['total_semestres' => 6]);
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn('total_semestres');
        });
    }
};
