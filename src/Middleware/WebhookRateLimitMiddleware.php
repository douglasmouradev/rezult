<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Services\RateLimitService;
use Closure;

/** Rate limit para webhooks públicos (sem sessão). */
final class WebhookRateLimitMiddleware
{
    public function __construct(
        private string $acao = 'webhook',
        private int $max = 120,
        private int $minutos = 1,
    ) {}

    public function __invoke(Closure $next): void
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $rate = new RateLimitService();
        if ($rate->excedido($this->acao, $ip, $this->max, $this->minutos)) {
            View::json(['error' => 'Too many requests'], 429);
        }
        $rate->registrar($this->acao, $ip);
        $next();
    }
}
