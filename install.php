<?php
/**
 * CuanLog - 1-Click Database Installer & Migration Wizard
 */
require_once __DIR__ . '/config/database.php';

$message = null;
$message_type = 'info';
$action = $_POST['action'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if ($action === 'install' || $action === 'reset') {
        $host = $_POST['db_host'] ?? DB_HOST;
        $port = $_POST['db_port'] ?? DB_PORT;
        $user = $_POST['db_user'] ?? DB_USER;
        $pass = $_POST['db_pass'] ?? DB_PASS;
        $dbname = $_POST['db_name'] ?? DB_NAME;

        try {
            // First connect without dbname to create database if not exists
            $pdo_root = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $pdo_root->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo_root->exec("USE `$dbname`");

            // Read database.sql file
            $sql_file = __DIR__ . '/database.sql';
            if (!file_exists($sql_file)) {
                throw new Exception("File database.sql tidak ditemukan di $sql_file");
            }

            $sql = file_get_contents($sql_file);

            // Execute queries
            $pdo_root->exec($sql);

            $message = "Sukses! Database '$dbname' dan seluruh tabel serta data sampel UMKM berhasil diinisialisasi.";
            $message_type = 'success';
        } catch (Exception $e) {
            $message = "Gagal inisialisasi database: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

$status = checkDatabaseStatus();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inisialisasi Database - CuanLog POS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex items-center justify-center p-4">

    <div class="max-w-xl w-full bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 p-8 text-white relative">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-2xl font-bold shadow-inner">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight">CuanLog Setup Wizard</h1>
                    <p class="text-emerald-100 text-sm">Sistem Kasir Pintar & Akuntansi UMKM Modern</p>
                </div>
            </div>
            <div class="text-xs bg-emerald-800/40 border border-emerald-400/30 rounded-lg px-3 py-1.5 inline-block mt-2">
                <i class="fa-solid fa-bolt mr-1"></i> Sub-tema: Accelerating Digital Transformation for Business and SMEs
            </div>
        </div>

        <div class="p-8">
            <!-- Alert message if any -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-2xl flex items-start space-x-3 <?= $message_type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
                    <div class="text-lg mt-0.5">
                        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?>"></i>
                    </div>
                    <div class="flex-1 text-sm leading-relaxed">
                        <?= htmlspecialchars($message) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Status Card -->
            <div class="p-5 rounded-2xl mb-6 <?= $status['status'] ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200' ?>">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider <?= $status['status'] ? 'text-emerald-700' : 'text-amber-800' ?>">
                        Status Database
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $status['status'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                        <span class="w-2 h-2 rounded-full mr-1.5 <?= $status['status'] ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' ?>"></span>
                        <?= $status['status'] ? 'Siap Digunakan' : 'Perlu Setup / Konfigurasi' ?>
                    </span>
                </div>
                <p class="text-xs text-slate-600 mt-1">
                    <?= $status['status'] ? 'Database cuanlog_db telah terpasang dengan data demo produk, kasir, dan transaksi UMKM.' : htmlspecialchars($status['error'] ?? 'Database belum diinisialisasi.') ?>
                </p>
            </div>

            <!-- Form Setup -->
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="<?= $status['status'] ? 'reset' : 'install' ?>">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">DB Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars(DB_HOST) ?>" required
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">DB Port</label>
                        <input type="text" name="db_port" value="<?= htmlspecialchars(DB_PORT) ?>" required
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">DB Username</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars(DB_USER) ?>" required
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">DB Password</label>
                        <input type="password" name="db_pass" value="<?= htmlspecialchars(DB_PASS) ?>" placeholder="(Kosong jika default XAMPP)"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Database</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars(DB_NAME) ?>" required
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                </div>

                <div class="pt-3">
                    <button type="submit" onclick="return confirm('Proses ini akan membuat atau memperbarui tabel database cuanlog_db. Lanjutkan?')"
                            class="w-full py-3.5 px-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-2xl shadow-lg shadow-emerald-500/25 flex items-center justify-center space-x-2 transition transform active:scale-98">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span><?= $status['status'] ? 'Reset & Pasang Ulang Data Demo' : 'Inisialisasi Database Otomatis' ?></span>
                    </button>
                </div>
            </form>

            <?php if ($status['status']): ?>
                <div class="mt-6 pt-6 border-t border-slate-100 flex flex-col space-y-3">
                    <a href="index.php" class="w-full py-3 px-6 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-2xl text-center shadow transition flex items-center justify-center space-x-2">
                        <span>Buka Aplikasi CuanLog</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                    <div class="bg-slate-100 p-4 rounded-xl text-xs text-slate-600 space-y-1">
                        <p class="font-bold text-slate-800">Akun Pengguna Demo:</p>
                        <p><span class="font-semibold">Administrator (Owner):</span> username: <code class="bg-white px-1.5 py-0.5 rounded border">admin</code> | pass: <code class="bg-white px-1.5 py-0.5 rounded border">admin123</code></p>
                        <p><span class="font-semibold">Kasir:</span> username: <code class="bg-white px-1.5 py-0.5 rounded border">kasir</code> | pass: <code class="bg-white px-1.5 py-0.5 rounded border">kasir123</code></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

