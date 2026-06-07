# Sistema de Grade de Horários — UniSENAI MT

Sistema web para geração e gerenciamento automático de grades de horários acadêmicas, desenvolvido para a UniSENAI MT. Monta a grade de várias turmas automaticamente respeitando a disponibilidade dos professores, evita conflitos, e ainda avisa os professores por e-mail sobre suas aulas.

**Stack:** Laravel 12 · Livewire 3 · Bootstrap 5 · Spatie Laravel Permission · MySQL · PHP 8.3

---

## Sumário

- [Visão geral](#visão-geral)
- [Principais funcionalidades](#principais-funcionalidades)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração de e-mail](#configuração-de-e-mail)
- [Agendador de tarefas (scheduler)](#agendador-de-tarefas-scheduler)
- [Perfis e permissões](#perfis-e-permissões)
- [Como o gerador de grade funciona](#como-o-gerador-de-grade-funciona)
- [Conceitos importantes](#conceitos-importantes)
- [Documentação de instalação](#documentação-de-instalação)
- [Estrutura do projeto](#estrutura-do-projeto)

---

## Visão geral

O sistema resolve um problema clássico das instituições de ensino: montar a grade de horários de várias turmas sem que um professor caia em duas turmas no mesmo dia, sem que uma turma tenha duas disciplinas no mesmo horário, e respeitando os dias em que cada professor está disponível.

O coordenador cadastra cursos, turmas, disciplinas, professores (com suas competências e disponibilidade) e salas. Com um clique, o sistema gera a grade completa, aponta conflitos quando existem e sugere soluções. A grade pode ser visualizada na tela e impressa (colorida ou preto e branco), uma turma por página.

---

## Principais funcionalidades

- **Gerador de grade automático** — monta a grade de múltiplas turmas respeitando disponibilidade, sem conflitos de professor/turma/sala. Quando há conflito, explica o motivo e sugere dias alternativos.
- **Cadastros completos** — cursos, turmas, disciplinas, professores, salas, horários e períodos letivos.
- **Professores em dois níveis** — *competências* (disciplinas que sabe lecionar, sem limite) e *vínculos do período* (turmas que vai lecionar, máximo 5, pois a semana tem 5 dias úteis).
- **Impressão da grade** — colorida ou P&B, uma turma por página, com cabeçalho institucional, intervalo e QR Code de contato da coordenação.
- **Avisos por e-mail** — envio automático diário (aulas do dia seguinte) e resumo semanal aos professores, com horários configuráveis e histórico de envios.
- **Relatórios** — relatório de professores com filtros por curso, turma e disciplina; identificação de disciplinas com mais de um professor por turma.
- **Períodos letivos** — múltiplos períodos coexistem; avanço de semestre das turmas em lote, com inativação automática das turmas que concluíram o curso.
- **Controle de acesso** — perfis admin, coordenador e consulta (Spatie Permission).
- **Logs e ajuda** — registro de ações e ajuda contextual em cada tela, além de um manual completo no menu.

---

## Requisitos

| Software   | Versão           |
|------------|------------------|
| PHP        | 8.3 ou superior  |
| Composer   | (gerenciador PHP)|
| MySQL      | 8.0 ou superior  |
| Node.js    | 18 LTS ou superior |
| Git        | qualquer versão  |

Extensões PHP: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `gd`.

---

## Instalação

> Para instalação detalhada em servidor (Windows local ou Linux dedicado com Nginx), veja os manuais em [Documentação de instalação](#documentação-de-instalação).

**1. Clonar o projeto**

```bash
git clone https://github.com/Jcarloscbamt/grade-horarios.git
cd grade-horarios
```

**2. Instalar dependências**

```bash
composer install
npm install
npm run build
```

**3. Configurar o ambiente**

```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` com os dados do banco e o fuso horário:

```env
APP_NAME="Grade de Horarios UniSENAI MT"
APP_TIMEZONE=America/Cuiaba
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grade_horarios
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

**4. Criar o banco de dados**

```sql
CREATE DATABASE grade_horarios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**5. Rodar migrations e seeders**

```bash
php artisan migrate
php artisan db:seed
```

O seeder cria os perfis (`admin`, `coordenador`, `consulta`).

**6. Criar o usuário administrador**

```bash
php artisan tinker
```

```php
$user = App\Models\User::create([
    'name'     => 'Administrador',
    'email'    => 'admin@unisenai.com.br',
    'password' => bcrypt('TroqueEstaSenha123'),
]);
$user->assignRole('admin');
exit
```

**7. Iniciar o servidor**

```bash
php artisan serve
```

Acesse `http://127.0.0.1:8000`. Para acesso por outros dispositivos na rede, use `php artisan serve --host=0.0.0.0 --port=8000`.

---

## Configuração de e-mail

O sistema envia avisos aos professores. Para habilitar, configure o SMTP no `.env` (exemplo com Gmail):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seuemail@gmail.com
MAIL_PASSWORD=senha_de_app_16_caracteres
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="seuemail@gmail.com"
MAIL_FROM_NAME="Grade de Horarios UniSENAI MT"
```

> O Gmail exige uma **Senha de App** de 16 caracteres (com verificação em duas etapas ativada), não a senha normal da conta.

Após editar o `.env`, limpe o cache de configuração:

```bash
php artisan config:clear
```

**Testar o envio** (ignora horário/dia):

```bash
php artisan avisos:aulas --forcar
```

Os horários, a ativação dos envios e o histórico ficam na tela **Envio de E-mails** (acesso admin).

---

## Agendador de tarefas (scheduler)

Para os e-mails saírem automaticamente nos horários configurados, o agendador do Laravel precisa rodar a cada minuto. A lógica já está em `routes/console.php` — basta executar o `schedule:run` continuamente.

**Linux (cron):**

```bash
crontab -e
```

```cron
* * * * * cd /caminho/para/grade-horarios && php artisan schedule:run >> /dev/null 2>&1
```

**Windows:** crie uma tarefa no Agendador de Tarefas que execute `php artisan schedule:run` a cada minuto (detalhado no manual de instalação).

| Tipo    | O que envia            | Quando                                             |
|---------|------------------------|----------------------------------------------------|
| Diário  | Aulas do dia seguinte  | Todo dia no horário configurado. Pula fim de semana. |
| Semanal | Resumo da semana       | No dia e horário configurados.                     |

---

## Perfis e permissões

| Ação        | Admin | Coordenador        | Consulta |
|-------------|-------|--------------------|----------|
| Visualizar  | ✅    | ✅                 | ✅       |
| Incluir     | ✅    | ✅                 | ❌       |
| Editar      | ✅    | ✅                 | ❌       |
| Excluir     | ✅    | ❌                 | ❌       |
| Gerar grade | ✅    | ✅                 | ❌       |
| Administração (usuários, logs, e-mails) | ✅ | ❌ | ❌ |

Controle de acesso via [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission). Botões e telas restritas usam diretivas como `@role('admin')` / `@hasrole('admin')`.

---

## Como o gerador de grade funciona

O gerador não tenta alocar de qualquer jeito — segue uma estratégia para resolver o máximo de conflitos automaticamente:

1. **Escassez (MRV)** — aloca primeiro os professores com menos dias disponíveis (menos opções), como num quebra-cabeça começando pelas peças mais difíceis.
2. **Distribuição (matching)** — distribui as aulas de cada professor garantindo no máximo uma aula por dia.
3. **Reparo global multipassada** — quando duas aulas disputam o mesmo dia, move uma para outro dia livre, em várias rodadas (resolver um conflito libera espaço para outro).
4. **Validação de duplicidade** — checagem final que garante as regras invioláveis.
5. **Sugestões coordenadas** — para o que sobrar, sugere dias específicos a adicionar à disponibilidade do professor.

**Regras invioláveis:**

- Um professor não pode estar em duas turmas no mesmo dia.
- Uma turma não pode ter duas disciplinas no mesmo dia.
- Uma sala não pode ser usada por duas turmas ao mesmo tempo.

Quando um conflito não pode ser resolvido, o sistema explica o motivo (faltam dias, professor com mais de 5 vínculos, etc.) e oferece sugestões.

---

## Conceitos importantes

- **1 vínculo = 1 aula = 1 dia.** Cada disciplina que um professor leciona em uma turma ocupa um dia. Como a semana tem 5 dias úteis, o máximo é **5 vínculos por professor**.
- **Competências × vínculos.** Competência é o que o professor *sabe* lecionar (sem limite); vínculo é o que ele *vai* lecionar no período (máximo 5). Os vínculos só aceitam disciplinas das competências.
- **Períodos letivos.** Apenas um fica ativo por vez (usado pelos e-mails). Vários coexistem; cada aula pertence a um período. Ativar um período não apaga as grades dos outros.
- **Virada de semestre.** Fluxo recomendado: criar o novo período (inativo) → avançar o semestre das turmas → ajustar vínculos → gerar e conferir → ativar o novo período. Turmas que concluem o curso são inativadas automaticamente ao avançar.

---

## Documentação de instalação

Há guias de instalação detalhados (com passo a passo de e-mail, scheduler e configurações de servidor):

- **Computador local (Windows/Linux)** — uso interno, servidor simples.
- **Servidor Linux dedicado (Ubuntu Server + Nginx + PHP-FPM + MySQL)** — uso permanente na instituição.

Consulte os manuais distribuídos com o projeto (`Manual_Instalacao_*.docx`).

---

## Estrutura do projeto

```
app/
  Livewire/         Componentes das telas (CRUDs, gerador, relatórios, e-mails)
  Models/           Modelos Eloquent
  Http/Controllers/ Controladores (ex.: impressão da grade)
  Services/         Serviços (ex.: envio de avisos por e-mail)
  Console/Commands/ Comando avisos:aulas
  Mail/             E-mails (aviso de aula)
database/
  migrations/       Estrutura do banco
  seeders/          Perfis e dados iniciais
resources/views/
  livewire/         Telas Livewire
  emails/           Modelos de e-mail
  grade-impressao*  Páginas de impressão da grade
routes/
  web.php           Rotas da aplicação
  console.php       Agendamento dos e-mails (scheduler)
```

### Principais telas

Grade de Horários · Gerador de Grade · Cursos · Turmas · Disciplinas · Professores · Salas · Horários · Períodos Letivos · Aulas · Relatórios (Grade e Professores) · Usuários · Logs · Envio de E-mails · Ajuda.

---

## Créditos

Desenvolvido para a **UniSENAI MT — Departamento de TI**.

Construído sobre [Laravel](https://laravel.com), [Livewire](https://livewire.laravel.com), [Bootstrap](https://getbootstrap.com) e [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission). O framework Laravel é open-source sob a [licença MIT](https://opensource.org/licenses/MIT).
