<?php
class Validator
{
    /**
     * Valida email brasileiro com domínios comuns
     */
    public static function validateEmail(string $email): array
    {
        if (empty($email)) {
            return ['valid' => false, 'message' => 'Email é obrigatório'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Formato de email inválido'];
        }
        
        // Verifica domínios temporários conhecidos
        $tempDomains = ['10minutemail.com', 'tempmail.org', 'guerrillamail.com'];
        $domain = substr(strrchr($email, "@"), 1);
        
        if (in_array($domain, $tempDomains)) {
            return ['valid' => false, 'message' => 'Email temporário não permitido'];
        }
        
        return ['valid' => true, 'message' => 'Email válido'];
    }
    
    /**
     * Valida telefone brasileiro
     */
    public static function validatePhone(string $phone): array
    {
        if (empty($phone)) {
            return ['valid' => false, 'message' => 'Telefone é obrigatório'];
        }
        
        // Remove caracteres não numéricos
        $cleanPhone = preg_replace('/\D/', '', $phone);
        
        // Telefone fixo: 10 dígitos (DDD + 8 dígitos)
        // Celular: 11 dígitos (DDD + 9 dígitos começando com 9)
        if (strlen($cleanPhone) === 10) {
            // Telefone fixo
            if (preg_match('/^[1-9]{2}[2-5][0-9]{7}$/', $cleanPhone)) {
                return ['valid' => true, 'message' => 'Telefone fixo válido'];
            }
        } elseif (strlen($cleanPhone) === 11) {
            // Celular
            if (preg_match('/^[1-9]{2}9[0-9]{8}$/', $cleanPhone)) {
                return ['valid' => true, 'message' => 'Celular válido'];
            }
        }
        
        return ['valid' => false, 'message' => 'Telefone inválido. Use formato (XX) XXXXX-XXXX ou (XX) XXXX-XXXX'];
    }
    
    /**
     * Formata telefone para exibição
     */
    public static function formatPhone(string $phone): string
    {
        $cleanPhone = preg_replace('/\D/', '', $phone);
        
        if (strlen($cleanPhone) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($cleanPhone, 0, 2),
                substr($cleanPhone, 2, 4),
                substr($cleanPhone, 6, 4)
            );
        } elseif (strlen($cleanPhone) === 11) {
            return sprintf('(%s) %s-%s', 
                substr($cleanPhone, 0, 2),
                substr($cleanPhone, 2, 5),
                substr($cleanPhone, 7, 4)
            );
        }
        
        return $phone;
    }
    
    /**
     * Valida CPF
     */
    public static function validateCPF(string $cpf): array
    {
        if (empty($cpf)) {
            return ['valid' => false, 'message' => 'CPF é obrigatório'];
        }
        
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) !== 11) {
            return ['valid' => false, 'message' => 'CPF deve ter 11 dígitos'];
        }
        
        // Verifica sequências inválidas
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return ['valid' => false, 'message' => 'CPF inválido'];
        }
        
        // Calcula primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;
        
        // Calcula segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;
        
        if (intval($cpf[9]) !== $digit1 || intval($cpf[10]) !== $digit2) {
            return ['valid' => false, 'message' => 'CPF inválido'];
        }
        
        return ['valid' => true, 'message' => 'CPF válido'];
    }
    
    /**
     * Formata CPF para exibição
     */
    public static function formatCPF(string $cpf): string
    {
        $cleanCPF = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cleanCPF) === 11) {
            return sprintf('%s.%s.%s-%s', 
                substr($cleanCPF, 0, 3),
                substr($cleanCPF, 3, 3),
                substr($cleanCPF, 6, 3),
                substr($cleanCPF, 9, 2)
            );
        }
        
        return $cpf;
    }
    
    /**
     * Valida CEP
     */
    public static function validateCEP(string $cep): array
    {
        if (empty($cep)) {
            return ['valid' => false, 'message' => 'CEP é obrigatório'];
        }
        
        $cleanCEP = preg_replace('/\D/', '', $cep);
        
        if (strlen($cleanCEP) !== 8) {
            return ['valid' => false, 'message' => 'CEP deve ter 8 dígitos'];
        }
        
        if (!preg_match('/^[0-9]{8}$/', $cleanCEP)) {
            return ['valid' => false, 'message' => 'CEP inválido'];
        }
        
        return ['valid' => true, 'message' => 'CEP válido'];
    }
    
    /**
     * Formata CEP para exibição
     */
    public static function formatCEP(string $cep): string
    {
        $cleanCEP = preg_replace('/\D/', '', $cep);
        
        if (strlen($cleanCEP) === 8) {
            return substr($cleanCEP, 0, 5) . '-' . substr($cleanCEP, 5, 3);
        }
        
        return $cep;
    }
    
    /**
     * Valida senha forte
     */
    public static function validatePassword(string $password): array
    {
        if (empty($password)) {
            return ['valid' => false, 'message' => 'Senha é obrigatória'];
        }
        
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Senha deve ter pelo menos 8 caracteres'];
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => 'Senha deve conter pelo menos uma letra maiúscula'];
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'message' => 'Senha deve conter pelo menos uma letra minúscula'];
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Senha deve conter pelo menos um número'];
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Senha deve conter pelo menos um caractere especial'];
        }
        
        return ['valid' => true, 'message' => 'Senha forte'];
    }
    
    /**
     * Valida nome completo
     */
    public static function validateFullName(string $name): array
    {
        if (empty($name)) {
            return ['valid' => false, 'message' => 'Nome é obrigatório'];
        }
        
        if (strlen(trim($name)) < 2) {
            return ['valid' => false, 'message' => 'Nome deve ter pelo menos 2 caracteres'];
        }
        
        if (strlen($name) > 120) {
            return ['valid' => false, 'message' => 'Nome muito longo (máximo 120 caracteres)'];
        }
        
        // Verifica se tem pelo menos nome e sobrenome
        $parts = explode(' ', trim($name));
        if (count($parts) < 2) {
            return ['valid' => false, 'message' => 'Digite nome completo'];
        }
        
        // Verifica caracteres válidos (letras, espaços, acentos)
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $name)) {
            return ['valid' => false, 'message' => 'Nome deve conter apenas letras e espaços'];
        }
        
        return ['valid' => true, 'message' => 'Nome válido'];
    }
}
