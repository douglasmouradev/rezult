<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UploadImportTest extends TestCase
{
    public function testRejeitaExtensaoInvalida(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'rz');
        file_put_contents($tmp, 'test');
        $file = [
            'error' => UPLOAD_ERR_OK,
            'size' => 4,
            'name' => 'malware.exe',
            'tmp_name' => $tmp,
        ];
        $this->expectException(InvalidArgumentException::class);
        \App\Helpers\Upload::validateImport($file);
        @unlink($tmp);
    }
}
