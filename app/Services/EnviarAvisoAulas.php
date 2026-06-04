<?php
// app/Services/EnviarAvisoAulas.php
namespace App\Services;

use App\Models\{Aula, Professor, PeriodoLetivo, LogEmail};
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
     * @param string $tipo  diario | semanal | manual (para o histórico)
     * @return bool  enviou ou não
     */
    public function enviarParaProfessor(Professor $professor, bool $semanal, ?int $diaAlvo = null, string $tipo = 'manual'): bool
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
        if ($aulasRaw->isEmpty()) return false; // sem aula = não envia (não registra)

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

        usort($aulas, fn($a, $b) => [$a['dia_num'], $a['horario']] <=> [$b['dia_num'], $b['horario']]);

        $titulo = $semanal
            ? 'esta semana'
            : ($this->nomeDias[$diaAlvo] ?? 'amanhã') . ' (' . Carbon::now()->next($diaAlvo)->format('d/m') . ')';

        // Tenta enviar e REGISTRA o resultado no histórico
        try {
            Mail::to($professor->email)->send(
                new AvisoAulaProfessor($professor->nome, $aulas, $titulo, $semanal)
            );

            // Sucesso = o servidor SMTP (Gmail) aceitou a mensagem sem erro
            $this->registrarLog($professor, $tipo, true, null, count($aulas));
            return true;

        } catch (\Exception $e) {
            // Falha = o Gmail recusou ou houve erro de conexão
            \Log::error("Falha ao enviar aviso para {$professor->email}: " . $e->getMessage());
            $this->registrarLog($professor, $tipo, false, $e->getMessage(), count($aulas));
            return false;
        }
    }

    /**
     * Envia para TODOS os professores que têm aula no dia alvo (ou na semana).
     * @return array  ['enviados' => N, 'falhas' => N, 'detalhes' => [...]]
     */
    public function enviarParaTodos(bool $semanal, ?int $diaAlvo = null, string $tipo = 'manual'): array
    {
        $enviados = 0;
        $falhas   = 0;
        $detalhes = [];

        $professores = Professor::where('ativo', true)->whereNotNull('email')->get();

        foreach ($professores as $prof) {
            // Verifica se tem aula antes (para distinguir "sem aula" de "falha")
            $ok = $this->enviarParaProfessor($prof, $semanal, $diaAlvo, $tipo);
            if ($ok) {
                $enviados++;
                $detalhes[] = "✓ {$prof->nome} ({$prof->email})";
            }
        }

        // Conta as falhas que foram registradas neste lote (último minuto)
        $falhas = LogEmail::where('sucesso', false)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->count();

        if ($falhas > 0) {
            $detalhes[] = "⚠️ {$falhas} falha(s) — veja o histórico abaixo.";
        }

        return ['enviados' => $enviados, 'falhas' => $falhas, 'detalhes' => $detalhes];
    }

    // ── Registra o envio no histórico ──────────────────────
    private function registrarLog(Professor $professor, string $tipo, bool $sucesso, ?string $erro, int $qtdAulas): void
    {
        try {
            LogEmail::create([
                'professor_id'   => $professor->id,
                'professor_nome' => $professor->nome,
                'email'          => $professor->email,
                'tipo'           => $tipo,
                'sucesso'        => $sucesso,
                'erro'           => $erro,
                'qtd_aulas'      => $qtdAulas,
                'enviado_em'     => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Falha ao registrar log de e-mail: " . $e->getMessage());
        }
    }
}
