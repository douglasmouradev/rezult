#!/usr/bin/env bash
# Deploy Rezult na VPS — execute na raiz do projeto (/www/wwwroot/rezult.tdesksolutions.com.br)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "==> Rezult deploy em $ROOT"

BACKUP_DIR="storage/deploy-backup-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

for f in .htaccess 404.html 502.html index.html; do
    if [[ -f "$f" ]]; then
        echo "    Backup local: $f"
        mv "$f" "$BACKUP_DIR/"
    fi
done

echo "==> git pull"
git pull origin main

echo "==> composer install"
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader
else
    php "$(command -v composer.phar 2>/dev/null || echo composer.phar)" install --no-dev --optimize-autoloader 2>/dev/null || echo "AVISO: composer não encontrado — instale dependências manualmente"
fi

echo "==> migrations"
php bin/migrate.php

echo "==> repair schema"
php bin/repair-schema.php

echo "==> doctor"
php bin/doctor.php || true

echo "==> permissões storage"
chmod -R 775 storage 2>/dev/null || true
mkdir -p storage/sessions && chmod 775 storage/sessions 2>/dev/null || true

echo "==> deploy concluído"
echo "    Crons sugeridos:"
echo "      */15 * * * * php $ROOT/bin/cron-recorrente.php"
echo "      0 8 * * *     php $ROOT/bin/cron-emails.php"
echo "      0 9 * * *     php $ROOT/bin/cron-planos.php"
echo "      */30 * * * *  php $ROOT/bin/cron-webhooks.php"
echo "      0 4 * * *     php $ROOT/bin/cron-maintenance.php"
echo "      0 2 * * *     php $ROOT/bin/cron-backup.php"
echo "      0 * * * *     php $ROOT/bin/cron-automacao.php"
echo "      0 3 * * *     php $ROOT/bin/cron-lgpd.php"
echo ""
echo "    Diagnóstico: php $ROOT/bin/doctor.php"
echo "    Reparo DB:   php $ROOT/bin/repair-schema.php"
