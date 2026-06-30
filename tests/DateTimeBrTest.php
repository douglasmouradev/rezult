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
        $this->assertSame('—', DateTimeBr::format('0000-00-00 00:00:00'));
    }

    public function testToDatetimeLocalInvalido(): void
    {
        DateTimeBr::init('America/Sao_Paulo');
        $this->assertSame('', DateTimeBr::toDatetimeLocal(null));
        $this->assertSame('', DateTimeBr::toDatetimeLocal('0000-00-00'));
    }
}
