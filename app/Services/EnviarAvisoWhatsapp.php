<?php
// app/Services/EnviarAvisoWhatsapp.php
namespace App\Services;

use App\Models\{Professor, LogWhatsapp, ConfigWhatsapp};
use Illuminate\Support\Carbon;

/**
 * Camada de alto nível: monta o aviso de aulas (reaproveitando a lógica do e-mail)
 * e envia por WhatsApp, usando o PROVEDOR ATIVO (Meta ou Twilio).
 * Registra o resultado no histórico (log_whatsapps).
 */
class EnviarAvisoWhatsapp
{
    public function __construct(
        private WhatsappManager $manager,    // decide Meta x Twilio
        private EnviarAvisoAulas $avisoAulas, // reusa montarDadosProfessor()
    ) {}

    /**
     * Envia o aviso para um professor.
     * @return bool enviou ou não
     */
    public function enviarParaProfessor(Professor $professor, bool $semanal, ?int $diaAlvo = null, string $tipo = 'manual'): bool
    {
        if (empty($professor->telefone)) return false;

        // Reaproveita EXATAMENTE a mesma montagem de aulas do e-mail
        $dados = $this->avisoAulas->montarDadosProfessor($professor, $semanal, $diaAlvo);
        if ($dados === null) return false; // sem aula = não envia

        $aulas  = $dados['aulas'];
        $titulo = $dados['titulo'];

        $provedor = $this->manager->ativo();          // Meta ou Twilio
        $cfg      = ConfigWhatsapp::atual();
        $modo     = $this->manager->modo();           // text | template

        if ($modo === 'template') {
            // Produção: template aprovado. Variáveis: {{1}}=nome, {{2}}=titulo, {{3}}=lista de aulas
            $resultado = $provedor->enviarTemplate(
                $professor->telefone,
                $cfg->meta_template ?: 'aviso_aula',
                $cfg->meta_template_lang ?: 'pt_BR',
                [$professor->nome, $titulo, $this->resumoAulas($aulas, true)]
            );
        } else {
            // Teste/texto livre: monta a mensagem completa
            $resultado = $provedor->enviarTexto(
                $professor->telefone,
                $this->montarTexto($professor->nome, $titulo, $aulas, $semanal)
            );
        }

        $this->registrarLog($professor, $tipo, $resultado['ok'], $resultado['erro'] ?? null, count($aulas), $resultado['id'] ?? null);
        return $resultado['ok'];
    }

    /**
     * Envia para todos os professores ativos com telefone.
     * @return array{enviados:int, falhas:int, sem_telefone:int, sem_aula:int}
     */
    public function enviarParaTodos(bool $semanal, ?int $diaAlvo = null, string $tipo = 'manual'): array
    {
        $professores = Professor::where('ativo', true)->get();
        $enviados = 0; $falhas = 0; $semTelefone = 0; $semAula = 0;

        foreach ($professores as $professor) {
            if (empty($professor->telefone)) { $semTelefone++; continue; }

            $dados = $this->avisoAulas->montarDadosProfessor($professor, $semanal, $diaAlvo);
            if ($dados === null) { $semAula++; continue; }

            $ok = $this->enviarParaProfessor($professor, $semanal, $diaAlvo, $tipo);
            $ok ? $enviados++ : $falhas++;
        }

        return ['enviados' => $enviados, 'falhas' => $falhas, 'sem_telefone' => $semTelefone, 'sem_aula' => $semAula];
    }

    /** Monta o texto completo (modo texto livre) */
    private function montarTexto(string $nome, string $titulo, array $aulas, bool $semanal): string
    {
        $primeiroNome = ucwords(strtolower(trim(strtok($nome, ' '))));
        $qtd = count($aulas);

        $cabecalho = $semanal
            ? "👋 Olá, *{$primeiroNome}*!\n📅 Aqui está o resumo das suas aulas *desta semana*:"
            : "👋 Olá, *{$primeiroNome}*!\n🔔 Lembrete das suas aulas de *{$titulo}*:";

        $linhas = [$cabecalho, '━━━━━━━━━━━━━━━'];

        foreach ($aulas as $a) {
            $online = strtolower($a['modalidade'] ?? '') === 'online';
            $local  = $online ? '💻 Online' : '📍 ' . ($a['sala'] ?? 'Sem sala');
            $disc   = ucwords(strtolower($a['disciplina']));

            if ($semanal) {
                $linhas[] = "📚 *{$a['dia']}* — {$disc}";
            } else {
                $linhas[] = "📚 *{$disc}*";
            }
            $linhas[] = "🕐 {$a['horario']}   |   {$local}";
            $linhas[] = "👥 Turma: {$a['turma']}";
            $linhas[] = '';
        }

        // remove a última linha em branco
        if (end($linhas) === '') array_pop($linhas);

        $linhas[] = '━━━━━━━━━━━━━━━';
        $rodape = $qtd === 1 ? '1 aula programada' : "{$qtd} aulas programadas";
        $linhas[] = "✅ _{$rodape}_";
        $linhas[] = '_UniSENAI MT — Grade de Horários_';

        return implode("\n", $linhas);
    }

    /** Resumo curto das aulas (uma linha) — usado como variável de template */
    private function resumoAulas(array $aulas, bool $comDia = false): string
    {
        $partes = [];
        foreach ($aulas as $a) {
            $partes[] = $comDia
                ? "{$a['dia']} {$a['horario']} {$a['disciplina']} ({$a['turma']})"
                : "{$a['horario']} {$a['disciplina']} ({$a['turma']})";
        }
        return implode('; ', $partes);
    }

    private function registrarLog(Professor $professor, string $tipo, bool $sucesso, ?string $erro, int $qtdAulas, ?string $messageId): void
    {
        try {
            LogWhatsapp::create([
                'professor_id'   => $professor->id,
                'professor_nome' => $professor->nome,
                'telefone'       => $professor->telefone,
                'tipo'           => $tipo,
                'sucesso'        => $sucesso,
                'erro'           => $erro,
                'qtd_aulas'      => $qtdAulas,
                'message_id'     => $messageId,
                'enviado_em'     => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Falha ao registrar log WhatsApp: ' . $e->getMessage());
        }
    }
}
