<?php
// database/migrations/2026_06_05_000001_add_dias_to_professor_disciplinas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('professor_disciplinas', function (Blueprint $table) {
            if (!Schema::hasColumn('professor_disciplinas', 'dias')) {
                $table->json('dias')->nullable()->after('turma_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('professor_disciplinas', function (Blueprint $table) {
            $table->dropColumn('dias');
        });
    }
};
