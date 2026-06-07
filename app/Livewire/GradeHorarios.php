<?php
// app/Livewire/GradeHorarios.php
namespace App\Livewire;

use App\Models\{Aula, Turma, Curso, PeriodoLetivo, Horario};
use Livewire\Component;

class GradeHorarios extends Component
{
    public string $curso_id          = '';
    public array  $turmasSelecionadas = [];
    public string $periodo_letivo_id  = '';

    public array $dias = [1=>'SEG', 2=>'TER', 3=>'QUA', 4=>'QUI', 5=>'SEX'];

    public function mount(): void
    {
        $periodo = PeriodoLetivo::where('ativo', true)->first();
        if ($periodo) {
            $this->periodo_letivo_id = $periodo->id;
        }
    }

    public function updatedCursoId(): void
    {
        $this->turmasSelecionadas = [];
    }

    public function toggleTodasTurmas(): void
    {
        $query = Turma::where('ativo', true);
        if ($this->curso_id) {
            $query->where('curso_id', $this->curso_id);
        }
        $todos = $query->pluck('id')->toArray();
        if (count($this->turmasSelecionadas) >= count($todos)) {
            $this->turmasSelecionadas = [];
        } else {
            $this->turmasSelecionadas = $todos;
        }
    }

    public function limpar(): void
    {
        $this->curso_id          = '';
        $this->turmasSelecionadas = [];
        $this->periodo_letivo_id  = '';
    }

    private function gerarQrCodeSvg(string $texto): string
    {
        // Cache persistente (Laravel) — QR só é gerado 1x por URL
        $chave = 'qrcode_grade_' . md5($texto);

        return \Illuminate\Support\Facades\Cache::rememberForever($chave, function () use ($texto) {
            try {
                if (!class_exists(\BaconQrCode\Writer::class)) return '';
                $renderer = new \BaconQrCode\Renderer\Image\SvgImageBackEnd();
                $style    = new \BaconQrCode\Renderer\RendererStyle\RendererStyle(90);
                $image    = new \BaconQrCode\Renderer\ImageRenderer($style, $renderer);
                $writer   = new \BaconQrCode\Writer($image);
                return $writer->writeString($texto);
            } catch (\Exception $e) {
                return '';
            }
        });
    }

    public function render()
    {
        $cursos          = Curso::where('ativo', true)->orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->get();

        // Turmas filtradas por curso selecionado
        $turmasQuery = Turma::with('curso')->where('ativo', true);
        if ($this->curso_id) {
            $turmasQuery->where('curso_id', $this->curso_id);
        }
        $turmas = $turmasQuery->orderBy('nome')->get();

        $grades     = [];
        $horarios   = collect();
        $periodoObj = null;
        $turmasAtivas = collect();
        $qrCodes    = [];

        if (!empty($this->turmasSelecionadas) && $this->periodo_letivo_id) {
            $periodoObj = PeriodoLetivo::find($this->periodo_letivo_id);

            $aulas = Aula::with([
                    'disciplina:id,nome,tipo_sala',
                    'professor:id,nome',
                    'sala:id,nome,bloco',
                    'horario:id,hora_inicio,hora_fim,tipo'
                ])
                ->whereIn('turma_id', $this->turmasSelecionadas)
                ->where('periodo_letivo_id', $this->periodo_letivo_id)
                ->get();

            $horariosAula = $aulas->map(fn($a) => $a->horario)->filter()->unique('id');

            // Inclui a(s) linha(s) de INTERVALO que caem DENTRO da faixa das aulas exibidas
            // (entre o primeiro e o último horário de aula da grade)
            $intervalos = collect();
            if ($horariosAula->isNotEmpty()) {
                $min = $horariosAula->min('hora_inicio');
                $max = $horariosAula->max('hora_inicio');
                $intervalos = Horario::where('tipo', 'intervalo')
                    ->where('hora_inicio', '>=', $min)
                    ->where('hora_inicio', '<=', $max)
                    ->get();
            }

            $horarios = $horariosAula->concat($intervalos)
                ->unique('id')->sortBy('hora_inicio')->values();

            foreach ($aulas as $aula) {
                $grades[$aula->turma_id][$aula->horario_id][$aula->dia_semana] = $aula;
            }

            $turmasAtivas = Turma::with('curso')
                ->whereIn('id', $this->turmasSelecionadas)
                ->orderBy('nome')->get();

            // QR codes por turma
            foreach ($turmasAtivas as $turma) {
                if ($turma->curso?->telefone_coord) {
                    $tel = preg_replace('/\D/', '', $turma->curso->telefone_coord);
                    if ($tel) {
                        $qrCodes[$turma->id] = $this->gerarQrCodeSvg("https://wa.me/55{$tel}");
                    }
                }
            }
        }

        return view('livewire.grade-horarios', compact(
            'cursos', 'turmas', 'periodosLetivos',
            'grades', 'horarios', 'periodoObj', 'turmasAtivas', 'qrCodes'
        ));
    }
}
