<?php

declare(strict_types=1);

use App\Helpers\DateTimeBr;
use PHPUnit\Framework\TestCase;

final class DateTimeBrTest extends TestCase
{
    public function testInitAmericaSaoPaulo(): void
    {
        DateTimeBr::init('America/Sao_Paulo');
        $this->assertSame('America/Sao_Paulo', DateTimeBr::timezone());
        $this->assertMatchesRegularExpression('/^[+-]\d{2}:\d{2}$/', DateTimeBr::mysqlOffset());
    }

    public function testFormatVazio(): void
    {
        DateTimeBr::init('America/Sao_Paulo');
        $this->assertSame('—', DateTimeBr::format(null));
        $this->assertSame('—', DateTimeBr::format(''));
    }
}
