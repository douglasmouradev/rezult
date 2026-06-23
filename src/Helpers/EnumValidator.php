<?php

declare(strict_types=1);

namespace App\Helpers;

final class EnumValidator
{
    public const TIPOS_LANCAMENTO = ['receita', 'despesa', 'transferencia'];
    public const STATUS_LANCAMENTO = ['pendente', 'pago', 'cancelado', 'aguardando_aprovacao'];
    public const MOEDAS = ['BRL', 'USD', 'EUR'];
    public const PAPEIS = ['dono', 'admin', 'operador'];
    public const TIPOS_CONTATO = ['cliente', 'fornecedor', 'ambos'];
    public const PLANOS = ['starter', 'pro', 'business'];

    public static function assertIn(string $value, array $allowed, string $label = 'Valor'): string
    {
        if (!in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException("{$label} inválido.");
        }
        return $value;
    }
}
