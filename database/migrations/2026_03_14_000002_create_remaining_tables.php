<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('professores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('email', 100)->unique();
            $table->string('telefone', 20)->nullable();
            $table->char('cpf', 11)->unique();
            $table->timestamps();
        });

        Schema::create('salas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50)->unique();
            $table->string('tipo', 50);
            $table->smallInteger('capacidade')->nullable();
            $table->string('bloco', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->string('tipo', 20);
            $table->timestamps();
            $table->unique(['hora_inicio', 'hora_fim']);
        });

        Schema::create('periodo_letivos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 20);
            $table->year('ano');
            $table->char('semestre', 1);
            $table->date('avaliacao1_inicio')->nullable();
            $table->date('avaliacao1_fim')->nullable();
            $table->date('avaliacao2_inicio')->nullable();
            $table->date('avaliacao2_fim')->nullable();
            $table->boolean('ativo')->default(false);
            $table->timestamps();
            $table->unique(['ano', 'semestre']);
        });

        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->string('nome', 50)->unique();
            $table->tinyInteger('semestre');
            $table->year('ano');
            $table->char('periodo', 1);
            $table->timestamps();
        });

        Schema::create('disciplinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->string('nome', 100);
            $table->smallInteger('carga_horaria');
            $table->tinyInteger('semestre_grade');
            $table->timestamps();
        });

        Schema::create('aulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->restrictOnDelete();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('professor_id')->constrained('professores')->restrictOnDelete();
            $table->foreignId('sala_id')->nullable()->constrained('salas')->nullOnDelete();
            $table->foreignId('horario_id')->constrained('horarios')->restrictOnDelete();
            $table->foreignId('periodo_letivo_id')->constrained('periodo_letivos')->restrictOnDelete();
            $table->tinyInteger('dia_semana');
            $table->string('modalidade', 20)->default('presencial');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aulas');
        Schema::dropIfExists('disciplinas');
        Schema::dropIfExists('turmas');
        Schema::dropIfExists('periodo_letivos');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('salas');
        Schema::dropIfExists('professores');
    }
};
