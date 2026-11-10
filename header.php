<?php
/**
 * CuanLog - Global Header & App Bootstrap
 */
require_once __DIR__ . '/../config/app.php';

// Redirect to install if DB not ready
$db_check = checkDatabaseStatus();
if (!$db_check['status'] && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: ' . BASE_URL . '/install.php');
    exit;
}

// Auth gate (skip for auth pages and install)
$current_page  = basename($_SERVER['PHP_SELF'] ?? '');
$in_auth_folder = strpos($_SERVER['PHP_SELF'] ?? '', '/auth/') !== false;

if (!$in_auth_folder && $current_page !== 'install.php' && $current_page !== 'login.php') {
    require_login();
}

$user = auth_user();
$page_title = $page_title ?? 'CuanLog POS';

// Low stock badge data (used by sidebar + navbar)
$low_stock_count = 0;
$low_stock_items = [];
$db = getDB();
if ($db) {
    try {
        $ls = $db->query("SELECT id, name, stock, min_stock, unit FROM products
                          WHERE stock <= min_stock AND is_active = 1
                          ORDER BY stock ASC LIMIT 8");
        $low_stock_items = $ls->fetchAll();
        $low_stock_count = (int)$db->query("SELECT COUNT(*) FROM products
                                            WHERE stock <= min_stock AND is_active = 1")->fetchColumn();
    } catch (Exception $e) {
        $low_stock_count = 0;
    }
}
?><!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — <?= APP_NAME ?></title>
    <meta name="description" content="CuanLog - Sistem POS & Manajemen Keuangan UMKM Modern">
    <meta name="theme-color" content="#059669">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        cuan: {
                            50:  '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
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

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

    <!-- Custom Styles -->
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .transition-all { transition-duration: 200ms; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

<?php
// Flash message via SweetAlert
$flash = get_flash();
if ($flash):
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: '<?= in_array($flash['type'], ['error','warning','success','info']) ? $flash['type'] : 'info' ?>',
        title: <?= json_encode($flash['type'] === 'error' ? 'Oops!' : ($flash['type'] === 'warning' ? 'Perhatian' : 'Berhasil!')) ?>,
        text: <?= json_encode($flash['message']) ?>,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
    });
});
</script>
<?php endif; ?>

<!-- App Layout Wrapper: flex h-screen -->
<div class="flex h-screen overflow-hidden">
