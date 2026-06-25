<?php
// app/Livewire/ConfigWhatsappProvider.php
namespace App\Livewire;

use App\Models\{ConfigWhatsapp, Professor};
use App\Services\WhatsappManager;
use Livewire\Component;

class ConfigWhatsappProvider extends Component
{
    public string $provider = 'meta';
    public string $mode     = 'text';
    public string $default_country = '55';

    // Meta
    public string $meta_phone_id     = '';
    public string $meta_api_version  = 'v25.0';
    public string $meta_template      = 'aviso_aula';
    public string $meta_template_lang = 'pt_BR';
    public string $meta_token_novo    = ''; // só preenche se for trocar

    // Twilio
    public string $twilio_sid         = '';
    public string $twilio_from        = '';
    public string $twilio_content_sid = '';
    public string $twilio_token_novo  = ''; // só preenche se for trocar

    // Flags de "já tem token salvo"
    public bool $metaTokenSalvo   = false;
    public bool $twilioTokenSalvo = false;

    // Teste
    public string $telefoneTeste = '';
    public array  $resultadoTeste = [];

    public function mount(): void
    {
        $cfg = ConfigWhatsapp::atual();
        $this->provider          = $cfg->provider ?: 'meta';
        $this->mode              = $cfg->mode ?: 'text';
        $this->default_country   = $cfg->default_country ?: '55';

        $this->meta_phone_id     = $cfg->meta_phone_id ?? '';
        $this->meta_api_version  = $cfg->meta_api_version ?: 'v25.0';
        $this->meta_template      = $cfg->meta_template ?: 'aviso_aula';
        $this->meta_template_lang = $cfg->meta_template_lang ?: 'pt_BR';

        $this->twilio_sid         = $cfg->twilio_sid ?? '';
        $this->twilio_from        = $cfg->twilio_from ?? '';
        $this->twilio_content_sid = $cfg->twilio_content_sid ?? '';

        $this->metaTokenSalvo   = !empty($cfg->meta_token);
        $this->twilioTokenSalvo = !empty($cfg->twilio_token);
    }

    public function salvar(): void
    {
        $cfg = ConfigWhatsapp::atual();
        $cfg->provider          = $this->provider;
        $cfg->mode              = $this->mode;
        $cfg->default_country   = $this->default_country ?: '55';

        $cfg->meta_phone_id     = $this->meta_phone_id ?: null;
        $cfg->meta_api_version  = $this->meta_api_version ?: 'v25.0';
        $cfg->meta_template      = $this->meta_template ?: 'aviso_aula';
        $cfg->meta_template_lang = $this->meta_template_lang ?: 'pt_BR';
        if ($this->meta_token_novo !== '') {
            $cfg->meta_token = $this->meta_token_novo; // criptografado pelo cast
        }

        $cfg->twilio_sid         = $this->twilio_sid ?: null;
        $cfg->twilio_from        = $this->twilio_from ?: null;
        $cfg->twilio_content_sid = $this->twilio_content_sid ?: null;
        if ($this->twilio_token_novo !== '') {
            $cfg->twilio_token = $this->twilio_token_novo;
        }

        $cfg->save();

        $this->meta_token_novo   = '';
        $this->twilio_token_novo = '';
        $this->metaTokenSalvo    = !empty($cfg->meta_token);
        $this->twilioTokenSalvo  = !empty($cfg->twilio_token);

        session()->flash('cfgwa_ok', 'Configuração do WhatsApp salva.');
    }

    /** Envia um texto de teste pelo provedor ATIVO para o número informado */
    public function testar(WhatsappManager $manager): void
    {
        $this->salvar(); // garante que o teste usa o que está na tela

        if (empty($this->telefoneTeste)) {
            $this->resultadoTeste = ['ok' => false, 'erro' => 'Informe um telefone para o teste.'];
            return;
        }

        $provedor = $manager->ativo();
        if (!$provedor->configurado()) {
            $this->resultadoTeste = ['ok' => false, 'erro' => $provedor->nome() . ' não está configurado (faltam credenciais).'];
            return;
        }

        $this->resultadoTeste = $provedor->enviarTexto(
            $this->telefoneTeste,
            'Teste do sistema Grade de Horarios (' . $provedor->nome() . ') - ' . now()->format('d/m H:i')
        );
    }

    public function render()
    {
        return view('livewire.config-whatsapp-provider');
    }
}
