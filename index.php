<?php
// Tampilkan semua error untuk debugging sementara
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = 'Dashboard Bisnis';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = getDB();

// === FILTER PERIODE ===
$period = $_GET['period'] ?? 'today';
$today  = date('Y-m-d');

switch ($period) {
    case '7days':
        $start_date  = date('Y-m-d', strtotime('-6 days'));
        $end_date    = $today;
        $period_label = '7 Hari Terakhir';
        break;
    case 'month':
        $start_date  = date('Y-m-01');
        $end_date    = $today;
        $period_label = 'Bulan Ini (' . date('F Y') . ')';
        break;
    case 'year':
        $start_date  = date('Y-01-01');
        $end_date    = $today;
        $period_label = 'Tahun Ini (' . date('Y') . ')';
        break;
    case 'all':
        $start_date  = '2020-01-01';
        $end_date    = $today;
        $period_label = 'Semua Waktu';
        break;
    case 'custom':
        $start_date  = $_GET['start_date'] ?? date('Y-m-01');
        $end_date    = $_GET['end_date']   ?? $today;
        $period_label = date('d M Y', strtotime($start_date)) . ' s/d ' . date('d M Y', strtotime($end_date));
        break;
    default: // today
        $period      = 'today';
        $start_date  = $today;
        $end_date    = $today;
        $period_label = 'Hari Ini (' . date('d M Y') . ')';
}

// === DATA ===
$period_revenue     = 0;
$period_profit      = 0;
$period_orders      = 0;
$period_items_sold  = 0;
$total_products     = 0;
$total_categories   = 0;
$total_customers    = 0;
$low_stock_total    = 0;
$chart_labels       = [];
$chart_revenue      = [];
$chart_profit       = [];
$payment_data       = [];
$top_products       = [];
$recent_transactions = [];

$store_name   = get_setting('store_name', 'CuanLog Mart UMKM');
$store_tagline = get_setting('store_tagline', 'Solusi Kasir Pintar & Akuntansi Keuangan UMKM');
$store_address = get_setting('store_address', 'Jl. Pemuda No. 88, Jakarta Pusat');
$store_phone   = get_setting('store_phone', '0812-3456-7890');

if ($db) {
    try {
        // Ringkasan periode
        $s = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(grand_total),0) as rev, COALESCE(SUM(grand_total - total_cost),0) as prof
            FROM transactions WHERE DATE(created_at) BETWEEN ? AND ? AND status='completed'");
        $s->execute([$start_date, $end_date]);
        $row = $s->fetch();
        $period_orders  = (int)$row['cnt'];
        $period_revenue = (float)$row['rev'];
        $period_profit  = (float)$row['prof'];

        // Total unit terjual
        $s2 = $db->prepare("SELECT COALESCE(SUM(ti.qty),0) FROM transaction_items ti
            JOIN transactions t ON ti.transaction_id=t.id
            WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status='completed'");
        $s2->execute([$start_date, $end_date]);
        $period_items_sold = (int)$s2->fetchColumn();

        // Master data
        $total_products   = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
        $total_categories = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $total_customers  = (int)$db->query("SELECT COUNT(*) FROM customers WHERE id>1")->fetchColumn();
        $low_stock_total  = (int)$db->query("SELECT COUNT(*) FROM products WHERE stock<=min_stock AND is_active=1")->fetchColumn();

        // Grafik 7 hari
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('d/m', strtotime($d));
            $sd = $db->prepare("SELECT COALESCE(SUM(grand_total),0) as r, COALESCE(SUM(grand_total-total_cost),0) as p
                FROM transactions WHERE DATE(created_at)=? AND status='completed'");
            $sd->execute([$d]);
            $dd = $sd->fetch();
            $chart_revenue[] = (float)$dd['r'];
            $chart_profit[]  = (float)$dd['p'];
        }

        // Sebaran pembayaran
        $sp = $db->prepare("SELECT payment_method, COUNT(*) as cnt, SUM(grand_total) as total
            FROM transactions WHERE DATE(created_at) BETWEEN ? AND ? AND status!='cancelled'
            GROUP BY payment_method");
        $sp->execute([$start_date, $end_date]);
        $payment_data = $sp->fetchAll();

        // Top 5 produk
        $st = $db->prepare("SELECT ti.product_name, SUM(ti.qty) as qty, SUM(ti.subtotal) as sales
            FROM transaction_items ti JOIN transactions t ON ti.transaction_id=t.id
            WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status='completed'
            GROUP BY ti.product_id, ti.product_name ORDER BY qty DESC LIMIT 5");
        $st->execute([$start_date, $end_date]);
        $top_products = $st->fetchAll();

        // Transaksi terkini
        $sr = $db->query("SELECT t.*, u.name as cashier_name, c.name as customer_name
            FROM transactions t
            LEFT JOIN users u ON t.user_id=u.id
            LEFT JOIN customers c ON t.customer_id=c.id
            ORDER BY t.created_at DESC LIMIT 8");
        $recent_transactions = $sr->fetchAll();

    } catch (Exception $e) {}
}

$avg_nota = $period_orders > 0 ? $period_revenue / $period_orders : 0;
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 bg-slate-50">

        <!-- ====== HEADER DASHBOARD ====== -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <i class="fa-solid fa-chart-pie text-[10px]"></i> Dashboard Bisnis
                    </span>
                    <span class="text-xs text-slate-400">Periode: <b class="text-slate-700"><?= htmlspecialchars($period_label) ?></b></span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900">Ringkasan Penjualan & Operasional</h1>
                <p class="text-xs text-slate-500 mt-0.5">Monitor omset, transaksi, cuan bersih, dan status toko secara real-time.</p>
            </div>

            <!-- Filter Periode -->
            <div class="flex flex-wrap items-center gap-2">
                <?php
                $periods = [
                    'today'  => 'Hari Ini',
                    '7days'  => '7 Hari',
                    'month'  => 'Bulan Ini',
                    'year'   => 'Tahun Ini',
                    'all'    => 'Semua',
                ];
                foreach ($periods as $key => $label):
                    $active = $period === $key;
                ?>
                    <a href="?period=<?= $key ?>"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $active ? 'bg-emerald-600 text-white shadow shadow-emerald-600/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ====== 4 KPI CARDS ====== -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Omset -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Omset</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="fa-solid fa-money-bill-trend-up text-lg"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-slate-800 leading-tight"><?= format_rupiah($period_revenue) ?></p>
                <p class="mt-1.5 text-xs text-slate-400 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-[10px]"></i>
                    Penjualan selesai
                </p>
            </div>

            <!-- Jumlah Transaksi -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jml Transaksi</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-receipt text-lg"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-slate-800 leading-tight">
                    <?= number_format($period_orders) ?> <span class="text-sm font-medium text-slate-400">nota</span>
                </p>
                <p class="mt-1.5 text-xs text-slate-400"><?= number_format($period_items_sold) ?> unit produk terjual</p>
            </div>

            <!-- Cuan / Laba -->
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl border border-emerald-200 shadow-sm p-5 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Cuan / Laba</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <i class="fa-solid fa-coins text-lg"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-emerald-700 leading-tight">+<?= format_rupiah($period_profit) ?></p>
                <p class="mt-1.5 text-xs text-emerald-700 font-medium">Omset &minus; HPP (Modal Pokok)</p>
            </div>

            <!-- Rata-rata Nota -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Rata-rata Nota</span>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i class="fa-solid fa-basket-shopping text-lg"></i>
                    </div>
                </div>
                <p class="text-xl sm:text-2xl font-black text-slate-800 leading-tight"><?= format_rupiah($avg_nota) ?></p>
                <p class="mt-1.5 text-xs text-slate-400">Nilai belanja rata-rata / pelanggan</p>
            </div>

        </div>

        <!-- ====== INFORMASI USAHA + QUICK STATS ====== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Info Usaha -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-md shadow-emerald-600/20">
                            <i class="fa-solid fa-store text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900"><?= htmlspecialchars($store_name) ?></h3>
                            <p class="text-xs text-slate-400"><?= htmlspecialchars($store_tagline) ?></p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Aktif
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Alamat Usaha</span>
                        <p class="font-semibold text-slate-700"><?= htmlspecialchars($store_address) ?></p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">No. Telepon / WA</span>
                        <p class="font-semibold text-slate-700"><?= htmlspecialchars($store_phone) ?></p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Produk Aktif</span>
                        <p class="font-semibold text-slate-700"><?= $total_products ?> Produk (<?= $total_categories ?> Kategori)</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Pelanggan Terdaftar</span>
                        <p class="font-semibold text-slate-700"><?= $total_customers ?> Anggota</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/pos/index.php"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow shadow-emerald-600/30 transition">
                        <i class="fa-solid fa-cash-register"></i> Buka Kasir POS
                    </a>
                    <a href="<?= BASE_URL ?>/products/create.php"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                        <i class="fa-solid fa-plus"></i> Tambah Produk
                    </a>
                    <a href="<?= BASE_URL ?>/reports/index.php"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                        <i class="fa-solid fa-chart-line"></i> Laporan Penjualan
                    </a>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="grid grid-cols-2 gap-3">
                <a href="<?= BASE_URL ?>/products/index.php?filter=low_stock"
                   class="bg-rose-50 border border-rose-200 rounded-2xl p-4 hover:bg-rose-100 transition">
                    <div class="w-9 h-9 rounded-xl bg-rose-500 text-white flex items-center justify-center mb-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <p class="text-2xl font-black text-rose-700"><?= $low_stock_total ?></p>
                    <p class="text-[11px] text-rose-600 font-semibold">Stok Menipis</p>
                </a>
                <a href="<?= BASE_URL ?>/products/index.php"
                   class="bg-blue-50 border border-blue-200 rounded-2xl p-4 hover:bg-blue-100 transition">
                    <div class="w-9 h-9 rounded-xl bg-blue-500 text-white flex items-center justify-center mb-2">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <p class="text-2xl font-black text-blue-700"><?= $total_products ?></p>
                    <p class="text-[11px] text-blue-600 font-semibold">Produk Aktif</p>
                </a>
                <a href="<?= BASE_URL ?>/customers/index.php"
                   class="bg-purple-50 border border-purple-200 rounded-2xl p-4 hover:bg-purple-100 transition">
                    <div class="w-9 h-9 rounded-xl bg-purple-500 text-white flex items-center justify-center mb-2">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <p class="text-2xl font-black text-purple-700"><?= $total_customers ?></p>
                    <p class="text-[11px] text-purple-600 font-semibold">Pelanggan</p>
                </a>
                <a href="<?= BASE_URL ?>/transactions/index.php"
                   class="bg-amber-50 border border-amber-200 rounded-2xl p-4 hover:bg-amber-100 transition">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center mb-2">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <p class="text-2xl font-black text-amber-700"><?= $period_orders ?></p>
                    <p class="text-[11px] text-amber-600 font-semibold">Transaksi</p>
                </a>
            </div>
        </div>

        <!-- ====== GRAFIK + PEMBAYARAN ====== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Grafik Tren 7 Hari -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Tren Penjualan 7 Hari Terakhir</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Omset & laba bersih harian</p>
                    </div>
                    <a href="<?= BASE_URL ?>/reports/index.php" class="text-xs text-emerald-600 hover:underline font-semibold">
                        Laporan lengkap &rarr;
                    </a>
                </div>
                <div class="h-52">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Sebaran Pembayaran -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="mb-4">
                    <h3 class="font-bold text-slate-900 text-sm">Metode Pembayaran</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Sebaran pada periode ini</p>
                </div>
                <div class="h-40 flex items-center justify-center">
                    <?php if (!empty($payment_data)): ?>
                        <canvas id="paymentChart"></canvas>
                    <?php else: ?>
                        <div class="text-center text-slate-400">
                            <i class="fa-solid fa-chart-pie text-4xl mb-2 opacity-20"></i>
                            <p class="text-xs">Belum ada transaksi</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($payment_data)): ?>
                    <div class="mt-3 space-y-1.5">
                        <?php
                        $pay_colors = ['cash'=>'#f59e0b','qris'=>'#10b981','transfer'=>'#3b82f6','kasbon'=>'#8b5cf6','other'=>'#94a3b8'];
                        foreach ($payment_data as $pm):
                            $color = $pay_colors[$pm['payment_method']] ?? '#94a3b8';
                            $labels_map = ['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','kasbon'=>'Kasbon','other'=>'Lainnya'];
                            $lbl = $labels_map[$pm['payment_method']] ?? ucfirst($pm['payment_method']);
                        ?>
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background:<?= $color ?>"></span>
                                    <span class="text-slate-600 font-medium"><?= htmlspecialchars($lbl) ?></span>
                                </div>
                                <span class="font-bold text-slate-800"><?= $pm['cnt'] ?> nota</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ====== TOP PRODUK + TRANSAKSI TERKINI ====== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- Top 5 Produk Terlaris -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-900 text-sm">🏆 Produk Terlaris</h3>
                    <a href="<?= BASE_URL ?>/products/index.php" class="text-xs text-emerald-600 hover:underline font-semibold">Semua &rarr;</a>
                </div>
                <?php if (!empty($top_products)): ?>
                    <div class="space-y-3">
                        <?php foreach ($top_products as $i => $prod): ?>
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-lg <?= $i===0 ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-500' ?> text-xs font-black flex items-center justify-center shrink-0">
                                    <?= $i+1 ?>
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($prod['product_name']) ?></p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <div class="flex-1 bg-slate-100 rounded-full h-1.5">
                                            <?php $pct = $top_products[0]['qty'] > 0 ? ($prod['qty'] / $top_products[0]['qty'] * 100) : 100; ?>
                                            <div class="bg-emerald-500 h-1.5 rounded-full" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-medium"><?= number_format($prod['qty']) ?> unit</span>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-emerald-700 shrink-0"><?= format_rupiah($prod['sales']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-slate-400">
                        <i class="fa-solid fa-box-open text-4xl mb-2 opacity-20"></i>
                        <p class="text-xs">Belum ada data penjualan pada periode ini.</p>
                        <a href="<?= BASE_URL ?>/pos/index.php" class="mt-2 inline-block text-xs text-emerald-600 font-bold hover:underline">
                            Mulai transaksi di Kasir POS &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Transaksi Terkini -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-900 text-sm">🧾 Transaksi Terkini</h3>
                    <a href="<?= BASE_URL ?>/transactions/index.php" class="text-xs text-emerald-600 hover:underline font-semibold">Riwayat &rarr;</a>
                </div>
                <?php if (!empty($recent_transactions)): ?>
                    <div class="space-y-2">
                        <?php foreach ($recent_transactions as $trx): ?>
                            <?php
                            $status_cls = match($trx['status']) {
                                'completed' => 'bg-emerald-100 text-emerald-700',
                                'cancelled' => 'bg-rose-100 text-rose-700',
                                default     => 'bg-slate-100 text-slate-600',
                            };
                            $status_lbl = match($trx['status']) {
                                'completed' => 'Selesai',
                                'cancelled' => 'Batal',
                                default     => ucfirst($trx['status']),
                            };
                            $pay_lbl = ['cash'=>'Tunai','qris'=>'QRIS','transfer'=>'Transfer','kasbon'=>'Kasbon'][$trx['payment_method']] ?? 'Lainnya';
                            ?>
                            <a href="<?= BASE_URL ?>/transactions/detail.php?id=<?= $trx['id'] ?>"
                               class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition group">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition">
                                        <i class="fa-solid fa-receipt text-sm"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 font-mono"><?= htmlspecialchars($trx['invoice_no']) ?></p>
                                        <p class="text-[10px] text-slate-400 truncate">
                                            <?= htmlspecialchars($trx['customer_name'] ?? 'Umum') ?> &bull; <?= $pay_lbl ?> &bull;
                                            <?= date('d/m H:i', strtotime($trx['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <p class="text-sm font-black text-slate-800"><?= format_rupiah($trx['grand_total']) ?></p>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md <?= $status_cls ?>"><?= $status_lbl ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-slate-400">
                        <i class="fa-solid fa-clock-rotate-left text-4xl mb-2 opacity-20"></i>
                        <p class="text-xs">Belum ada riwayat transaksi.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ====== 8 MODUL SISTEM (PETA FITUR KOMPETISI) ====== -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">⚡ Peta 8 Modul Sistem CuanLog</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Semua modul kompetisi tersedia dan berfungsi penuh</p>
                </div>
                <span class="text-[11px] bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2.5 py-1 rounded-full">
                    8/8 Modul Aktif ✓
                </span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <?php
                $modules = [
                    ['icon'=>'fa-right-to-bracket','color'=>'blue','title'=>'1. Login & Akses','desc'=>'Autentikasi multi-role (Admin / Kasir)','url'=>BASE_URL.'/auth/login.php'],
                    ['icon'=>'fa-chart-pie','color'=>'emerald','title'=>'2. Dashboard','desc'=>'Ringkasan omset & info usaha periode tertentu','url'=>BASE_URL.'/index.php'],
                    ['icon'=>'fa-box-open','color'=>'violet','title'=>'3. Manajemen Produk','desc'=>'CRUD produk, kode, kategori, harga, stok','url'=>BASE_URL.'/products/index.php'],
                    ['icon'=>'fa-cash-register','color'=>'amber','title'=>'4. Kasir / Transaksi','desc'=>'Pilih produk, qty, diskon, total bayar','url'=>BASE_URL.'/pos/index.php'],
                    ['icon'=>'fa-file-invoice','color'=>'teal','title'=>'5. Bukti Transaksi','desc'=>'Nota termal 58/80mm & share WhatsApp','url'=>BASE_URL.'/pos/receipt.php'],
                    ['icon'=>'fa-clock-rotate-left','color'=>'slate','title'=>'6. Riwayat Transaksi','desc'=>'Cari nota, filter tanggal & nomor','url'=>BASE_URL.'/transactions/index.php'],
                    ['icon'=>'fa-chart-line','color'=>'rose','title'=>'7. Laporan Penjualan','desc'=>'Rekap rentang tanggal & ekspor CSV','url'=>BASE_URL.'/reports/index.php'],
                    ['icon'=>'fa-layer-group','color'=>'orange','title'=>'8. Manajemen Stok','desc'=>'Stok otomatis & notif stok kritis','url'=>BASE_URL.'/products/index.php?filter=low_stock'],
                ];
                $color_map = [
                    'blue'    => ['bg'=>'bg-blue-50',    'icon'=>'bg-blue-500',    'text'=>'text-blue-700'],
                    'emerald' => ['bg'=>'bg-emerald-50', 'icon'=>'bg-emerald-500', 'text'=>'text-emerald-700'],
                    'violet'  => ['bg'=>'bg-violet-50',  'icon'=>'bg-violet-500',  'text'=>'text-violet-700'],
                    'amber'   => ['bg'=>'bg-amber-50',   'icon'=>'bg-amber-500',   'text'=>'text-amber-700'],
                    'teal'    => ['bg'=>'bg-teal-50',    'icon'=>'bg-teal-500',    'text'=>'text-teal-700'],
                    'slate'   => ['bg'=>'bg-slate-50',   'icon'=>'bg-slate-600',   'text'=>'text-slate-700'],
                    'rose'    => ['bg'=>'bg-rose-50',    'icon'=>'bg-rose-500',    'text'=>'text-rose-700'],
                    'orange'  => ['bg'=>'bg-orange-50',  'icon'=>'bg-orange-500',  'text'=>'text-orange-700'],
                ];
                foreach ($modules as $mod):
                    $c = $color_map[$mod['color']];
                ?>
                    <a href="<?= $mod['url'] ?>"
                       class="<?= $c['bg'] ?> rounded-xl p-4 border border-slate-100 hover:shadow-md transition group">
                        <div class="w-9 h-9 rounded-xl <?= $c['icon'] ?> text-white flex items-center justify-center mb-3 group-hover:scale-110 transition">
                            <i class="fa-solid <?= $mod['icon'] ?> text-sm"></i>
                        </div>
                        <p class="text-xs font-bold <?= $c['text'] ?> leading-tight"><?= $mod['title'] ?></p>
                        <p class="text-[10px] text-slate-500 mt-1 leading-relaxed"><?= $mod['desc'] ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grafik Tren Penjualan
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'Omset (Rp)',
                        data: <?= json_encode($chart_revenue) ?>,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5,150,105,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#059669',
                        borderWidth: 2.5
                    },
                    {
                        label: 'Cuan/Laba (Rp)',
                        data: <?= json_encode($chart_profit) ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.05)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#3b82f6',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 11, family: 'Plus Jakarta Sans' } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Grafik Sebaran Pembayaran (Donut)
    const payCtx = document.getElementById('paymentChart');
    if (payCtx) {
        const payData = <?= json_encode($payment_data) ?>;
        const labelsMap = {'cash':'Tunai', 'qris':'QRIS', 'transfer':'Transfer', 'kasbon':'Kasbon', 'other':'Lainnya'};
        const colorMap  = {'cash':'#f59e0b', 'qris':'#10b981', 'transfer':'#3b82f6', 'kasbon':'#8b5cf6', 'other':'#94a3b8'};

        let labels = payData.map(item => labelsMap[item.payment_method] || item.payment_method);
        let values = payData.map(item => item.cnt);
        let colors = payData.map(item => colorMap[item.payment_method] || '#94a3b8');

        new Chart(payCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '70%'
            }
        });
    }
});
</script>