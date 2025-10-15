<?php
// app/lib/ErrorHandler.php
// Sistema de tratamento de erros e mensagens para o usuário

require_once __DIR__ . '/Logger.php';

class ErrorHandler {
    private static $errorMessages = [
        // Erros de banco de dados
        'database_connection' => 'Não foi possível conectar ao banco de dados. Verifique as configurações.',
        'database_query' => 'Erro ao executar consulta no banco de dados.',
        'database_constraint' => 'Violação de integridade dos dados. Verifique se os dados estão corretos.',
        'database_not_found' => 'Registro não encontrado.',
        'database_duplicate' => 'Este registro já existe no sistema.',
        
        // Erros de autenticação
        'auth_required' => 'Você precisa estar logado para acessar esta página.',
        'auth_invalid' => 'Login ou senha incorretos.',
        'auth_expired' => 'Sua sessão expirou. Faça login novamente.',
        'auth_insufficient_permissions' => 'Você não tem permissão para realizar esta ação.',
        
        // Erros de validação
        'validation_required' => 'Este campo é obrigatório.',
        'validation_email' => 'Digite um e-mail válido.',
        'validation_phone' => 'Digite um telefone válido.',
        'validation_cpf' => 'Digite um CPF válido.',
        'validation_cep' => 'Digite um CEP válido.',
        'validation_min_length' => 'Este campo deve ter pelo menos {min} caracteres.',
        'validation_max_length' => 'Este campo deve ter no máximo {max} caracteres.',
        'validation_numeric' => 'Este campo deve conter apenas números.',
        'validation_positive' => 'Este valor deve ser maior que zero.',
        
        // Erros de arquivo
        'file_not_found' => 'Arquivo não encontrado.',
        'file_upload_failed' => 'Falha no upload do arquivo.',
        'file_too_large' => 'Arquivo muito grande. Tamanho máximo: {max_size}MB.',
        'file_invalid_type' => 'Tipo de arquivo não permitido. Tipos aceitos: {allowed_types}.',
        'file_permission_denied' => 'Sem permissão para acessar este arquivo.',
        
        // Erros de sistema
        'system_error' => 'Ocorreu um erro interno do sistema. Tente novamente.',
        'system_maintenance' => 'Sistema em manutenção. Tente novamente mais tarde.',
        'system_configuration' => 'Erro de configuração do sistema.',
        
        // Erros específicos do sistema BCC
        'client_not_found' => 'Cliente não encontrado.',
        'client_already_exists' => 'Já existe um cliente com estes dados.',
        'employee_not_found' => 'Funcionário não encontrado.',
        'employee_inactive' => 'Funcionário inativo.',
        'sale_not_found' => 'Venda não encontrada.',
        'sale_permission_denied' => 'Você não tem permissão para acessar esta venda.',
        'contract_already_exists' => 'Já existe uma venda com este número de contrato.',
        'invalid_sale_data' => 'Dados da venda inválidos.',
        
        // Erros de API
        'api_endpoint_not_found' => 'Endpoint da API não encontrado.',
        'api_invalid_request' => 'Requisição inválida para a API.',
        'api_rate_limit' => 'Muitas requisições. Tente novamente em alguns minutos.',
        
        // Erros de notificação
        'notification_failed' => 'Falha ao enviar notificação.',
        'notification_not_found' => 'Notificação não encontrada.',
    ];
    
    private static $successMessages = [
        'login_success' => 'Login realizado com sucesso!',
        'logout_success' => 'Logout realizado com sucesso!',
        'client_created' => 'Cliente cadastrado com sucesso!',
        'client_updated' => 'Cliente atualizado com sucesso!',
        'client_deleted' => 'Cliente excluído com sucesso!',
        'employee_created' => 'Funcionário cadastrado com sucesso!',
        'employee_updated' => 'Funcionário atualizado com sucesso!',
        'sale_created' => 'Venda cadastrada com sucesso!',
        'sale_updated' => 'Venda atualizada com sucesso!',
        'sale_deleted' => 'Venda excluída com sucesso!',
        'file_uploaded' => 'Arquivo enviado com sucesso!',
        'data_exported' => 'Dados exportados com sucesso!',
        'settings_saved' => 'Configurações salvas com sucesso!',
        'password_changed' => 'Senha alterada com sucesso!',
        'notification_sent' => 'Notificação enviada com sucesso!',
    ];
    
    private static $warningMessages = [
        'data_not_saved' => 'Dados não foram salvos. Verifique os campos obrigatórios.',
        'session_expiring' => 'Sua sessão está expirando em breve.',
        'backup_recommended' => 'Recomenda-se fazer backup dos dados.',
        'system_update_available' => 'Atualização do sistema disponível.',
        'disk_space_low' => 'Espaço em disco baixo.',
    ];
    
    /**
     * Obter mensagem de erro
     */
    public static function getErrorMessage($key, $params = []) {
        if (!isset(self::$errorMessages[$key])) {
            return "Erro desconhecido: {$key}";
        }
        
        $message = self::$errorMessages[$key];
        
        // Substituir parâmetros na mensagem
        foreach ($params as $param => $value) {
            $message = str_replace("{{$param}}", $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Obter mensagem de sucesso
     */
    public static function getSuccessMessage($key, $params = []) {
        if (!isset(self::$successMessages[$key])) {
            return "Operação realizada com sucesso!";
        }
        
        $message = self::$successMessages[$key];
        
        // Substituir parâmetros na mensagem
        foreach ($params as $param => $value) {
            $message = str_replace("{{$param}}", $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Obter mensagem de aviso
     */
    public static function getWarningMessage($key, $params = []) {
        if (!isset(self::$warningMessages[$key])) {
            return "Aviso: {$key}";
        }
        
        $message = self::$warningMessages[$key];
        
        // Substituir parâmetros na mensagem
        foreach ($params as $param => $value) {
            $message = str_replace("{{$param}}", $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Definir mensagem de erro na sessão
     */
    public static function setError($key, $params = [], $customMessage = null) {
        $message = $customMessage ?: self::getErrorMessage($key, $params);
        $_SESSION['error'] = $message;
        
        // Log do erro
        Logger::error($message, [
            'error_key' => $key,
            'params' => $params,
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        
        return $message;
    }
    
    /**
     * Definir mensagem de sucesso na sessão
     */
    public static function setSuccess($key, $params = [], $customMessage = null) {
        $message = $customMessage ?: self::getSuccessMessage($key, $params);
        $_SESSION['success'] = $message;
        
        // Log da ação
        Logger::action("Sucesso: {$message}", $_SESSION['user_id'] ?? null, [
            'success_key' => $key,
            'params' => $params
        ]);
        
        return $message;
    }
    
    /**
     * Definir mensagem de aviso na sessão
     */
    public static function setWarning($key, $params = [], $customMessage = null) {
        $message = $customMessage ?: self::getWarningMessage($key, $params);
        $_SESSION['warning'] = $message;
        
        // Log do aviso
        Logger::warning($message, [
            'warning_key' => $key,
            'params' => $params,
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        
        return $message;
    }
    
    /**
     * Obter e limpar mensagem da sessão
     */
    public static function getAndClearMessage($type = 'error') {
        $key = $type; // error, success, warning
        $message = $_SESSION[$key] ?? null;
        
        if ($message) {
            unset($_SESSION[$key]);
        }
        
        return $message;
    }
    
    /**
     * Tratar exceção e gerar mensagem de erro
     */
    public static function handleException($exception, $context = []) {
        $errorMessage = self::getErrorMessage('system_error');
        $context['exception'] = $exception->getMessage();
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();
        
        // Log detalhado do erro
        Logger::error($exception->getMessage(), $context, $exception->getFile(), $exception->getLine());
        
        // Definir mensagem na sessão
        self::setError('system_error', [], $errorMessage);
        
        return $errorMessage;
    }
    
    /**
     * Validar dados e retornar erros
     */
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Campo obrigatório
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = self::getErrorMessage('validation_required');
                continue;
            }
            
            // Se campo está vazio e não é obrigatório, pular validações
            if (empty($value)) {
                continue;
            }
            
            // Validação de e-mail
            if (isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = self::getErrorMessage('validation_email');
            }
            
            // Validação de telefone (básica)
            if (isset($rule['phone']) && $rule['phone'] && !preg_match('/^[\d\s\(\)\-\+]+$/', $value)) {
                $errors[$field] = self::getErrorMessage('validation_phone');
            }
            
            // Validação de CPF (básica)
            if (isset($rule['cpf']) && $rule['cpf'] && !self::validateCPF($value)) {
                $errors[$field] = self::getErrorMessage('validation_cpf');
            }
            
            // Validação de CEP (básica)
            if (isset($rule['cep']) && $rule['cep'] && !preg_match('/^\d{5}-?\d{3}$/', $value)) {
                $errors[$field] = self::getErrorMessage('validation_cep');
            }
            
            // Validação de tamanho mínimo
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = self::getErrorMessage('validation_min_length', ['min' => $rule['min_length']]);
            }
            
            // Validação de tamanho máximo
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = self::getErrorMessage('validation_max_length', ['max' => $rule['max_length']]);
            }
            
            // Validação numérica
            if (isset($rule['numeric']) && $rule['numeric'] && !is_numeric($value)) {
                $errors[$field] = self::getErrorMessage('validation_numeric');
            }
            
            // Validação de valor positivo
            if (isset($rule['positive']) && $rule['positive'] && $value <= 0) {
                $errors[$field] = self::getErrorMessage('validation_positive');
            }
        }
        
        return $errors;
    }
    
    /**
     * Validar CPF
     */
    private static function validateCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Exibir mensagem formatada
     */
    public static function displayMessage($message, $type = 'error') {
        $class = '';
        $icon = '';
        
        switch ($type) {
            case 'success':
                $class = 'alert alert-success';
                $icon = '✅';
                break;
            case 'warning':
                $class = 'alert alert-warning';
                $icon = '⚠️';
                break;
            default:
                $class = 'alert alert-danger';
                $icon = '❌';
                break;
        }
        
        return "<div class='{$class}'>
                    <strong>{$icon}</strong> {$message}
                </div>";
    }
    
    /**
     * Obter todas as mensagens disponíveis
     */
    public static function getAllMessages() {
        return [
            'errors' => self::$errorMessages,
            'success' => self::$successMessages,
            'warnings' => self::$warningMessages
        ];
    }
}
?>
