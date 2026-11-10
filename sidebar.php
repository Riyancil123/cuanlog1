<?php
/**
 * CuanLog - Main Navigation Sidebar
 * Fixed: $is_pos_page (was missing $ causing PHP 8 Fatal Error)
 */
$current_uri  = $_SERVER['REQUEST_URI'] ?? '';
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$is_pos_page  = strpos($current_uri, '/pos/') !== false;

function is_active_nav($pattern) {
    global $current_uri;
    return (strpos($current_uri, $pattern) !== false);
}

function nav_cls($pattern, $exact = false) {
    global $current_uri, $current_page, $is_pos_page;
    $active = $exact
        ? $current_page === $pattern && !$is_pos_page
        : is_active_nav($pattern);
    return $active
        ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30'
        : 'hover:bg-slate-800 text-slate-300 hover:text-white';
}
?>

<!-- Mobile Sidebar Overlay -->
<div id="sidebar-overlay" onclick="toggleMobileSidebar()"
     class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm hidden lg:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col
              transition-transform duration-300 -translate-x-full
              lg:translate-x-0 lg:static lg:inset-auto
              shrink-0 border-r border-slate-800 shadow-xl">

    <!-- Brand Logo -->
    <div class="h-16 flex items-center px-5 border-b border-slate-800/80 bg-slate-950/50 shrink-0">
        <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 group min-w-0">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400
                        flex items-center justify-center text-white shadow-lg shadow-emerald-500/20
                        group-hover:scale-110 transition-transform shrink-0">
                <i class="fa-solid fa-coins text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="text-lg font-extrabold text-white tracking-tight">
                        Cuan<span class="text-emerald-400">Log</span>
                    </span>
                    <span class="text-[9px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30
                                 px-1.5 py-0.5 rounded font-mono font-bold">POS</span>
                </div>
                <p class="text-[11px] text-slate-400 truncate">
                    <?= htmlspecialchars(get_setting('store_name', 'CuanLog Mart')) ?>
                </p>
            </div>
        </a>
        <button onclick="toggleMobileSidebar()" class="lg:hidden ml-auto text-slate-400 hover:text-white p-1 shrink-0">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto p-3 space-y-0.5 scrollbar-thin">

        <!-- === UTAMA === -->
        <p class="px-3 pt-3 pb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500">Utama</p>

        <!-- Dashboard -->
        <a href="<?= BASE_URL ?>/index.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                  <?= (!$is_pos_page && $current_page === 'index.php') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-chart-pie w-5 text-center shrink-0
                      <?= (!$is_pos_page && $current_page === 'index.php') ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400' ?>"></i>
            <span>Dashboard Bisnis</span>
        </a>

        <!-- Kasir POS (highlighted) -->
        <a href="<?= BASE_URL ?>/pos/index.php"
           class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all group
                  <?= $is_pos_page ? 'bg-emerald-500 text-slate-950 shadow-lg shadow-emerald-500/30' : 'bg-emerald-600/15 text-emerald-300 border border-emerald-500/25 hover:bg-emerald-600 hover:text-white' ?>">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-cash-register w-5 text-center shrink-0
                          <?= $is_pos_page ? 'text-slate-950' : 'text-emerald-400' ?>"></i>
                <span>Kasir POS</span>
            </div>
            <span class="text-[9px] font-black px-1.5 py-0.5 rounded
                         <?= $is_pos_page ? 'bg-slate-950/20 text-slate-950' : 'bg-emerald-400 text-slate-950 animate-pulse' ?>">
                LIVE
            </span>
        </a>

        <!-- === OPERASIONAL UMKM === -->
        <p class="px-3 pt-4 pb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500">Operasional</p>

        <!-- Produk & Stok -->
        <a href="<?= BASE_URL ?>/products/index.php"
           class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                  <?= is_active_nav('/products/') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-box-open w-5 text-center shrink-0
                          <?= is_active_nav('/products/') ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400' ?>"></i>
                <span>Produk & Stok</span>
            </div>
            <?php if (isset($low_stock_count) && $low_stock_count > 0): ?>
                <span class="text-[9px] bg-rose-500 text-white font-black px-1.5 py-0.5 rounded-full shrink-0">
                    <?= $low_stock_count ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- Kategori -->
        <a href="<?= BASE_URL ?>/categories/index.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                  <?= is_active_nav('/categories/') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-tags w-5 text-center shrink-0
                      <?= is_active_nav('/categories/') ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400' ?>"></i>
            <span>Kategori Produk</span>
        </a>

        <!-- Pelanggan & CRM -->
        <a href="<?= BASE_URL ?>/customers/index.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                  <?= is_active_nav('/customers/') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-users w-5 text-center shrink-0
                      <?= is_active_nav('/customers/') ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400' ?>"></i>
            <span>Pelanggan & CRM</span>
        </a>

        <!-- Riwayat Transaksi -->
        <a href="<?= BASE_URL ?>/transactions/index.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                  <?= is_active_nav('/transactions/') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-receipt w-5 text-center shrink-0
                      <?= is_active_nav('/transactions/') ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400' ?>"></i>
            <span>Riwayat Transaksi</span>
        </a>

        <!-- === ANALITIK & PENGATURAN === -->
        <p class="px-3 pt-4 pb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500">Analitik & Pengaturan</p>

        <!-- Laporan -->
        <a href="<?= BASE_URL ?>/reports/index.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                  <?= is_active_nav('/reports/') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-chart-line w-5 text-center shrink-0
                      <?= is_active_nav('/reports/') ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400' ?>"></i>
            <span>Laporan Penjualan</span>
        </a>

        <?php if (is_admin()): ?>
        <!-- Pengaturan Toko -->
        <a href="<?= BASE_URL ?>/settings/index.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                  <?= is_active_nav('/settings/') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-gear w-5 text-center shrink-0
                      <?= is_active_nav('/settings/') ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400' ?>"></i>
            <span>Pengaturan Toko</span>
        </a>

        <!-- Restock Pintar -->
        <a href="<?= BASE_URL ?>/products/restock.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group
                  <?= is_active_nav('/products/restock') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-wand-magic-sparkles w-5 text-center shrink-0 text-amber-400"></i>
            <span>Smart Restock</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- Bottom User Card -->
    <div class="p-3 border-t border-slate-800/80 bg-slate-950/50 shrink-0">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700
                            text-white font-black text-sm flex items-center justify-center shrink-0 shadow">
                    <?= strtoupper(substr(($user['name'] ?? 'U'), 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">
                        <?= htmlspecialchars($user['name'] ?? 'Pengguna') ?>
                    </p>
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded
                                 <?= is_admin() ? 'bg-amber-500/25 text-amber-300' : 'bg-emerald-500/25 text-emerald-300' ?>">
                        <?= strtoupper($user['role'] ?? 'kasir') ?>
                    </span>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout.php"
               onclick="return confirm('Keluar dari sistem?')"
               class="p-2 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-xl transition shrink-0"
               title="Logout">
                <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
            </a>
        </div>
    </div>

</aside>
