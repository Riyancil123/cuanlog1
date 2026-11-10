<?php
/**
 * CuanLog - Top Navbar
 */
$nav_user = $user ?? auth_user();
?>
<header class="h-14 bg-white border-b border-slate-200 px-4 sm:px-5 flex items-center justify-between shrink-0 shadow-sm z-30">

    <!-- Left: Mobile toggle + Page Title -->
    <div class="flex items-center gap-3">
        <button type="button" onclick="toggleMobileSidebar()"
                class="lg:hidden p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition">
            <i class="fa-solid fa-bars text-base"></i>
        </button>

        <div class="flex items-center gap-2">
            <span class="hidden sm:inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-100">
                <i class="fa-solid fa-store text-[10px]"></i> CuanLog POS
            </span>
            <span class="text-slate-300 hidden sm:inline text-xs">/</span>
            <h1 class="text-sm font-bold text-slate-800 truncate max-w-[200px] sm:max-w-xs">
                <?= htmlspecialchars($page_title ?? 'Dashboard') ?>
            </h1>
        </div>
    </div>

    <!-- Right: Actions -->
    <div class="flex items-center gap-2">

        <!-- Live Clock -->
        <div class="hidden md:flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500">
            <i class="fa-regular fa-clock text-emerald-600 text-[11px]"></i>
            <span id="live-clock"><?= date('H:i') ?></span>
            <span class="text-slate-300">|</span>
            <span><?= date('d M Y') ?></span>
        </div>

        <!-- Quick POS Button -->
        <a href="<?= BASE_URL ?>/pos/index.php"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700
                  text-white text-xs font-bold rounded-xl shadow shadow-emerald-600/20 transition">
            <i class="fa-solid fa-cash-register text-[11px]"></i>
            <span class="hidden sm:inline">Kasir POS</span>
        </a>

        <!-- Notification Bell (Low Stock) -->
        <div class="relative" id="notif-wrap">
            <button type="button" onclick="toggleNotif()" id="notif-btn"
                    class="relative p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition">
                <i class="fa-solid fa-bell text-base"></i>
                <?php if (isset($low_stock_count) && $low_stock_count > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-rose-500 text-white text-[9px] font-black
                                 rounded-full flex items-center justify-center animate-pulse">
                        <?= min($low_stock_count, 9) ?><?= $low_stock_count > 9 ? '+' : '' ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown Notifikasi Stok -->
            <div id="notif-menu"
                 class="hidden absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-50">
                <div class="px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
                    <span class="text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-400"></i>
                        Peringatan Stok
                    </span>
                    <?php if (isset($low_stock_count) && $low_stock_count > 0): ?>
                        <span class="text-[10px] bg-rose-500 text-white px-2 py-0.5 rounded-full font-black">
                            <?= $low_stock_count ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="max-h-64 overflow-y-auto divide-y divide-slate-50">
                    <?php if (empty($low_stock_items)): ?>
                        <div class="p-6 text-center text-slate-400">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-2 block"></i>
                            <p class="text-xs font-semibold">Stok aman! Semua produk OK.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($low_stock_items as $ls_item): ?>
                            <a href="<?= BASE_URL ?>/products/edit.php?id=<?= $ls_item['id'] ?>"
                               class="flex items-center justify-between px-4 py-3 hover:bg-slate-50 transition">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-800 truncate">
                                        <?= htmlspecialchars($ls_item['name']) ?>
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        Min <?= $ls_item['min_stock'] ?> <?= $ls_item['unit'] ?> • klik untuk restock
                                    </p>
                                </div>
                                <span class="ml-2 shrink-0 text-xs font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-100">
                                    <?= $ls_item['stock'] ?> <?= $ls_item['unit'] ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-100 text-center">
                    <a href="<?= BASE_URL ?>/products/index.php?filter=low_stock"
                       class="text-xs font-bold text-emerald-600 hover:text-emerald-700">
                        Lihat Semua Stok Menipis &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- User Avatar -->
        <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
            <div class="w-8 h-8 rounded-xl bg-slate-800 text-white text-xs font-black
                        flex items-center justify-center shadow-xs">
                <?= strtoupper(substr(($nav_user['name'] ?? 'U'), 0, 1)) ?>
            </div>
            <div class="hidden lg:block">
                <p class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[90px]">
                    <?= htmlspecialchars($nav_user['name'] ?? 'Pengguna') ?>
                </p>
                <p class="text-[10px] text-slate-400 capitalize leading-tight">
                    <?= htmlspecialchars($nav_user['role'] ?? 'kasir') ?>
                </p>
            </div>
        </div>

    </div>
</header>

<script>
// Live clock
setInterval(() => {
    const el = document.getElementById('live-clock');
    if (el) {
        const n = new Date();
        el.textContent = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
    }
}, 10000);

// Notification dropdown
function toggleNotif() {
    document.getElementById('notif-menu').classList.toggle('hidden');
}
document.addEventListener('click', e => {
    const wrap = document.getElementById('notif-wrap');
    const menu = document.getElementById('notif-menu');
    if (wrap && menu && !wrap.contains(e.target)) menu.classList.add('hidden');
});
</script>
