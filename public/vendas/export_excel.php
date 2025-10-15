<?php
// public/vendas/export_excel.php
declare(strict_types=1);

require __DIR__ . '/../../app/lib/Database.php';
require __DIR__ . '/../../app/lib/Auth.php';
require __DIR__ . '/../../app/lib/Helpers.php';
require __DIR__ . '/../../app/models/Venda.php';
require __DIR__ . '/../../app/middleware/require_login.php';

// Somente ADMIN
if (!Auth::isAdmin()) {
  http_response_code(403);
  echo 'Acesso negado.';
  exit;
}

// PhpSpreadsheet (via Composer)
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$pdo = Database::getConnection();

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
            $venda['numero_contrato'],
            $venda['cliente_nome'],
            $venda['cpf'],
            $venda['vendedor_nome'],
            $venda['virador_nome'],
            $venda['administradora'],
            $venda['tipo'],
            $venda['segmento']
        ];
        
        $searchText = implode(' ', $searchFields);
        return stripos($searchText, $q) !== false;
    });
}

// ---------- Monta planilha ----------
$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Vendas');

// Cabeçalho - apenas os campos solicitados
$headers = [
    'Nome do Cliente',
    'Nome do Vendedor', 
    'Nome do Virador',
    'Valor da Venda'
];
$sheet->fromArray($headers, null, 'A1');

// Deixa o cabeçalho em negrito
$sheet->getStyle('A1:D1')->getFont()->setBold(true);
$sheet->getStyle('A1:D1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Dados
$startRow = 2;
$r = $startRow;
foreach ($vendas as $venda) {
  $sheet->setCellValue("A{$r}", $venda['cliente_nome']);
  $sheet->setCellValue("B{$r}", $venda['vendedor_nome']);
  $sheet->setCellValue("C{$r}", $venda['virador_nome']);
  
  // Valor como número; a formatação de moeda é aplicada via NumberFormat
  $sheet->setCellValue("D{$r}", (float)$venda['valor_credito']);
  
  $r++;
}

// Auto largura das colunas
foreach (range('A','D') as $col) {
  $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Formatar coluna de valor como moeda
$sheet->getStyle('D2:D' . ($r - 1))->getNumberFormat()->setFormatCode('"R$ "#,##0.00');

// ---------- Envia para o navegador ----------
$filename = 'vendas_' . ($period === 'month' ? $month : 'todos') . '.xlsx';

// É importante NÃO mandar nenhum echo/HTML antes dos headers:
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->setPreCalculateFormulas(false); // performance
$writer->save('php://output');
exit;
