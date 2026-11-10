<?php
/**
 * CuanLog - Printable Barcode Label Generator
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$copies = isset($_GET['copies']) ? max(1, min(100, (int)$_GET['copies'])) : 12;
$db = getDB();

$products_to_print = [];

if ($db) {
    try {
        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) $products_to_print[] = $row;
        } else {
            $stmt = $db->query("SELECT * FROM products WHERE is_active = 1 AND barcode IS NOT NULL AND barcode != '' ORDER BY name ASC LIMIT 20");
            $products_to_print = $stmt->fetchAll();
        }
    } catch (Exception $e) {}
}

$store_name = get_setting('store_name', 'CuanLog Mart');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label Barcode - <?= APP_NAME ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- JsBarcode -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .barcode-card {
            page-break-inside: avoid;
            width: 58mm;
            height: 38mm;
            border: 1px dashed #cbd5e1;
            padding: 3mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            box-sizing: border-box;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; margin: 0; }
            .barcode-container {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 2mm;
                padding: 4mm;
            }
            .barcode-card {
                border: 1px dotted #ccc;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-6 text-slate-800">

    <!-- Top Action Toolbar -->
    <div class="no-print max-w-4xl mx-auto mb-6 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <a href="<?= BASE_URL ?>/products/index.php" class="p-2 text-slate-500 hover:text-slate-900 bg-slate-100 rounded-xl transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-sm font-bold text-slate-800">Cetak Label Barcode Harga</h1>
                <p class="text-xs text-slate-400">Format stiker rak/produk untuk UMKM</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <form action="" method="GET" class="flex items-center space-x-2">
                <?php if ($id > 0): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>
                <label class="text-xs text-slate-600 font-semibold">Jumlah Label:</label>
                <input type="number" name="copies" value="<?= $copies ?>" min="1" max="100" class="w-16 px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-center">
                <button type="submit" class="px-2.5 py-1 bg-slate-800 text-white rounded-lg text-xs font-semibold">Set</button>
            </form>

            <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Label</span>
            </button>
        </div>
    </div>

    <!-- Label Stickers Grid -->
    <div class="max-w-4xl mx-auto">
        <div class="barcode-container grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <?php if (empty($products_to_print)): ?>
                <div class="col-span-full py-16 text-center text-slate-400">
                    Produk tidak ditemukan atau tidak memiliki nomor barcode.
                </div>
            <?php else: ?>
                <?php foreach ($products_to_print as $p): ?>
                    <?php 
                        $barcode_val = $p['barcode'] ?: '899' . str_pad($p['id'], 9, '0', STR_PAD_LEFT);
                        $count = ($id > 0) ? $copies : 1;
                        for ($i = 0; $i < $count; $i++):
                    ?>
                        <div class="barcode-card rounded-xl text-center mx-auto">
                            <!-- Store Header -->
                            <div class="text-[9px] font-bold text-slate-500 uppercase tracking-wider truncate w-full">
                                <?= htmlspecialchars($store_name) ?>
                            </div>

                            <!-- Product Name -->
                            <div class="text-[10px] font-bold text-slate-900 leading-tight line-clamp-1 w-full px-1">
                                <?= htmlspecialchars($p['name']) ?>
                            </div>

                            <!-- Barcode SVG -->
                            <svg class="barcode-svg" jsbarcode-value="<?= htmlspecialchars($barcode_val) ?>" jsbarcode-height="28" jsbarcode-width="1.3" jsbarcode-fontSize="10" jsbarcode-margin="0"></svg>

                            <!-- Price Tag -->
                            <div class="text-xs font-extrabold text-emerald-700 font-mono">
                                <?= format_rupiah($p['selling_price']) ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            JsBarcode(".barcode-svg").init();
        });
    </script>
</body>
</html>

