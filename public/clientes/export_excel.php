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
    ob_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('Erro: autoload.php não encontrado. Execute: composer install');
}

// Carregar e executar o autoload explicitamente
$loader = require_once $autoloadPath;

// Garantir que o autoloader está registrado
if (!$loader instanceof \Composer\Autoload\ClassLoader) {
    ob_clean();
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
        ob_clean();
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        // Debug: mostrar caminhos tentados
        $debug = 'Erro: Classe Composer\Pcre\Preg não encontrada. Caminhos tentados:' . PHP_EOL;
        foreach ($possiblePaths as $path) {
            $debug .= '  - ' . ($path ?: 'NULL') . ' (' . ($path && file_exists($path) ? 'existe' : 'não existe') . ')' . PHP_EOL;
        }
        die($debug);
    }
}

// Verificar autenticação antes de carregar classes que podem gerar output
// Carregar Auth primeiro para verificar sem iniciar sessão que gere output
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

$pdo = Database::getConnection();

// ---------- Coleta filtros (apenas MÊS por padrão) ----------
$period = ($_GET['period'] ?? 'month') === 'all' ? 'all' : 'month';
$month  = $_GET['m'] ?? date('Y-m');

// filtros avançados (iguais aos da listagem)
$nome      = trim($_GET['f_nome']     ?? '');
$tel       = trim($_GET['f_telefone'] ?? '');
$cidade    = trim($_GET['f_cidade']   ?? '');
$estado    = strtoupper(substr(trim($_GET['f_estado'] ?? ''), 0, 2));
$interesse = trim($_GET['f_interesse'] ?? '');
$q         = trim($_GET['q'] ?? '');

$where  = ["c.deleted_at IS NULL"];
$params = [];

// período do mês
if ($period === 'month' && preg_match('/^\d{4}-\d{2}$/', $month)) {
  $start = $month . '-01';
  $end   = date('Y-m-d', strtotime('last day of ' . $start));
  $where[]        = "c.created_at BETWEEN :start AND :end";
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

// ---------- Monta planilha ----------
$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Clientes');

// Cabeçalho
$headers = ['ID','Nome','Telefone','Cidade','Estado','Interesse','Criado por','Criado em'];
$sheet->fromArray($headers, null, 'A1'); // escreve a partir da célula A1
// Deixa o cabeçalho em negrito
$sheet->getStyle('A1:H1')->getFont()->setBold(true); // exemplo: linha 1 em bold. :contentReference[oaicite:2]{index=2}
$sheet->getStyle('A1:H1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Dados
$startRow = 2;
$r = $startRow;
foreach ($rows as $row) {
  $sheet->setCellValue("A{$r}", (int)$row['id']);
  $sheet->setCellValue("B{$r}", $row['nome']);
  $sheet->setCellValue("C{$r}", $row['telefone']);
  $sheet->setCellValue("D{$r}", $row['cidade']);
  $sheet->setCellValue("E{$r}", $row['estado']);
  $sheet->setCellValue("F{$r}", (string)($row['interesse'] ?? ''));
  $sheet->setCellValue("G{$r}", $row['criado_por']);
  // formata data/hora como texto legível
  $sheet->setCellValue("H{$r}", date('d/m/Y H:i:s', strtotime((string)$row['created_at'])));
  $r++;
}

// Auto largura das colunas
foreach (range('A','H') as $col) {
  $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ---------- Envia para o navegador ----------
$filename = 'clientes_' . ($period === 'month' ? $month : 'todos') . '.xlsx';

// Limpar qualquer output anterior e desabilitar qualquer buffer adicional
// Fazer isso ANTES de qualquer operação que possa gerar output
while (ob_get_level() > 0) {
    ob_end_clean();
}

// É importante NÃO mandar nenhum echo/HTML antes dos headers:
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');
header('Content-Transfer-Encoding: binary');

try {
    $writer = new Xlsx($ss);
    $writer->setPreCalculateFormulas(false); // performance
    
    // Salvar para arquivo temporário primeiro para garantir integridade
    $tempFile = sys_get_temp_dir() . '/' . uniqid('excel_') . '.xlsx';
    $writer->save($tempFile);
    
    // Ler e enviar arquivo
    readfile($tempFile);
    
    // Remover arquivo temporário
    @unlink($tempFile);
    
} catch (Exception $e) {
    // Em caso de erro, limpar output e mostrar erro
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die('Erro ao gerar arquivo Excel: ' . $e->getMessage());
}
exit;
