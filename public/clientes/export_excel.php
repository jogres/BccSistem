<?php
// public/clientes/export_excel.php
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
        // Tentar caminho alternativo
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
    // Última tentativa: carregar diretamente usando vários caminhos possíveis
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
    $pdo = Database::getConnection();

    // ---------- Coleta filtros (apenas MÊS por padrão) ----------
    $period = ($_GET['period'] ?? 'month') === 'all' ? 'all' : 'month';
    $month  = $_GET['m'] ?? date('Y-m');

    // filtros avançados (iguais aos da listagem)
    $nome      = trim($_GET['f_nome'] ?? '');
    $tel       = trim($_GET['f_telefone'] ?? '');
    $cidade    = trim($_GET['f_cidade'] ?? '');
    $estado    = strtoupper(substr(trim($_GET['f_estado'] ?? ''), 0, 2));
    $interesse = trim($_GET['f_interesse'] ?? '');
    $q         = trim($_GET['q'] ?? '');

    $where  = ["c.deleted_at IS NULL"];
    $params = [];

    // período do mês
    if ($period === 'month' && preg_match('/^\d{4}-\d{2}$/', $month)) {
      $start = $month . '-01';
      $end   = date('Y-m-d', strtotime('last day of ' . $start));
      $where[] = "c.created_at BETWEEN :start AND :end";
      $params[':start'] = $start . ' 00:00:00';
      $params[':end']   = $end   . ' 23:59:59';
    }

    if ($nome !== '')   { $where[] = "c.nome LIKE :fn";      $params[':fn'] = "%$nome%"; }
    if ($tel !== '')    { $where[] = "c.telefone LIKE :ft";  $params[':ft'] = "%$tel%"; }
    if ($cidade !== '') { $where[] = "c.cidade LIKE :fc";    $params[':fc'] = "%$cidade%"; }
    if ($estado !== '') { $where[] = "c.estado = :fe";       $params[':fe'] = $estado; }
    if ($interesse !== '') { $where[] = "c.interesse = :fi"; $params[':fi'] = $interesse; }
    if ($q !== '') {
      $where[] = "(c.nome LIKE :q OR c.telefone LIKE :q OR c.cidade LIKE :q OR c.estado LIKE :q OR c.interesse LIKE :q)";
      $params[':q'] = '%'.str_replace(' ', '%', $q).'%';
    }

    $whereSql = implode(' AND ', $where);

    // ---------- Consulta ----------
    $sql = "SELECT c.id, c.nome, c.telefone, c.cidade, c.estado, c.interesse,
                   f.nome AS criado_por, c.created_at
            FROM clientes c
            JOIN funcionarios f ON f.id = c.criado_por
            WHERE $whereSql
            ORDER BY c.created_at DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
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
    $sheet->setTitle('Clientes');

    // Cabeçalho
    $headers = ['ID', 'Nome', 'Telefone', 'Cidade', 'Estado', 'Interesse', 'Criado por', 'Criado em'];
    $sheet->fromArray($headers, null, 'A1');
    
    // Estilizar cabeçalho
    $headerStyle = $sheet->getStyle('A1:H1');
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
    foreach ($rows as $row) {
      $sheet->setCellValue("A{$r}", (int)($row['id'] ?? 0));
      $sheet->setCellValue("B{$r}", (string)($row['nome'] ?? ''));
      $sheet->setCellValue("C{$r}", (string)($row['telefone'] ?? ''));
      $sheet->setCellValue("D{$r}", (string)($row['cidade'] ?? ''));
      $sheet->setCellValue("E{$r}", (string)($row['estado'] ?? ''));
      $sheet->setCellValue("F{$r}", (string)($row['interesse'] ?? ''));
      $sheet->setCellValue("G{$r}", (string)($row['criado_por'] ?? ''));
      
      // Formatar data/hora
      if (!empty($row['created_at'])) {
          try {
              $dateStr = date('d/m/Y H:i:s', strtotime((string)$row['created_at']));
              $sheet->setCellValue("H{$r}", $dateStr);
          } catch (Exception $e) {
              $sheet->setCellValue("H{$r}", (string)$row['created_at']);
          }
      } else {
          $sheet->setCellValue("H{$r}", '');
      }
      
      // Aplicar bordas nas células de dados
      $dataStyle = $sheet->getStyle("A{$r}:H{$r}");
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
    foreach (range('A','H') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
      // Limitar largura máxima para não ficar muito largo
      $sheet->getColumnDimension($col)->setAutoSize(false);
      $currentWidth = $sheet->getColumnDimension($col)->getWidth();
      if ($currentWidth > 50) {
          $sheet->getColumnDimension($col)->setWidth(50);
      }
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
    $filename = 'clientes_' . ($period === 'month' ? $month : 'todos') . '_' . date('Y-m-d') . '.xlsx';

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
