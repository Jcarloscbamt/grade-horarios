<?php
// app/Services/EnviarAvisoAulas.php
namespace App\Services;

use App\Models\{Aula, Professor, PeriodoLetivo};
use App\Mail\AvisoAulaProfessor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class EnviarAvisoAulas
{
    private array $nomeDias = [1=>'Segunda', 2=>'Terça', 3=>'Quarta', 4=>'Quinta', 5=>'Sexta'];

    /**
     * Envia aviso para um professor específico.
     * @param bool $semanal  true = todas as aulas da semana; false = só o dia alvo
     * @param int|null $diaAlvo  dia da semana (1-5) quando não é semanal
     * @return bool  enviou ou não
     */
    public function enviarParaProfessor(Professor $professor, bool $semanal, ?int $diaAlvo = null): bool
    {
        if (empty($professor->email)) return false;

        $periodo = PeriodoLetivo::where('ativo', true)->first();
        if (!$periodo) return false;

        $query = Aula::with(['disciplina:id,nome', 'turma:id,nome', 'sala:id,nome', 'horario:id,hora_inicio,hora_fim'])
            ->where('professor_id', $professor->id)
            ->where('periodo_letivo_id', $periodo->id);

        if (!$semanal && $diaAlvo) {
            $query->where('dia_semana', $diaAlvo);
        }

        $aulasRaw = $query->get();
        if ($aulasRaw->isEmpty()) return false;

        // Agrupa por turma+disciplina+dia (uma linha por aula, não por horário)
        $agrupado = $aulasRaw->groupBy(fn($a) => $a->turma_id.'_'.$a->disciplina_id.'_'.$a->dia_semana);

        $aulas = [];
        foreach ($agrupado as $grupo) {
            $primeira = $grupo->sortBy(fn($a) => $a->horario->hora_inicio ?? '')->first();
            $ultima   = $grupo->sortByDesc(fn($a) => $a->horario->hora_fim ?? '')->first();
            $aulas[] = [
                'dia'        => $this->nomeDias[$primeira->dia_semana] ?? '',
                'dia_num'    => $primeira->dia_semana,
                'horario'    => substr($primeira->horario->hora_inicio ?? '', 0, 5) . ' - ' . substr($ultima->horario->hora_fim ?? '', 0, 5),
                'disciplina' => $primeira->disciplina->nome ?? '',
                'turma'      => $primeira->turma->nome ?? '',
                'sala'       => $primeira->sala->nome ?? 'Sem sala',
                'modalidade' => $primeira->modalidade ?? 'presencial',
            ];
        }

        // Ordena por dia da semana e horário
        usort($aulas, fn($a, $b) => [$a['dia_num'], $a['horario']] <=> [$b['dia_num'], $b['horario']]);

        $titulo = $semanal
            ? 'esta semana'
            : ($this->nomeDias[$diaAlvo] ?? 'amanhã') . ' (' . Carbon::now()->next($diaAlvo)->format('d/m') . ')';

        try {
            Mail::to($professor->email)->send(
                new AvisoAulaProfessor($professor->nome, $aulas, $titulo, $semanal)
            );
            return true;
        } catch (\Exception $e) {
            \Log::error("Falha ao enviar aviso para {$professor->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia para TODOS os professores que têm aula no dia alvo (ou na semana).
     * @return array  ['enviados' => N, 'falhas' => N, 'detalhes' => [...]]
     */
    public function enviarParaTodos(bool $semanal, ?int $diaAlvo = null): array
    {
        $enviados = 0;
        $falhas   = 0;
        $detalhes = [];

        $professores = Professor::where('ativo', true)->whereNotNull('email')->get();

        foreach ($professores as $prof) {
            $ok = $this->enviarParaProfessor($prof, $semanal, $diaAlvo);
            if ($ok) {
                $enviados++;
                $detalhes[] = "✓ {$prof->nome} ({$prof->email})";
            } else {
                // Só conta falha se o professor TEM aula mas o envio falhou
                // (sem aula = não envia, não é falha)
            }
        }

        return ['enviados' => $enviados, 'falhas' => $falhas, 'detalhes' => $detalhes];
    }
}
