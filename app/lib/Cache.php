<?php
class Cache
{
    private static ?Redis $redis = null;
    private static bool $enabled = true;
    
    /**
     * Inicializa conexão Redis
     */
    public static function init(): void
    {
        if (!extension_loaded('redis')) {
            self::$enabled = false;
            return;
        }
        
        try {
            self::$redis = new Redis();
            self::$redis->connect('127.0.0.1', 6379);
            self::$redis->select(0); // Database 0
        } catch (Exception $e) {
            self::$enabled = false;
            error_log("Redis connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica se cache está disponível
     */
    public static function isEnabled(): bool
    {
        return self::$enabled && self::$redis !== null;
    }
    
    /**
     * Armazena valor no cache
     */
    public static function set(string $key, $value, int $ttl = 3600): bool
    {
        if (!self::isEnabled()) {
            return false;
        }
        
        try {
            $serialized = serialize($value);
            return self::$redis->setex($key, $ttl, $serialized);
        } catch (Exception $e) {
            error_log("Cache set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recupera valor do cache
     */
    public static function get(string $key, $default = null)
    {
        if (!self::isEnabled()) {
            return $default;
        }
        
        try {
            $value = self::$redis->get($key);
            if ($value === false) {
                return $default;
            }
            return unserialize($value);
        } catch (Exception $e) {
            error_log("Cache get error: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Remove valor do cache
     */
    public static function delete(string $key): bool
    {
        if (!self::isEnabled()) {
            return false;
        }
        
        try {
            return self::$redis->del($key) > 0;
        } catch (Exception $e) {
            error_log("Cache delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove múltiplas chaves
     */
    public static function deletePattern(string $pattern): int
    {
        if (!self::isEnabled()) {
            return 0;
        }
        
        try {
            $keys = self::$redis->keys($pattern);
            if (empty($keys)) {
                return 0;
            }
            return self::$redis->del($keys);
        } catch (Exception $e) {
            error_log("Cache delete pattern error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica se chave existe
     */
    public static function exists(string $key): bool
    {
        if (!self::isEnabled()) {
            return false;
        }
        
        try {
            return self::$redis->exists($key) > 0;
        } catch (Exception $e) {
            error_log("Cache exists error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Define TTL para chave existente
     */
    public static function expire(string $key, int $ttl): bool
    {
        if (!self::isEnabled()) {
            return false;
        }
        
        try {
            return self::$redis->expire($key, $ttl);
        } catch (Exception $e) {
            error_log("Cache expire error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Incrementa valor numérico
     */
    public static function increment(string $key, int $value = 1): int
    {
        if (!self::isEnabled()) {
            return 0;
        }
        
        try {
            return self::$redis->incrBy($key, $value);
        } catch (Exception $e) {
            error_log("Cache increment error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Cache com callback (padrão comum)
     */
    public static function remember(string $key, callable $callback, int $ttl = 3600)
    {
        $value = self::get($key);
        
        if ($value === null) {
            $value = $callback();
            self::set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Cache para queries de dashboard
     */
    public static function getDashboardData(string $mode, string $start, string $end, array $userIds = []): ?array
    {
        $key = "dashboard:{$mode}:" . md5($start . $end . implode(',', $userIds));
        return self::get($key);
    }
    
    /**
     * Cache para dados de dashboard
     */
    public static function setDashboardData(string $mode, string $start, string $end, array $userIds, array $data, int $ttl = 300): bool
    {
        $key = "dashboard:{$mode}:" . md5($start . $end . implode(',', $userIds));
        return self::set($key, $data, $ttl);
    }
    
    /**
     * Invalida cache de dashboard
     */
    public static function invalidateDashboard(): void
    {
        self::deletePattern("dashboard:*");
    }
    
    /**
     * Cache para lista de clientes
     */
    public static function getClientsList(array $filters = []): ?array
    {
        $key = "clients:" . md5(serialize($filters));
        return self::get($key);
    }
    
    /**
     * Cache para lista de clientes
     */
    public static function setClientsList(array $filters, array $data, int $ttl = 180): bool
    {
        $key = "clients:" . md5(serialize($filters));
        return self::set($key, $data, $ttl);
    }
    
    /**
     * Invalida cache de clientes
     */
    public static function invalidateClients(): void
    {
        self::deletePattern("clients:*");
    }
    
    /**
     * Cache para estatísticas gerais
     */
    public static function getStats(): ?array
    {
        return self::get('stats:general');
    }
    
    /**
     * Cache para estatísticas gerais
     */
    public static function setStats(array $stats, int $ttl = 600): bool
    {
        return self::set('stats:general', $stats, $ttl);
    }
    
    /**
     * Invalida todas as estatísticas
     */
    public static function invalidateStats(): void
    {
        self::deletePattern("stats:*");
    }
    
    /**
     * Limpa todo o cache
     */
    public static function flush(): bool
    {
        if (!self::isEnabled()) {
            return false;
        }
        
        try {
            return self::$redis->flushDB();
        } catch (Exception $e) {
            error_log("Cache flush error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém informações do cache
     */
    public static function info(): array
    {
        if (!self::isEnabled()) {
            return ['enabled' => false];
        }
        
        try {
            $info = self::$redis->info();
            return [
                'enabled' => true,
                'connected' => true,
                'memory_used' => $info['used_memory_human'] ?? 'unknown',
                'keys' => self::$redis->dbSize(),
                'uptime' => $info['uptime_in_seconds'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'enabled' => true,
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Inicializa automaticamente
Cache::init();
