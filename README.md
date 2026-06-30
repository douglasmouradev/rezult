# Rezult — SaaS de Gestão Financeira

Sistema multi-tenant em **PHP 8.3 + MySQL 8**, MVC manual, LGPD e RBAC completo.

## Instalação local

```bash
cp .env.example .env
# Gere APP_KEY: php -r "echo bin2hex(random_bytes(32));"
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

Configure no `.env` de produção:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com
APP_KEY=sua-chave-secreta-64-chars-hex
```

## Planos e features

| Plano | Empresas | Usuários | API | Webhooks | Integrações | NFS-e / OF / IA |
|-------|----------|----------|-----|----------|-------------|-----------------|
| Starter | 1 | 1 | — | — | — | — |
| Pro | 5 | 10 | ✓ | ✓ | ✓ | — |
| Business | ∞ | ∞ | ✓ | ✓ | ✓ | ✓ |

Gestão de plano: `/plano` · Superadmin: `/superadmin/empresas`

## Segurança

- Segredos de integração **criptografados** (`APP_KEY` + `Crypto`)
- Webhooks com proteção **SSRF** e HTTPS em produção
- API com **escopos** (leitura / leitura+escrita) e rate limit
- `storage/` bloqueado via `.htaccess`
- CSRF + header `X-CSRF-Token`

## Integrações (modo demonstração)

Open Finance, gateway e NFS-e salvam configuração; chamadas reais aos provedores são extensão futura. Cobranças usam gateway se configurado; senão **Pix/boleto simulados** (não use em produção financeira sem gateway).

## E-mail (SMTP)

Ver variáveis `MAIL_*` no `.env.example`.

## Cron

```bash
*/15 * * * * php bin/cron-recorrente.php
0 8 * * *     php bin/cron-emails.php
0 9 * * *     php bin/cron-planos.php      # avisos + desativa expirados
*/30 * * * *  php bin/cron-webhooks.php
0 3 * * *     php bin/cron-lgpd.php
```

## API

Documentação OpenAPI: `docs/api.openapi.yaml`

Tokens em `/api/tokens` (planos Pro+). Header: `Authorization: Bearer TOKEN`

## Testes e qualidade

```bash
composer test
vendor/bin/phpstan analyse -c phpstan.neon
composer audit
```

CI roda migrations, audit, PHPStan e PHPUnit no GitHub Actions.

## PWA

`manifest.json` + `public/sw.js` (cache de assets estáticos).

## Superadmin

`/superadmin`, `/superadmin/usuarios`, `/superadmin/empresas`, `/superadmin/sistema`

```bash
php bin/create-superadmin.php "Nome" email@exemplo.com "SenhaForte123"
```
