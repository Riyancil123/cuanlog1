<?php
/**
 * CuanLog - Export Laporan ke CSV
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$db = getDB();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-d');
$type       = $_GET['type']       ?? 'transactions';

if (!$db) {
    die('Database tidak tersedia.');
}

$filename = 'CuanLog_' . $type . '_' . $start_date . '_' . $end_date . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
// UTF-8 BOM for Excel
fputs($output, "\xEF\xBB\xBF");

if ($type === 'products') {
    // Export produk
    fputcsv($output, ['ID', 'Barcode', 'Nama Produk', 'Kategori', 'Harga Modal', 'Harga Jual', 'Stok', 'Min Stok', 'Satuan', 'Margin %', 'Status']);
    $rows = $db->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.name");
    $rows->execute();
    foreach ($rows->fetchAll() as $r) {
        $margin = $r['selling_price'] > 0 ? round(($r['selling_price'] - $r['cost_price']) / $r['selling_price'] * 100, 1) : 0;
        fputcsv($output, [
            $r['id'], $r['barcode'], $r['name'], $r['cat_name'] ?? 'Tanpa Kategori',
            $r['cost_price'], $r['selling_price'], $r['stock'], $r['min_stock'], $r['unit'],
            $margin . '%',
            $r['stock'] <= $r['min_stock'] ? 'Stok Menipis' : 'Normal'
        ]);
    }
} else {
    // Export transactions
    fputcsv($output, [
        'No. Nota', 'Tanggal', 'Waktu', 'Kasir', 'Pelanggan',
        'Metode Bayar', 'Subtotal', 'Diskon', 'Total Bayar',
        'HPP', 'Laba Bersih', 'Status'
    ]);
    $stmt = $db->prepare("SELECT t.*, u.name as kasir, c.name as pelanggan
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN customers c ON t.customer_id = c.id
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        ORDER BY t.created_at DESC");
    $stmt->execute([$start_date, $end_date]);
    $pay_map = ['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','debt'=>'Kasbon','kasbon'=>'Kasbon'];
    foreach ($stmt->fetchAll() as $r) {
        fputcsv($output, [
            $r['invoice_no'],
            date('d/m/Y', strtotime($r['created_at'])),
            date('H:i', strtotime($r['created_at'])),
            $r['kasir'] ?? '-',
            $r['pelanggan'] ?? 'Umum',
            $pay_map[$r['payment_method']] ?? $r['payment_method'],
            $r['subtotal'],
            $r['discount'],
            $r['grand_total'],
            $r['total_cost'],
            $r['grand_total'] - $r['total_cost'],
            ucfirst($r['status'])
        ]);
    }
}

fclose($output);
exit;
