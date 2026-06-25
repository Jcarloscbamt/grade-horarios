<?php
// app/Livewire/LogsCrud.php
namespace App\Livewire;

use App\Models\Log;
use App\Models\ConfigEmail;
use App\Services\LimpezaLogs;
use Livewire\Component;
use Livewire\WithPagination;

class LogsCrud extends Component
{
    use WithPagination;

    public string $search  = '';
    public string $filtro  = 'todos';
    public string $modulo  = '';
    public string $acao    = '';

    // Limpeza de logs
    public int  $retencaoDias = 60;
    public bool $limpezaAuto  = false;
    public array $resultadoLimpeza = [];

    protected $queryString = ['search', 'filtro', 'modulo', 'acao'];

    public function mount(): void
    {
        $cfg = ConfigEmail::atual();
        $this->retencaoDias = (int) ($cfg->log_retencao_dias ?? 60);
        $this->limpezaAuto  = (bool) ($cfg->log_limpeza_auto ?? false);
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltro(): void { $this->resetPage(); $this->search = ''; }
    public function updatingModulo(): void { $this->resetPage(); }
    public function updatingAcao():   void { $this->resetPage(); }

    public function limpar(): void
    {
        $this->search = '';
        $this->filtro = 'todos';
        $this->modulo = '';
        $this->acao   = '';
        $this->resetPage();
    }

    /** Salva a configuração de retenção/limpeza automática */
    public function salvarRetencao(): void
    {
        $cfg = ConfigEmail::atual();
        $cfg->log_retencao_dias = max(0, (int) $this->retencaoDias);
        $cfg->log_limpeza_auto  = $this->limpezaAuto;
        $cfg->save();
        session()->flash('msgLog', 'Configuração de limpeza salva.');
    }

    /** Apaga agora os logs mais antigos que o período escolhido (todos os logs do sistema) */
    public function limparAntigos(LimpezaLogs $limpeza): void
    {
        $r = $limpeza->limpar(max(0, (int) $this->retencaoDias));
        $this->resultadoLimpeza = $r;
        Log::registrar('excluiu', 'Logs',
            "Limpeza manual: removeu {$r['total']} registro(s) com mais de {$r['dias']} dias");
        $this->resetPage();
    }

    public function render()
    {
        $logs = Log::with('user')
            ->when($this->search, function($q) {
                $s = $this->search;
                match($this->filtro) {
                    'usuario'   => $q->where('user_name', 'like', "%$s%"),
                    'descricao' => $q->where('descricao', 'like', "%$s%"),
                    default     => $q->where('user_name', 'like', "%$s%")
                                     ->orWhere('descricao', 'like', "%$s%"),
                };
            })
            ->when($this->modulo, fn($q) => $q->where('modulo', $this->modulo))
            ->when($this->acao,   fn($q) => $q->where('acao',   $this->acao))
            ->orderByDesc('created_at')
            ->paginate(20);

        $modulos = Log::select('modulo')->distinct()->orderBy('modulo')->pluck('modulo');
        $acoes   = ['criou', 'editou', 'excluiu'];

        // Total de registros em todos os logs (para mostrar o "tamanho" atual)
        $totalRegistros = Log::count()
            + \App\Models\LogEmail::count()
            + (\Schema::hasTable('log_whatsapps') ? \App\Models\LogWhatsapp::count() : 0);

        return view('livewire.logs-crud', compact('logs', 'modulos', 'acoes', 'totalRegistros'));
    }
}
