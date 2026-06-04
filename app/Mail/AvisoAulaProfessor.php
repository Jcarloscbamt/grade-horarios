<?php
// app/Mail/AvisoAulaProfessor.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvisoAulaProfessor extends Mailable
{
    use Queueable, SerializesModels;

    public string  $professorNome;
    public array   $aulas;       // lista de aulas (array)
    public string  $tituloPeriodo; // "amanhã (04/06)" ou "esta semana"
    public bool    $semanal;

    public function __construct(string $professorNome, array $aulas, string $tituloPeriodo, bool $semanal = false)
    {
        $this->professorNome = $professorNome;
        $this->aulas         = $aulas;
        $this->tituloPeriodo = $tituloPeriodo;
        $this->semanal       = $semanal;
    }

    public function envelope(): Envelope
    {
        $assunto = $this->semanal
            ? 'Suas aulas desta semana — UniSENAI MT'
            : 'Lembrete: suas aulas de ' . $this->tituloPeriodo . ' — UniSENAI MT';

        return new Envelope(subject: $assunto);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.aviso-aula');
    }
}
