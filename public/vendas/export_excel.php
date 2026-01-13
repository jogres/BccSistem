<?php
// public/vendas/export_excel.php
declare(strict_types=1);

// Desabilitar display de erros para evitar que quebrem o arquivo
ini_set('display_errors', '0');
error_reporting(0);

// Iniciar buffer de saída para evitar que erros quebrem o download
if (!ob_get_level()) {
    ob_start();
}

// PhpSpreadsheet (via Composer) - Carregar primeiro e garantir que funciona
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('Erro: autoload.php não encontrado. Execute: composer install');
}

// Carregar e executar o autoload explicitamente
$loader = require_once $autoloadPath;

// Garantir que o autoloader está registrado
if (!$loader instanceof \Composer\Autoload\ClassLoader) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('Erro: Autoloader não foi inicializado corretamente');
}

// Forçar carregamento manual da classe se necessário (workaround para Windows)
if (!class_exists('Composer\Pcre\Preg', false)) {
    $pregPath = __DIR__ . '/../../vendor/composer/pcre/src/Preg.php';
    $pregPathReal = realpath($pregPath);
    
    if ($pregPathReal && file_exists($pregPathReal)) {
        require_once $pregPathReal;
    } else {
        $altPath = dirname(__DIR__, 2) . '/vendor/composer/pcre/src/Preg.php';
        $altPathReal = realpath($altPath);
        if ($altPathReal && file_exists($altPathReal)) {
            require_once $altPathReal;
        }
    }
}

// Registrar autoloader manual como fallback
spl_autoload_register(function($class) {
    if (strpos($class, 'Composer\\Pcre\\') === 0) {
        $basePath = dirname(__DIR__, 2) . '/vendor/composer/pcre/src/';
        $file = $basePath . str_replace('\\', '/', substr($class, 15)) . '.php';
        $realPath = realpath($file);
        if ($realPath && file_exists($realPath)) {
            require_once $realPath;
            return true;
        }
    }
    return false;
}, true, true);

// Verificar novamente se a classe está disponível após todas as tentativas
if (!class_exists('Composer\Pcre\Preg', false)) {
    $baseDir = dirname(__DIR__, 2);
    $possiblePaths = [
        $baseDir . '/vendor/composer/pcre/src/Preg.php',
        __DIR__ . '/../../vendor/composer/pcre/src/Preg.php',
        realpath($baseDir . '/vendor/composer/pcre/src/Preg.php'),
    ];
    
    $loaded = false;
    foreach ($possiblePaths as $path) {
        if ($path && file_exists($path)) {
            require_once $path;
            if (class_exists('Composer\Pcre\Preg', false)) {
                $loaded = true;
                break;
            }
        }
    }
    
    if (!$loaded) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        die('Erro: Classe Composer\Pcre\Preg não encontrada. Execute: composer dump-autoload');
    }
}

// Verificar autenticação antes de carregar classes que podem gerar output
require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';

// Verificar autenticação manualmente para evitar headers de redirecionamento
try {
    Auth::startSessionSecure();
    if (!Auth::check()) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die('Acesso negado. Faça login primeiro.');
    }
} catch (Exception $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('Erro de autenticação: ' . $e->getMessage());
}

require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/models/Venda.php';

// Somente ADMIN
if (!Auth::isAdmin()) {
  while (ob_get_level() > 0) {
    ob_end_clean();
  }
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  die('Acesso negado. Apenas administradores podem exportar.');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

try {
    // ---------- Coleta filtros (apenas MÊS por padrão) ----------
    $period = ($_GET['period'] ?? 'month') === 'all' ? 'all' : 'month';
    $month  = $_GET['m'] ?? date('Y-m');

    // filtros avançados (iguais aos da listagem)
    $vendedorId = isset($_GET['vendedor_id']) && $_GET['vendedor_id'] !== '' ? (int)$_GET['vendedor_id'] : null;
    $viradorId = isset($_GET['virador_id']) && $_GET['virador_id'] !== '' ? (int)$_GET['virador_id'] : null;
    $administradora = trim($_GET['administradora'] ?? '');
    $tipo = trim($_GET['tipo'] ?? '');
    $segmento = trim($_GET['segmento'] ?? '');
    $q = trim($_GET['q'] ?? '');

    // Montar filtros
    $filters = [];

    // Adicionar filtro de período
    if ($period === 'month' && preg_match('/^\d{4}-\d{2}$/', $month)) {
        $filters['mes'] = (int)date('n', strtotime($month . '-01'));
        $filters['ano'] = (int)date('Y', strtotime($month . '-01'));
    }

    if ($vendedorId) $filters['vendedor_id'] = $vendedorId;
    if ($viradorId) $filters['virador_id'] = $viradorId;
    if ($administradora !== '') $filters['administradora'] = $administradora;
    if ($tipo !== '') $filters['tipo'] = $tipo;
    if ($segmento !== '') $filters['segmento'] = $segmento;

    // Buscar vendas (todas, sem paginação)
    $vendas = Venda::all(null, $filters);

    // Se há busca geral, filtrar localmente
    if ($q !== '') {
        $vendas = array_filter($vendas, function($venda) use ($q) {
            $searchFields = [
                $venda['numero_contrato'] ?? '',
                $venda['cliente_nome'] ?? '',
                $venda['cpf'] ?? '',
                $venda['vendedor_nome'] ?? '',
                $venda['virador_nome'] ?? '',
                $venda['administradora'] ?? '',
                $venda['tipo'] ?? '',
                $venda['segmento'] ?? ''
            ];
            
            $searchText = implode(' ', array_filter($searchFields));
            return stripos($searchText, $q) !== false;
        });
    }
} catch (Exception $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('Erro ao buscar dados: ' . $e->getMessage());
}

// ---------- Monta planilha ----------
try {
    $ss = new Spreadsheet();
    $sheet = $ss->getActiveSheet();
    $sheet->setTitle('Vendas');

    // Cabeçalho
    $headers = [
        'Nome do Cliente',
        'Nome do Vendedor', 
        'Nome do Virador',
        'Valor da Venda'
    ];
    $sheet->fromArray($headers, null, 'A1');

    // Estilizar cabeçalho
    $headerStyle = $sheet->getStyle('A1:D1');
    $headerStyle->getFont()
        ->setBold(true)
        ->setSize(11)
        ->setColor(new Color(Color::COLOR_WHITE));
    $headerStyle->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF4472C4'); // Azul BCC
    $headerStyle->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER);
    $headerStyle->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);

    // Dados
    $startRow = 2;
    $r = $startRow;
    foreach ($vendas as $venda) {
      $sheet->setCellValue("A{$r}", (string)($venda['cliente_nome'] ?? ''));
      $sheet->setCellValue("B{$r}", (string)($venda['vendedor_nome'] ?? ''));
      $sheet->setCellValue("C{$r}", (string)($venda['virador_nome'] ?? ''));
      
      // Valor como número
      $valorCredito = (float)($venda['valor_credito'] ?? 0);
      $sheet->setCellValue("D{$r}", $valorCredito);
      
      // Aplicar bordas nas células de dados
      $dataStyle = $sheet->getStyle("A{$r}:D{$r}");
      $dataStyle->getBorders()
          ->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN);
      
      // Alternar cor de fundo para melhor visualização
      if ($r % 2 == 0) {
          $dataStyle->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFF2F2F2');
      }
      
      $r++;
    }

    // Auto largura das colunas
    foreach (range('A','D') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
      $sheet->getColumnDimension($col)->setAutoSize(false);
      $currentWidth = $sheet->getColumnDimension($col)->getWidth();
      if ($currentWidth > 50) {
          $sheet->getColumnDimension($col)->setWidth(50);
      }
    }

    // Formatar coluna de valor como moeda (apenas se houver dados)
    if ($r > 2) {
        $sheet->getStyle('D2:D' . ($r - 1))->getNumberFormat()->setFormatCode('"R$ "#,##0.00');
        // Alinhar valores à direita
        $sheet->getStyle('D2:D' . ($r - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
    
    // Definir altura do cabeçalho
    $sheet->getRowDimension(1)->setRowHeight(20);
    
    // Congelar primeira linha (cabeçalho)
    $sheet->freezePane('A2');
    
} catch (Exception $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('Erro ao criar planilha: ' . $e->getMessage());
}

// ---------- Envia para o navegador ----------
try {
    $filename = 'vendas_' . ($period === 'month' ? $month : 'todos') . '_' . date('Y-m-d') . '.xlsx';

    // Limpar qualquer output anterior e desabilitar qualquer buffer adicional
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Verificar se headers já foram enviados
    if (headers_sent($file, $line)) {
        error_log("Headers já enviados em $file:$line");
        throw new Exception('Headers já foram enviados. Não é possível enviar arquivo Excel.');
    }

    // É importante NÃO mandar nenhum echo/HTML antes dos headers:
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', true);
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"', true);
    header('Cache-Control: max-age=0', true);
    header('Pragma: public', true);
    header('Content-Transfer-Encoding: binary', true);

    $writer = new Xlsx($ss);
    $writer->setPreCalculateFormulas(false);
    
    // Salvar para arquivo temporário primeiro para garantir integridade
    $tempDir = sys_get_temp_dir();
    if (!is_writable($tempDir)) {
        throw new Exception('Diretório temporário não é gravável: ' . $tempDir);
    }
    
    $tempFile = $tempDir . DIRECTORY_SEPARATOR . uniqid('excel_', true) . '.xlsx';
    $writer->save($tempFile);
    
    if (!file_exists($tempFile) || filesize($tempFile) === 0) {
        throw new Exception('Arquivo temporário não foi criado corretamente');
    }
    
    // Ler e enviar arquivo
    $filesize = filesize($tempFile);
    header('Content-Length: ' . $filesize, true);
    
    readfile($tempFile);
    
    // Remover arquivo temporário
    @unlink($tempFile);
    
} catch (Throwable $e) {
    // Em caso de erro, limpar output e mostrar erro
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        die('Erro ao gerar arquivo Excel: ' . $e->getMessage() . ' (Linha: ' . $e->getLine() . ')');
    } else {
        error_log('Erro ao gerar Excel: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
        exit;
    }
}
exit;
