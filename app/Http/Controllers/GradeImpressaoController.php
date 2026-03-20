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

        // Carrega logo local como base64 para evitar requisição externa
        $logoPath = public_path('images/logo-unisenai.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        return view('grade-impressao', compact(
            'turma', 'periodo', 'horarios', 'grade', 'dias', 'logoBase64'
        ));
    }
}
