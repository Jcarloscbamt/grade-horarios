<?php
// app/Services/WhatsappProvider.php
namespace App\Services;

/**
 * Contrato comum a qualquer provedor de WhatsApp (Meta, Twilio, ...).
 * Toda a aplicação fala com esta interface — nunca com um provedor específico.
 */
interface WhatsappProvider
{
    /** Há credenciais suficientes para enviar? */
    public function configurado(): bool;

    /** Nome amigável do provedor (ex.: "Meta Cloud API", "Twilio"). */
    public function nome(): string;

    /** Normaliza o telefone para o formato esperado pelo provedor. */
    public function normalizarTelefone(?string $telefone): ?string;

    /**
     * Envia uma mensagem de texto livre.
     * @return array{ok: bool, id?: string, erro?: string}
     */
    public function enviarTexto(string $telefone, string $texto): array;

    /**
     * Envia uma mensagem por template aprovado.
     * @return array{ok: bool, id?: string, erro?: string}
     */
    public function enviarTemplate(string $telefone, string $template, string $idioma, array $parametros = []): array;
}
