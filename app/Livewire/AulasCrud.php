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

    public ?int $aulaId          = null;
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
    public string $modalTitle = '';

    public array $dias = [
        1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira',
        4 => 'Quinta-feira',  5 => 'Sexta-feira',  6 => 'Sábado',
    ];

    public array $diasCurtos = [
        1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta',
        4 => 'Quinta',  5 => 'Sexta', 6 => 'Sábado',
    ];

    public array $modalidades = ['presencial', 'online', 'híbrido'];

    protected $queryString = ['search', 'filtro'];

    protected function rules(): array
    {
        return [
            'turma_id'          => 'required',
            'disciplina_id'     => 'required',
            'professor_id'      => 'required',
            'sala_id'           => 'nullable',
            'horario_id'        => 'required_without:todosHorarios',
            'periodo_letivo_id' => 'required',
            'dia_semana'        => 'required|integer|min:1|max:6',
            'modalidade'        => 'required',
        ];
    }

    protected array $messages = [
        'turma_id.required'          => 'Selecione a turma.',
        'disciplina_id.required'     => 'Selecione a disciplina.',
        'professor_id.required'      => 'Selecione o professor.',
        'horario_id.required_without'=> 'Selecione o horário.',
        'periodo_letivo_id.required' => 'Selecione o período letivo.',
        'dia_semana.required'        => 'Selecione o dia da semana.',
        'modalidade.required'        => 'Selecione a modalidade.',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Nova Aula';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $a = Aula::findOrFail($id);
        $this->aulaId           = $a->id;
        $this->turma_id         = $a->turma_id;
        $this->disciplina_id    = $a->disciplina_id;
        $this->professor_id     = $a->professor_id;
        $this->sala_id          = $a->sala_id ?? '';
        $this->horario_id       = $a->horario_id;
        $this->periodo_letivo_id = $a->periodo_letivo_id;
        $this->dia_semana       = $a->dia_semana;
        $this->modalidade       = $a->modalidade;
        $this->modalTitle       = 'Editar Aula';
        $this->showModal        = true;
    }

    public function save(): void
    {
        $erros = [];

        // Verifica duplicidade
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
            foreach ($erros as $e) {
                $this->addError('geral', $e);
            }
            return;
        }

        $this->validate();

        if ($this->todosHorarios) {
            $horarios = Horario::where('tipo', '!=', 'intervalo')->get();
            foreach ($horarios as $h) {
                Aula::firstOrCreate(
                    [
                        'turma_id'          => $this->turma_id,
                        'disciplina_id'     => $this->disciplina_id,
                        'horario_id'        => $h->id,
                        'dia_semana'        => $this->dia_semana,
                        'periodo_letivo_id' => $this->periodo_letivo_id,
                    ],
                    [
                        'professor_id' => $this->professor_id,
                        'sala_id'      => $this->sala_id ?: null,
                        'modalidade'   => $this->modalidade,
                    ]
                );
            }
        } else {
            Aula::updateOrCreate(
                ['id' => $this->aulaId],
                [
                    'turma_id'          => $this->turma_id,
                    'disciplina_id'     => $this->disciplina_id,
                    'professor_id'      => $this->professor_id,
                    'sala_id'           => $this->sala_id ?: null,
                    'horario_id'        => $this->horario_id,
                    'periodo_letivo_id' => $this->periodo_letivo_id,
                    'dia_semana'        => $this->dia_semana,
                    'modalidade'        => $this->modalidade,
                ]
            );
        }

        // Log da ação
        $dias = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb'];
        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Aulas',
            ($isNovo ? 'Nova aula: ' : 'Editou aula: ') . ($dias[$this->dia_semana] ?? '')
        );
        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $isNovo ? 'Aula cadastrada com sucesso!' : 'Aula atualizada com sucesso!');
    }

    public function confirmDelete(int $id): void
    {
        $this->aulaId    = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $aula = Aula::with(['disciplina','turma'])->findOrFail($this->aulaId);
        $descricao = 'Excluiu aula: ' . ($aula->disciplina->nome ?? '') . ' - ' . ($aula->turma->nome ?? '');
        $aula->delete();
        // Log da ação
        Log::registrar('excluiu', 'Aulas', $descricao);
        $this->showDelete = false;
        $this->resetForm();
        session()->flash('success', 'Aula excluída com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->aulaId = null;
        $this->turma_id = $this->disciplina_id = $this->professor_id = '';
        $this->sala_id = $this->horario_id = $this->periodo_letivo_id = '';
        $this->dia_semana = $this->modalidade = '';
        $this->modalidade = 'presencial';
        $this->todosHorarios = false;
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltro(): void { $this->resetPage(); $this->search = ''; }

    public function render()
    {
        $diasNomes = [
            1=>'Segunda', 2=>'Terça', 3=>'Quarta',
            4=>'Quinta',  5=>'Sexta', 6=>'Sábado',
        ];

        $diaNumero = null;
        if ($this->search) {
            foreach ($diasNomes as $num => $nome) {
                if (stripos($nome, $this->search) !== false) {
                    $diaNumero = $num;
                    break;
                }
            }
        }

        $aulas = Aula::with(['turma', 'disciplina', 'professor', 'sala', 'horario', 'periodoLetivo'])
            ->when($this->search, function($q) use ($diaNumero) {
                $s = $this->search;
                match($this->filtro) {
                    'turma'      => $q->whereHas('turma', fn($q) => $q->where('nome', 'like', "%$s%")),
                    'disciplina' => $q->whereHas('disciplina', fn($q) => $q->where('nome', 'like', "%$s%")),
                    'professor'  => $q->whereHas('professor', fn($q) => $q->where('nome', 'like', "%$s%")),
                    'sala'       => $q->whereHas('sala', fn($q) => $q->where('nome', 'like', "%$s%")),
                    'dia'        => $diaNumero ? $q->where('dia_semana', $diaNumero) : $q->whereRaw('0=1'),
                    default      => $q->whereHas('turma', fn($q) => $q->where('nome', 'like', "%$s%"))
                                      ->orWhereHas('disciplina', fn($q) => $q->where('nome', 'like', "%$s%"))
                                      ->orWhereHas('professor', fn($q) => $q->where('nome', 'like', "%$s%"))
                                      ->orWhereHas('sala', fn($q) => $q->where('nome', 'like', "%$s%"))
                                      ->orWhere(fn($q) => $diaNumero ? $q->where('dia_semana', $diaNumero) : null),
                };
            })
            ->orderBy('dia_semana')
            ->paginate(15);

        $turmas          = Turma::orderBy('nome')->get();
        $disciplinas     = Disciplina::orderBy('nome')->get();
        $professores     = Professor::orderBy('nome')->get();
        $salas           = Sala::orderBy('nome')->get();
        $horarios        = Horario::orderBy('hora_inicio')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->get();

        return view('livewire.aulas-crud', compact(
            'aulas', 'turmas', 'disciplinas', 'professores',
            'salas', 'horarios', 'periodosLetivos'
        ));
    }
}
