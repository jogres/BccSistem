<?php
class FileUpload
{
    private const UPLOAD_DIR = __DIR__ . '/../../public/uploads/';
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    
    /**
     * Faz upload de arquivo
     */
    public static function upload(array $file, string $subfolder = '', bool $renameFile = true): array
    {
        try {
            // Validações básicas
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return ['success' => false, 'error' => 'Arquivo inválido'];
            }
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'error' => self::getUploadErrorMessage($file['error'])];
            }
            
            // Verificar tamanho
            if ($file['size'] > self::MAX_FILE_SIZE) {
                return ['success' => false, 'error' => 'Arquivo muito grande (máximo 5MB)'];
            }
            
            // Verificar extensão
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                return ['success' => false, 'error' => 'Tipo de arquivo não permitido'];
            }
            
            // Criar diretório se necessário
            $uploadPath = self::UPLOAD_DIR . $subfolder;
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Gerar nome único
            $filename = $renameFile 
                ? uniqid() . '.' . $extension
                : self::sanitizeFilename($file['name']);
            
            $fullPath = $uploadPath . '/' . $filename;
            
            // Mover arquivo
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => $fullPath,
                    'url' => base_url('uploads/' . $subfolder . '/' . $filename),
                    'size' => $file['size'],
                    'extension' => $extension
                ];
            } else {
                return ['success' => false, 'error' => 'Erro ao salvar arquivo'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()];
        }
    }
    
    /**
     * Upload múltiplos arquivos
     */
    public static function uploadMultiple(array $files, string $subfolder = ''): array
    {
        $results = [];
        
        foreach ($files['tmp_name'] as $key => $tmpName) {
            if (empty($tmpName)) continue;
            
            $file = [
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $tmpName,
                'error' => $files['error'][$key],
                'size' => $files['size'][$key]
            ];
            
            $results[] = self::upload($file, $subfolder);
        }
        
        return $results;
    }
    
    /**
     * Remove arquivo
     */
    public static function delete(string $filename, string $subfolder = ''): bool
    {
        $filePath = self::UPLOAD_DIR . $subfolder . '/' . $filename;
        
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
    
    /**
     * Lista arquivos em diretório
     */
    public static function listFiles(string $subfolder = ''): array
    {
        $dir = self::UPLOAD_DIR . $subfolder;
        
        if (!is_dir($dir)) {
            return [];
        }
        
        $files = [];
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $filePath = $dir . '/' . $item;
            
            if (is_file($filePath)) {
                $files[] = [
                    'filename' => $item,
                    'path' => $filePath,
                    'url' => base_url('uploads/' . $subfolder . '/' . $item),
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                    'extension' => strtolower(pathinfo($item, PATHINFO_EXTENSION))
                ];
            }
        }
        
        // Ordenar por data de modificação (mais recente primeiro)
        usort($files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $files;
    }
    
    /**
     * Valida imagem
     */
    public static function validateImage(string $filePath): bool
    {
        $imageInfo = getimagesize($filePath);
        
        if ($imageInfo === false) {
            return false;
        }
        
        // Verificar se é realmente uma imagem
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
        
        return in_array($imageInfo[2], $allowedTypes);
    }
    
    /**
     * Redimensiona imagem
     */
    public static function resizeImage(string $filePath, int $maxWidth = 800, int $maxHeight = 600): bool
    {
        if (!self::validateImage($filePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($filePath);
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Se a imagem já é menor que o máximo, não redimensionar
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }
        
        // Calcular novas dimensões mantendo proporção
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        // Criar imagem de origem
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filePath);
                break;
            default:
                return false;
        }
        
        // Criar nova imagem redimensionada
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparência para PNG e GIF
        if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefill($resized, 0, 0, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Salvar imagem redimensionada
        $result = false;
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($resized, $filePath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($resized, $filePath, 8);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($resized, $filePath);
                break;
        }
        
        // Limpar memória
        imagedestroy($source);
        imagedestroy($resized);
        
        return $result;
    }
    
    /**
     * Gera thumbnail
     */
    public static function generateThumbnail(string $filePath, int $thumbSize = 150): ?string
    {
        if (!self::validateImage($filePath)) {
            return null;
        }
        
        $imageInfo = getimagesize($filePath);
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Criar imagem de origem
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filePath);
                break;
            default:
                return null;
        }
        
        // Calcular dimensões do thumbnail (quadrado)
        $thumbWidth = $thumbSize;
        $thumbHeight = $thumbSize;
        
        // Determinar se cortar ou redimensionar
        if ($width > $height) {
            $cropX = ($width - $height) / 2;
            $cropY = 0;
            $cropSize = $height;
        } else {
            $cropX = 0;
            $cropY = ($height - $width) / 2;
            $cropSize = $width;
        }
        
        // Criar thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preservar transparência
        if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }
        
        // Cortar e redimensionar
        imagecopyresampled($thumbnail, $source, 0, 0, $cropX, $cropY, $thumbWidth, $thumbHeight, $cropSize, $cropSize);
        
        // Salvar thumbnail
        $thumbPath = dirname($filePath) . '/thumb_' . basename($filePath);
        $result = false;
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($thumbnail, $thumbPath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($thumbnail, $thumbPath, 8);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($thumbnail, $thumbPath);
                break;
        }
        
        // Limpar memória
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return $result ? $thumbPath : null;
    }
    
    /**
     * Utilitários
     */
    private static function getUploadErrorMessage(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Arquivo muito grande';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload incompleto';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo enviado';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Diretório temporário não encontrado';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Erro ao escrever arquivo';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload bloqueado por extensão';
            default:
                return 'Erro desconhecido';
        }
    }
    
    private static function sanitizeFilename(string $filename): string
    {
        // Remove caracteres especiais
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove múltiplos underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Remove underscores do início e fim
        $filename = trim($filename, '_');
        
        return $filename;
    }
    
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
