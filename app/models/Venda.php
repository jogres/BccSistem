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
    }
    
    /**
     * Atualiza uma venda existente
     */
    public static function update(int $id, array $data): void
    {
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
    }
    
    /**
     * Busca uma venda por ID
     */
    public static function find(int $id): ?array
    {
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
        }
        
        // Filtro de vendedor
        if (isset($filters['vendedor_id']) && $filters['vendedor_id'] !== '' && $filters['vendedor_id'] !== null) {
            $where[] = "v.vendedor_id = ?";
            $params[] = (int)$filters['vendedor_id'];
        }
        
        // Filtro de virador
        if (isset($filters['virador_id']) && $filters['virador_id'] !== '' && $filters['virador_id'] !== null) {
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
        
        // Filtro de cliente
        if (isset($filters['cliente_id']) && $filters['cliente_id'] !== '' && $filters['cliente_id'] !== null) {
            $where[] = "v.cliente_id = ?";
            $params[] = (int)$filters['cliente_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT v.*,
                       c.nome AS cliente_nome,
                       c.telefone AS cliente_telefone,
                       vend.nome AS vendedor_nome,
                       vir.nome AS virador_nome
                FROM vendas v
                INNER JOIN clientes c ON c.id = v.cliente_id
                INNER JOIN funcionarios vend ON vend.id = v.vendedor_id
                INNER JOIN funcionarios vir ON vir.id = v.virador_id
                WHERE {$whereClause}
                ORDER BY v.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Soft delete de uma venda
     */
    public static function softDelete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE vendas SET deleted_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
    
    /**
     * Verifica se o usuário pode editar/excluir a venda
     * Admin pode tudo, funcionário não pode editar/excluir
     */
    public static function canModify(int $vendaId, int $userId, bool $isAdmin): bool
    {
        return $isAdmin;
    }
    
    /**
     * Verifica se o usuário pode visualizar a venda
     */
    public static function canView(int $vendaId, int $userId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }
        
        $venda = self::find($vendaId);
        if (!$venda) {
            return false;
        }
        
        // Funcionário pode ver se for vendedor ou virador
        return ($venda['vendedor_id'] == $userId || $venda['virador_id'] == $userId);
    }
    
    /**
     * Busca estatísticas de vendas
     */
    public static function getStats(?int $userId, ?int $mes = null, ?int $ano = null): array
    {
        $pdo = Database::getConnection();
        
        $where = ["v.deleted_at IS NULL"];
        $params = [];
        
        if ($userId !== null) {
            $where[] = "(v.vendedor_id = ? OR v.virador_id = ?)";
            $params[] = $userId;
            $params[] = $userId;
        }
        
        // Só adiciona filtro de mês/ano se ambos estiverem definidos
        if ($mes !== null && $ano !== null) {
            $where[] = "MONTH(v.created_at) = ? AND YEAR(v.created_at) = ?";
            $params[] = $mes;
            $params[] = $ano;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT 
                    COUNT(*) as total_vendas,
                    SUM(v.valor_credito) as total_credito,
                    AVG(v.valor_credito) as media_credito,
                    COUNT(DISTINCT v.vendedor_id) as total_vendedores,
                    COUNT(CASE WHEN v.tipo = 'Normal' THEN 1 END) as vendas_normais,
                    COUNT(CASE WHEN v.tipo = 'Meia' THEN 1 END) as vendas_meia
                FROM vendas v
                WHERE {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
    }
    
    /**
     * Verifica se o número do contrato já existe
     */
    public static function contratoExists(string $numeroContrato, ?int $excludeId = null): bool
    {
        $pdo = Database::getConnection();
        
        $sql = "SELECT COUNT(*) FROM vendas WHERE numero_contrato = :numero AND deleted_at IS NULL";
        $params = [':numero' => $numeroContrato];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Busca vendas por cliente
     */
    public static function findByCliente(int $clienteId): array
    {
        $pdo = Database::getConnection();
        
        $sql = "SELECT v.*,
                       vend.nome AS vendedor_nome,
                       vir.nome AS virador_nome
                FROM vendas v
                INNER JOIN funcionarios vend ON vend.id = v.vendedor_id
                INNER JOIN funcionarios vir ON vir.id = v.virador_id
                WHERE v.cliente_id = :cliente_id AND v.deleted_at IS NULL
                ORDER BY v.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cliente_id' => $clienteId]);
        
        return $stmt->fetchAll();
    }
}

