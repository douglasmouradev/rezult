<?php

declare(strict_types=1);

use App\Helpers\NavHelper;
use PHPUnit\Framework\TestCase;

final class NavHelperTest extends TestCase
{
    public function testBadgePlanoPro(): void
    {
        $this->assertSame('Pro', NavHelper::badgePlano('cobrancas'));
        $this->assertSame('Business', NavHelper::badgePlano('nfse'));
        $this->assertNull(NavHelper::badgePlano(null));
    }

    public function testNavMainIncluiCobrancasComFeature(): void
    {
        $paths = array_column(NavHelper::navMain(), 0);
        $this->assertContains('/cobrancas', $paths);
    }
}
