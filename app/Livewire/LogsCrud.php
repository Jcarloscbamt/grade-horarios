<?php
// app/Livewire/LogsCrud.php
namespace App\Livewire;

use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;

class LogsCrud extends Component
{
    use WithPagination;

    public string $search  = '';
    public string $filtro  = 'todos';
    public string $modulo  = '';
    public string $acao    = '';

    protected $queryString = ['search', 'filtro', 'modulo', 'acao'];

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

        return view('livewire.logs-crud', compact('logs', 'modulos', 'acoes'));
    }
}
