<?php
/**
 * CuanLog - Edit Produk
 */
$page_title = 'Edit Produk';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_admin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$product = null;
$categories = [];
$errors = [];

if (!$id || !$db) {
    set_flash('error', 'Produk tidak ditemukan.');
    header('Location: ' . BASE_URL . '/products/index.php'); exit;
}

try {
    $product = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $product->execute([$id]);
    $product = $product->fetch();
    if (!$product) {
        set_flash('error', 'Produk tidak ditemukan.');
        header('Location: ' . BASE_URL . '/products/index.php'); exit;
    }
    $categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
} catch (Exception $e) {
    set_flash('error', 'Error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . '/products/index.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $barcode       = trim($_POST['barcode'] ?? '');
    $category_id   = (int)($_POST['category_id'] ?? 0);
    $cost_price    = (float)($_POST['cost_price'] ?? 0);
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $stock         = (int)($_POST['stock'] ?? 0);
    $min_stock     = (int)($_POST['min_stock'] ?? 5);
    $unit          = trim($_POST['unit'] ?? 'pcs');
    $description   = trim($_POST['description'] ?? '');

    if (empty($name)) $errors[] = 'Nama produk wajib diisi.';
    if ($selling_price <= 0) $errors[] = 'Harga jual harus lebih dari 0.';

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE products SET
                name=?, barcode=?, category_id=?, cost_price=?, selling_price=?,
                stock=?, min_stock=?, unit=?, description=?, updated_at=NOW()
                WHERE id=?");
            $stmt->execute([
                $name, $barcode ?: null, $category_id ?: null,
                $cost_price, $selling_price, $stock, $min_stock,
                $unit, $description ?: null, $id
            ]);
            log_activity('Edit Produk', "Edit produk ID $id: $name");
            set_flash('success', "Produk \"$name\" berhasil diperbarui!");
            header('Location: ' . BASE_URL . '/products/index.php'); exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal memperbarui: ' . $e->getMessage();
        }
    }

    // Merge POST with product to show latest values on error
    $product = array_merge($product, $_POST);
}
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 bg-slate-50">

        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/products/index.php"
               class="p-2 text-slate-500 hover:text-slate-800 hover:bg-white rounded-xl border border-slate-200 transition">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-xl font-black text-slate-900">Edit Produk</h1>
                <p class="text-xs text-slate-500">Perbarui data produk: <b><?= htmlspecialchars($product['name']) ?></b></p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4">
                <p class="text-sm font-bold text-rose-700 mb-1"><i class="fa-solid fa-circle-exclamation mr-1.5"></i>Ada kesalahan:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($errors as $err): ?>
                        <li class="text-xs text-rose-600"><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100">
                        <i class="fa-solid fa-box-open text-emerald-600 mr-2"></i>Informasi Produk
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Produk *</label>
                            <input type="text" name="name" required
                                   value="<?= htmlspecialchars($product['name']) ?>"
                                   class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kode / Barcode</label>
                                <input type="text" name="barcode"
                                       value="<?= htmlspecialchars($product['barcode'] ?? '') ?>"
                                       class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori</label>
                                <select name="category_id"
                                        class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none bg-white">
                                    <option value="">— Pilih Kategori —</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Deskripsi</label>
                            <textarea name="description" rows="2"
                                      class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none resize-none"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100">
                        <i class="fa-solid fa-tag text-emerald-600 mr-2"></i>Harga & Stok
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Harga Modal (HPP) *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="number" name="cost_price" id="cost_price" min="0" step="100"
                                       value="<?= $product['cost_price'] ?>"
                                       oninput="calcMargin()"
                                       class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Harga Jual *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                <input type="number" name="selling_price" id="selling_price" min="0" step="100" required
                                       value="<?= $product['selling_price'] ?>"
                                       oninput="calcMargin()"
                                       class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Stok Saat Ini</label>
                            <input type="number" name="stock" min="0"
                                   value="<?= $product['stock'] ?>"
                                   class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Stok Minimum</label>
                            <input type="number" name="min_stock" min="0"
                                   value="<?= $product['min_stock'] ?>"
                                   class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Satuan</label>
                            <select name="unit" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none bg-white">
                                <?php foreach (['pcs','kg','g','liter','ml','lusin','box','pak','karton','botol','sachet','lembar'] as $u): ?>
                                    <option value="<?= $u ?>" <?= $product['unit'] === $u ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="space-y-4">
                <div class="bg-gradient-to-br from-emerald-900 to-teal-800 rounded-2xl p-5 text-white">
                    <h3 class="text-sm font-bold text-emerald-200 mb-4">
                        <i class="fa-solid fa-coins mr-2 text-amber-400"></i>Kalkulator Cuan
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between">
                            <span class="text-emerald-300">Harga Jual</span>
                            <span id="preview_sell" class="font-bold text-white">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-emerald-300">Harga Modal</span>
                            <span id="preview_cost" class="font-bold text-white">Rp 0</span>
                        </div>
                        <div class="border-t border-emerald-700 pt-3 flex justify-between">
                            <span class="text-emerald-200 font-bold">Laba / unit</span>
                            <span id="preview_profit" class="font-black text-amber-300 text-sm">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-emerald-300">Margin Cuan</span>
                            <span id="preview_margin" class="font-black text-emerald-300">0%</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-emerald-700">
                        <div class="w-full bg-emerald-900/60 rounded-full h-2.5">
                            <div id="margin_bar" class="bg-amber-400 h-2.5 rounded-full transition-all" style="width:0%"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <p class="text-[11px] text-slate-400 mb-2">ID Produk: <b class="font-mono text-slate-600">#<?= $product['id'] ?></b></p>
                    <p class="text-[11px] text-slate-400 mb-4">Dibuat: <?= format_date_indo($product['created_at']) ?></p>
                    <button type="submit"
                            class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                    <a href="<?= BASE_URL ?>/products/index.php"
                       class="block mt-2 w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition text-center">
                        Batal
                    </a>
                </div>
            </div>
        </form>

    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
function calcMargin() {
    const sell = parseFloat(document.getElementById('selling_price').value) || 0;
    const cost = parseFloat(document.getElementById('cost_price').value) || 0;
    const profit = sell - cost;
    const margin = sell > 0 ? (profit / sell * 100) : 0;
    document.getElementById('preview_sell').textContent   = fmt(sell);
    document.getElementById('preview_cost').textContent   = fmt(cost);
    document.getElementById('preview_profit').textContent = fmt(profit);
    document.getElementById('preview_margin').textContent = margin.toFixed(1) + '%';
    document.getElementById('margin_bar').style.width = Math.min(margin, 100) + '%';
    const bar = document.getElementById('margin_bar');
    bar.className = 'h-2.5 rounded-full transition-all ' + (margin >= 20 ? 'bg-emerald-400' : margin >= 10 ? 'bg-amber-400' : 'bg-rose-400');
}
calcMargin();
</script>
