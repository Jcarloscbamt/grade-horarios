<?php
// app/Livewire/GradeHorarios.php
namespace App\Livewire;

use App\Models\Aula;
use App\Models\Turma;
use App\Models\PeriodoLetivo;
use Livewire\Component;

class GradeHorarios extends Component
{
    public string $turma_id         = '';
    public string $periodo_letivo_id = '';

    public array $dias = [
        1 => 'SEG',
        2 => 'TER',
        3 => 'QUA',
        4 => 'QUI',
        5 => 'SEX',
    ];

    public function mount(): void
    {
        $periodoAtivo = PeriodoLetivo::where('ativo', true)->first();
        if ($periodoAtivo) {
            $this->periodo_letivo_id = $periodoAtivo->id;
        }
    }

    private function gerarQrCodeSvg(string $texto): string
    {
        try {
            if (!class_exists(\BaconQrCode\Writer::class)) {
                return '';
            }
            $renderer = new \BaconQrCode\Renderer\Image\SvgImageBackEnd();
            $style    = new \BaconQrCode\Renderer\RendererStyle\RendererStyle(90);
            $image    = new \BaconQrCode\Renderer\ImageRenderer($style, $renderer);
            $writer   = new \BaconQrCode\Writer($image);
            return $writer->writeString($texto);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function render()
    {
        $turmas          = Turma::with('curso')->orderBy('nome')->get();
        $periodosLetivos = PeriodoLetivo::orderByDesc('ano')->get();
        $grade           = [];
        $horarios        = [];
        $periodo         = null;
        $qrCodeSvg       = '';

        if ($this->turma_id && $this->periodo_letivo_id) {
            $periodo = PeriodoLetivo::find($this->periodo_letivo_id);
            $turma   = Turma::with('curso')->find($this->turma_id);

            $aulas = Aula::with(['disciplina', 'professor', 'sala', 'horario'])
                ->where('turma_id', $this->turma_id)
                ->where('periodo_letivo_id', $this->periodo_letivo_id)
                ->get();

            $horarios = $aulas->map(fn($a) => $a->horario)
                ->unique('id')
                ->sortBy('hora_inicio')
                ->values();

            foreach ($aulas as $aula) {
                $grade[$aula->horario_id][$aula->dia_semana] = $aula;
            }

            // Gera QR Code SVG com o WhatsApp da coordenação
            if ($turma && $turma->curso && $turma->curso->telefone_coord) {
                $tel = preg_replace('/\D/', '', $turma->curso->telefone_coord);
                if ($tel) {
                    $qrCodeSvg = $this->gerarQrCodeSvg("https://wa.me/55{$tel}");
                }
            }
        }

        return view('livewire.grade-horarios', compact(
            'turmas', 'periodosLetivos', 'grade', 'horarios', 'periodo', 'qrCodeSvg'
        ));
    }
}
