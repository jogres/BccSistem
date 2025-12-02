<?php
final class Cliente {
    private static function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    public static function isPhoneTaken(string $phone, ?int $ignoreId = null): bool
    {
        try {
            $pdo = Database::getConnection();
            $cleanPhone = self::normalizePhone($phone);

            $phoneExpression = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '.', ''), '+', '')";

            $sql = "SELECT 1 FROM clientes WHERE {$phoneExpression} = :telefone AND deleted_at IS NULL";

            if ($ignoreId !== null) {
                $sql .= " AND id <> :ignore_id";
            }

            $sql .= " LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':telefone', $cleanPhone);

            if ($ignoreId !== null) {
                $stmt->bindValue(':ignore_id', $ignoreId, \PDO::PARAM_INT);
            }

            $stmt->execute();

            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao verificar telefone duplicado', [
                'phone' => $phone,
                'ignoreId' => $ignoreId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public static function softDelete($id): void {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE clientes SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao deletar cliente', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    public static function create(array $d): int {
        try {
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
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao criar cliente', [
                'data' => $d,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public static function update(int $id, array $d): void {
        try {
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
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao atualizar cliente', [
                'id' => $id,
                'data' => $d,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza apenas campos específicos do cliente
     * @param int $id ID do cliente
     * @param array $fields Array associativo com os campos a atualizar (ex: ['nome' => 'João'])
     * @return void
     */
    public static function updateFields(int $id, array $fields): void {
        if (empty($fields)) {
            return;
        }

        try {
            $pdo = Database::getConnection();
            
            // Construir dinamicamente a query com apenas os campos fornecidos
            $setClauses = [];
            $params = [':id' => $id];
            
            $allowedFields = ['nome', 'telefone', 'cidade', 'estado', 'interesse'];
            
            foreach ($fields as $field => $value) {
                if (in_array($field, $allowedFields, true)) {
                    $setClauses[] = "$field = :$field";
                    $params[":$field"] = $value;
                }
            }
            
            if (empty($setClauses)) {
                return;
            }
            
            $sql = "UPDATE clientes SET " . implode(', ', $setClauses) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao atualizar campos do cliente', [
                'id' => $id,
                'fields' => $fields,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public static function find(int $id): ?array {
        try {
            $pdo = Database::getConnection();
            $st  = $pdo->prepare("SELECT * FROM clientes WHERE id=:id LIMIT 1");
            $st->execute([':id'=>$id]);
            return $st->fetch() ?: null;
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar cliente', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public static function allForUser(?int $userId, ?string $interesse=''): array {
        try {
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
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao listar clientes', [
                'userId' => $userId,
                'interesse' => $interesse,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
