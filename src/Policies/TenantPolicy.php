<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\View;
use App\Enums\PapelEmpresa;
use App\Helpers\Session;
use App\Models\Conta;
use App\Models\Empresa;
use App\Models\Categoria;
use App\Models\Meta;

/** Regras de acesso multi-tenant e RBAC */
final class TenantPolicy
{
    public static function usuarioId(): int
    {
        return (int) Session::get('usuario_id');
    }

    public static function empresaId(): int
    {
        return (int) Session::get('empresa_id');
    }

    public static function abortUnlessEmpresaAccess(int $empresaId): void
    {
        if (!(new Empresa())->usuarioTemAcesso(self::usuarioId(), $empresaId)) {
            self::forbidden();
        }
    }

    public static function papel(int $empresaId): ?PapelEmpresa
    {
        $p = (new Empresa())->papelUsuario(self::usuarioId(), $empresaId);
        return $p ? PapelEmpresa::from($p) : null;
    }

    public static function abortUnlessCanManageEmpresa(int $empresaId): void
    {
        self::abortUnlessEmpresaAccess($empresaId);
        if (!self::papel($empresaId)?->podeGerenciarEmpresa()) {
            self::forbidden();
        }
    }

    public static function podeGerenciarConfig(?int $empresaId = null): bool
    {
        $eid = $empresaId ?? self::empresaId();
        return self::papel($eid)?->podeGerenciarEmpresa() ?? false;
    }

    public static function abortUnlessCanManageConfig(?int $empresaId = null): void
    {
        $eid = $empresaId ?? self::empresaId();
        self::abortUnlessEmpresaAccess($eid);
        if (!self::podeGerenciarConfig($eid)) {
            self::forbidden();
        }
    }

    public static function contaDaEmpresa(int $contaId, int $empresaId): bool
    {
        return (new Conta())->find($contaId, $empresaId) !== null;
    }

    public static function categoriaDaEmpresa(int $categoriaId, int $empresaId): bool
    {
        return (new Categoria())->find($categoriaId, $empresaId) !== null;
    }

    public static function metaDaEmpresa(int $metaId, int $empresaId): bool
    {
        return (new Meta())->find($metaId, $empresaId) !== null;
    }

    public static function centroCustoDaEmpresa(int $centroCustoId, int $empresaId): bool
    {
        $stmt = \App\Core\App::pdo()->prepare(
            'SELECT 1 FROM centros_custo WHERE id = :id AND empresa_id = :e AND ativo = 1 LIMIT 1'
        );
        $stmt->execute(['id' => $centroCustoId, 'e' => $empresaId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function abortUnlessCanDeleteLancamento(): void
    {
        if (!self::papel(self::empresaId())?->podeExcluirLancamento()) {
            self::forbidden();
        }
    }

    public static function abortUnlessCanApproveLancamento(): void
    {
        if (!self::papel(self::empresaId())?->podeAprovarLancamento()) {
            self::forbidden();
        }
    }

    public static function abortUnlessCanTransfer(): void
    {
        if (!self::papel(self::empresaId())?->podeTransferir()) {
            self::forbidden();
        }
    }

    public static function forbidden(): never
    {
        Session::flash('error', 'Acesso negado.');
        View::redirect('/dashboard');
    }
}
