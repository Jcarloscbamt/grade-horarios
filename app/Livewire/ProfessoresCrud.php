<?php
// app/Livewire/ProfessoresCrud.php
namespace App\Livewire;

use App\Models\{Professor, Disciplina, Turma, ProfessorDisciplina, Log};
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class ProfessoresCrud extends Component
{
    use WithPagination;

    // ── Dados do professor ────────────────────────────
    public ?int   $professorId = null;
    public string $nome        = '';
    public string $email       = '';
    public string $telefone    = '';
    public string $cpf         = '';
    public bool   $ativo       = true;
    public array  $disponibilidade = []; // dias disponíveis (array de ints 1-5)

    // ── Aviso de alocação (dias < disciplinas) ────────
    public bool   $mostrarAvisoAlocacao    = false;
    public string $msgAvisoAlocacao        = '';
    public bool   $avisoAlocacaoConfirmado = false;

    // ── Vínculos disciplina/turma ─────────────────────
    public array  $vinculos           = []; // [{disciplina_id, disciplina_nome, curso_id, curso_nome, turma_id, turma_nome, dias}]
    public string $filtro_curso_id   = '';
    public string $filtro_turma_id   = '';
    public int    $sel_disciplina_id  = 0;
    public string $sel_disciplina_nome = '';
    public int    $sel_curso_id       = 0;
    public string $sel_curso_nome     = '';
    public string $sel_turma_id       = '';
    public int    $editandoVinculoIdx = -1;

    // ── NÍVEL 1: Competências (curso + disciplina que o professor sabe lecionar) ──
    public array  $competencias       = []; // [{curso_id, curso_nome, disciplina_id, disciplina_nome}]
    public string $comp_curso_id      = ''; // curso selecionado no formulário de competência
    public string $comp_disciplina_id = ''; // disciplina selecionada no formulário de competência

    // ── Modal / UI ────────────────────────────────────
    public bool   $showModal  = false;
    public bool   $showDelete = false;
    public string $search     = '';
    public string $filtro     = 'todos';
    public string $filtroAtivo = 'todos';
    public string $modalTitle = '';

    protected $queryString = ['search', 'filtro'];

    protected function rules(): array
    {
        $emailRule = 'required|email';
        if ($this->professorId) {
            $emailRule .= '|unique:professores,email,' . $this->professorId;
        } else {
            $emailRule .= '|unique:professores,email';
        }

        return [
            'nome'  => 'required|min:3|max:100',
            'email' => $emailRule,
            'cpf'   => 'required|min:14',
        ];
    }

    protected array $messages = [
        'nome.required'  => 'O nome é obrigatório.',
        'nome.min'       => 'Nome deve ter ao menos 3 caracteres.',
        'email.required' => 'O e-mail é obrigatório.',
        'email.email'    => 'Informe um e-mail válido.',
        'email.unique'   => 'Este e-mail já está cadastrado.',
        'cpf.required'   => 'O CPF é obrigatório.',
        'cpf.min'        => 'CPF inválido.',
        'cpf.unique'     => 'Este CPF já está cadastrado.',
    ];

    // ── CPF/Telefone com formatação blur ─────────────
    public function updatedCpf(string $value): void
    {
        $this->cpf = $this->formatarCPF($value);
        $this->resetValidation('cpf');
        $digits = preg_replace('/\D/', '', $this->cpf);

        if (strlen($digits) === 11) {
            if (!$this->validarCPF($digits)) {
                $this->addError('cpf', 'CPF inválido. Verifique os dígitos verificadores da Receita Federal.');
            }
            // Verificar unicidade
            $existe = \App\Models\Professor::where('cpf', $this->cpf)
                ->when($this->professorId, fn($q) => $q->where('id', '!=', $this->professorId))
                ->exists();
            if ($existe) {
                $this->addError('cpf', 'Este CPF já está cadastrado.');
            }
        }
    }

    // Valida email ao sair do campo
    public function updatedEmail(string $value): void
    {
        $this->resetValidation('email');
        if (empty(trim($value))) {
            $this->addError('email', 'O e-mail é obrigatório.');
            return;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'Informe um e-mail válido.');
            return;
        }
        // Verificar unicidade
        $existe = \App\Models\Professor::where('email', $value)
            ->when($this->professorId, fn($q) => $q->where('id', '!=', $this->professorId))
            ->exists();
        if ($existe) {
            $this->addError('email', 'Este e-mail já está cadastrado.');
        }
    }

    public function updatedTelefone(string $value): void
    {
        $this->telefone = $this->formatarTelefone($value);
        $this->resetValidation('telefone');
    }


    // ── Validação matemática CPF (Receita Federal) ───
    private function validarCPF(string $cpf): bool
    {
        $n = preg_replace('/\D/', '', $cpf);
        if (strlen($n) !== 11) return false;
        if (preg_match('/^(\d)\1+$/', $n)) return false; // todos dígitos iguais

        // Primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int)$n[$i] * (10 - $i);
        }
        $resto   = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;
        if ((int)$n[9] !== $digito1) return false;

        // Segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int)$n[$i] * (11 - $i);
        }
        $resto   = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;
        if ((int)$n[10] !== $digito2) return false;

        return true;
    }

    private function formatarCPF(string $v): string
    {
        $n = preg_replace('/\D/', '', $v);
        $n = substr($n, 0, 11);
        if (strlen($n) > 9) return substr($n,0,3).'.'.substr($n,3,3).'.'.substr($n,6,3).'-'.substr($n,9,2);
        if (strlen($n) > 6) return substr($n,0,3).'.'.substr($n,3,3).'.'.substr($n,6);
        if (strlen($n) > 3) return substr($n,0,3).'.'.substr($n,3);
        return $n;
    }

    private function formatarTelefone(string $v): string
    {
        $n = preg_replace('/\D/', '', $v);
        $n = substr($n, 0, 11);
        if (strlen($n) > 10) return '('.substr($n,0,2).') '.substr($n,2,5).'-'.substr($n,7,4);
        if (strlen($n) > 6)  return '('.substr($n,0,2).') '.substr($n,2,4).'-'.substr($n,6);
        if (strlen($n) > 2)  return '('.substr($n,0,2).') '.substr($n,2);
        return $n;
    }

    // ── Disponibilidade geral ─────────────────────────
    public function toggleTodosDisponibilidade(): void
    {
        if (count($this->disponibilidade) === 5) {
            $this->disponibilidade = [];
        } else {
            $this->disponibilidade = [1, 2, 3, 4, 5];
        }
    }

    // ── Disciplinas: busca reativa ────────────────────
    public function getSel_disciplinaIdProperty(): int { return $this->sel_disciplina_id; }

    // ── Selecionar disciplina da lista ────────────────
    public function selecionarDisciplina(int $id, string $nome, string $cursoNome, int $cursoId): void
    {
        $this->sel_disciplina_id   = $id;
        $this->sel_disciplina_nome = $nome;
        $this->sel_curso_id        = $cursoId;
        $this->sel_curso_nome      = $cursoNome;
        $this->sel_turma_id        = $this->filtro_turma_id;
        $this->filtro_turma_id    = '';
    }

    public function cancelarSelecao(): void
    {
        $this->sel_disciplina_id   = 0;
        $this->sel_disciplina_nome = '';
        $this->sel_curso_id        = 0;
        $this->sel_curso_nome      = '';
        $this->sel_turma_id        = '';
        $this->filtro_curso_id    = '';
        $this->filtro_turma_id    = '';
        $this->editandoVinculoIdx  = -1;
    }

    // ── Adicionar / editar vínculo ────────────────────
    // ── NÍVEL 1: Competências ─────────────────────────
    public function adicionarCompetencia(): void
    {
        if (!$this->comp_curso_id || !$this->comp_disciplina_id) {
            $this->addError('competencia', 'Selecione o curso e a disciplina.');
            return;
        }

        // Bloqueia duplicada
        foreach ($this->competencias as $c) {
            if ((int)$c['curso_id'] === (int)$this->comp_curso_id
                && (int)$c['disciplina_id'] === (int)$this->comp_disciplina_id) {
                $this->addError('competencia', 'Esta competência já foi adicionada.');
                return;
            }
        }

        $curso      = \App\Models\Curso::find($this->comp_curso_id);
        $disciplina = Disciplina::find($this->comp_disciplina_id);
        if (!$curso || !$disciplina) {
            $this->addError('competencia', 'Curso ou disciplina inválidos.');
            return;
        }

        $this->competencias[] = [
            'curso_id'        => (int) $this->comp_curso_id,
            'curso_nome'      => $curso->nome,
            'disciplina_id'   => (int) $this->comp_disciplina_id,
            'disciplina_nome' => $disciplina->nome,
        ];

        $this->comp_disciplina_id = '';
        $this->resetValidation('competencia');
    }

    public function removerCompetencia(int $idx): void
    {
        if (!isset($this->competencias[$idx])) return;

        $comp = $this->competencias[$idx];
        // Remove a competência
        unset($this->competencias[$idx]);
        $this->competencias = array_values($this->competencias);

        // Remove também os vínculos do período que dependiam dessa competência
        // (mesma disciplina + mesmo curso), pois não pode haver vínculo sem competência
        $this->vinculos = array_values(array_filter($this->vinculos, fn($v) =>
            !((int)$v['disciplina_id'] === (int)$comp['disciplina_id']
              && (int)$v['curso_id'] === (int)$comp['curso_id'])
        ));
    }

    public function adicionarVinculo(): void
    {
        if (!$this->sel_disciplina_id || !$this->sel_turma_id) {
            $this->addError('vinculo', 'Selecione disciplina e turma.');
            return;
        }
        if (empty($this->disponibilidade)) {
            $this->addError('vinculo', 'Defina a disponibilidade geral do professor antes de adicionar disciplinas.');
            return;
        }

        // Bloqueia vínculo duplicado (mesma disciplina + turma)
        foreach ($this->vinculos as $i => $v) {
            if ($i === $this->editandoVinculoIdx) continue;
            if ((int)$v['disciplina_id'] === (int)$this->sel_disciplina_id
                && (int)$v['turma_id'] === (int)$this->sel_turma_id) {
                $this->addError('vinculo', 'Este vínculo (disciplina + turma) já foi adicionado.');
                return;
            }
        }

        // LIMITE: máximo 5 vínculos por professor.
        // A semana letiva tem 5 dias úteis e cada aula (disciplina+turma) ocupa 1 dia,
        // então um professor não pode assumir mais de 5 aulas. Só bloqueia ao ADICIONAR novo.
        if ($this->editandoVinculoIdx < 0 && count($this->vinculos) >= 5) {
            $this->addError('vinculo',
                'Limite de 5 vínculos atingido. Como a semana letiva tem apenas 5 dias úteis e cada aula ocupa 1 dia, '
                . 'um professor pode assumir no máximo 5 turmas/disciplinas. Remova um vínculo ou distribua a disciplina para outro professor.');
            return;
        }

        // Usa todos os dias da disponibilidade geral
        // O Gerador de Grade gerencia os conflitos entre turmas automaticamente
        $turma = Turma::find($this->sel_turma_id);
        $novoVinculo = [
            'disciplina_id'   => $this->sel_disciplina_id,
            'disciplina_nome' => $this->sel_disciplina_nome,
            'curso_id'        => $this->sel_curso_id,
            'curso_nome'      => $this->sel_curso_nome,
            'turma_id'        => (int) $this->sel_turma_id,
            'turma_nome'      => $turma->nome ?? '',
            'dias'            => array_values(array_map('intval', $this->disponibilidade)),
        ];

        if ($this->editandoVinculoIdx >= 0) {
            $this->vinculos[$this->editandoVinculoIdx] = $novoVinculo;
        } else {
            $this->vinculos[] = $novoVinculo;
        }

        $this->cancelarSelecao();
        $this->resetValidation('vinculo');
    }

    public function editarVinculo(int $idx): void
    {
        $v = $this->vinculos[$idx] ?? null;
        if (!$v) return;
        $this->sel_disciplina_id   = $v['disciplina_id'];
        $this->sel_disciplina_nome = $v['disciplina_nome'];
        $this->sel_curso_id        = $v['curso_id'];
        $this->sel_curso_nome      = $v['curso_nome'];
        $this->sel_turma_id        = (string) $v['turma_id'];
        $this->editandoVinculoIdx  = $idx;
        $this->filtro_curso_id    = '';
        $this->filtro_turma_id    = '';
    }

    public function removerVinculo(int $idx): void
    {
        array_splice($this->vinculos, $idx, 1);
    }

    // ── CRUD ──────────────────────────────────────────
    #[Url]
    public int $editar = 0;

    public function mount(): void
    {
        // Abre edição via URL ?editar=ID (ex: vindo do gerador de grade)
        if ($this->editar > 0) {
            $this->edit($this->editar);
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Novo Professor';
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $p = Professor::findOrFail($id);
        $this->professorId     = $p->id;
        $this->nome            = $p->nome;
        $this->email           = $p->email;
        $this->telefone        = $p->telefone ?? '';
        $this->cpf             = $p->cpf;
        $this->ativo           = (bool) $p->ativo;
        $this->disponibilidade = is_array($p->disponibilidade)
            ? $p->disponibilidade
            : (json_decode($p->disponibilidade ?? '[]', true) ?? []);

        // Carrega vínculos existentes
        $this->vinculos = ProfessorDisciplina::where('professor_id', $id)
            ->with(['disciplina.curso', 'turma'])
            ->get()
            ->map(fn($v) => [
                'disciplina_id'   => $v->disciplina_id,
                'disciplina_nome' => $v->disciplina->nome ?? '',
                'curso_id'        => $v->disciplina->curso_id ?? 0,
                'curso_nome'      => $v->disciplina->curso->nome ?? '',
                'turma_id'        => $v->turma_id,
                'turma_nome'      => $v->turma->nome ?? '',
                'dias'            => is_array($v->dias)
                    ? $v->dias
                    : (json_decode($v->dias ?? '[]', true) ?? []),
            ])
            ->toArray();

        // Carrega competências (Nível 1)
        $this->competencias = \App\Models\ProfessorCompetencia::where('professor_id', $id)
            ->with(['curso', 'disciplina'])
            ->get()
            ->map(fn($c) => [
                'curso_id'        => $c->curso_id,
                'curso_nome'      => $c->curso->nome ?? '',
                'disciplina_id'   => $c->disciplina_id,
                'disciplina_nome' => $c->disciplina->nome ?? '',
            ])
            ->toArray();

        $this->modalTitle = 'Editar Professor';
        $this->showModal  = true;
    }

    public function confirmarSalvarComAviso(): void
    {
        $this->avisoAlocacaoConfirmado = true;
        $this->mostrarAvisoAlocacao    = false;
        $this->save();
    }

    public function cancelarAvisoAlocacao(): void
    {
        $this->mostrarAvisoAlocacao = false;
        // mantém o formulário aberto para o usuário ajustar a disponibilidade
    }

    public function save(): void
    {
        // Garantir que CPF e email estejam preenchidos
        if (empty(trim($this->cpf))) {
            $this->addError('cpf', 'O CPF é obrigatório.');
            return;
        }
        if (empty(trim($this->email))) {
            $this->addError('email', 'O e-mail é obrigatório.');
            return;
        }

        // Disponibilidade geral obrigatória
        if (empty($this->disponibilidade)) {
            $this->addError('disponibilidade', 'Informe ao menos um dia de disponibilidade.');
            return;
        }


        // Validação matemática do CPF (não usa closure para compatibilidade com Livewire 3)
        $cpfDigits = preg_replace('/\D/', '', $this->cpf);
        if (!$this->validarCPF($cpfDigits)) {
            $this->addError('cpf', 'CPF inválido. Verifique os dígitos verificadores da Receita Federal.');
            return;
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'Informe um e-mail válido.');
            return;
        }

        // Verificar unicidade manualmente (também não usa closure)
        $cpfExiste = \App\Models\Professor::where('cpf', $this->cpf)
            ->when($this->professorId, fn($q) => $q->where('id', '!=', $this->professorId))
            ->exists();
        if ($cpfExiste) {
            $this->addError('cpf', 'Este CPF já está cadastrado.');
            return;
        }
        $emailExiste = \App\Models\Professor::where('email', $this->email)
            ->when($this->professorId, fn($q) => $q->where('id', '!=', $this->professorId))
            ->exists();
        if ($emailExiste) {
            $this->addError('email', 'Este e-mail já está cadastrado.');
            return;
        }

        $this->validate();

        // ── AVISO DE ALOCAÇÃO: dias de disponibilidade < disciplinas vinculadas ──
        // Um professor só dá 1 aula por dia, então N disciplinas exigem ao menos N dias.
        $numDias = count($this->disponibilidade);
        $numDisc = count($this->vinculos);
        if ($numDisc > $numDias && !$this->avisoAlocacaoConfirmado) {
            if ($numDisc > 5) {
                // Impossível: mais disciplinas do que dias úteis na semana
                $excedente = $numDisc - 5;
                $this->msgAvisoAlocacao =
                    "Este professor tem {$numDisc} disciplina(s) vinculada(s), mas a semana letiva tem apenas 5 dias úteis. "
                    . "Como ele dá no máximo 1 aula por dia (5 no total), é IMPOSSÍVEL alocar todas na grade — "
                    . "pelo menos {$excedente} disciplina(s) ficará(ão) em conflito. O ideal é redistribuir para outro professor. "
                    . "Deseja salvar mesmo assim?";
            } else {
                $faltam = $numDisc - $numDias;
                $this->msgAvisoAlocacao =
                    "Este professor tem {$numDisc} disciplina(s) vinculada(s), mas apenas {$numDias} dia(s) de disponibilidade. "
                    . "Como um professor só pode dar 1 aula por dia, faltam {$faltam} dia(s) — isso provavelmente vai gerar conflito(s) na geração da grade. "
                    . "Deseja salvar mesmo assim?";
            }
            $this->mostrarAvisoAlocacao = true;
            return; // aguarda decisão do usuário (Continuar / Cancelar)
        }

        $isNovo = is_null($this->professorId);
        $cpfFormatado = $this->cpf;

        $prof = Professor::updateOrCreate(
            ['id' => $this->professorId],
            [
                'nome'            => $this->nome,
                'email'           => $this->email,
                'telefone'        => $this->telefone ?: null,
                'cpf'             => $cpfFormatado,
                'disponibilidade' => $this->disponibilidade,
                'ativo'           => $this->ativo,
            ]
        );

        // Sincroniza vínculos — dias sempre = disponibilidade geral atual
        // Garante que mudanças na disponibilidade reflitam em todos os vínculos
        ProfessorDisciplina::where('professor_id', $prof->id)->delete();
        $diasAtuais = array_values(array_map('intval', $this->disponibilidade));
        foreach ($this->vinculos as $v) {
            ProfessorDisciplina::create([
                'professor_id'  => $prof->id,
                'disciplina_id' => $v['disciplina_id'],
                'turma_id'      => $v['turma_id'],
                'dias'          => $diasAtuais, // sempre usa disponibilidade atual
            ]);
        }

        // NÍVEL 1: sincroniza competências (curso + disciplina, sem turma, sem limite)
        \App\Models\ProfessorCompetencia::where('professor_id', $prof->id)->delete();
        foreach ($this->competencias as $c) {
            \App\Models\ProfessorCompetencia::create([
                'professor_id'  => $prof->id,
                'curso_id'      => $c['curso_id'],
                'disciplina_id' => $c['disciplina_id'],
            ]);
        }

        Log::registrar(
            $isNovo ? 'criou' : 'editou',
            'Professores',
            ($isNovo ? 'Novo: ' : 'Editou: ') . $this->nome
        );

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', $isNovo ? 'Professor cadastrado com sucesso!' : 'Professor atualizado com sucesso!');
    }

    public function toggleAtivo(int $id): void
    {
        $prof = Professor::findOrFail($id);
        $prof->ativo = !$prof->ativo;
        $prof->save();
        $status = $prof->ativo ? 'ativado' : 'desativado';
        session()->flash('success', 'Professor ' . $status . ' com sucesso!');
        Log::registrar('editou', 'Professores', 'Professor ' . $status . ': ' . $prof->nome);
    }

    public function confirmDelete(int $id): void
    {
        $this->professorId = $id;
        $this->showDelete  = true;
    }

    public function delete(): void
    {
        $prof = Professor::findOrFail($this->professorId);
        ProfessorDisciplina::where('professor_id', $this->professorId)->delete();
        \App\Models\ProfessorCompetencia::where('professor_id', $this->professorId)->delete();
        $nome = $prof->nome;
        $prof->delete();
        Log::registrar('excluiu', 'Professores', 'Excluiu: ' . $nome);
        $this->showDelete = false;
        $this->resetForm();
        session()->flash('success', 'Professor excluído com sucesso!');
    }

    public function closeModal(): void
    {
        $this->showModal  = false;
        $this->showDelete = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->professorId         = null;
        $this->nome                = '';
        $this->email               = '';
        $this->telefone            = '';
        $this->cpf                 = '';
        $this->ativo               = true;
        $this->disponibilidade     = [];
        $this->vinculos            = [];
        $this->competencias        = [];
        $this->comp_curso_id       = '';
        $this->comp_disciplina_id  = '';
        $this->filtro_curso_id    = '';
        $this->filtro_turma_id    = '';
        $this->sel_disciplina_id   = 0;
        $this->sel_disciplina_nome = '';
        $this->sel_curso_id        = 0;
        $this->sel_curso_nome      = '';
        $this->sel_turma_id        = '';
        $this->editandoVinculoIdx  = -1;
        $this->mostrarAvisoAlocacao    = false;
        $this->msgAvisoAlocacao        = '';
        $this->avisoAlocacaoConfirmado = false;
        $this->resetValidation();
    }

    public function updatedFiltroCursoId(): void
    {
        $this->filtro_turma_id   = '';
        $this->sel_disciplina_id = 0;
        $this->sel_disciplina_nome = '';
    }

    public function updatedFiltroTurmaId(): void
    {
        $this->sel_disciplina_id   = 0;
        $this->sel_disciplina_nome = '';
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFiltro(): void { $this->resetPage(); $this->search = ''; }
    public function updatingFiltroAtivo(): void { $this->resetPage(); }

    public function render()
    {
        $professores = Professor::with('disciplinasTurmas')
            ->when($this->filtroAtivo === 'ativos', fn($q) => $q->where('ativo', true))
            ->when($this->filtroAtivo === 'inativos', fn($q) => $q->where('ativo', false))
            ->when($this->search, function ($q) {
                $s = $this->search;
                match ($this->filtro) {
                    'nome'     => $q->where('nome', 'like', "%$s%"),
                    'email'    => $q->where('email', 'like', "%$s%"),
                    'cpf'      => $q->where('cpf', 'like', "%$s%"),
                    'telefone' => $q->where('telefone', 'like', "%$s%"),
                    default    => $q->where('nome', 'like', "%$s%")
                                    ->orWhere('email', 'like', "%$s%"),
                };
            })
            ->orderBy('nome')
            ->paginate(20);

        $diasNomes = [1=>'SEG', 2=>'TER', 3=>'QUA', 4=>'QUI', 5=>'SEX'];

        // Filtros por Curso + Turma
        $cursosFiltro = \App\Models\Curso::where('ativo', true)->orderBy('nome')->get();
        $turmasFiltro = [];
        if ($this->filtro_curso_id) {
            $turmasFiltro = Turma::where('curso_id', $this->filtro_curso_id)
                ->where('ativo', true)->orderBy('nome')->get();
        }

        // Disciplinas do semestre atual da turma selecionada
        // NÍVEL 2: só oferece disciplinas que estão nas COMPETÊNCIAS do professor
        $disciplinasDisponiveis = [];
        $mostrarLista = !empty($this->filtro_turma_id);
        if ($mostrarLista) {
            $turmaFiltro = Turma::with('curso')->find($this->filtro_turma_id);
            if ($turmaFiltro) {
                $jaVinculadas = collect($this->vinculos)
                    ->where('turma_id', (int) $this->filtro_turma_id)
                    ->pluck('disciplina_id')->toArray();

                // IDs de disciplinas que o professor TEM competência neste curso
                $idsCompetencia = collect($this->competencias)
                    ->where('curso_id', (int) $turmaFiltro->curso_id)
                    ->pluck('disciplina_id')->map(fn($x) => (int)$x)->toArray();

                $disciplinasDisponiveis = Disciplina::where('curso_id', $turmaFiltro->curso_id)
                    ->where('ativo', true)
                    ->where('semestre_grade', $turmaFiltro->semestre)
                    ->whereIn('id', $idsCompetencia)   // ← só as competências do professor
                    ->whereNotIn('id', $jaVinculadas)
                    ->orderBy('nome')
                    ->get()
                    ->map(fn($d) => [
                        'id'         => $d->id,
                        'nome'       => $d->nome,
                        'semestre'   => $d->semestre_grade,
                        'curso_nome' => $turmaFiltro->curso->nome ?? '',
                        'curso_id'   => $d->curso_id,
                    ])->toArray();
            }
        }
        $turmasDoVinculo = [];

        // NÍVEL 1: dados do formulário de competências
        $disciplinasCompetencia = [];
        if ($this->comp_curso_id) {
            $idsJaComp = collect($this->competencias)
                ->where('curso_id', (int) $this->comp_curso_id)
                ->pluck('disciplina_id')->map(fn($x) => (int)$x)->toArray();
            $disciplinasCompetencia = Disciplina::where('curso_id', $this->comp_curso_id)
                ->where('ativo', true)
                ->whereNotIn('id', $idsJaComp)
                ->orderBy('semestre_grade')->orderBy('nome')
                ->get(['id', 'nome', 'semestre_grade'])
                ->toArray();
        }

        return view('livewire.professores-crud', compact(
            'professores', 'diasNomes',
            'disciplinasDisponiveis', 'mostrarLista', 'turmasDoVinculo',
            'cursosFiltro', 'turmasFiltro', 'disciplinasCompetencia'
        ));
    }
}
