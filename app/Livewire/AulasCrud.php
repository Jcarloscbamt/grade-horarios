<?php
// app/Livewire/AulasCrud.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Disciplina, Professor, Sala, Horario, PeriodoLetivo};
use App\Models\Log;
use Livewire\Component;
use Livewire\WithPagination;

class AulasCrud extends Component
{
    use WithPagination;

    public ?int $aulaId           = null;
    public string $turma_id         = '';
    public string $disciplina_id    = '';
    public string $professor_id     = '';
    public string $sala_id          = '';
    public string $horario_id       = '';
    public string $periodo_letivo_id = '';
    public string $dia_semana       = '';
    public string $modalidade       = 'presencial';
    public bool   $todosHorarios    = false;

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $search   = '';
    public string $filtro   = 'todos';
    public string $filtroPeriodo = ''; // filtro de período letivo na LISTAGEM
    public string $modalTitle = '';

    public array $dias = [
        1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira',
        4 => 'Quinta-feira',  5 => 'Sexta-feira',
    ];

    public array $diasCurtos = [
        1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta',
        4 => 'Quinta',  5 => 'Sexta',
    ];

    public array $modalidades = ['presencial', 'online', 'híbrido'];

    protected $queryString = ['search', 'filtro', 'filtroPeriodo'];

    protected function rules(): array
    {
        return [
            'turma_id'          => 'required',
            'disciplina_id'     => 'required',
            'professor_id'      => 'required',
            'sala_id'           => 'nullable',
            'horario_id'        => 'required_without:todosHorarios',
            'periodo_letivo_id' => 'required',
            'dia_semana'        => 'required|integer|min:1|max:5',
            'modalidade'        => 'required',
        ];
    }

    protected array $messages = [
        'turma_id.required'           => 'Selecione a turma.',
        'disciplina_id.required'      => 'Selecione a disciplina.',
        'professor_id.required'       => 'Selecione o professor.',
        'horario_id.required_without' => 'Selecione o horário.',
        'periodo_letivo_id.required'  => 'Selecione o período letivo.',
        'dia_semana.required'         => 'Selecione o dia da semana.',
        'modalidade.required'         => 'Selecione a modalidade.',
    ];

    // Quando turma ou disciplina mudam, reseta o professor selecionado
    public function updatedTurmaId(): void
    {
        $this->professor_id = '';
    }

    public function updatedDisciplinaId(): void
    {
        $this->professor_id = '';
    }

    public function create(): void
    {
        $this->limparFormulario();
        $this->modalTitle = 'Nova Aula';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $a = Aula::findOrFail($id);
        $this->aulaId            = $a->id;
        $this->turma_id          = $a->turma_id;
        $this->disciplina_id     = $a->disciplina_id;
        $this->professor_id      = $a->professor_id;
        $this->sala_id           = $a->sala_id ?? '';
        $this->horario_id        = $a->horario_id;
        $this->periodo_letivo_id = $a->periodo_letivo_id;
        $this->dia_semana        = $a->dia_semana;
        $this->modalidade        = $a->modalidade;
        $this->modalTitle        = 'Editar Aula';
        $this->showModal         = true;
    }

    public function save(): void
    {
        $erros = [];

        // Verificar duplicidade
        $query = Aula::where('turma_id', $this->turma_id)
            ->where('disciplina_id', $this->disciplina_id)
            ->where('periodo_letivo_id', $this->periodo_letivo_id)
            ->where('dia_semana', $this->dia_semana);
        if ($this->aulaId) $query->where('id', '!=', $this->aulaId);
        if (!$this->todosHorarios && $this->horario_id) {
            $query->where('horario_id', $this->horario_id);
        }
        if ($query->exists()) {
            $erros[] = 'Já existe uma aula cadastrada com esta combinação.';
        }

        // Verificar se professor está vinculado à disciplina+turma
        if ($this->professor_id && $this->disciplina_id && $this->turma_id) {
            $vinculado = \App\Models\ProfessorDisciplina::where('professor_id', $this->professor_id)
                ->where('disciplina_id', $this->disciplina_id)
                ->where('turma_id', $this->turma_id)
                ->exists();
            if (!$vinculado) {
                $erros[] = 'Este professor não está vinculado a esta disciplina e turma. Configure no cadastro de Professores.';
            }
        }

        // Conflito professor
        if ($this->horario_id && !$this->todosHorarios) {
            $confP = Aula::where('professor_id', $this->professor_id)
                ->where('horario_id', $this->horario_id)
                ->where('dia_semana', $this->dia_semana)
                ->where('periodo_letivo_id', $this->periodo_letivo_id)
                ->when($this->aulaId, fn($q) => $q->where('id', '!=', $this->aulaId))
                ->exists();
            if ($confP) $erros[] = 'Professor já possui aula neste dia e horário.';

            // Conflito sala
            if ($this->sala_id) {
                $confS = Aula::where('sala_id', $this->sala_id)
                    ->where('horario_id', $this->horario_id)
                    ->where('dia_semana', $this->dia_semana)
                    ->where('periodo_letivo_id', $this->periodo_letivo_id)
                    ->when($this->aulaId, fn($q) => $q->where('id', '!=', $this->aulaId))
                    ->exists();
                if ($confS) $erros[] = 'Sala já ocupada neste dia e horário.';
            }
        }

        if ($erros) {
            foreach ($erros as $e) $this->addError('geral', $e);
            return;
        }

        $this->validate();

        $isNovo = is_null($this->aulaId);

        if ($this->todosHorarios) {
            $horarios = Horario::where('tipo', '!=', 'intervalo')->get();
            $criados  = 0;
            foreach ($horarios as $h) {
                [,$novo] = Aula::firstOrCreate(
                    ['turma_id'=>$this->turma_id,'disciplina_id'=>$this->disciplina_id,'horario_id'=>$h->id,'dia_semana'=>$this->dia_semana,'periodo_letivo_id'=>$this->periodo_letivo_id],
                    ['professor_id'=>$this->professor_id,'sala_id'=>$this->sala_id?:null,'modalidade'=>$this->modalidade]
                );
                if ($novo) $criados++;
            }
            $dias = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex'];
            Log::registrar('criou', 'Aulas', "Lote: {$criados} aulas criadas - " . ($dias[$this->dia_semana] ?? ''));
            $this->showModal = false;
            $this->limparFormulario();
            session()->flash('success', "{$criados} aula(s) cadastrada(s) com sucesso!");
        } else {
            Aula::updateOrCreate(
                ['id' => $this->aulaId],
                [
                    'turma_id'=>$this->turma_id, 'disciplina_id'=>$this->disciplina_id,
                    'professor_id'=>$this->professor_id, 'sala_id'=>$this->sala_id?:null,
                    'horario_id'=>$this->horario_id, 'periodo_letivo_id'=>$this->periodo_letivo_id,
                    'dia_semana'=>$this->dia_semana, 'modalidade'=>$this->modalidade,
                ]
            );
            $dias = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex'];
            Log::registrar($isNovo?'criou':'editou', 'Aulas', ($isNovo?'Nova aula: ':'Editou aula: ').($dias[$this->dia_semana]??''));
            $this->showModal = false;
            $this->limparFormulario();
            session()->flash('success', $isNovo ? 'Aula cadastrada com sucesso!' : 'Aula atualizada com sucesso!');
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->aulaId    = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $aula = Aula::with(['disciplina','turma'])->findOrFail($this->aulaId);
        $desc = 'Excluiu aula: ' . ($aula->disciplina->nome ?? '') . ' - ' . ($aula->turma->nome ?? '');
        $aula->delete();
        Log::registrar('excluiu', 'Aulas', $desc);
        $this->showDelete = false;
        $this->limparFormulario();
        session()->flash('success', 'Aula excluída com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->limparFormulario();
    }

    private function limparFormulario(): void
    {
        $this->aulaId = null;
        $this->turma_id = $this->disciplina_id = $this->professor_id = '';
        $this->sala_id = $this->horario_id = $this->periodo_letivo_id = '';
        $this->dia_semana = '';
        $this->modalidade = 'presencial';
        $this->todosHorarios = false;
        $this->resetValidation();
    }


    // ── Seleção em lote ──────────────────────────────
    public array $selecionados    = [];
    public bool  $todosSelecionados = false;
    public bool  $showDeleteLote  = false;

    public function toggleTodos(array $idsVisiveis): void
    {
        $idsVisiveis = array_map('intval', $idsVisiveis);
        if (count($this->selecionados) === count($idsVisiveis) && count($idsVisiveis) > 0) {
            $this->selecionados = [];
            $this->todosSelecionados = false;
        } else {
            $this->selecionados = $idsVisiveis;
            $this->todosSelecionados = true;
        }
    }

    // Marca/desmarca UMA aula (mesmo mecanismo do toggleTodos: sempre int)
    public function toggleUm(int $id): void
    {
        if (in_array($id, $this->selecionados, true)) {
            $this->selecionados = array_values(array_diff($this->selecionados, [$id]));
        } else {
            $this->selecionados[] = $id;
        }
    }

    public function confirmarDeleteLote(): void
    {
        if (!empty($this->selecionados)) {
            $this->showDeleteLote = true;
        }
    }

    public function deleteLote(): void
    {
        // Cada item selecionado é o MIN(id) de um GRUPO (turma+disciplina+dia+...).
        // Como cada grupo tem várias linhas (uma por horário), apagamos o GRUPO inteiro,
        // não só o MIN(id) — senão o grupo reaparece e seria preciso excluir várias vezes.
        $aulasBase = \App\Models\Aula::whereIn('id', $this->selecionados)->get();
        $totalRemovidas = 0;
        foreach ($aulasBase as $a) {
            $totalRemovidas += \App\Models\Aula::where('turma_id', $a->turma_id)
                ->where('disciplina_id', $a->disciplina_id)
                ->where('professor_id', $a->professor_id)
                ->where('dia_semana', $a->dia_semana)
                ->where('periodo_letivo_id', $a->periodo_letivo_id)
                ->delete();
        }
        $qtdGrupos = count($this->selecionados);
        \App\Models\Log::registrar('excluiu', 'Aulas', "Exclusão em lote: {$qtdGrupos} aula(s) removida(s) ({$totalRemovidas} registro(s))");
        $this->selecionados      = [];
        $this->todosSelecionados = false;
        $this->showDeleteLote    = false;
        session()->flash('success', "{$qtdGrupos} aula(s) excluída(s) com sucesso!");
    }

    // Seleciona TODAS as aulas (grupos) de TODAS as páginas que batem com o filtro atual
    public function selecionarTodas(): void
    {
        $this->selecionados = $this->queryAgrupada()->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->todosSelecionados = true;
    }

    public function cancelarDeleteLote(): void
    {
        $this->showDeleteLote = false;
    }

    public function updatingSearch(): void { $this->resetPage(); $this->selecionados = []; $this->todosSelecionados = false; }
    public function updatingFiltro(): void { $this->resetPage(); $this->search = ''; $this->selecionados = []; $this->todosSelecionados = false; }
    public function updatingFiltroPeriodo(): void { $this->resetPage(); $this->selecionados = []; $this->todosSelecionados = false; }

    // Monta a query agrupada (1 linha por turma/disciplina/dia) respeitando o filtro atual.
    // Usada pelo render (paginado) e pela seleção total (todas as páginas).
    private function queryAgrupada()
    {
        $diasNomes = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta'];
        $diaNumero = null;
        if ($this->search) {
            foreach ($diasNomes as $num => $nome) {
                if (stripos($nome, $this->search) !== false) { $diaNumero = $num; break; }
            }
        }

        return Aula::with(['turma','disciplina','professor','sala','periodoLetivo'])
            ->when($this->filtroPeriodo, fn($q) => $q->where('periodo_letivo_id', $this->filtroPeriodo))
            ->when($this->search, function($q) use ($diaNumero) {
                $s = $this->search;
                match($this->filtro) {
                    'turma'      => $q->whereHas('turma', fn($q)=>$q->where('nome','like',"%$s%")),
                    'disciplina' => $q->whereHas('disciplina', fn($q)=>$q->where('nome','like',"%$s%")),
                    'professor'  => $q->whereHas('professor', fn($q)=>$q->where('nome','like',"%$s%")),
                    'sala'       => $q->whereHas('sala', fn($q)=>$q->where('nome','like',"%$s%")),
                    'dia'        => $diaNumero ? $q->where('dia_semana',$diaNumero) : $q->whereRaw('0=1'),
                    default      => $q->whereHas('turma',fn($q)=>$q->where('nome','like',"%$s%"))
                                      ->orWhereHas('disciplina',fn($q)=>$q->where('nome','like',"%$s%"))
                                      ->orWhereHas('professor',fn($q)=>$q->where('nome','like',"%$s%")),
                };
            })
            ->select('turma_id','disciplina_id','professor_id','sala_id','dia_semana','periodo_letivo_id','modalidade',
                     \DB::raw('MIN(id) as id'),
                     \DB::raw('MIN(horario_id) as horario_id_min'),
                     \DB::raw('MAX(horario_id) as horario_id_max'))
            ->groupBy('turma_id','disciplina_id','professor_id','sala_id','dia_semana','periodo_letivo_id','modalidade')
            ->orderBy('dia_semana');
    }

    public function render()
    {
        $diasNomes = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta'];

        // Lista agrupada e paginada (reutiliza a query base)
        $aulas = $this->queryAgrupada()->paginate(20);

        // Total de grupos em TODAS as páginas (para o botão "selecionar todas")
        $totalGrupos = $this->queryAgrupada()->get()->count();

        $turmas          = Turma::where('ativo', true)->orderBy('nome')->get();
        $disciplinas     = Disciplina::where('ativo', true)->orderBy('nome')->get();
        $salas           = Sala::where('ativo', true)->orderBy('nome')->get();
        $horarios        = Horario::orderBy('hora_inicio')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->get();

        // Professores filtrados por disciplina + turma
        $professoresFiltrados = collect();
        if ($this->turma_id && $this->disciplina_id) {
            $diasLabels = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex'];
            $professoresFiltrados = Professor::where('ativo', true)
                ->whereHas('disciplinasTurmas', fn($q) =>
                    $q->where('disciplina_id', $this->disciplina_id)
                      ->where('turma_id', $this->turma_id)
                )
                ->orderBy('nome')
                ->get()
                ->map(function($p) use ($diasLabels) {
                    $disp = collect(is_array($p->disponibilidade) ? $p->disponibilidade : json_decode($p->disponibilidade ?? '[]', true))
                        ->map(fn($d) => $diasLabels[$d] ?? $d)->implode(', ');
                    $p->dias_disponiveis = $disp;
                    return $p;
                });
        }

        return view('livewire.aulas-crud', compact(
            'aulas', 'turmas', 'disciplinas', 'salas',
            'horarios', 'periodosLetivos', 'professoresFiltrados', 'totalGrupos'
        ));
    }
}
