<?php
// database/migrations/2026_06_08_000001_add_sala_id_to_disciplinas.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->unsignedBigInteger('sala_id')->nullable()->after('bloco_preferencial');
            $table->foreign('sala_id')->references('id')->on('salas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->dropForeign(['sala_id']);
            $table->dropColumn('sala_id');
        });
    }
};
