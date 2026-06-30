<?php

declare(strict_types=1);

namespace App\Enums;

enum PapelEmpresa: string
{
    case Dono = 'dono';
    case Admin = 'admin';
    case Operador = 'operador';

    public function podeGerenciarEmpresa(): bool
    {
        return match ($this) {
            self::Dono, self::Admin => true,
            self::Operador => false,
        };
    }

    public function podeExcluirLancamento(): bool
    {
        return match ($this) {
            self::Dono, self::Admin => true,
            self::Operador => false,
        };
    }

    public function podeAprovarLancamento(): bool
    {
        return $this->podeGerenciarEmpresa();
    }

    public function podeTransferir(): bool
    {
        return $this->podeGerenciarEmpresa();
    }

    public function label(): string
    {
        return match ($this) {
            self::Dono => 'Dono',
            self::Admin => 'Administrador',
            self::Operador => 'Operador',
        };
    }
}
