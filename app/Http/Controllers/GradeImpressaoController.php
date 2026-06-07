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
        // Aceita turma_id (uma) OU turma_ids (várias, separadas por vírgula)
        $idsParam          = $request->turma_ids ?? $request->turma_id;
        $periodo_letivo_id = $request->periodo_letivo_id;
        $modo              = $request->modo ?? 'colorido';

        if (!$idsParam || !$periodo_letivo_id) {
            abort(404, 'Turma ou período não informado.');
        }

        // Normaliza para lista de IDs inteiros
        $ids = is_array($idsParam) ? $idsParam : explode(',', (string) $idsParam);
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            abort(404, 'Nenhuma turma válida informada.');
        }

        $periodo = PeriodoLetivo::findOrFail($periodo_letivo_id);
        $dias    = [1=>'SEG', 2=>'TER', 3=>'QUA', 4=>'QUI', 5=>'SEX'];

        // ── Logo em base64 — CACHEADO (não recodifica a cada impressão) ──
        $logoBase64 = Cache::rememberForever('logo_unisenai_base64', function () {
            $logoPath = public_path('images/logo-unisenai.png');
            if (file_exists($logoPath)) {
                return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
            return null;
        });

        // ── Monta os dados de CADA turma (uma página por turma na impressão) ──
        $turmasData = [];
        foreach ($ids as $tid) {
            $turma = Turma::with('curso')->find($tid);
            if (!$turma) continue;

            $aulas = Aula::with([
                    'disciplina:id,nome,tipo_sala',
                    'professor:id,nome',
                    'sala:id,nome,bloco',
                    'horario:id,hora_inicio,hora_fim,tipo',
                ])
                ->where('turma_id', $tid)
                ->where('periodo_letivo_id', $periodo_letivo_id)
                ->get();

            $horariosAula = $aulas->map(fn($a) => $a->horario)
                ->filter()
                ->unique('id');

            // Inclui a(s) linha(s) de INTERVALO entre o primeiro e o último horário de aula
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
                ->unique('id')
                ->sortBy('hora_inicio')
                ->values();

            $grade = [];
            foreach ($aulas as $aula) {
                $grade[$aula->horario_id][$aula->dia_semana] = $aula;
            }

            // QR Code gerado no NAVEGADOR — passamos só o link do WhatsApp
            $telefoneLimpo = $turma->curso?->telefone_coord
                ? preg_replace('/\D/', '', $turma->curso->telefone_coord)
                : null;
            $whatsappLink = $telefoneLimpo ? "https://wa.me/55{$telefoneLimpo}" : '';

            $turmasData[] = [
                'turma'        => $turma,
                'horarios'     => $horarios,
                'grade'        => $grade,
                'whatsappLink' => $whatsappLink,
            ];
        }

        if (empty($turmasData)) {
            abort(404, 'Nenhuma turma encontrada.');
        }

        $view = $modo === 'pb' ? 'grade-impressao-pb' : 'grade-impressao';

        return view($view, compact('turmasData', 'periodo', 'dias', 'logoBase64'));
    }
}
