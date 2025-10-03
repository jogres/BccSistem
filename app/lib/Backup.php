<?php
class Backup
{
    private const BACKUP_DIR = __DIR__ . '/../../backups/';
    private const MAX_BACKUPS = 30; // Manter 30 backups
    private const COMPRESSION_LEVEL = 6;
    
    /**
     * Cria backup completo do sistema
     */
    public static function createFullBackup(): array
    {
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "bcc_backup_{$timestamp}";
        $backupPath = self::BACKUP_DIR . $backupName;
        
        try {
            // Criar diretório se não existir
            if (!is_dir(self::BACKUP_DIR)) {
                mkdir(self::BACKUP_DIR, 0755, true);
            }
            
            // Criar diretório do backup
            mkdir($backupPath, 0755, true);
            
            $results = [];
            
            // 1. Backup do banco de dados
            $results['database'] = self::backupDatabase($backupPath);
            
            // 2. Backup dos arquivos de configuração
            $results['config'] = self::backupConfigFiles($backupPath);
            
            // 3. Backup dos uploads (se existirem)
            $results['uploads'] = self::backupUploads($backupPath);
            
            // 4. Criar arquivo de metadados
            $results['metadata'] = self::createMetadata($backupPath, $results);
            
            // 5. Compactar backup
            $results['compression'] = self::compressBackup($backupPath, $backupName);
            
            // 6. Limpar backups antigos
            self::cleanupOldBackups();
            
            return [
                'success' => true,
                'backup_name' => $backupName,
                'backup_path' => $backupPath,
                'results' => $results,
                'size' => self::getBackupSize($backupPath . '.tar.gz')
            ];
            
        } catch (Exception $e) {
            // Limpar em caso de erro
            if (is_dir($backupPath)) {
                self::deleteDirectory($backupPath);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Backup do banco de dados
     */
    private static function backupDatabase(string $backupPath): array
    {
        $config = require __DIR__ . '/../config/config.php';
        $dbConfig = $config['db'];
        
        $filename = $backupPath . '/database.sql';
        
        // Comando mysqldump
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['user']),
            escapeshellarg($dbConfig['pass']),
            escapeshellarg($dbConfig['dbname']),
            escapeshellarg($filename)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Erro no backup do banco: ' . implode("\n", $output));
        }
        
        return [
            'success' => true,
            'file' => $filename,
            'size' => filesize($filename)
        ];
    }
    
    /**
     * Backup dos arquivos de configuração
     */
    private static function backupConfigFiles(string $backupPath): array
    {
        $configDir = $backupPath . '/config';
        mkdir($configDir, 0755, true);
        
        $files = [
            __DIR__ . '/../config/config.php',
            __DIR__ . '/../config/interesses.php'
        ];
        
        $copied = [];
        foreach ($files as $file) {
            if (file_exists($file)) {
                $filename = basename($file);
                $dest = $configDir . '/' . $filename;
                if (copy($file, $dest)) {
                    $copied[] = $filename;
                }
            }
        }
        
        return [
            'success' => true,
            'files' => $copied,
            'count' => count($copied)
        ];
    }
    
    /**
     * Backup dos uploads
     */
    private static function backupUploads(string $backupPath): array
    {
        $uploadsDir = __DIR__ . '/../../public/uploads';
        $backupUploadsDir = $backupPath . '/uploads';
        
        if (!is_dir($uploadsDir)) {
            return [
                'success' => true,
                'message' => 'Diretório de uploads não existe'
            ];
        }
        
        if (!self::copyDirectory($uploadsDir, $backupUploadsDir)) {
            throw new Exception('Erro ao fazer backup dos uploads');
        }
        
        return [
            'success' => true,
            'files_count' => self::countFiles($backupUploadsDir)
        ];
    }
    
    /**
     * Cria arquivo de metadados
     */
    private static function createMetadata(string $backupPath, array $results): array
    {
        $metadata = [
            'created_at' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'php_version' => PHP_VERSION,
            'system' => [
                'os' => PHP_OS,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
            ],
            'database' => [
                'backed_up' => $results['database']['success'] ?? false,
                'size' => $results['database']['size'] ?? 0
            ],
            'files' => [
                'config' => $results['config']['count'] ?? 0,
                'uploads' => $results['uploads']['files_count'] ?? 0
            ]
        ];
        
        $filename = $backupPath . '/metadata.json';
        file_put_contents($filename, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return [
            'success' => true,
            'file' => $filename
        ];
    }
    
    /**
     * Compacta o backup
     */
    private static function compressBackup(string $backupPath, string $backupName): array
    {
        $archivePath = self::BACKUP_DIR . $backupName . '.tar.gz';
        
        // Usar tar para compactar
        $command = sprintf(
            'tar -czf %s -C %s . 2>&1',
            escapeshellarg($archivePath),
            escapeshellarg(dirname($backupPath))
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Erro ao compactar backup: ' . implode("\n", $output));
        }
        
        // Remover diretório não compactado
        self::deleteDirectory($backupPath);
        
        return [
            'success' => true,
            'archive' => $archivePath,
            'size' => filesize($archivePath)
        ];
    }
    
    /**
     * Lista backups disponíveis
     */
    public static function listBackups(): array
    {
        if (!is_dir(self::BACKUP_DIR)) {
            return [];
        }
        
        $backups = [];
        $files = glob(self::BACKUP_DIR . 'bcc_backup_*.tar.gz');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'size_human' => self::formatBytes(filesize($file))
            ];
        }
        
        // Ordenar por data de criação (mais recente primeiro)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }
    
    /**
     * Restaura backup
     */
    public static function restoreBackup(string $backupFilename): array
    {
        $backupPath = self::BACKUP_DIR . $backupFilename;
        
        if (!file_exists($backupPath)) {
            return [
                'success' => false,
                'error' => 'Arquivo de backup não encontrado'
            ];
        }
        
        try {
            $tempDir = self::BACKUP_DIR . 'temp_restore_' . uniqid();
            mkdir($tempDir, 0755, true);
            
            // Extrair backup
            $command = sprintf(
                'tar -xzf %s -C %s 2>&1',
                escapeshellarg($backupPath),
                escapeshellarg($tempDir)
            );
            
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('Erro ao extrair backup: ' . implode("\n", $output));
            }
            
            // Encontrar diretório extraído
            $extractedDirs = glob($tempDir . '/bcc_backup_*');
            if (empty($extractedDirs)) {
                throw new Exception('Estrutura de backup inválida');
            }
            
            $extractedDir = $extractedDirs[0];
            
            // Restaurar banco de dados
            $dbFile = $extractedDir . '/database.sql';
            if (file_exists($dbFile)) {
                self::restoreDatabase($dbFile);
            }
            
            // Restaurar arquivos de configuração
            $configDir = $extractedDir . '/config';
            if (is_dir($configDir)) {
                self::restoreConfigFiles($configDir);
            }
            
            // Restaurar uploads
            $uploadsDir = $extractedDir . '/uploads';
            if (is_dir($uploadsDir)) {
                self::restoreUploads($uploadsDir);
            }
            
            // Limpar arquivos temporários
            self::deleteDirectory($tempDir);
            
            return [
                'success' => true,
                'message' => 'Backup restaurado com sucesso'
            ];
            
        } catch (Exception $e) {
            // Limpar em caso de erro
            if (isset($tempDir) && is_dir($tempDir)) {
                self::deleteDirectory($tempDir);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restaura banco de dados
     */
    private static function restoreDatabase(string $sqlFile): void
    {
        $config = require __DIR__ . '/../config/config.php';
        $dbConfig = $config['db'];
        
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['user']),
            escapeshellarg($dbConfig['pass']),
            escapeshellarg($dbConfig['dbname']),
            escapeshellarg($sqlFile)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Erro ao restaurar banco: ' . implode("\n", $output));
        }
    }
    
    /**
     * Restaura arquivos de configuração
     */
    private static function restoreConfigFiles(string $configDir): void
    {
        $files = glob($configDir . '/*');
        foreach ($files as $file) {
            $filename = basename($file);
            $dest = __DIR__ . '/../config/' . $filename;
            copy($file, $dest);
        }
    }
    
    /**
     * Restaura uploads
     */
    private static function restoreUploads(string $uploadsDir): void
    {
        $destDir = __DIR__ . '/../../public/uploads';
        
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        
        self::copyDirectory($uploadsDir, $destDir);
    }
    
    /**
     * Remove backups antigos
     */
    private static function cleanupOldBackups(): void
    {
        $backups = self::listBackups();
        
        if (count($backups) > self::MAX_BACKUPS) {
            $toDelete = array_slice($backups, self::MAX_BACKUPS);
            
            foreach ($toDelete as $backup) {
                unlink($backup['path']);
            }
        }
    }
    
    /**
     * Utilitários
     */
    private static function copyDirectory(string $src, string $dst): bool
    {
        if (!is_dir($src)) {
            return false;
        }
        
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;
            
            if (is_dir($srcFile)) {
                if (!self::copyDirectory($srcFile, $dstFile)) {
                    return false;
                }
            } else {
                if (!copy($srcFile, $dstFile)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    private static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    private static function countFiles(string $dir): int
    {
        $count = 0;
        $files = glob($dir . '/*');
        
        foreach ($files as $file) {
            if (is_dir($file)) {
                $count += self::countFiles($file);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
    
    private static function getBackupSize(string $file): string
    {
        return file_exists($file) ? self::formatBytes(filesize($file)) : '0 B';
    }
    
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
