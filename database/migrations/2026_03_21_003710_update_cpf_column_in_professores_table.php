<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professores', function (Blueprint $table) {
            // Aumenta de CHAR(11) para VARCHAR(14) para suportar formato 000.000.000-00
            $table->string('cpf', 14)->change();
        });
    }

    public function down(): void
    {
        Schema::table('professores', function (Blueprint $table) {
            $table->char('cpf', 11)->change();
        });
    }
};