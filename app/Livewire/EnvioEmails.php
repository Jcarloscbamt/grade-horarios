<?php
// app/Livewire/EnvioEmails.php
namespace App\Livewire;

use App\Models\{Professor, PeriodoLetivo};
use App\Services\EnviarAvisoAulas;
use App\Models\Log;
use Livewire\Component;

class EnvioEmails extends Component
{
    public string $tipo        = 'amanha';  // amanha | semana | dia
    public string $diaEspecifico = '1';
    public array  $resultado   = [];
    public bool   $enviando    = false;
    public string $professorFiltro = ''; // '' = todos, ou ID específico

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

        // Envio para um professor específico ou todos
        if ($this->professorFiltro) {
            $prof = Professor::find($this->professorFiltro);
            if ($prof) {
                $ok = $service->enviarParaProfessor($prof, $semanal, $diaAlvo);
                $this->resultado = $ok
                    ? ['enviados' => 1, 'detalhes' => ["✓ {$prof->nome} ({$prof->email})"]]
                    : ['enviados' => 0, 'detalhes' => [], 'aviso' => "Nenhuma aula encontrada para {$prof->nome} no período, ou e-mail não cadastrado."];
            }
        } else {
            $this->resultado = $service->enviarParaTodos($semanal, $diaAlvo);
        }

        Log::registrar('enviou', 'Avisos por E-mail', "Enviou {$this->resultado['enviados']} aviso(s) de aula ({$this->tipo})");

        $this->enviando = false;
    }

    public function render()
    {
        $professores = Professor::where('ativo', true)
            ->whereNotNull('email')
            ->orderBy('nome')->get();
        $periodoAtivo = PeriodoLetivo::where('ativo', true)->first();
        $dias = [1=>'Segunda', 2=>'Terça', 3=>'Quarta', 4=>'Quinta', 5=>'Sexta'];

        return view('livewire.envio-emails', compact('professores', 'periodoAtivo', 'dias'));
    }
}
