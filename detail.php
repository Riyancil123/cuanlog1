<?php
/**
 * CuanLog - Detail Transaksi & Cetak Nota
 * Poin 5: Bukti transaksi / nota
 */
$page_title = 'Detail Transaksi';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_login();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id || !$db) {
    set_flash('error', 'Transaksi tidak valid.');
    header('Location: ' . BASE_URL . '/transactions/index.php'); exit;
}

$trx   = null;
$items = [];

try {
    $s = $db->prepare("SELECT t.*, u.name as cashier, c.name as cust_name, c.phone as cust_phone
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN customers c ON t.customer_id = c.id
        WHERE t.id = ?");
    $s->execute([$id]);
    $trx = $s->fetch();

    if (!$trx) {
        set_flash('error', 'Transaksi tidak ditemukan.');
        header('Location: ' . BASE_URL . '/transactions/index.php'); exit;
    }

    $si = $db->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
    $si->execute([$id]);
    $items = $si->fetchAll();

} catch (Exception $e) {
    set_flash('error', 'Error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/transactions/index.php'); exit;
}

$pay_labels = ['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer Bank','debt'=>'Kasbon/Hutang','kasbon'=>'Kasbon/Hutang'];
$store_name = get_setting('store_name', 'CuanLog Mart UMKM');
$store_phone = get_setting('store_phone', '');
$store_address = get_setting('store_address', '');
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 bg-slate-50">

        <!-- Header -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL ?>/transactions/index.php"
                   class="p-2 text-slate-500 hover:text-slate-800 hover:bg-white rounded-xl border border-slate-200 transition">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h1 class="text-xl font-black text-slate-900">Detail Transaksi</h1>
                    <p class="text-xs text-slate-500 font-mono"><?= htmlspecialchars($trx['invoice_no']) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>/pos/receipt.php?id=<?= $trx['id'] ?>" target="_blank"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition">
                    <i class="fa-solid fa-print"></i> Cetak Nota
                </a>
                <?php if (!empty($trx['cust_phone'])): ?>
                <a href="https://wa.me/<?= preg_replace('/\D/', '', $trx['cust_phone']) ?>?text=<?= urlencode("Halo! Ini nota transaksi Anda di {$store_name}.\nNo. Nota: {$trx['invoice_no']}\nTotal: " . format_rupiah($trx['grand_total']) . "\nTerima kasih!") ?>"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-xl transition">
                    <i class="fa-brands fa-whatsapp"></i> Share WA
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Main: Items List -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Items Table -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-800">Daftar Item Pembelian</h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left px-4 py-3 text-[11px] font-bold text-slate-500 uppercase">Produk</th>
                                <th class="text-center px-4 py-3 text-[11px] font-bold text-slate-500 uppercase">Qty</th>
                                <th class="text-right px-4 py-3 text-[11px] font-bold text-slate-500 uppercase">Harga</th>
                                <th class="text-right px-4 py-3 text-[11px] font-bold text-slate-500 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3 font-semibold text-slate-800"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td class="px-4 py-3 text-center text-slate-600"><?= number_format($item['qty']) ?></td>
                                <td class="px-4 py-3 text-right text-slate-500 font-mono text-xs"><?= format_rupiah($item['price']) ?></td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800 font-mono text-xs"><?= format_rupiah($item['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                            <tr>
                                <td colspan="3" class="px-4 py-2.5 text-xs font-bold text-slate-500 text-right">Subtotal:</td>
                                <td class="px-4 py-2.5 text-right font-bold text-slate-800 font-mono text-xs"><?= format_rupiah($trx['subtotal']) ?></td>
                            </tr>
                            <?php if ($trx['discount'] > 0): ?>
                            <tr>
                                <td colspan="3" class="px-4 py-1 text-xs font-bold text-rose-500 text-right">Diskon:</td>
                                <td class="px-4 py-1 text-right font-bold text-rose-500 font-mono text-xs">-<?= format_rupiah($trx['discount']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($trx['tax'] > 0): ?>
                            <tr>
                                <td colspan="3" class="px-4 py-1 text-xs font-bold text-slate-500 text-right">Pajak (PPN):</td>
                                <td class="px-4 py-1 text-right font-bold text-slate-600 font-mono text-xs"><?= format_rupiah($trx['tax']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="bg-emerald-50">
                                <td colspan="3" class="px-4 py-3 text-sm font-black text-emerald-800 text-right">TOTAL BAYAR:</td>
                                <td class="px-4 py-3 text-right font-black text-emerald-700 text-sm"><?= format_rupiah($trx['grand_total']) ?></td>
                            </tr>
                            <?php if ($trx['payment_method'] === 'cash' && isset($trx['amount_paid']) && $trx['amount_paid'] > 0): ?>
                            <tr>
                                <td colspan="3" class="px-4 py-1.5 text-xs font-bold text-slate-500 text-right">Bayar (Tunai):</td>
                                <td class="px-4 py-1.5 text-right font-bold text-slate-700 font-mono text-xs"><?= format_rupiah($trx['amount_paid']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-1.5 text-xs font-bold text-blue-600 text-right">Kembalian:</td>
                                <td class="px-4 py-1.5 text-right font-bold text-blue-600 font-mono text-xs"><?= format_rupiah(max(0, $trx['amount_paid'] - $trx['grand_total'])) ?></td>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>

                <?php if (!empty($trx['notes'])): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                    <p class="text-xs font-bold text-amber-800 mb-1"><i class="fa-solid fa-note-sticky mr-1.5"></i>Catatan Transaksi</p>
                    <p class="text-sm text-amber-700"><?= nl2br(htmlspecialchars($trx['notes'])) ?></p>
                </div>
                <?php endif; ?>

            </div>

            <!-- Right: Transaction Info -->
            <div class="space-y-4">

                <!-- Status Badge -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                    <?php if ($trx['status'] === 'completed'): ?>
                        <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-3 text-2xl">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <p class="font-black text-emerald-700 text-lg">Transaksi Selesai</p>
                    <?php elseif ($trx['status'] === 'cancelled'): ?>
                        <div class="w-14 h-14 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3 text-2xl">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                        <p class="font-black text-rose-700 text-lg">Dibatalkan</p>
                    <?php endif; ?>
                    <p class="text-sm font-black text-slate-800 mt-2"><?= format_rupiah($trx['grand_total']) ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= format_date_indo($trx['created_at'], true) ?></p>
                </div>

                <!-- Transaction Details -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3 text-xs">
                    <h3 class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100">Info Transaksi</h3>
                    <div class="flex justify-between">
                        <span class="text-slate-500">No. Nota</span>
                        <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($trx['invoice_no']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Kasir</span>
                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($trx['cashier'] ?? '-') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Pelanggan</span>
                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($trx['cust_name'] ?? 'Umum') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Pembayaran</span>
                        <span class="font-bold text-slate-800"><?= $pay_labels[$trx['payment_method']] ?? ucfirst($trx['payment_method']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Item Dibeli</span>
                        <span class="font-semibold text-slate-800"><?= count($items) ?> jenis produk</span>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex justify-between">
                        <span class="text-slate-500">Laba Est.</span>
                        <span class="font-black text-emerald-700">+<?= format_rupiah($trx['grand_total'] - $trx['total_cost']) ?></span>
                    </div>
                </div>

            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
