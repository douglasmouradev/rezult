# Rezult — SaaS de Gestão Financeira

Sistema multi-tenant em **PHP 8.3 + MySQL 8**, MVC manual, LGPD e RBAC completo.

## Instalação local

```bash
cp .env.example .env
composer install
php bin/migrate.php
php bin/seed.php
php -S localhost:8000 -t public
```

**Demo:** `demo@rezult.app` / `Senha@123`

## Deploy na VPS

```bash
bash bin/deploy.sh
```

O script faz backup de arquivos locais conflitantes (`.htaccess`, `index.html`), `git pull`, `composer install`, migrations e ajusta permissões de `storage/`.

**Após o primeiro deploy:**

```bash
php bin/create-superadmin.php "Seu Nome" email@exemplo.com "SenhaForte123"
```

## Funcionalidades

| Módulo | Descrição |
|--------|-----------|
| Financeiro | Lançamentos, contas, transferências, extrato, import CSV/OFX |
| Relatórios | DRE, fluxo, categoria (Excel/PDF) |
| Contatos | Clientes e fornecedores |
| Orçamento | Planejado vs realizado por categoria/mês |
| Cobranças | Pix/boleto com envio por e-mail HTML |
| Equipe | Membros, convites, remoção |
| API REST | `GET/POST /api/v1/lancamentos` com Bearer token e rate limit |
| Webhooks | Eventos HTTP com HMAC, log de entregas e retry automático |
| Integrações | Open Finance, gateway e NFS-e (stubs configuráveis) |
| Planos | Starter / Pro / Business com limites e expiração |
| Superadmin | Usuários, lojas, logins, logs do sistema, migrations |
| LGPD | Consentimento, export, retificação, exclusão agendada |
| PWA | Manifest para instalação no celular |

## E-mail (SMTP)

Configure no `.env` para envio em produção:

```env
MAIL_FROM=noreply@seudominio.com
MAIL_FROM_NAME=Rezult
MAIL_HOST=smtp.seudominio.com
MAIL_PORT=587
MAIL_USER=usuario
MAIL_PASSWORD=senha
MAIL_ENCRYPTION=tls
```

Sem SMTP, e-mails são gravados em `storage/mail/` (desenvolvimento).

Templates HTML: confirmação de conta, recuperação de senha, convites, vencimentos, resumo semanal, cobranças e aviso de plano expirando.

## Cron (agendar no servidor)

```bash
*/15 * * * * php /caminho/bin/cron-recorrente.php   # lançamentos recorrentes
0 8 * * *     php /caminho/bin/cron-emails.php       # vencimentos + resumo semanal
0 9 * * *     php /caminho/bin/cron-planos.php       # aviso plano expirando (7d e 1d)
*/30 * * * *  php /caminho/bin/cron-webhooks.php     # retry webhooks com falha
0 3 * * *     php /caminho/bin/cron-lgpd.php         # exclusões após 15 dias
```

## API

1. Admin gera token em `/api/tokens`
2. Requisições: `Authorization: Bearer SEU_TOKEN`

```bash
curl -H "Authorization: Bearer TOKEN" https://rezult.tdesksolutions.com.br/api/v1/lancamentos
```

## Superadmin

Rotas: `/superadmin`, `/superadmin/usuarios`, `/superadmin/empresas`, `/superadmin/logins`, `/superadmin/sistema`

Promover via CLI: `php bin/promote-superadmin.php email@exemplo.com`

## Docker

```bash
docker compose up -d
```

## Testes

```bash
composer test
```

## Produção

- `APP_ENV=production`, `APP_DEBUG=false`
- Nginx: ver `docker/nginx.conf`
- `LGPD_DPO_EMAIL`, `MAIL_*` e opcionalmente `HEALTH_TOKEN`, `SUPERADMIN_EMAIL` no `.env`
