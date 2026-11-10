<?php
/**
 * CuanLog - Hapus Produk (Soft Delete)
 */
require_once __DIR__ . '/../includes/header.php';
require_admin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id || !$db) {
    set_flash('error', 'Produk tidak valid.');
    header('Location: ' . BASE_URL . '/products/index.php'); exit;
}

try {
    // Check if product has transactions
    $check = $db->prepare("SELECT COUNT(*) FROM transaction_items WHERE product_id = ?");
    $check->execute([$id]);
    $has_trx = (int)$check->fetchColumn() > 0;

    $prod = $db->prepare("SELECT name FROM products WHERE id = ?");
    $prod->execute([$id]);
    $product = $prod->fetch();

    if (!$product) {
        set_flash('error', 'Produk tidak ditemukan.');
        header('Location: ' . BASE_URL . '/products/index.php'); exit;
    }

    if ($has_trx) {
        // Soft delete - just deactivate
        $db->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([$id]);
        log_activity('Nonaktifkan Produk', "Produk ID $id: {$product['name']} dinonaktifkan (memiliki riwayat transaksi)");
        set_flash('warning', "Produk \"{$product['name']}\" dinonaktifkan (memiliki riwayat transaksi, tidak dihapus permanen).");
    } else {
        // Hard delete
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        log_activity('Hapus Produk', "Produk ID $id: {$product['name']} dihapus permanen");
        set_flash('success', "Produk \"{$product['name']}\" berhasil dihapus.");
    }
} catch (Exception $e) {
    set_flash('error', 'Gagal menghapus produk: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/products/index.php'); exit;
