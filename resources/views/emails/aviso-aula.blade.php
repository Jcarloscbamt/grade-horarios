{{-- resources/views/emails/aviso-aula.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                    {{-- Cabeçalho --}}
                    <tr>
                        <td style="background:#1a1a1a;padding:24px 32px;border-bottom:4px solid #E30613;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:bold;">
                                UniSENAI MT — Grade de Horários
                            </h1>
                        </td>
                    </tr>

                    {{-- Saudação --}}
                    <tr>
                        <td style="padding:32px 32px 16px 32px;">
                            <p style="margin:0 0 8px 0;font-size:16px;color:#333;">
                                Olá, <strong>{{ $professorNome }}</strong>!
                            </p>
                            <p style="margin:0;font-size:14px;color:#666;line-height:1.6;">
                                @if($semanal)
                                    Segue o resumo das suas aulas <strong>desta semana</strong>:
                                @else
                                    Este é um lembrete das suas aulas de <strong>{{ $tituloPeriodo }}</strong>:
                                @endif
                            </p>
                        </td>
                    </tr>

                    {{-- Tabela de aulas --}}
                    <tr>
                        <td style="padding:8px 32px 24px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#f8f8f8;">
                                        <th style="padding:10px;text-align:left;font-size:12px;color:#888;border-bottom:2px solid #eee;text-transform:uppercase;">Dia</th>
                                        <th style="padding:10px;text-align:left;font-size:12px;color:#888;border-bottom:2px solid #eee;text-transform:uppercase;">Horário</th>
                                        <th style="padding:10px;text-align:left;font-size:12px;color:#888;border-bottom:2px solid #eee;text-transform:uppercase;">Disciplina</th>
                                        <th style="padding:10px;text-align:left;font-size:12px;color:#888;border-bottom:2px solid #eee;text-transform:uppercase;">Turma</th>
                                        <th style="padding:10px;text-align:left;font-size:12px;color:#888;border-bottom:2px solid #eee;text-transform:uppercase;">Sala</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($aulas as $aula)
                                    <tr>
                                        <td style="padding:10px;font-size:13px;color:#333;border-bottom:1px solid #f0f0f0;"><strong>{{ $aula['dia'] }}</strong></td>
                                        <td style="padding:10px;font-size:13px;color:#333;border-bottom:1px solid #f0f0f0;font-family:monospace;">{{ $aula['horario'] }}</td>
                                        <td style="padding:10px;font-size:13px;color:#333;border-bottom:1px solid #f0f0f0;">{{ $aula['disciplina'] }}</td>
                                        <td style="padding:10px;font-size:13px;color:#333;border-bottom:1px solid #f0f0f0;">{{ $aula['turma'] }}</td>
                                        <td style="padding:10px;font-size:13px;color:#333;border-bottom:1px solid #f0f0f0;">
                                            @if($aula['modalidade'] === 'online')
                                                <span style="color:#1565C0;font-weight:bold;">Online</span>
                                            @else
                                                {{ $aula['sala'] }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    {{-- Rodapé --}}
                    <tr>
                        <td style="padding:16px 32px 32px 32px;border-top:1px solid #eee;">
                            <p style="margin:0;font-size:12px;color:#999;line-height:1.6;">
                                Este é um e-mail automático do sistema de Grade de Horários da UniSENAI MT.
                                Em caso de dúvidas, entre em contato com a coordenação do seu curso.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
