<?php
class Funcionario
{
    /**
     * Lista funcionários conforme status.
     * $status: 'active' | 'inactive' | 'all'
     * (compat: se boolean, true=active, false=all)
     */
    public static function withActivityBetween(string $start, string $end): array
    {
        try {
            $pdo = Database::getConnection();
            $sql = "SELECT f.id, f.nome
                FROM funcionarios f
                JOIN (
                  SELECT DISTINCT criado_por AS uid
                  FROM clientes
                  WHERE deleted_at IS NULL
                    AND created_at BETWEEN :start AND :end
                ) a ON a.uid = f.id
                WHERE f.is_ativo = 1
                ORDER BY f.nome";
            $st = $pdo->prepare($sql);
            $st->execute([':start' => $start, ':end' => $end]);
            return $st->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar funcionários com atividade', [
                'start' => $start,
                'end' => $end,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public static function allActiveIds(): array
    {
        try {
            $pdo = Database::getConnection();
            $rows = $pdo->query("SELECT id FROM funcionarios WHERE is_ativo = 1")->fetchAll();
            return array_map('intval', array_column($rows, 'id'));
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar IDs de funcionários ativos', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    // Removed duplicate allActive method here to avoid duplicate symbol error.

    public static function all($status = 'active'): array
    {
        try {
            // compat com chamadas antigas: all(true) => 'active'; all(false) => 'all'
            if (is_bool($status)) {
                $status = $status ? 'active' : 'all';
            }
            $allowed = ['active', 'inactive', 'all'];
            if (!in_array($status, $allowed, true)) {
                $status = 'active';
            }

            $pdo = Database::getConnection();
            $sql = "SELECT f.id, f.nome, f.login, f.role_id, f.is_ativo, r.nome AS role_name,
                           f.created_at, f.updated_at
                      FROM funcionarios f
                      JOIN roles r ON r.id = f.role_id";

            if ($status === 'active') {
                $sql .= " WHERE f.is_ativo = 1";
            } elseif ($status === 'inactive') {
                $sql .= " WHERE f.is_ativo = 0";
            }
            $sql .= " ORDER BY f.nome";

            return $pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao listar funcionários', [
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /** Para selects do dashboard (somente ativos). */
    public static function allActive(): array
    {
        try {
            $pdo = Database::getConnection();
            $sql = "SELECT f.id, f.nome FROM funcionarios f WHERE f.is_ativo = 1 ORDER BY f.nome";
            return $pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao listar funcionários ativos', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public static function find(int $id): ?array
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $r = $stmt->fetch();
            return $r ?: null;
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar funcionário', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public static function create(string $nome, string $login, string $password, int $role_id, int $is_ativo = 1): int
    {
        try {
            $pdo = Database::getConnection();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO funcionarios (nome, login, senha_hash, role_id, is_ativo)
                 VALUES (:nome, :login, :senha_hash, :role_id, :is_ativo)"
            );
            $stmt->execute([
                ':nome' => $nome,
                ':login' => $login,
                ':senha_hash' => $hash,
                ':role_id' => $role_id,
                ':is_ativo' => $is_ativo,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao criar funcionário', [
                'nome' => $nome,
                'login' => $login,
                'role_id' => $role_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /** Atualiza dados; se $newPassword vazio/null, mantém a senha atual. */
    public static function update(int $id, string $nome, string $login, ?string $newPassword, int $role_id, int $is_ativo): void
    {
        try {
            $pdo = Database::getConnection();
            if ($newPassword !== null && $newPassword !== '') {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql = "UPDATE funcionarios
                           SET nome = :nome, login = :login, senha_hash = :senha_hash,
                               role_id = :role_id, is_ativo = :is_ativo
                         WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $nome,
                    ':login' => $login,
                    ':senha_hash' => $hash,
                    ':role_id' => $role_id,
                    ':is_ativo' => $is_ativo,
                    ':id' => $id,
                ]);
            } else {
                $sql = "UPDATE funcionarios
                           SET nome = :nome, login = :login,
                               role_id = :role_id, is_ativo = :is_ativo
                         WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $nome,
                    ':login' => $login,
                    ':role_id' => $role_id,
                    ':is_ativo' => $is_ativo,
                    ':id' => $id,
                ]);
            }
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao atualizar funcionário', [
                'id' => $id,
                'nome' => $nome,
                'login' => $login,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
