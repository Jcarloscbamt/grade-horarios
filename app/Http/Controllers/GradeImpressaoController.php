<?php
// app/Http/Controllers/GradeImpressaoController.php
namespace App\Http\Controllers;

use App\Models\{Aula, Turma, Horario, PeriodoLetivo};
use Illuminate\Http\Request;

class GradeImpressaoController extends Controller
{
    public function __invoke(Request $request)
    {
        $turma_id          = $request->turma_id;
        $periodo_letivo_id = $request->periodo_letivo_id;
        $modo              = $request->modo ?? 'colorido'; // 'colorido' ou 'pb'

        if (!$turma_id || !$periodo_letivo_id) {
            abort(404, 'Turma ou período não informado.');
        }

        $turma   = Turma::with('curso')->findOrFail($turma_id);
        $periodo = PeriodoLetivo::findOrFail($periodo_letivo_id);

        $aulas = Aula::with(['disciplina', 'professor', 'sala', 'horario'])
            ->where('turma_id', $turma_id)
            ->where('periodo_letivo_id', $periodo_letivo_id)
            ->get();

        $horarios = $aulas->map(fn($a) => $a->horario)
            ->unique('id')
            ->sortBy('hora_inicio')
            ->values();

        $grade = [];
        foreach ($aulas as $aula) {
            $grade[$aula->horario_id][$aula->dia_semana] = $aula;
        }

        $dias = [1=>'SEG', 2=>'TER', 3=>'QUA', 4=>'QUI', 5=>'SEX'];

        // Logo local base64
        $logoPath   = public_path('images/logo-unisenai.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // QR Code base64
        $qrBase64 = null;
        if (class_exists(\BaconQrCode\Writer::class) && $turma->curso?->telefone_coord) {
            try {
                $tel  = preg_replace('/\D/', '', $turma->curso->telefone_coord);
                $link = "https://wa.me/55{$tel}";
                $renderer = new \BaconQrCode\Renderer\Image\SvgImageBackEnd();
                $style    = new \BaconQrCode\Renderer\RendererStyle\RendererStyle(90);
                $image    = new \BaconQrCode\Renderer\ImageRenderer($style, $renderer);
                $writer   = new \BaconQrCode\Writer($image);
                $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($link));
            } catch (\Exception $e) {
                $qrBase64 = null;
            }
        }

        // Escolhe a view conforme o modo
        $view = $modo === 'pb' ? 'grade-impressao-pb' : 'grade-impressao';

        return view($view, compact(
            'turma', 'periodo', 'horarios', 'grade', 'dias', 'logoBase64', 'qrBase64'
        ));
    }
}
