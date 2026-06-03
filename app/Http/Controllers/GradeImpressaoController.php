<?php
// app/Http/Controllers/GradeImpressaoController.php
namespace App\Http\Controllers;

use App\Models\{Aula, Turma, Horario, PeriodoLetivo};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GradeImpressaoController extends Controller
{
    public function __invoke(Request $request)
    {
        $turma_id          = $request->turma_id;
        $periodo_letivo_id = $request->periodo_letivo_id;
        $modo              = $request->modo ?? 'colorido';

        if (!$turma_id || !$periodo_letivo_id) {
            abort(404, 'Turma ou período não informado.');
        }

        $turma   = Turma::with('curso')->findOrFail($turma_id);
        $periodo = PeriodoLetivo::findOrFail($periodo_letivo_id);

        // Seleciona apenas colunas necessárias (mais rápido)
        $aulas = Aula::with([
                'disciplina:id,nome,tipo_sala',
                'professor:id,nome',
                'sala:id,nome,bloco',
                'horario:id,hora_inicio,hora_fim'
            ])
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

        // ── Logo em base64 — CACHEADO (não recodifica a cada impressão) ──
        $logoBase64 = Cache::rememberForever('logo_unisenai_base64', function () {
            $logoPath = public_path('images/logo-unisenai.png');
            if (file_exists($logoPath)) {
                return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
            return null;
        });

        // ── QR Code em base64 — CACHEADO por telefone ──
        $qrBase64 = null;
        $tel = $turma->curso?->telefone_coord
            ? preg_replace('/\D/', '', $turma->curso->telefone_coord)
            : null;

        if ($tel && class_exists(\BaconQrCode\Writer::class)) {
            $qrBase64 = Cache::rememberForever('qrcode_impressao_' . md5($tel), function () use ($tel) {
                try {
                    $link     = "https://wa.me/55{$tel}";
                    $renderer = new \BaconQrCode\Renderer\Image\SvgImageBackEnd();
                    $style    = new \BaconQrCode\Renderer\RendererStyle\RendererStyle(90);
                    $image    = new \BaconQrCode\Renderer\ImageRenderer($style, $renderer);
                    $writer   = new \BaconQrCode\Writer($image);
                    return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($link));
                } catch (\Exception $e) {
                    return null;
                }
            });
        }

        $view = $modo === 'pb' ? 'grade-impressao-pb' : 'grade-impressao';

        return view($view, compact(
            'turma', 'periodo', 'horarios', 'grade', 'dias', 'logoBase64', 'qrBase64'
        ));
    }
}
