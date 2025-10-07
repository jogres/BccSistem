<?php

class PasswordReset
{
    /**
     * Valida um token de redefinição de senha
     * 
     * @param string $token
     * @return array|null Dados do usuário se válido, null se inválido
     */
    public static function validateToken($token)
    {
        $db = Database::getConnection();
        
        // Busca o token que não expirou
        $stmt = $db->prepare("
            SELECT pr.*, f.nome, f.login 
            FROM password_resets pr
            JOIN funcionarios f ON pr.funcionario_id = f.id
            WHERE pr.token = ? 
            AND pr.expira_em > NOW()
            AND pr.usado = 0
        ");
        $stmt->execute([$token]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Redefine a senha do usuário
     * 
     * @param string $token
     * @param string $newPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public static function resetPassword($token, $newPassword)
    {
        $db = Database::getConnection();
        
        // Valida o token novamente
        $resetData = self::validateToken($token);
        if (!$resetData) {
            return [
                'success' => false,
                'message' => 'Token inválido ou expirado'
            ];
        }
        
        try {
            $db->beginTransaction();
            
            // Atualiza a senha do funcionário
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                UPDATE funcionarios 
                SET senha_hash = ? 
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $resetData['funcionario_id']]);
            
            // Marca o token como usado
            $stmt = $db->prepare("
                UPDATE password_resets 
                SET usado = 1 
                WHERE token = ?
            ");
            $stmt->execute([$token]);
            
            $db->commit();
            
            return [
                'success' => true,
                'message' => 'Senha redefinida com sucesso!'
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Erro ao redefinir senha: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Solicita redefinição de senha para um login
     * 
     * @param string $login
     * @return array ['success' => bool, 'message' => string, 'reset_link' => string, 'user_name' => string]
     */
    public static function requestReset($login)
    {
        $db = Database::getConnection();
        
        // Busca o funcionário pelo login
        $stmt = $db->prepare("SELECT id, nome, login FROM funcionarios WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Login não encontrado no sistema'
            ];
        }
        
        // Cria o token
        $token = self::createToken($user['id']);
        
        // Gera o link de redefinição
        $resetLink = base_url('reset_password.php') . '?token=' . $token;
        
        return [
            'success' => true,
            'message' => 'Link de redefinição gerado com sucesso',
            'reset_link' => $resetLink,
            'user_name' => $user['nome']
        ];
    }
    
    /**
     * Cria um novo token de redefinição de senha
     * 
     * @param int $funcionarioId
     * @return string O token gerado
     */
    public static function createToken($funcionarioId)
    {
        $db = Database::getConnection();
        
        // Gera token único
        $token = bin2hex(random_bytes(32));
        
        // Expira em 1 hora
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $db->prepare("
            INSERT INTO password_resets (funcionario_id, token, expira_em, criado_em)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$funcionarioId, $token, $expiresAt]);
        
        return $token;
    }
    
    /**
     * Remove tokens expirados
     */
    public static function cleanupExpiredTokens()
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            DELETE FROM password_resets 
            WHERE expira_em < NOW() OR usado = 1
        ");
        $stmt->execute();
    }
}
