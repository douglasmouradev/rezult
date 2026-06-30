<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Rezult',
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/'),
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'upload_max_mb' => (int) ($_ENV['UPLOAD_MAX_MB'] ?? 5),
    'session_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200),
    'dashboard_cache_ttl' => (int) ($_ENV['DASHBOARD_CACHE_TTL'] ?? 300),
    'mail_from' => $_ENV['MAIL_FROM'] ?? 'noreply@rezult.local',
    'mail_from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Rezult',
    'mail_host' => $_ENV['MAIL_HOST'] ?? '',
    'mail_port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'mail_user' => $_ENV['MAIL_USER'] ?? '',
    'mail_password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'mail_encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
    'lgpd_dpo_email' => $_ENV['LGPD_DPO_EMAIL'] ?? 'privacidade@rezult.app',
    'superadmin_email' => $_ENV['SUPERADMIN_EMAIL'] ?? '',
    'app_key' => $_ENV['APP_KEY'] ?? '',
];
