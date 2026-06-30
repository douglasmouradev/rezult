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
HEALTH_TOKEN=token-aleatorio-para-health-check
FINANCIAL_MODE=live
TRUSTED_PROXIES=127.0.0.1
```

Em produção, o bootstrap **bloqueia** a aplicação sem `APP_KEY` (32+ chars) e `HEALTH_TOKEN`. Com `FINANCIAL_MODE=live`, cobranças simuladas são bloqueadas — configure gateway em Integrações.

## Planos e features

| Plano | Empresas | Usuários | API | Webhooks | Integrações | NFS-e / OF / IA |
|-------|----------|----------|-----|----------|-------------|-----------------|
| Starter | 1 | 1 | — | — | — | — |
| Pro | 5 | 10 | ✓ | ✓ | ✓ | — |
| Business | ∞ | ∞ | ✓ | ✓ | ✓ | ✓ |

Gestão de plano: `/plano` · Superadmin: `/superadmin/empresas`

## Segurança

- Segredos de integração e **webhooks** criptografados (`APP_KEY` + `Crypto`)
- Webhooks com proteção **SSRF** e HTTPS em produção
- API com **escopos** (leitura / leitura+escrita), rate limit e lookup de token sem colisão de prefixo
- `/health` protegido por token em produção
- Menu lateral com **badges de plano** (Pro/Business) em recursos bloqueados
- RBAC em cobranças e pagamento/recebimento em lote
- `storage/` bloqueado via `.htaccess`
- CSRF + header `X-CSRF-Token`

## Integrações

### Gateway Asaas (cobranças reais)

1. Em **Integrações → Gateway**, informe API Key (sandbox ou produção) e token do webhook
2. No painel Asaas, cadastre webhook: `https://seu-dominio.com/webhooks/gateway/asaas`
3. Eventos: `PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED`
4. Com gateway ativo, Pix/boleto são emitidos via API — **sem simulação**
5. Pagamentos confirmados atualizam cobrança e lançamento automaticamente

Open Finance e NFS-e ainda salvam configuração; emissão municipal e extrato automático em evolução.

Com `FINANCIAL_MODE=demo` (padrão local), Pix/boleto simulados são permitidos sem gateway. Em `live` + produção, exige gateway ativo.

## E-mail (SMTP)

Ver variáveis `MAIL_*` no `.env.example`.

## Cron

```bash
*/15 * * * * php bin/cron-recorrente.php
0 8 * * *     php bin/cron-emails.php
0 9 * * *     php bin/cron-planos.php
0 * * * *     php bin/cron-automacao.php     # vencimentos + cobranças
*/30 * * * *  php bin/cron-webhooks.php
0 4 * * *     php bin/cron-maintenance.php   # purge rate_limits
0 2 * * *     php bin/cron-backup.php        # mysqldump (requer mysqldump no PATH)
0 3 * * *     php bin/cron-lgpd.php
```

Alertas de erro em produção: configure `ERROR_ALERT_EMAIL` no `.env`.

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

## Problemas em produção

```bash
php bin/doctor.php          # diagnóstico (env, tabelas, colunas, log)
php bin/repair-schema.php   # corrige colunas/tabelas ausentes
php bin/migrate.php
tail -50 storage/logs/app.log
```

Se aparecer erro de `APP_KEY`, adicione no `.env` (mín. 32 caracteres) e `HEALTH_TOKEN`.
Após trocar `APP_KEY`, reconfigure integrações (segredos antigos ficam ilegíveis).

## PWA

`manifest.json` + `public/sw.js` (cache de assets estáticos).

## Superadmin

`/superadmin`, `/superadmin/usuarios`, `/superadmin/empresas`, `/superadmin/sistema`

```bash
php bin/create-superadmin.php "Nome" email@exemplo.com "SenhaForte123"
```
