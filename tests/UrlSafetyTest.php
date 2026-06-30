<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UrlSafetyTest extends TestCase
{
    public function testBloqueiaLocalhost(): void
    {
        $r = \App\Helpers\UrlSafety::webhookPermitida('http://localhost/hook');
        $this->assertFalse($r['ok']);
    }

    public function testPermiteHttpsPublico(): void
    {
        $r = \App\Helpers\UrlSafety::webhookPermitida('https://example.com/webhook');
        $this->assertTrue($r['ok']);
    }
}
