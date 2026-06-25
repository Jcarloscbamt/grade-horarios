<?php
// config/whatsapp.php
return [
    // Liga/desliga o envio por WhatsApp (independente do e-mail)
    'enabled' => env('WHATSAPP_ENABLED', false),

    // Credenciais da Meta Cloud API (WhatsApp Business)
    // No número de TESTE da Meta, o token é temporário (24h) e o phone_number_id é o de teste.
    // Em PRODUÇÃO, use um token permanente (System User) e o phone_number_id do número aprovado.
    'token'           => env('WHATSAPP_TOKEN', ''),
    'phone_number_id' => env('WHATSAPP_PHONE_ID', ''),
    'api_version'     => env('WHATSAPP_API_VERSION', 'v21.0'),

    // Modo de envio:
    //  'text'     = mensagem de texto livre (funciona no NÚMERO DE TESTE e dentro da janela de 24h).
    //               Ideal para testar agora, sem template aprovado.
    //  'template' = mensagem por template aprovado pela Meta (OBRIGATÓRIO em produção para
    //               mensagens proativas fora da janela de 24h).
    'mode' => env('WHATSAPP_MODE', 'text'),

    // Dados do template (usados só quando mode = 'template')
    // Crie o template no WhatsApp Manager (categoria UTILITY) e informe o nome e idioma aqui.
    'template_name'     => env('WHATSAPP_TEMPLATE', 'aviso_aula'),
    'template_language' => env('WHATSAPP_TEMPLATE_LANG', 'pt_BR'),

    // Código do país padrão para normalizar telefones sem DDI (Brasil = 55)
    'default_country' => env('WHATSAPP_DEFAULT_COUNTRY', '55'),
];
