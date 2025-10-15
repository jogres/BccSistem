<?php
// scripts/cleanup_logs.php
// Script para limpeza automática de logs antigos

require __DIR__ . '/../app/lib/Logger.php';

// Configurar
$daysToKeep = 30; // Manter logs dos últimos 30 dias
$runFromCommandLine = php_sapi_name() === 'cli';

if ($runFromCommandLine) {
    echo "🧹 Iniciando limpeza de logs...\n";
    echo "📅 Removendo logs mais antigos que {$daysToKeep} dias\n\n";
}

try {
    $deleted = Logger::cleanup($daysToKeep);
    
    if ($runFromCommandLine) {
        echo "✅ Limpeza concluída!\n";
        echo "🗑️ {$deleted} arquivos de log removidos\n";
    } else {
        echo "Limpeza de logs concluída. {$deleted} arquivos removidos.";
    }
    
    // Log da limpeza
    Logger::info("Limpeza automática de logs executada", [
        'days_kept' => $daysToKeep,
        'files_deleted' => $deleted
    ]);
    
} catch (Exception $e) {
    if ($runFromCommandLine) {
        echo "❌ Erro na limpeza: " . $e->getMessage() . "\n";
    } else {
        echo "Erro na limpeza: " . $e->getMessage();
    }
    
    Logger::error("Erro na limpeza de logs", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

if ($runFromCommandLine) {
    echo "\n📋 Estatísticas atuais:\n";
    $stats = Logger::getStats();
    foreach ($stats as $type => $count) {
        echo "   {$type}: {$count} registros\n";
    }
}
?>
