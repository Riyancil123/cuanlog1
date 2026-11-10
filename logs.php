<?php
/**
 * CuanLog - Security & Activity Logs Audit Trail
 */
$page_title = 'Log Aktivitas & Audit Keamanan';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_admin();

$db = getDB();

// Handle clear logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear') {
    try {
        $db->exec("TRUNCATE TABLE activity_logs");
        log_activity('Bersihkan Log', 'Seluruh riwayat audit log aktivitas telah dibersihkan');
        set_flash('success', 'Riwayat log berhasil dibersihkan.');
    } catch (Exception $e) {
        set_flash('error', $e->getMessage());
    }
    header("Location: " . BASE_URL . "/settings/logs.php");
    exit;
}

$logs = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT l.*, u.name as user_name, u.role as user_role 
            FROM activity_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC LIMIT 100");
        $logs = $stmt->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <a href="<?= BASE_URL ?>/settings/index.php" class="p-2 text-slate-500 hover:text-slate-900 bg-white border border-slate-200 rounded-xl transition shadow-xs">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Log Aktivitas & Audit Keamanan</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Catatan jejak digital seluruh tindakan operasional sistem (audit trail).</p>
                </div>
            </div>

            <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan riwayat log aktivitas?')">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-2xl transition">
                    <i class="fa-solid fa-trash-can mr-1"></i> Bersihkan Log
                </button>
            </form>
        </div>

        <!-- Logs Table Card -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Aktivitas / Aksi</th>
                            <th class="py-3 px-4">Detail Keterangan</th>
                            <th class="py-3 px-4 text-center">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    <i class="fa-solid fa-clock-rotate-left text-3xl mb-2 text-slate-300 block"></i>
                                    Belum ada catatan aktivitas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-500 font-mono">
                                        <?= format_date_indo($log['created_at'], true) ?>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="font-bold text-slate-800"><?= htmlspecialchars($log['user_name'] ?? 'Sistem') ?></span>
                                        <?php if (!empty($log['user_role'])): ?>
                                            <span class="text-[10px] text-slate-400 block capitalize"><?= htmlspecialchars($log['user_role']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg font-bold text-[11px]">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">
                                        <?= htmlspecialchars($log['details'] ?: '-') ?>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-slate-400">
                                        <?= htmlspecialchars($log['ip_address'] ?: '-') ?>
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

