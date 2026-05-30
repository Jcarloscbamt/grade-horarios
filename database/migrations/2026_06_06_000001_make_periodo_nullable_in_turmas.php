<?php
// database/migrations/2026_06_06_000001_make_periodo_nullable_in_turmas.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            // Torna a coluna periodo nullable com valor padrão null
            // pois o campo foi removido do sistema
            $table->string('periodo')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->string('periodo')->nullable(false)->change();
        });
    }
};
