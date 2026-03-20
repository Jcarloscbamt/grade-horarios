<?php
// app/Livewire/AulasCrud.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Disciplina, Professor, Sala, Horario, PeriodoLetivo};
use Livewire\Component;
use Livewire\WithPagination;

class AulasCrud extends Component
{
    use WithPagination;

    public ?int $aulaId            = null;
    public string $turma_id        = '';
    public string $disciplina_id   = '';
    public string $professor_id    = '';
    public string $sala_id         = '';
    public string $horario_id      = '';
    public string $periodo_letivo_id = '';
    public string $dia_semana      = '';
    public string $modalidade      = 'presencial';
    public string $tipo_lancamento = 'unico'; // unico | todos_horarios

    public bool $showModal  = false;
    public bool $showDelete = false;
    public string $search   = '';
    public string $modalTitle = '';

    protected $queryString = ['search'];

    public array $dias = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    public array $modalidades = ['presencial', 'online', 'híbrido'];

    protected function rules(): array
    {
        return [
            'turma_id'          => 'required|exists:turmas,id',
            'disciplina_id'     => 'required|exists:disciplinas,id',
            'professor_id'      => 'required|exists:professores,id',
            'sala_id'           => 'nullable|exists:salas,id',
            'horario_id'        => 'required_if:tipo_lancamento,unico|nullable|exists:horarios,id',
            'periodo_letivo_id' => 'required|exists:periodo_letivos,id',
            'dia_semana'        => 'required|integer|min:1|max:6',
            'modalidade'        => 'required|in:presencial,online,híbrido',
            'tipo_lancamento'   => 'required|in:unico,todos_horarios',
        ];
    }

    protected array $messages = [
        'turma_id.required'          => 'Selecione uma turma.',
        'disciplina_id.required'     => 'Selecione uma disciplina.',
        'professor_id.required'      => 'Selecione um professor.',
        'horario_id.required_if'     => 'Selecione um horário.',
        'periodo_letivo_id.required' => 'Selecione um período letivo.',
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
        $this->aulaId            = $a->id;
        $this->turma_id          = $a->turma_id;
        $this->disciplina_id     = $a->disciplina_id;
        $this->professor_id      = $a->professor_id;
        $this->sala_id           = $a->sala_id ?? '';
        $this->horario_id        = $a->horario_id;
        $this->periodo_letivo_id = $a->periodo_letivo_id;
        $this->dia_semana        = $a->dia_semana;
        $this->modalidade        = $a->modalidade;
        $this->tipo_lancamento   = 'unico'; // edição sempre é unitária
        $this->modalTitle        = 'Editar Aula';
        $this->showModal         = true;
    }

    public function save(): void
    {
        $this->validate();

        // Lançamento em todos os horários do dia
        if ($this->tipo_lancamento === 'todos_horarios' && !$this->aulaId) {
            $this->salvarTodosHorarios();
            return;
        }

        // Lançamento unitário
        $this->salvarUnico($this->horario_id);
    }

    // Salva um único horário com validações de conflito
    private function salvarUnico(string $horarioId): bool
    {
        // 1. Verificar duplicidade
        $duplicada = Aula::where('turma_id',          $this->turma_id)
            ->where('disciplina_id',     $this->disciplina_id)
            ->where('horario_id',        $horarioId)
            ->where('dia_semana',        $this->dia_semana)
            ->where('periodo_letivo_id', $this->periodo_letivo_id)
            ->when($this->aulaId, fn($q) => $q->where('id', '!=', $this->aulaId))
            ->exists();

        if ($duplicada) {
            $horario = Horario::find($horarioId);
            $this->addError('disciplina_id', "Aula duplicada: já existe cadastro para este horário (" . substr($horario->hora_inicio,0,5) . " - " . substr($horario->hora_fim,0,5) . ") neste dia.");
            return false;
        }

        // 2. Conflito de professor
        $conflitoProf = Aula::where('professor_id',      $this->professor_id)
            ->where('horario_id',        $horarioId)
            ->where('dia_semana',        $this->dia_semana)
            ->where('periodo_letivo_id', $this->periodo_letivo_id)
            ->when($this->aulaId, fn($q) => $q->where('id', '!=', $this->aulaId))
            ->exists();

        if ($conflitoProf) {
            $professor = Professor::find($this->professor_id);
            $horario   = Horario::find($horarioId);
            $this->addError('professor_id', "Conflito: {$professor->nome} já tem aula às " . substr($horario->hora_inicio,0,5) . " neste dia em outra turma.");
            return false;
        }

        // 3. Conflito de sala
        if ($this->sala_id) {
            $conflitoSala = Aula::where('sala_id',           $this->sala_id)
                ->where('horario_id',        $horarioId)
                ->where('dia_semana',        $this->dia_semana)
                ->where('periodo_letivo_id', $this->periodo_letivo_id)
                ->when($this->aulaId, fn($q) => $q->where('id', '!=', $this->aulaId))
                ->exists();

            if ($conflitoSala) {
                $sala    = Sala::find($this->sala_id);
                $horario = Horario::find($horarioId);
                $this->addError('sala_id', "Conflito: {$sala->nome} já está ocupada às " . substr($horario->hora_inicio,0,5) . " neste dia.");
                return false;
            }
        }

        Aula::updateOrCreate(
            ['id' => $this->aulaId],
            [
                'turma_id'          => $this->turma_id,
                'disciplina_id'     => $this->disciplina_id,
                'professor_id'      => $this->professor_id,
                'sala_id'           => $this->sala_id ?: null,
                'horario_id'        => $horarioId,
                'periodo_letivo_id' => $this->periodo_letivo_id,
                'dia_semana'        => $this->dia_semana,
                'modalidade'        => $this->modalidade,
            ]
        );

        return true;
    }

    // Salva automaticamente em todos os horários do dia
    private function salvarTodosHorarios(): void
    {
        $horarios = Horario::orderBy('hora_inicio')->get();

        $salvos   = 0;
        $erros    = [];

        foreach ($horarios as $horario) {
            // Verifica duplicidade silenciosamente
            $duplicada = Aula::where('turma_id',          $this->turma_id)
                ->where('disciplina_id',     $this->disciplina_id)
                ->where('horario_id',        $horario->id)
                ->where('dia_semana',        $this->dia_semana)
                ->where('periodo_letivo_id', $this->periodo_letivo_id)
                ->exists();

            if ($duplicada) {
                $erros[] = "Horário " . substr($horario->hora_inicio,0,5) . " já estava cadastrado — ignorado.";
                continue;
            }

            // Conflito de professor
            $conflitoProf = Aula::where('professor_id',      $this->professor_id)
                ->where('horario_id',        $horario->id)
                ->where('dia_semana',        $this->dia_semana)
                ->where('periodo_letivo_id', $this->periodo_letivo_id)
                ->exists();

            if ($conflitoProf) {
                $professor = Professor::find($this->professor_id);
                $erros[] = "Conflito às " . substr($horario->hora_inicio,0,5) . ": {$professor->nome} já tem aula neste horário.";
                continue;
            }

            // Conflito de sala
            if ($this->sala_id) {
                $conflitoSala = Aula::where('sala_id',           $this->sala_id)
                    ->where('horario_id',        $horario->id)
                    ->where('dia_semana',        $this->dia_semana)
                    ->where('periodo_letivo_id', $this->periodo_letivo_id)
                    ->exists();

                if ($conflitoSala) {
                    $sala = Sala::find($this->sala_id);
                    $erros[] = "Conflito às " . substr($horario->hora_inicio,0,5) . ": {$sala->nome} já está ocupada.";
                    continue;
                }
            }

            Aula::create([
                'turma_id'          => $this->turma_id,
                'disciplina_id'     => $this->disciplina_id,
                'professor_id'      => $this->professor_id,
                'sala_id'           => $this->sala_id ?: null,
                'horario_id'        => $horario->id,
                'periodo_letivo_id' => $this->periodo_letivo_id,
                'dia_semana'        => $this->dia_semana,
                'modalidade'        => $this->modalidade,
            ]);

            $salvos++;
        }

        $this->showModal = false;
        $this->resetForm();

        if ($salvos > 0 && empty($erros)) {
            session()->flash('success', "{$salvos} aulas cadastradas com sucesso!");
        } elseif ($salvos > 0 && !empty($erros)) {
            $avisos = implode(' | ', $erros);
            session()->flash('success', "{$salvos} aulas cadastradas. Avisos: {$avisos}");
        } else {
            session()->flash('error', 'Nenhuma aula foi cadastrada. ' . implode(' | ', $erros));
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->aulaId     = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        Aula::findOrFail($this->aulaId)->delete();
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
        $this->aulaId          = null;
        $this->turma_id        = '';
        $this->disciplina_id   = '';
        $this->professor_id    = '';
        $this->sala_id         = '';
        $this->horario_id      = '';
        $this->periodo_letivo_id = '';
        $this->dia_semana      = '';
        $this->modalidade      = 'presencial';
        $this->tipo_lancamento = 'unico';
        $this->resetValidation();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $aulas = Aula::with(['turma', 'disciplina', 'professor', 'sala', 'horario', 'periodoLetivo'])
            ->when($this->search, fn($q) =>
                $q->whereHas('turma', fn($q) => $q->where('nome', 'like', "%{$this->search}%"))
                  ->orWhereHas('disciplina', fn($q) => $q->where('nome', 'like', "%{$this->search}%"))
                  ->orWhereHas('professor', fn($q) => $q->where('nome', 'like', "%{$this->search}%"))
            )
            ->orderBy('dia_semana')
            ->orderBy('horario_id')
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
