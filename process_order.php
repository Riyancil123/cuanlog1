<?php
/**
 * CuanLog - POS Transaction Processor (AJAX / POST)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!auth_check()) {
    echo json_encode(['success' => false, 'message' => 'Sesi Anda telah berakhir. Silakan login kembali.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

// Receive JSON payload or POST
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data) {
    $data = $_POST;
}

$cart_items = $data['items'] ?? [];
$customer_id = !empty($data['customer_id']) ? (int)$data['customer_id'] : 1; // 1 = Walk-in default
$discount = max(0, (float)($data['discount'] ?? 0));
$payment_method = $data['payment_method'] ?? 'cash';
$amount_paid = (float)($data['amount_paid'] ?? 0);
$notes = trim($data['notes'] ?? '');

if (empty($cart_items) || !is_array($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Keranjang belanja masih kosong!']);
    exit;
}

$db = getDB();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Gagal terhubung ke database.']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Calculate and verify items & stock
    $subtotal = 0;
    $total_cost = 0;
    $verified_items = [];

    // Get current tax rate from settings
    $tax_percent = (float)get_setting('store_tax_percent', 0);

    foreach ($cart_items as $item) {
        $product_id = (int)($item['id'] ?? 0);
        $qty = (int)($item['qty'] ?? 1);

        if ($qty <= 0) continue;

        // Fetch fresh product details and lock row FOR UPDATE
        $stmt_prod = $db->prepare("SELECT id, name, cost_price, selling_price, stock, is_active FROM products WHERE id = ? FOR UPDATE");
        $stmt_prod->execute([$product_id]);
        $prod = $stmt_prod->fetch();

        if (!$prod || (int)$prod['is_active'] !== 1) {
            throw new Exception("Produk #" . $product_id . " tidak ditemukan atau sudah dinonaktifkan.");
        }

        if ($prod['stock'] < $qty) {
            throw new Exception("Stok untuk '" . $prod['name'] . "' tidak mencukupi (Tersisa: " . $prod['stock'] . ", Diminta: " . $qty . ").");
        }

        $item_subtotal = (float)$prod['selling_price'] * $qty;
        $item_cost = (float)$prod['cost_price'] * $qty;

        $subtotal += $item_subtotal;
        $total_cost += $item_cost;

        $verified_items[] = [
            'product_id' => $prod['id'],
            'product_name' => $prod['name'],
            'cost_price' => $prod['cost_price'],
            'price' => $prod['selling_price'],
            'qty' => $qty,
            'subtotal' => $item_subtotal,
            'remaining_stock' => $prod['stock'] - $qty
        ];
    }

    if (empty($verified_items)) {
        throw new Exception("Tidak ada item yang valid untuk diproses.");
    }

    // Calculate tax & grand total
    $tax_amount = round(($subtotal - $discount) * ($tax_percent / 100), 2);
    if ($tax_amount < 0) $tax_amount = 0;
    $grand_total = max(0, ($subtotal - $discount + $tax_amount));

    // Handle payment method specific validations
    $status = 'completed';
    if ($payment_method === 'cash') {
        if ($amount_paid < $grand_total) {
            throw new Exception("Nominal pembayaran tunai kurang dari total belanja!");
        }
        $change_due = $amount_paid - $grand_total;
    } elseif ($payment_method === 'debt') {
        if ($customer_id <= 1) {
            throw new Exception("Metode Kasbon / Piutang wajib memilih pelanggan terdaftar (bukan Pelanggan Umum).");
        }
        $amount_paid = 0;
        $change_due = 0;
        $status = 'pending';
    } else {
        // Non-cash (QRIS, Transfer): exact payment
        $amount_paid = $grand_total;
        $change_due = 0;
    }

    // 2. Insert into transactions table
    $invoice_no = generate_invoice_no();
    $user_id = (int)$_SESSION['user_id'];

    $stmt_tx = $db->prepare("INSERT INTO transactions 
        (invoice_no, user_id, customer_id, subtotal, discount, tax, grand_total, total_cost, payment_method, amount_paid, change_due, status, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_tx->execute([
        $invoice_no,
        $user_id,
        $customer_id > 0 ? $customer_id : null,
        $subtotal,
        $discount,
        $tax_amount,
        $grand_total,
        $total_cost,
        $payment_method,
        $amount_paid,
        $change_due,
        $status,
        $notes
    ]);

    $transaction_id = $db->lastInsertId();

    // 3. Insert transaction items & decrement stock
    $stmt_item = $db->prepare("INSERT INTO transaction_items 
        (transaction_id, product_id, product_name, cost_price, price, qty, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt_stock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($verified_items as $v_item) {
        $stmt_item->execute([
            $transaction_id,
            $v_item['product_id'],
            $v_item['product_name'],
            $v_item['cost_price'],
            $v_item['price'],
            $v_item['qty'],
            $v_item['subtotal']
        ]);

        $stmt_stock->execute([$v_item['qty'], $v_item['product_id']]);
    }

    // 4. If registered customer, award loyalty points (e.g. 1 point per Rp 10.000 spent)
    if ($customer_id > 1) {
        $earned_points = floor($grand_total / 10000);
        $stmt_cust = $db->prepare("UPDATE customers SET points = points + ?, total_spent = total_spent + ? WHERE id = ?");
        $stmt_cust->execute([$earned_points, $grand_total, $customer_id]);
    }

    // 5. Activity log
    log_activity('Transaksi POS', "Penjualan berhasil #$invoice_no sebesar " . format_rupiah($grand_total));

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil diproses!',
        'transaction_id' => $transaction_id,
        'invoice_no' => $invoice_no,
        'grand_total' => $grand_total,
        'amount_paid' => $amount_paid,
        'change_due' => $change_due
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

