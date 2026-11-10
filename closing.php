<?php
/**
 * CuanLog - Shift Closing & Cash Drawer Reconciliation (Z-Report)
 * Sub-tema: Accelerating Digital Transformation for Business and SMEs
 */
$page_title = 'Tutup Kasir & Rekonsiliasi Kas';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$user_id = (int)($_SESSION['user_id'] ?? 1);
$today = date('Y-m-d');

// Shift aggregates for today
$shift_orders_count = 0;
$shift_cash_total = 0;
$shift_qris_total = 0;
$shift_transfer_total = 0;
$shift_debt_total = 0;
$shift_grand_total = 0;

if ($db) {
    try {
        $stmt = $db->prepare("SELECT payment_method, COUNT(*) as count, SUM(grand_total) as total 
            FROM transactions 
            WHERE DATE(created_at) = ? AND user_id = ? AND status != 'cancelled'
            GROUP BY payment_method");
        $stmt->execute([$today, $user_id]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $r) {
            $amt = (float)$r['total'];
            $cnt = (int)$r['count'];
            $shift_orders_count += $cnt;
            $shift_grand_total += $amt;

            if ($r['payment_method'] === 'cash') $shift_cash_total += $amt;
            elseif ($r['payment_method'] === 'qris') $shift_qris_total += $amt;
            elseif ($r['payment_method'] === 'transfer') $shift_transfer_total += $amt;
            elseif ($r['payment_method'] === 'debt') $shift_debt_total += $amt;
        }
    } catch (Exception $e) {}
}

$store_name = get_setting('store_name', 'CuanLog Mart UMKM');
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Tutup Shift Kasir & Rekonsiliasi Uang Fisik</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Laporan pergantian shift, pencocokan uang tunai di laci kasir (Z-Report), dan ringkasan transaksi harian.</p>
            </div>

            <div class="flex items-center space-x-2">
                <button onclick="window.print()" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-2xl shadow-xs transition flex items-center space-x-2">
                    <i class="fa-solid fa-print text-emerald-400"></i>
                    <span>Cetak Laporan Tutup Shift (Z-Report)</span>
                </button>
                <a href="<?= BASE_URL ?>/pos/index.php" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-bold rounded-2xl shadow-xs transition">
                    Kembali ke Kasir
                </a>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-xs">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Penjualan Tunai</span>
                <p class="text-2xl font-black text-amber-600 font-mono-num tracking-tight mt-1">
                    <?= format_rupiah($shift_cash_total) ?>
                </p>
                <p class="text-xs text-slate-400 mt-1">Uang fisik yang wajib ada di laci</p>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-xs">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pembayaran QRIS</span>
                <p class="text-2xl font-black text-emerald-700 font-mono-num tracking-tight mt-1">
                    <?= format_rupiah($shift_qris_total) ?>
                </p>
                <p class="text-xs text-slate-400 mt-1">Masuk ke rekening e-wallet/bank</p>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-xs">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Transfer Bank</span>
                <p class="text-2xl font-black text-blue-700 font-mono-num tracking-tight mt-1">
                    <?= format_rupiah($shift_transfer_total) ?>
                </p>
                <p class="text-xs text-slate-400 mt-1">Masuk ke rekening giro toko</p>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-xs">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Kasbon Baru</span>
                <p class="text-2xl font-black text-rose-600 font-mono-num tracking-tight mt-1">
                    <?= format_rupiah($shift_debt_total) ?>
                </p>
                <p class="text-xs text-slate-400 mt-1">Tercatat di buku piutang pelanggan</p>
            </div>
        </div>

        <!-- Reconciliation Interactive Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Cash Drawer Balancing Calculator (2 cols) -->
            <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-100 shadow-sm space-y-5">
                <h3 class="text-base font-bold text-slate-800 flex items-center">
                    <i class="fa-solid fa-calculator text-emerald-600 mr-2"></i> Form Rekonsiliasi & Uji Selisih Kasir
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Modal Kas Awal Kasir (Opening Cash)</label>
                        <input type="number" id="opening-cash" value="100000" min="0" step="10000" oninput="calculateDiscrepancy()"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono-num font-bold focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition">
                        <span class="text-[11px] text-slate-400">Modal uang receh/kembalian saat mulai shift</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Uang Fisik Dihitung di Laci (Actual Cash)</label>
                        <input type="number" id="counted-cash" placeholder="0" min="0" step="1000" oninput="calculateDiscrepancy()"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono-num font-black text-emerald-800 focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:outline-none transition">
                        <span class="text-[11px] text-slate-400">Total lembaran dan koin di laci kasir saat tutup</span>
                    </div>
                </div>

                <!-- Result Box -->
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-semibold">Total Uang Tunai Seharusnya di Laci (Expected):</span>
                        <span id="expected-cash-label" class="font-mono-num font-bold text-slate-800 text-sm">Rp 0</span>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-200">
                        <span class="text-slate-500 font-semibold">Status Selisih Kas:</span>
                        <div class="text-right">
                            <span id="discrepancy-label" class="font-mono-num font-black text-lg text-emerald-700">Seimbang (Rp 0)</span>
                            <span id="discrepancy-badge" class="block text-[11px] font-bold text-slate-400">Kas Sempurna</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Catatan Pergantian Shift (Opsional)</label>
                    <textarea rows="2" placeholder="Catatan untuk kasir shift berikutnya atau pemilik toko..."
                              class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>
            </div>

            <!-- Thermal Z-Report Preview (1 col) -->
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Pratinjau Nota Z-Report</h4>
                        <span class="text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded font-mono font-bold">58mm / 80mm</span>
                    </div>

                    <!-- Printable Z-Report Container -->
                    <div id="printable-receipt" class="font-mono text-[11px] text-slate-800 p-4 bg-slate-50/70 rounded-2xl border border-slate-200/60 mt-3 space-y-1.5 leading-relaxed">
                        <div class="text-center pb-2 border-b border-dashed border-slate-300">
                            <p class="font-bold text-xs uppercase"><?= htmlspecialchars($store_name) ?></p>
                            <p class="text-[10px] text-slate-500">LAPORAN TUTUP SHIFT KASIR</p>
                            <p class="text-[10px] text-slate-400"><?= date('d/m/Y H:i') ?> WIB</p>
                        </div>
                        <div class="py-1 border-b border-dashed border-slate-300 space-y-0.5 text-[10px]">
                            <p>Kasir: <?= htmlspecialchars($user['name'] ?? 'Kasir') ?></p>
                            <p>Total Nota: <?= $shift_orders_count ?> transaksi</p>
                        </div>
                        <div class="py-1 border-b border-dashed border-slate-300 space-y-0.5">
                            <div class="flex justify-between"><span>Penjualan Tunai:</span><span><?= format_rupiah($shift_cash_total) ?></span></div>
                            <div class="flex justify-between"><span>Penjualan QRIS:</span><span><?= format_rupiah($shift_qris_total) ?></span></div>
                            <div class="flex justify-between"><span>Transfer Bank:</span><span><?= format_rupiah($shift_transfer_total) ?></span></div>
                            <div class="flex justify-between"><span>Kasbon Pelanggan:</span><span><?= format_rupiah($shift_debt_total) ?></span></div>
                        </div>
                        <div class="pt-1 font-bold text-xs flex justify-between">
                            <span>TOTAL OMSET:</span>
                            <span><?= format_rupiah($shift_grand_total) ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 text-center">
                    <button onclick="window.print()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center space-x-1.5">
                        <i class="fa-solid fa-print"></i>
                        <span>Cetak Struk Z-Report</span>
                    </button>
                </div>
            </div>

        </div>

    </main>

<script>
    const CASH_SALES = <?= $shift_cash_total ?>;

    function calculateDiscrepancy() {
        const opening = parseFloat(document.getElementById('opening-cash').value) || 0;
        const counted = parseFloat(document.getElementById('counted-cash').value) || 0;

        const expected = opening + CASH_SALES;
        const diff = counted - expected;

        document.getElementById('expected-cash-label').textContent = formatRupiah(expected);

        const diffLabel = document.getElementById('discrepancy-label');
        const diffBadge = document.getElementById('discrepancy-badge');

        if (counted === 0) {
            diffLabel.textContent = 'Masukkan Uang Fisik';
            diffLabel.className = 'font-mono-num font-bold text-sm text-slate-400';
            diffBadge.textContent = 'Belum dihitung';
            diffBadge.className = 'block text-[11px] font-bold text-slate-400';
        } else if (diff === 0) {
            diffLabel.textContent = 'Seimbang (Rp 0)';
            diffLabel.className = 'font-mono-num font-black text-lg text-emerald-600';
            diffBadge.textContent = '✓ Kas Sempurna / Tidak Ada Selisih';
            diffBadge.className = 'block text-[11px] font-bold text-emerald-600';
        } else if (diff > 0) {
            diffLabel.textContent = '+ ' + formatRupiah(diff);
            diffLabel.className = 'font-mono-num font-black text-lg text-blue-600';
            diffBadge.textContent = '⚠️ Kas Lebih (Surplus Rp ' + diff.toLocaleString('id-ID') + ')';
            diffBadge.className = 'block text-[11px] font-bold text-blue-600';
        } else {
            diffLabel.textContent = '- ' + formatRupiah(Math.abs(diff));
            diffLabel.className = 'font-mono-num font-black text-lg text-rose-600';
            diffBadge.textContent = '⛔ Kas Kurang (Defisit Rp ' + Math.abs(diff).toLocaleString('id-ID') + ')';
            diffBadge.className = 'block text-[11px] font-bold text-rose-600';
        }
    }

    calculateDiscrepancy();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

