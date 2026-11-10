<?php
/**
 * CuanLog - Thermal & Digital Receipt View
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if (!$id || !$db) {
    header("Location: " . BASE_URL . "/pos/index.php");
    exit;
}

// Fetch transaction
$stmt = $db->prepare("SELECT t.*, u.name as cashier_name, c.name as customer_name, c.phone as customer_phone 
    FROM transactions t 
    LEFT JOIN users u ON t.user_id = u.id 
    LEFT JOIN customers c ON t.customer_id = c.id 
    WHERE t.id = ?");
$stmt->execute([$id]);
$tx = $stmt->fetch();

if (!$tx) {
    set_flash('error', 'Transaksi tidak ditemukan!');
    header("Location: " . BASE_URL . "/pos/index.php");
    exit;
}

// Fetch items
$stmt_items = $db->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
$stmt_items->execute([$id]);
$items = $stmt_items->fetchAll();

// Store settings
$store_name = get_setting('store_name', 'CuanLog Mart UMKM');
$store_address = get_setting('store_address', 'Kawasan Niaga UMKM Indonesia');
$store_phone = get_setting('store_phone', '0812-3456-7890');
$receipt_footer = get_setting('receipt_footer', "Terima kasih atas kunjungan Anda!\nBersama CuanLog, UMKM Maju.");

// Build WhatsApp text
$wa_text = "*STRUK PEMBELIAN - $store_name*\n";
$wa_text .= "No. Nota: " . $tx['invoice_no'] . "\n";
$wa_text .= "Tanggal: " . format_date_indo($tx['created_at'], true) . "\n";
$wa_text .= "Kasir: " . ($tx['cashier_name'] ?? 'Kasir') . "\n";
$wa_text .= "--------------------------------\n";
foreach ($items as $it) {
    $wa_text .= $it['product_name'] . "\n";
    $wa_text .= "  " . $it['qty'] . " x " . format_rupiah($it['price']) . " = " . format_rupiah($it['subtotal']) . "\n";
}
$wa_text .= "--------------------------------\n";
$wa_text .= "*TOTAL: " . format_rupiah($tx['grand_total']) . "*\n";
$wa_text .= "Metode: " . strtoupper($tx['payment_method']) . "\n";
if ($tx['payment_method'] === 'cash') {
    $wa_text .= "Bayar: " . format_rupiah($tx['amount_paid']) . "\n";
    $wa_text .= "Kembali: " . format_rupiah($tx['change_due']) . "\n";
}
$wa_text .= "\n" . $receipt_footer;
$wa_url = "https://api.whatsapp.com/send?phone=" . preg_replace('/[^0-9]/', '', $tx['customer_phone'] ?? '') . "&text=" . urlencode($wa_text);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #<?= htmlspecialchars($tx['invoice_no']) ?> - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <style>
        .thermal-paper {
            font-family: 'JetBrains Mono', 'Courier New', Courier, monospace;
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center justify-center font-sans antialiased text-slate-800">

    <!-- Action Buttons (Hidden on Print) -->
    <div class="no-print w-full max-w-sm mb-5 flex items-center justify-between">
        <a href="<?= BASE_URL ?>/pos/index.php" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-semibold shadow-xs transition">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Kasir</span>
        </a>

        <div class="flex items-center space-x-2">
            <a href="<?= $wa_url ?>" target="_blank" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-semibold transition" title="Kirim Struk ke WhatsApp">
                <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                <span class="hidden sm:inline">WhatsApp</span>
            </a>
            <button onclick="window.print()" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Nota</span>
            </button>
        </div>
    </div>

    <!-- Thermal Receipt Card -->
    <div id="printable-receipt" class="thermal-paper w-full max-w-[340px] p-6 rounded-2xl border border-slate-200 text-slate-900 text-xs">
        
        <!-- Header -->
        <div class="text-center pb-3 border-b border-dashed border-slate-300">
            <h2 class="text-base font-black tracking-tight uppercase"><?= htmlspecialchars($store_name) ?></h2>
            <p class="text-[11px] text-slate-500 leading-snug mt-0.5"><?= nl2br(htmlspecialchars($store_address)) ?></p>
            <p class="text-[11px] text-slate-500">Telp: <?= htmlspecialchars($store_phone) ?></p>
        </div>

        <!-- Meta info -->
        <div class="py-2.5 border-b border-dashed border-slate-300 text-[11px] space-y-1">
            <div class="flex justify-between">
                <span class="text-slate-500">No. Nota:</span>
                <span class="font-bold"><?= htmlspecialchars($tx['invoice_no']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Waktu:</span>
                <span><?= format_date_indo($tx['created_at'], true) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Kasir:</span>
                <span><?= htmlspecialchars($tx['cashier_name'] ?? 'Kasir') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Pelanggan:</span>
                <span class="font-semibold"><?= htmlspecialchars($tx['customer_name'] ?? 'Pelanggan Umum') ?></span>
            </div>
        </div>

        <!-- Items Table -->
        <div class="py-3 border-b border-dashed border-slate-300">
            <table class="w-full text-left text-[11px]">
                <tbody class="divide-y divide-dotted divide-slate-200">
                    <?php foreach ($items as $item): ?>
                        <tr class="py-1">
                            <td colspan="2" class="pt-1.5 font-bold text-slate-800">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </td>
                        </tr>
                        <tr class="pb-1 text-slate-600">
                            <td class="pb-1 text-slate-500">
                                <?= $item['qty'] ?> x <?= number_format($item['price'], 0, ',', '.') ?>
                            </td>
                            <td class="pb-1 text-right font-semibold">
                                <?= number_format($item['subtotal'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pricing Summary -->
        <div class="py-2.5 border-b border-dashed border-slate-300 text-[11px] space-y-1">
            <div class="flex justify-between">
                <span class="text-slate-500">Subtotal</span>
                <span><?= format_rupiah($tx['subtotal']) ?></span>
            </div>
            <?php if ($tx['discount'] > 0): ?>
            <div class="flex justify-between text-rose-600">
                <span>Diskon</span>
                <span>- <?= format_rupiah($tx['discount']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($tx['tax'] > 0): ?>
            <div class="flex justify-between">
                <span class="text-slate-500">PPN</span>
                <span><?= format_rupiah($tx['tax']) ?></span>
            </div>
            <?php endif; ?>
            <div class="flex justify-between font-black text-sm pt-1 border-t border-slate-200">
                <span>TOTAL</span>
                <span class="text-emerald-700"><?= format_rupiah($tx['grand_total']) ?></span>
            </div>
        </div>

        <!-- Payment details -->
        <div class="py-2.5 border-b border-dashed border-slate-300 text-[11px] space-y-1">
            <div class="flex justify-between">
                <span class="text-slate-500">Metode Bayar:</span>
                <span class="font-bold uppercase"><?= htmlspecialchars($tx['payment_method']) ?></span>
            </div>
            <?php if ($tx['payment_method'] === 'cash'): ?>
            <div class="flex justify-between">
                <span class="text-slate-500">Tunai Diterima:</span>
                <span><?= format_rupiah($tx['amount_paid']) ?></span>
            </div>
            <div class="flex justify-between font-bold">
                <span class="text-slate-500">Kembalian:</span>
                <span class="text-slate-900"><?= format_rupiah($tx['change_due']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Receipt Footer Note -->
        <div class="pt-4 text-center text-[10px] text-slate-500 leading-relaxed">
            <?= nl2br(htmlspecialchars($receipt_footer)) ?>
            <div class="mt-2 text-[9px] text-slate-400 font-mono">
                CuanLog POS &bull; Powered by Antigravity
            </div>
        </div>

    </div>

</body>
</html>

