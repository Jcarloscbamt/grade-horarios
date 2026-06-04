<?php
// app/Livewire/EnvioEmails.php
namespace App\Livewire;

use App\Models\{Professor, PeriodoLetivo, ConfigEmail, LogEmail};
use App\Services\EnviarAvisoAulas;
use App\Models\Log;
use Livewire\Component;

class EnvioEmails extends Component
{
    // Envio manual
    public string $tipo            = 'amanha';
    public string $diaEspecifico   = '1';
    public array  $resultado       = [];
    public bool   $enviando        = false;
    public string $professorFiltro = '';

    // Histórico
    public string $filtroHistorico = 'todos'; // todos | sucesso | falha

    // Configuração automática
    public bool   $envio_diario_ativo  = false;
    public string $horario_diario      = '18:00';
    public bool   $envio_semanal_ativo = false;
    public string $dia_semanal         = '1';
    public string $horario_semanal     = '07:00';

    public function mount(): void
    {
        $cfg = ConfigEmail::atual();
        $this->envio_diario_ativo  = $cfg->envio_diario_ativo;
        $this->horario_diario      = substr($cfg->horario_diario, 0, 5);
        $this->envio_semanal_ativo = $cfg->envio_semanal_ativo;
        $this->dia_semanal         = (string) $cfg->dia_semanal;
        $this->horario_semanal     = substr($cfg->horario_semanal, 0, 5);
    }

    public function salvarConfig(): void
    {
        $cfg = ConfigEmail::atual();
        $cfg->update([
            'envio_diario_ativo'  => $this->envio_diario_ativo,
            'horario_diario'      => $this->horario_diario,
            'envio_semanal_ativo' => $this->envio_semanal_ativo,
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

        $service = app(EnviarAvisoAulas::class);
        $semanal = $this->tipo === 'semana';
        $diaAlvo = null;

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

        if ($this->professorFiltro) {
            $prof = Professor::find($this->professorFiltro);
            if ($prof) {
                $ok = $service->enviarParaProfessor($prof, $semanal, $diaAlvo, 'manual');
                $this->resultado = $ok
                    ? ['enviados' => 1, 'detalhes' => ["✓ {$prof->nome} ({$prof->email})"]]
                    : ['enviados' => 0, 'detalhes' => [], 'aviso' => "Nenhuma aula encontrada para {$prof->nome}, ou e-mail não cadastrado."];
            }
        } else {
            $this->resultado = $service->enviarParaTodos($semanal, $diaAlvo, 'manual');
        }

        Log::registrar('enviou', 'Avisos por E-mail', "Enviou {$this->resultado['enviados']} aviso(s) manual ({$this->tipo})");
        $this->enviando = false;
    }

    public function limparHistorico(): void
    {
        LogEmail::truncate();
        Log::registrar('excluiu', 'Avisos por E-mail', 'Limpou o histórico de envios');
        session()->flash('config_ok', 'Histórico limpo.');
    }

    public function render()
    {
        $professores = Professor::where('ativo', true)->whereNotNull('email')->orderBy('nome')->get();
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

        return view('livewire.envio-emails', compact(
            'professores', 'periodoAtivo', 'dias', 'historico', 'totalSucesso', 'totalFalha'
        ));
    }
}
