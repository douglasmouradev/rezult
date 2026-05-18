# Rezult — SaaS de Gestão Financeira

Sistema multi-tenant em **PHP 8.3 + MySQL 8.4**, MVC manual, LGPD e RBAC completo.

## Instalação

```bash
cp .env.example .env
composer install
php bin/migrate.php
php bin/seed.php
php -S localhost:8000 -t public
```

**Demo:** `demo@rezult.app` / `Senha@123`

## Funcionalidades

| Módulo | Descrição |
|--------|-----------|
| Financeiro | Lançamentos, contas, transferências, extrato, import CSV com preview |
| Relatórios | DRE, fluxo, categoria (Excel/PDF) |
| Orçamento | Planejado vs realizado por categoria/mês |
| Centros de custo | Cadastro por empresa |
| Equipe | Membros, convites, remoção |
| API REST | `GET/POST /api/v1/lancamentos` com Bearer token |
| Notificações | In-app + e-mails (vencimentos, resumo semanal) |
| LGPD | Consentimento, export titular/empresa, retificação, exclusão agendada 15 dias |
| Perfil | Nome, avatar, troca de senha |
| RBAC | Dono/admin vs operador (exclusão e config restritas) |

## Cron (agendar no servidor)

```bash
php bin/cron-recorrente.php   # lançamentos recorrentes
php bin/cron-emails.php       # vencimentos + resumo semanal
php bin/cron-lgpd.php         # exclusões após 15 dias
```

## API

1. Admin gera token em `/api/tokens`
2. Requisições: `Authorization: Bearer SEU_TOKEN`

```bash
curl -H "Authorization: Bearer TOKEN" http://localhost:8000/api/v1/lancamentos
```

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
- `LGPD_DPO_EMAIL`, `MAIL_HOST` no `.env`
