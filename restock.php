<?php
/**
 * CuanLog - Smart Restock & Supplier PO Generator
 * Sub-tema: Accelerating Digital Transformation for Business and SMEs
 */
$page_title = 'Rekomendasi Restock Pintar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();

// Handle quick restock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_stock') {
    $prod_id = (int)($_POST['product_id'] ?? 0);
    $added_qty = max(1, (int)($_POST['qty'] ?? 1));

    if ($prod_id > 0 && $db) {
        try {
            $stmt_up = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt_up->execute([$added_qty, $prod_id]);

            $stmt_get = $db->prepare("SELECT name, stock FROM products WHERE id = ?");
            $stmt_get->execute([$prod_id]);
            $p = $stmt_get->fetch();

            log_activity('Restock Barang', "Menambah stok {$p['name']} sebanyak $added_qty (Total stok sekarang: {$p['stock']})");
            set_flash('success', "Stok '{$p['name']}' berhasil ditambah +$added_qty!");
        } catch (Exception $e) {
            set_flash('error', $e->getMessage());
        }
    }
    header("Location: " . BASE_URL . "/products/restock.php");
    exit;
}

// Fetch low stock items
$low_stock_items = [];
$total_estimated_cost = 0;

if ($db) {
    try {
        $stmt = $db->query("SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.stock <= p.min_stock AND p.is_active = 1 
            ORDER BY p.stock ASC");
        $low_stock_items = $stmt->fetchAll();

        foreach ($low_stock_items as &$item) {
            // Intelligent recommended restock qty: 3x minimum stock threshold minus current stock
            $target_stock = max(10, $item['min_stock'] * 3);
            $recommended_qty = max(5, $target_stock - $item['stock']);
            $item['recommended_qty'] = $recommended_qty;
            $item['estimated_cost'] = (float)$item['cost_price'] * $recommended_qty;
            $total_estimated_cost += $item['estimated_cost'];
        }
        unset($item);
    } catch (Exception $e) {}
}

$store_name = get_setting('store_name', 'CuanLog Mart');
$store_phone = get_setting('store_phone', '0812-3456-7890');

// Build WhatsApp PO to Supplier
$po_text = "*SURAT PESANAN BARANG (PURCHASE ORDER) - $store_name*\n";
$po_text .= "Tanggal: " . date('d F Y') . "\n";
$po_text .= "Dari: $store_name ($store_phone)\n";
$po_text .= "Kepada: Yth. Mitra Distributor / Supplier\n";
$po_text .= "----------------------------------------\n";
$po_text .= "Mohon dapat dikirimkan barang kebutuhan stok toko kami sebagai berikut:\n\n";

foreach ($low_stock_items as $idx => $it) {
    $po_text .= ($idx + 1) . ". *" . $it['name'] . "*\n";
    $po_text .= "   Jumlah Order: " . $it['recommended_qty'] . " " . $it['unit'] . "\n";
    if (!empty($it['barcode'])) {
        $po_text .= "   Barcode/SKU: " . $it['barcode'] . "\n";
    }
}

$po_text .= "\n----------------------------------------\n";
$po_text .= "Estimasi Anggaran Modal: " . format_rupiah($total_estimated_cost) . "\n";
$po_text .= "Mohon konfirmasi ketersediaan barang dan invoice tagihan. Terima kasih!";
$wa_po_url = "https://api.whatsapp.com/send?text=" . urlencode($po_text);
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Fitur Pintar UMKM
                    </span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight mt-1">Rekomendasi Restock & Purchase Order (PO)</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Otomatisasi kalkulasi jumlah restock barang yang menipis dan buat surat pesanan ke distributor via WhatsApp.</p>
            </div>

            <div class="flex items-center space-x-2">
                <?php if (!empty($low_stock_items)): ?>
                    <a href="<?= $wa_po_url ?>" target="_blank" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-2xl shadow-md shadow-emerald-600/25 transition flex items-center space-x-2">
                        <i class="fa-brands fa-whatsapp text-sm"></i>
                        <span>Kirim PO ke Supplier via WA</span>
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/products/index.php" class="px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-2xl shadow-xs transition">
                    Kembali ke Katalog
                </a>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-xs">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Item Menipis / Kritis</span>
                <p class="text-2xl font-black text-rose-600 font-mono-num tracking-tight mt-1">
                    <?= count($low_stock_items) ?> Produk
                </p>
                <p class="text-xs text-slate-400 mt-1">Stok saat ini berada &le; batas aman</p>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-xs">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Estimasi Modal Belanja Stok</span>
                <p class="text-2xl font-black text-emerald-700 font-mono-num tracking-tight mt-1">
                    <?= format_rupiah($total_estimated_cost) ?>
                </p>
                <p class="text-xs text-slate-400 mt-1">Anggaran dana untuk kulakan ke supplier</p>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-xs">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Metode Otomasi</span>
                <p class="text-sm font-bold text-slate-800 mt-1">Surat Pesanan Instan (PO)</p>
                <p class="text-xs text-slate-400 mt-1">Format terstruktur otomatis tersalin ke WhatsApp</p>
            </div>
        </div>

        <!-- Recommended Restock Table -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-cart-flatbed text-emerald-600 mr-2"></i> Daftar Kebutuhan Belanja Produk
                </h3>
                <span class="text-xs text-slate-400">Algoritma: Penyeimbangan Stok Minimum x 3</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-4">Nama Produk & Barcode</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4 text-center">Sisa Stok</th>
                            <th class="py-3 px-4 text-center">Batas Aman</th>
                            <th class="py-3 px-4 text-center">Saran Order</th>
                            <th class="py-3 px-4 text-right">Harga Modal</th>
                            <th class="py-3 px-4 text-right">Subtotal Modal</th>
                            <th class="py-3 px-4 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($low_stock_items)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <i class="fa-solid fa-circle-check text-4xl text-emerald-500 mb-2 block"></i>
                                    <p class="font-bold text-slate-800">Persediaan Barang Sangat Aman!</p>
                                    <p class="text-xs text-slate-400 mt-1">Tidak ada produk yang berada di bawah batas minimum stok saat ini.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3 px-4">
                                        <p class="font-bold text-slate-900"><?= htmlspecialchars($item['name']) ?></p>
                                        <span class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($item['barcode'] ?: '-') ?></span>
                                    </td>

                                    <td class="py-3 px-4 text-slate-600">
                                        <?= htmlspecialchars($item['category_name'] ?? 'Umum') ?>
                                    </td>

                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] <?= $item['stock'] <= 0 ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' ?>">
                                            <?= $item['stock'] ?> <?= htmlspecialchars($item['unit']) ?>
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 text-center text-slate-500 font-mono-num">
                                        <?= $item['min_stock'] ?> <?= htmlspecialchars($item['unit']) ?>
                                    </td>

                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                                            +<?= $item['recommended_qty'] ?> <?= htmlspecialchars($item['unit']) ?>
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 text-right font-mono-num text-slate-600">
                                        <?= format_rupiah($item['cost_price']) ?>
                                    </td>

                                    <td class="py-3 px-4 text-right font-mono-num font-bold text-slate-900">
                                        <?= format_rupiah($item['estimated_cost']) ?>
                                    </td>

                                    <td class="py-3 px-4 text-center">
                                        <!-- Quick Add Stock Form -->
                                        <form action="" method="POST" class="inline-flex items-center space-x-1.5">
                                            <input type="hidden" name="action" value="add_stock">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <input type="number" name="qty" value="<?= $item['recommended_qty'] ?>" min="1" class="w-14 px-1.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-center">
                                            <button type="submit" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition" title="Tambah Stok Sekarang">
                                                <i class="fa-solid fa-plus mr-0.5"></i> Restock
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

