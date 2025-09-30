<?php
final class Cliente {
    public static function softDelete($id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE clientes SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }
    public static function create(array $d): int {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO clientes
                  (nome, telefone, cidade, estado, interesse, criado_por)
                VALUES
                  (:nome, :telefone, :cidade, :estado, :interesse, :criado_por)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'       => $d['nome'],
            ':telefone'   => $d['telefone'],
            ':cidade'     => $d['cidade'],
            ':estado'     => $d['estado'],
            ':interesse'  => $d['interesse'],
            ':criado_por' => (int)$d['criado_por'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $d): void {
        $pdo = Database::getConnection();
        $sql = "UPDATE clientes
                   SET nome = :nome,
                       telefone = :telefone,
                       cidade = :cidade,
                       estado = :estado,
                       interesse = :interesse
                 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'      => $d['nome'],
            ':telefone'  => $d['telefone'],
            ':cidade'    => $d['cidade'],
            ':estado'    => $d['estado'],
            ':interesse' => $d['interesse'],
            ':id'        => $id,
        ]);
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $st  = $pdo->prepare("SELECT * FROM clientes WHERE id=:id LIMIT 1");
        $st->execute([':id'=>$id]);
        return $st->fetch() ?: null;
    }

    public static function allForUser(?int $userId, ?string $interesse=''): array {
        $pdo = Database::getConnection();
        $where  = "c.deleted_at IS NULL";
        $params = [];
        if ($userId) { $where .= " AND c.criado_por = :u"; $params[':u'] = $userId; }
        if ($interesse !== '') { $where .= " AND c.interesse = :i"; $params[':i'] = $interesse; }
        $sql = "SELECT c.*, f.nome AS criado_por_nome
                FROM clientes c JOIN funcionarios f ON f.id=c.criado_por
                WHERE $where ORDER BY c.created_at DESC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
