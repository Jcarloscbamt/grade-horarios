<?php
// app/Livewire/EnvioEmails.php
namespace App\Livewire;

use App\Models\{Professor, PeriodoLetivo, ConfigEmail, LogEmail, LogWhatsapp};
use App\Services\EnviarAvisoAulas;
use App\Services\EnviarAvisoWhatsapp;
use App\Models\Log;
use Livewire\Component;

class EnvioEmails extends Component
{
    // Envio manual
    public string $tipo            = 'amanha';
    public string $diaEspecifico   = '1';
    public string $canalManual     = 'email'; // email | whatsapp | ambos
    public array  $resultado       = [];
    public bool   $enviando        = false;
    public string $professorFiltro = '';

    // Histórico
    public string $filtroHistorico = 'todos'; // todos | sucesso | falha
    public string $abaHistorico = 'email';    // email | whatsapp

    // Configuração automática
    public bool   $envio_diario_ativo  = false;
    public bool   $whatsapp_diario_ativo = false;
    public string $horario_diario      = '18:00';
    public bool   $envio_semanal_ativo = false;
    public bool   $whatsapp_semanal_ativo = false;
    public string $dia_semanal         = '1';
    public string $horario_semanal     = '07:00';

    public function mount(): void
    {
        $cfg = ConfigEmail::atual();
        $this->envio_diario_ativo  = $cfg->envio_diario_ativo;
        $this->whatsapp_diario_ativo = $cfg->whatsapp_diario_ativo;
        $this->horario_diario      = substr($cfg->horario_diario, 0, 5);
        $this->envio_semanal_ativo = $cfg->envio_semanal_ativo;
        $this->whatsapp_semanal_ativo = $cfg->whatsapp_semanal_ativo;
        $this->dia_semanal         = (string) $cfg->dia_semanal;
        $this->horario_semanal     = substr($cfg->horario_semanal, 0, 5);
    }

    public function salvarConfig(): void
    {
        $cfg = ConfigEmail::atual();
        $cfg->update([
            'envio_diario_ativo'  => $this->envio_diario_ativo,
            'whatsapp_diario_ativo' => $this->whatsapp_diario_ativo,
            'horario_diario'      => $this->horario_diario,
            'envio_semanal_ativo' => $this->envio_semanal_ativo,
            'whatsapp_semanal_ativo' => $this->whatsapp_semanal_ativo,
            'dia_semanal'         => (int) $this->dia_semanal,
            'horario_semanal'     => $this->horario_semanal,
        ]);

        Log::registrar('editou', 'Avisos por E-mail', 'Atualizou configuração de envio automático');
        session()->flash('config_ok', 'Configuração salva com sucesso!');
    }

    public function enviar(): void
    {
        $this->enviando  = true;
        $this->resultado = [];

        $emailSvc = app(EnviarAvisoAulas::class);
        $whatsSvc = app(EnviarAvisoWhatsapp::class);
        $semanal  = $this->tipo === 'semana';
        $diaAlvo  = null;

        if ($this->tipo === 'amanha') {
            $amanha = \Carbon\Carbon::tomorrow()->dayOfWeekIso;
            if ($amanha > 5) {
                $this->resultado = ['erro' => 'Amanhã é fim de semana — não há aulas.'];
                $this->enviando  = false;
                return;
            }
            $diaAlvo = $amanha;
        } elseif ($this->tipo === 'dia') {
            $diaAlvo = (int) $this->diaEspecifico;
        }

        $fazEmail = in_array($this->canalManual, ['email', 'ambos']);
        $fazWhats = in_array($this->canalManual, ['whatsapp', 'ambos']);

        $detalhes = [];
        $enviados = 0;

        if ($this->professorFiltro) {
            // Envio para UM professor específico
            $prof = Professor::find($this->professorFiltro);
            if ($prof) {
                if ($fazEmail) {
                    $ok = $emailSvc->enviarParaProfessor($prof, $semanal, $diaAlvo, 'manual');
                    $detalhes[] = ($ok ? '✓' : '✗') . " E-mail: {$prof->nome}" . ($ok ? '' : ' (sem aula ou sem e-mail)');
                    if ($ok) $enviados++;
                }
                if ($fazWhats) {
                    $ok = $whatsSvc->enviarParaProfessor($prof, $semanal, $diaAlvo, 'manual');
                    $detalhes[] = ($ok ? '✓' : '✗') . " WhatsApp: {$prof->nome}" . ($ok ? '' : ' (sem aula, sem telefone ou WhatsApp não liberado)');
                    if ($ok) $enviados++;
                }
                $this->resultado = ['enviados' => $enviados, 'detalhes' => $detalhes];
            } else {
                $this->resultado = ['enviados' => 0, 'detalhes' => [], 'aviso' => 'Professor não encontrado.'];
            }
        } else {
            // Envio para TODOS
            if ($fazEmail) {
                $r = $emailSvc->enviarParaTodos($semanal, $diaAlvo, 'manual');
                $enviados += $r['enviados'] ?? 0;
                $detalhes[] = "E-mail: " . ($r['enviados'] ?? 0) . " enviado(s)";
            }
            if ($fazWhats) {
                $rw = $whatsSvc->enviarParaTodos($semanal, $diaAlvo, 'manual');
                $enviados += $rw['enviados'] ?? 0;
                $detalhes[] = "WhatsApp: " . ($rw['enviados'] ?? 0) . " enviado(s)"
                    . (($rw['falhas'] ?? 0) ? " ({$rw['falhas']} falha(s))" : '')
                    . (($rw['sem_telefone'] ?? 0) ? ", {$rw['sem_telefone']} sem telefone" : '');
            }
            $this->resultado = ['enviados' => $enviados, 'detalhes' => $detalhes];
        }

        Log::registrar('enviou', 'Avisos', "Enviou {$enviados} aviso(s) manual ({$this->tipo}, canal: {$this->canalManual})");
        $this->enviando = false;
    }

    public function limparHistorico(): void
    {
        if ($this->abaHistorico === 'whatsapp') {
            LogWhatsapp::truncate();
            Log::registrar('excluiu', 'Avisos WhatsApp', 'Limpou o histórico de WhatsApp');
        } else {
            LogEmail::truncate();
            Log::registrar('excluiu', 'Avisos por E-mail', 'Limpou o histórico de e-mail');
        }
        session()->flash('config_ok', 'Histórico limpo.');
    }

    public function render()
    {
        $professores = Professor::where('ativo', true)->orderBy('nome')->get();
        $periodoAtivo = PeriodoLetivo::where('ativo', true)->first();
        $dias = [1=>'Segunda', 2=>'Terça', 3=>'Quarta', 4=>'Quinta', 5=>'Sexta'];

        $historico = LogEmail::query()
            ->when($this->filtroHistorico === 'sucesso', fn($q) => $q->where('sucesso', true))
            ->when($this->filtroHistorico === 'falha', fn($q) => $q->where('sucesso', false))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $totalSucesso = LogEmail::where('sucesso', true)->count();
        $totalFalha   = LogEmail::where('sucesso', false)->count();

        // Histórico de WhatsApp (mesma lógica de filtro)
        $historicoWhats = LogWhatsapp::query()
            ->when($this->filtroHistorico === 'sucesso', fn($q) => $q->where('sucesso', true))
            ->when($this->filtroHistorico === 'falha', fn($q) => $q->where('sucesso', false))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $totalSucessoWhats = LogWhatsapp::where('sucesso', true)->count();
        $totalFalhaWhats   = LogWhatsapp::where('sucesso', false)->count();

        return view('livewire.envio-emails', compact(
            'professores', 'periodoAtivo', 'dias', 'historico', 'totalSucesso', 'totalFalha',
            'historicoWhats', 'totalSucessoWhats', 'totalFalhaWhats'
        ));
    }
}
