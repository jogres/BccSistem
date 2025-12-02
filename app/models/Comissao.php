<?php
/**
 * Model de Comissões
 * Gerencia todas as operações relacionadas a comissões de vendedores e viradores
 */
final class Comissao
{
    /**
     * Cria uma nova comissão
     */
    public static function create(array $data): int
    {
        try {
            $pdo = Database::getConnection();
            
            // Buscar informações da venda
            $venda = Venda::find($data['venda_id']);
            if (!$venda) {
                throw new Exception('Venda não encontrada');
            }
            
            // Calcular valor base
            $valorBase = self::calcularValorBase($venda);
            
            // Calcular valor da comissão
            $valorComissao = ($valorBase * $data['porcentagem']) / 100;
            
            $sql = "INSERT INTO comissoes (
                        venda_id, funcionario_id, tipo_comissao, parcela, numero_parcela,
                        porcentagem, valor_base, valor_comissao, created_by
                    ) VALUES (
                        :venda_id, :funcionario_id, :tipo_comissao, :parcela, :numero_parcela,
                        :porcentagem, :valor_base, :valor_comissao, :created_by
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':venda_id' => $data['venda_id'],
                ':funcionario_id' => $data['funcionario_id'],
                ':tipo_comissao' => $data['tipo_comissao'],
                ':parcela' => $data['parcela'],
                ':numero_parcela' => $data['numero_parcela'],
                ':porcentagem' => $data['porcentagem'],
                ':valor_base' => $valorBase,
                ':valor_comissao' => $valorComissao,
                ':created_by' => $data['created_by']
            ]);
            
            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao criar comissão', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Calcula o valor base considerando a regra da Gazin (meia parcela)
     */
    public static function calcularValorBase(array $venda): float
    {
        $valorCredito = (float)$venda['valor_credito'];
        
        // Normalizar valores para comparação (remove espaços e converte para minúsculas)
        $administradora = trim(strtolower($venda['administradora'] ?? ''));
        $tipo = trim($venda['tipo'] ?? '');
        
        // Se for Gazin (independente de espaços ou case) e tipo Meia, divide por 2
        if ($administradora === 'gazin' && $tipo === 'Meia') {
            return $valorCredito / 2;
        }
        
        return $valorCredito;
    }
    
    /**
     * Busca comissão por ID
     */
    public static function find(int $id): ?array
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "SELECT c.*, v.numero_contrato, v.administradora, v.tipo as venda_tipo,
                           v.valor_credito, f.nome as funcionario_nome,
                           cli.nome as cliente_nome,
                           admin.nome as created_by_nome
                    FROM comissoes c
                    INNER JOIN vendas v ON v.id = c.venda_id
                    INNER JOIN funcionarios f ON f.id = c.funcionario_id
                    INNER JOIN clientes cli ON cli.id = v.cliente_id
                    INNER JOIN funcionarios admin ON admin.id = c.created_by
                    WHERE c.id = :id
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar comissão', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Busca última parcela gerada para uma venda e tipo de comissão
     */
    public static function getUltimaParcela(int $vendaId, string $tipoComissao): ?array
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "SELECT * FROM comissoes
                    WHERE venda_id = :venda_id AND tipo_comissao = :tipo_comissao
                    ORDER BY numero_parcela DESC
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':venda_id' => $vendaId,
                ':tipo_comissao' => $tipoComissao
            ]);
            
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar última parcela', [
                'vendaId' => $vendaId,
                'tipoComissao' => $tipoComissao,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Verifica se a venda já atingiu a parcela final para o tipo de comissão
     */
    public static function isParcelaFinal(int $vendaId, string $tipoComissao): bool
    {
        try {
            $ultimaParcela = self::getUltimaParcela($vendaId, $tipoComissao);
            
            if (!$ultimaParcela) {
                return false;
            }
            
            // Verifica se contém "final" ou "Final" na descrição da parcela
            return stripos($ultimaParcela['parcela'], 'final') !== false;
        } catch (Exception $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao verificar parcela final', [
                'vendaId' => $vendaId,
                'tipoComissao' => $tipoComissao,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Lista comissões com filtros
     */
    public static function all(array $filters = []): array
    {
        try {
            $pdo = Database::getConnection();
            
            $where = [];
            $params = [];
            
            if (isset($filters['funcionario_id']) && $filters['funcionario_id'] !== '') {
                $where[] = "c.funcionario_id = :funcionario_id";
                $params[':funcionario_id'] = (int)$filters['funcionario_id'];
            }
            
            if (isset($filters['tipo_comissao']) && $filters['tipo_comissao'] !== '') {
                $where[] = "c.tipo_comissao = :tipo_comissao";
                $params[':tipo_comissao'] = $filters['tipo_comissao'];
            }
            
            if (isset($filters['venda_id']) && $filters['venda_id'] !== '') {
                $where[] = "c.venda_id = :venda_id";
                $params[':venda_id'] = (int)$filters['venda_id'];
            }
            
            // Filtro por mês/ano
            if (isset($filters['mes']) && isset($filters['ano']) && $filters['mes'] !== '' && $filters['ano'] !== '') {
                $where[] = "MONTH(c.created_at) = :mes AND YEAR(c.created_at) = :ano";
                $params[':mes'] = (int)$filters['mes'];
                $params[':ano'] = (int)$filters['ano'];
            }
            
            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "SELECT c.*, v.numero_contrato, v.administradora, v.tipo as venda_tipo,
                           v.valor_credito, f.nome as funcionario_nome,
                           cli.nome as cliente_nome
                    FROM comissoes c
                    INNER JOIN vendas v ON v.id = c.venda_id
                    INNER JOIN funcionarios f ON f.id = c.funcionario_id
                    INNER JOIN clientes cli ON cli.id = v.cliente_id
                    {$whereSql}
                    ORDER BY c.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao listar comissões', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Busca vendedores que têm vendas disponíveis para comissão
     */
    public static function getVendedoresDisponiveis(): array
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "SELECT DISTINCT v.vendedor_id as funcionario_id, f.nome
                    FROM vendas v
                    INNER JOIN funcionarios f ON f.id = v.vendedor_id
                    WHERE v.deleted_at IS NULL
                      AND NOT EXISTS (
                          SELECT 1 FROM comissoes c
                          WHERE c.venda_id = v.id
                            AND c.tipo_comissao = 'vendedor'
                            AND c.parcela LIKE '%Final%'
                      )
                    ORDER BY f.nome";
            
            return $pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar vendedores disponíveis', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Busca viradores que têm vendas disponíveis para comissão
     */
    public static function getViradoresDisponiveis(): array
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "SELECT DISTINCT v.virador_id as funcionario_id, f.nome
                    FROM vendas v
                    INNER JOIN funcionarios f ON f.id = v.virador_id
                    WHERE v.deleted_at IS NULL
                      AND v.vendedor_id != v.virador_id
                      AND NOT EXISTS (
                          SELECT 1 FROM comissoes c
                          WHERE c.venda_id = v.id
                            AND c.tipo_comissao = 'virador'
                            AND c.parcela LIKE '%Final%'
                      )
                    ORDER BY f.nome";
            
            return $pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar viradores disponíveis', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Busca vendas disponíveis para gerar comissão de um funcionário específico
     */
    public static function getVendasDisponiveis(int $funcionarioId, string $tipoComissao): array
    {
        try {
            $pdo = Database::getConnection();
            
            $campoId = $tipoComissao === 'vendedor' ? 'vendedor_id' : 'virador_id';
            
            $sql = "SELECT v.*, c.nome AS cliente_nome,
                           vend.nome AS vendedor_nome, vir.nome AS virador_nome,
                           (SELECT MAX(c2.numero_parcela) FROM comissoes c2 
                            WHERE c2.venda_id = v.id AND c2.tipo_comissao = :tipo_comissao_1) as ultima_parcela
                    FROM vendas v
                    INNER JOIN clientes c ON c.id = v.cliente_id
                    INNER JOIN funcionarios vend ON vend.id = v.vendedor_id
                    INNER JOIN funcionarios vir ON vir.id = v.virador_id
                    WHERE v.deleted_at IS NULL
                      AND v.{$campoId} = :funcionario_id
                      AND NOT EXISTS (
                          SELECT 1 FROM comissoes c3
                          WHERE c3.venda_id = v.id
                            AND c3.tipo_comissao = :tipo_comissao_2
                            AND c3.parcela LIKE '%Final%'
                      )
                    ORDER BY v.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':funcionario_id' => $funcionarioId,
                ':tipo_comissao_1' => $tipoComissao,
                ':tipo_comissao_2' => $tipoComissao
            ]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar vendas disponíveis para comissão', [
                'funcionarioId' => $funcionarioId,
                'tipoComissao' => $tipoComissao,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Busca funcionários que têm comissões geradas
     */
    public static function getFuncionariosComComissoes(): array
    {
        try {
            $pdo = Database::getConnection();
            
            $sql = "SELECT DISTINCT c.funcionario_id, f.nome,
                           COUNT(DISTINCT c.id) as total_comissoes,
                           SUM(c.valor_comissao) as total_valor,
                           MAX(c.created_at) as ultima_comissao
                    FROM comissoes c
                    INNER JOIN funcionarios f ON f.id = c.funcionario_id
                    WHERE f.is_ativo = 1
                    GROUP BY c.funcionario_id, f.nome
                    ORDER BY f.nome";
            
            return $pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar funcionários com comissões', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Busca todas as comissões de uma venda específica
     */
    public static function getByVenda(int $vendaId, ?string $tipoComissao = null): array
    {
        try {
            $pdo = Database::getConnection();
            
            $where = ["venda_id = :venda_id"];
            $params = [':venda_id' => $vendaId];
            
            if ($tipoComissao !== null) {
                $where[] = "tipo_comissao = :tipo_comissao";
                $params[':tipo_comissao'] = $tipoComissao;
            }
            
            $whereSql = implode(' AND ', $where);
            
            $sql = "SELECT c.*, f.nome as funcionario_nome
                    FROM comissoes c
                    INNER JOIN funcionarios f ON f.id = c.funcionario_id
                    WHERE {$whereSql}
                    ORDER BY tipo_comissao, numero_parcela";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao buscar comissões da venda', [
                'vendaId' => $vendaId,
                'tipoComissao' => $tipoComissao,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Calcula próxima parcela para uma venda e tipo de comissão
     */
    public static function getProximaParcela(int $vendaId, string $tipoComissao): array
    {
        try {
            $ultimaParcela = self::getUltimaParcela($vendaId, $tipoComissao);
            
            if (!$ultimaParcela) {
                return [
                    'numero' => 1,
                    'descricao' => 'Parcela 1'
                ];
            }
            
            $proximoNumero = $ultimaParcela['numero_parcela'] + 1;
            
            return [
                'numero' => $proximoNumero,
                'descricao' => "Parcela {$proximoNumero}"
            ];
        } catch (Exception $e) {
            require_once __DIR__ . '/../lib/Logger.php';
            Logger::error('Erro ao calcular próxima parcela', [
                'vendaId' => $vendaId,
                'tipoComissao' => $tipoComissao,
                'error' => $e->getMessage()
            ]);
            return [
                'numero' => 1,
                'descricao' => 'Parcela 1'
            ];
        }
    }
}
