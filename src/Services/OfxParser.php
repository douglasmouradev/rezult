<?php

declare(strict_types=1);

namespace App\Services;

final class OfxParser
{
    /** @return array<int, array{date: string, amount: float, description: string}> */
    public function parse(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || $content === '') {
            return [];
        }

        $content = preg_replace('/<\?OFX[^>]*>/i', '', $content) ?? $content;
        $transactions = [];

        if (preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/is', $content, $blocks)) {
            foreach ($blocks[1] as $block) {
                $dateRaw = $this->tag($block, 'DTPOSTED') ?? $this->tag($block, 'DTUSER');
                $amountRaw = $this->tag($block, 'TRNAMT');
                if ($dateRaw === null || $amountRaw === null) {
                    continue;
                }

                $date = $this->parseDate($dateRaw);
                $amount = (float) str_replace(',', '.', $amountRaw);
                $description = trim($this->tag($block, 'MEMO') ?? $this->tag($block, 'NAME') ?? '');

                if (!$date || $amount == 0.0) {
                    continue;
                }

                $transactions[] = [
                    'date' => $date,
                    'amount' => $amount,
                    'description' => $description !== '' ? $description : 'Movimentação OFX',
                ];
            }
        }

        return $transactions;
    }

    private function tag(string $block, string $name): ?string
    {
        if (preg_match('/<' . preg_quote($name, '/') . '>([^<\r\n]+)/i', $block, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function parseDate(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === null || strlen($digits) < 8) {
            return null;
        }

        $y = substr($digits, 0, 4);
        $m = substr($digits, 4, 2);
        $d = substr($digits, 6, 2);

        if (!checkdate((int) $m, (int) $d, (int) $y)) {
            return null;
        }

        return "{$y}-{$m}-{$d}";
    }
}
