<?php
// database/migrations/2026_06_11_000001_create_professor_competencias_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ── NÍVEL 1: Competências (curso + disciplina que o professor SABE lecionar) ──
        // Sem turma e SEM limite. Diferente de professor_disciplinas (vínculos do período, máx 5).
        if (!Schema::hasTable('professor_competencias')) {
            Schema::create('professor_competencias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('professor_id')->constrained('professores')->cascadeOnDelete();
                $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
                $table->foreignId('disciplina_id')->constrained('disciplinas')->cascadeOnDelete();
                $table->timestamps();

                // Evita competência duplicada (mesmo professor + curso + disciplina)
                $table->unique(['professor_id', 'curso_id', 'disciplina_id'], 'uniq_competencia');
            });
        }

        // ── MIGRAÇÃO DE DADOS: vínculos atuais viram competências ──
        // Cada vínculo existente (professor_disciplinas) gera uma competência
        // (curso vem da disciplina). Usa INSERT IGNORE para não duplicar.
        if (Schema::hasTable('professor_disciplinas') && Schema::hasTable('disciplinas')) {
            $vinculos = DB::table('professor_disciplinas as pd')
                ->join('disciplinas as d', 'd.id', '=', 'pd.disciplina_id')
                ->select('pd.professor_id', 'd.curso_id', 'pd.disciplina_id')
                ->distinct()
                ->get();

            $agora = now();
            foreach ($vinculos as $v) {
                if (!$v->curso_id) continue;
                DB::table('professor_competencias')->updateOrInsert(
                    [
                        'professor_id'  => $v->professor_id,
                        'curso_id'      => $v->curso_id,
                        'disciplina_id' => $v->disciplina_id,
                    ],
                    ['created_at' => $agora, 'updated_at' => $agora]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('professor_competencias');
    }
};
