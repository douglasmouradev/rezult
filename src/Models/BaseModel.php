<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;
use PDO;

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = App::pdo();
    }

    public function find(int $id, ?int $empresaId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $params = ['id' => $id];

        if ($empresaId !== null && $this->hasEmpresaScope()) {
            $sql .= ' AND empresa_id = :empresa_id';
            $params['empresa_id'] = $empresaId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array> */
    public function findAll(?int $empresaId = null, string $orderBy = 'id DESC', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if ($empresaId !== null && $this->hasEmpresaScope()) {
            $sql .= ' WHERE empresa_id = :empresa_id';
            $params['empresa_id'] = $empresaId;
        }

        $sql .= " ORDER BY {$orderBy}";

        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function save(array $data, ?int $empresaId = null): int
    {
        $id = $data[$this->primaryKey] ?? null;
        unset($data[$this->primaryKey]);

        if ($id) {
            $sets = implode(', ', array_map(fn ($k) => "{$k} = :{$k}", array_keys($data)));
            $sql = "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = :id";
            $data['id'] = $id;
            if ($empresaId !== null && $this->hasEmpresaScope()) {
                $sql .= ' AND empresa_id = :_empresa_scope';
                $data['_empresa_scope'] = $empresaId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            if ($empresaId !== null && $stmt->rowCount() === 0) {
                throw new \RuntimeException('Registro não encontrado ou sem permissão.');
            }
            return (int) $id;
        }

        $cols = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id, ?int $empresaId = null): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $params = ['id' => $id];

        if ($empresaId !== null && $this->hasEmpresaScope()) {
            $sql .= ' AND empresa_id = :empresa_id';
            $params['empresa_id'] = $empresaId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    protected function hasEmpresaScope(): bool
    {
        return in_array('empresa_id', $this->getColumns(), true);
    }

    /** @return string[] */
    protected function getColumns(): array
    {
        static $cache = [];
        if (!isset($cache[$this->table])) {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table}");
            $cache[$this->table] = array_column($stmt->fetchAll(), 'Field');
        }
        return $cache[$this->table];
    }
}
