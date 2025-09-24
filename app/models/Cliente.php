<?php
class Cliente {
    public static function create(string $nome, string $telefone, string $cidade, string $estado,  string $interesse,int $criado_por): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone, cidade, estado, interesse, criado_por) 
                               VALUES (:nome, :telefone, :cidade, :estado, :interesse, :criado_por)");
        $stmt->execute([
            ':nome' => $nome,
            ':telefone' => $telefone,            
            ':cidade' => $cidade,
            ':estado' => $estado,
            ':interesse' => $interesse,
            ':criado_por' => $criado_por,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function update(int $id, string $nome, string $telefone, string $cidade, string $estado, string $interesse): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE clientes SET nome = :nome, telefone = :telefone, cidade = :cidade, estado = :estado, interesse = :interesse WHERE id = :id");
        $stmt->execute([
            ':id' => $id,
            ':nome' => $nome,
            ':telefone' => $telefone,
            ':cidade' => $cidade,
            ':estado' => $estado,
            ':interesse' => $interesse,
        ]);
    }

    public static function softDelete(int $id): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE clientes SET deleted_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public static function listForUser(?int $userId, bool $admin): array {
        $pdo = Database::getConnection();
        if ($admin) {
            $sql = "SELECT c.*, f.nome AS criado_por_nome 
                    FROM clientes c 
                    JOIN funcionarios f ON f.id = c.criado_por
                    WHERE c.deleted_at IS NULL
                    ORDER BY c.created_at DESC";
            return $pdo->query($sql)->fetchAll();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE criado_por = :u AND deleted_at IS NULL ORDER BY created_at DESC");
            $stmt->execute([':u' => $userId]);
            return $stmt->fetchAll();
        }
    }
}
