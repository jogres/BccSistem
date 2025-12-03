<?php
/**
 * Model de Vendas
 * Gerencia todas as operações relacionadas a vendas
 */
final class Venda
{
    /**
     * Cria uma nova venda
     */
    public static function create(array $data): int
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "INSERT INTO vendas (
                        cliente_id, vendedor_id, virador_id, numero_contrato,
                        rua, bairro, numero, cep, cpf,
                        segmento, tipo, administradora, valor_credito, arquivo_contrato
                    ) VALUES (
                        :cliente_id, :vendedor_id, :virador_id, :numero_contrato,
                        :rua, :bairro, :numero, :cep, :cpf,
                        :segmento, :tipo, :administradora, :valor_credito, :arquivo_contrato
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cliente_id' => $data['cliente_id'],
                ':vendedor_id' => $data['vendedor_id'],
                ':virador_id' => $data['virador_id'],
                ':numero_contrato' => $data['numero_contrato'],
                ':rua' => $data['rua'],
                ':bairro' => $data['bairro'],
                ':numero' => $data['numero'],
                ':cep' => $data['cep'],
                ':cpf' => $data['cpf'],
                ':segmento' => $data['segmento'],
                ':tipo' => $data['tipo'],
                ':administradora' => $data['administradora'],
                ':valor_credito' => $data['valor_credito'],
                ':arquivo_contrato' => $data['arquivo_contrato'] ?? null
            ]);
            
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao criar venda', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Atualiza uma venda existente
     */
    public static function update(int $id, array $data): void
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "UPDATE vendas SET
                        cliente_id = :cliente_id,
                        vendedor_id = :vendedor_id,
                        virador_id = :virador_id,
                        numero_contrato = :numero_contrato,
                        rua = :rua,
                        bairro = :bairro,
                        numero = :numero,
                        cep = :cep,
                        cpf = :cpf,
                        segmento = :segmento,
                        tipo = :tipo,
                        administradora = :administradora,
                        valor_credito = :valor_credito";
            
            $params = [
                ':cliente_id' => $data['cliente_id'],
                ':vendedor_id' => $data['vendedor_id'],
                ':virador_id' => $data['virador_id'],
                ':numero_contrato' => $data['numero_contrato'],
                ':rua' => $data['rua'],
                ':bairro' => $data['bairro'],
                ':numero' => $data['numero'],
                ':cep' => $data['cep'],
                ':cpf' => $data['cpf'],
                ':segmento' => $data['segmento'],
                ':tipo' => $data['tipo'],
                ':administradora' => $data['administradora'],
                ':valor_credito' => $data['valor_credito'],
                ':id' => $id
            ];
            
            // Atualiza arquivo do contrato se fornecido
            if (isset($data['arquivo_contrato']) && $data['arquivo_contrato'] !== null) {
                $sql .= ", arquivo_contrato = :arquivo_contrato";
                $params[':arquivo_contrato'] = $data['arquivo_contrato'];
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao atualizar venda', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Verifica se um número de contrato já existe
     * 
     * @param string $numeroContrato Número do contrato a verificar
     * @param int|null $ignoreId ID da venda a ignorar (útil na edição)
     * @return bool True se o contrato já existe, False caso contrário
     */
    public static function contratoExists(string $numeroContrato, ?int $ignoreId = null): bool
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "SELECT 1 FROM vendas 
                    WHERE numero_contrato = :numero_contrato 
                    AND deleted_at IS NULL";
            
            if ($ignoreId !== null) {
                $sql .= " AND id <> :ignore_id";
            }
            
            $sql .= " LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':numero_contrato', $numeroContrato);
            
            if ($ignoreId !== null) {
                $stmt->bindValue(':ignore_id', $ignoreId, \PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao verificar existência de contrato', [
                'numero_contrato' => $numeroContrato,
                'ignore_id' => $ignoreId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Busca uma venda por ID
     */
    public static function find(int $id): ?array
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "SELECT v.*,
                           c.nome AS cliente_nome,
                           c.telefone AS cliente_telefone,
                           c.cidade AS cliente_cidade,
                           c.estado AS cliente_estado,
                           vend.nome AS vendedor_nome,
                           vir.nome AS virador_nome
                    FROM vendas v
                    INNER JOIN clientes c ON c.id = v.cliente_id
                    INNER JOIN funcionarios vend ON vend.id = v.vendedor_id
                    INNER JOIN funcionarios vir ON vir.id = v.virador_id
                    WHERE v.id = :id AND v.deleted_at IS NULL
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar venda', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Lista vendas com filtros e permissões
     * 
     * @param int|null $userId ID do usuário (null para admin ver todas)
     * @param array $filters Filtros: mes, ano, vendedor_id, virador_id, administradora, tipo, segmento
     * @return array
     */
    public static function all(?int $userId, array $filters = []): array
    {
        try {
            $pdo = Database::getConnection();
            
            $where = ["v.deleted_at IS NULL"];
            $params = [];
            
            // Filtro de permissão: funcionário só vê suas vendas
            if ($userId !== null) {
                $where[] = "(v.vendedor_id = ? OR v.virador_id = ?)";
                $params[] = $userId;
                $params[] = $userId;
            }
            
            // Filtro de mês/ano
            if (isset($filters['mes']) && isset($filters['ano']) && $filters['mes'] !== '' && $filters['ano'] !== '') {
                $where[] = "MONTH(v.created_at) = ? AND YEAR(v.created_at) = ?";
                $params[] = (int)$filters['mes'];
                $params[] = (int)$filters['ano'];
            } elseif (isset($filters['mes']) && $filters['mes'] !== '') {
                // Apenas mês, usa ano atual
                $where[] = "MONTH(v.created_at) = ? AND YEAR(v.created_at) = ?";
                $params[] = (int)$filters['mes'];
                $params[] = (int)date('Y');
            }
            
            // Filtro de vendedor
            if (isset($filters['vendedor_id']) && $filters['vendedor_id'] !== '') {
                $where[] = "v.vendedor_id = ?";
                $params[] = (int)$filters['vendedor_id'];
            }
            
            // Filtro de virador
            if (isset($filters['virador_id']) && $filters['virador_id'] !== '') {
                $where[] = "v.virador_id = ?";
                $params[] = (int)$filters['virador_id'];
            }
            
            // Filtro de administradora
            if (isset($filters['administradora']) && $filters['administradora'] !== '') {
                $where[] = "v.administradora = ?";
                $params[] = $filters['administradora'];
            }
            
            // Filtro de tipo
            if (isset($filters['tipo']) && $filters['tipo'] !== '') {
                $where[] = "v.tipo = ?";
                $params[] = $filters['tipo'];
            }
            
            // Filtro de segmento
            if (isset($filters['segmento']) && $filters['segmento'] !== '') {
                $where[] = "v.segmento = ?";
                $params[] = $filters['segmento'];
            }
            
            $whereSql = implode(' AND ', $where);
            
            $sql = "SELECT v.*,
                           c.nome AS cliente_nome,
                           c.telefone AS cliente_telefone,
                           vend.nome AS vendedor_nome,
                           vir.nome AS virador_nome
                    FROM vendas v
                    INNER JOIN clientes c ON c.id = v.cliente_id
                    INNER JOIN funcionarios vend ON vend.id = v.vendedor_id
                    INNER JOIN funcionarios vir ON vir.id = v.virador_id
                    WHERE {$whereSql}
                    ORDER BY v.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao listar vendas', [
                'userId' => $userId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Soft delete de uma venda
     */
    public static function delete(int $id): void
    {
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare("UPDATE vendas SET deleted_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao deletar venda', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Estatísticas de vendas para um período
     */
    public static function getStats(?int $userId, int $mes, int $ano): array
    {
        try {
            $pdo = Database::getConnection();
            
            $where = ["v.deleted_at IS NULL"];
            $params = [];
            
            if ($userId !== null) {
                $where[] = "(v.vendedor_id = ? OR v.virador_id = ?)";
                $params[] = $userId;
                $params[] = $userId;
            }
            
            $where[] = "MONTH(v.created_at) = ? AND YEAR(v.created_at) = ?";
            $params[] = $mes;
            $params[] = $ano;
            
            $whereSql = implode(' AND ', $where);
            
            $sql = "SELECT 
                        COUNT(*) as total_vendas,
                        SUM(v.valor_credito) as valor_total,
                        AVG(v.valor_credito) as valor_medio,
                        COUNT(DISTINCT v.vendedor_id) as total_vendedores
                    FROM vendas v
                    WHERE {$whereSql}";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch() ?: [
                'total_vendas' => 0,
                'valor_total' => 0,
                'valor_medio' => 0,
                'total_vendedores' => 0
            ];
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar estatísticas de vendas', [
                'userId' => $userId,
                'mes' => $mes,
                'ano' => $ano,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Verifica se um usuário pode visualizar uma venda
     */
    public static function canView(int $vendaId, int $userId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }
        
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare(
                "SELECT 1 FROM vendas 
                 WHERE id = :id 
                 AND (vendedor_id = :userId OR virador_id = :userId2)
                 AND deleted_at IS NULL
                 LIMIT 1"
            );
            $stmt->execute([
                ':id' => $vendaId,
                ':userId' => $userId,
                ':userId2' => $userId
            ]);
            
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao verificar permissão de visualização de venda', [
                'vendaId' => $vendaId,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
