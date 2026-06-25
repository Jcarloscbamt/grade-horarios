<?php
// database/migrations/2026_06_18_000001_create_config_whatsapps_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('config_whatsapps')) return;

        Schema::create('config_whatsapps', function (Blueprint $table) {
            $table->id();

            // Provedor ativo: 'meta' ou 'twilio'
            $table->string('provider', 20)->default('meta');
            // Modo de envio: 'text' (texto livre/teste) ou 'template' (produção)
            $table->string('mode', 20)->default('text');
            $table->string('default_country', 5)->default('55');

            // ===== Meta Cloud API =====
            $table->text('meta_token')->nullable();          // criptografado
            $table->string('meta_phone_id')->nullable();
            $table->string('meta_api_version', 10)->default('v25.0');
            $table->string('meta_template')->default('aviso_aula');
            $table->string('meta_template_lang', 10)->default('pt_BR');

            // ===== Twilio =====
            $table->string('twilio_sid')->nullable();
            $table->text('twilio_token')->nullable();         // criptografado
            $table->string('twilio_from')->nullable();        // ex.: +14155238886 (sandbox)
            $table->string('twilio_content_sid')->nullable(); // template (ContentSid) p/ produção

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_whatsapps');
    }
};
