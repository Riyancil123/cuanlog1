<?php
/**
 * CuanLog - Manajemen Pengguna
 */
$page_title = 'Manajemen Pengguna';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_admin();

$db = getDB();
$errors = [];
$edit_user = null;

// Handle delete/toggle
if (isset($_GET['toggle']) && $db) {
    $uid = (int)$_GET['toggle'];
    $cur_user = auth_user();
    if ($uid === (int)($cur_user['id'] ?? 0)) {
        set_flash('error', 'Tidak dapat menonaktifkan akun sendiri!');
    } else {
        $db->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ?")->execute([$uid]);
        set_flash('success', 'Status pengguna diperbarui.');
    }
    header('Location: ' . BASE_URL . '/settings/users.php'); exit;
}

// Handle POST: Add or Edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $uid      = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'kasir';
    $phone    = trim($_POST['phone'] ?? '');

    if (empty($name)) $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($username)) $errors[] = 'Username wajib diisi.';
    if (!$uid && empty($password)) $errors[] = 'Password wajib diisi untuk pengguna baru.';

    if (empty($errors)) {
        try {
            if ($uid > 0) {
                // Update
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $db->prepare("UPDATE users SET name=?, username=?, password=?, role=?, phone=? WHERE id=?")
                       ->execute([$name, $username, $hashed, $role, $phone, $uid]);
                } else {
                    $db->prepare("UPDATE users SET name=?, username=?, role=?, phone=? WHERE id=?")
                       ->execute([$name, $username, $role, $phone, $uid]);
                }
                set_flash('success', "Pengguna \"$name\" berhasil diperbarui!");
            } else {
                // Create
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $db->prepare("INSERT INTO users (name, username, password, role, phone) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$name, $username, $hashed, $role, $phone]);
                set_flash('success', "Pengguna \"$name\" berhasil ditambahkan!");
            }
            log_activity('Kelola Pengguna', "Simpan pengguna: $name ($role)");
            header('Location: ' . BASE_URL . '/settings/users.php'); exit;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $errors[] = 'Username sudah digunakan, pilih username lain.';
            } else {
                $errors[] = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}

// Edit form
if (isset($_GET['edit']) && $db) {
    $e = $db->prepare("SELECT * FROM users WHERE id = ?");
    $e->execute([(int)$_GET['edit']]);
    $edit_user = $e->fetch();
}

// Fetch users
$users = [];
if ($db) {
    try {
        $users = $db->query("SELECT u.*, COUNT(t.id) as total_trx
            FROM users u
            LEFT JOIN transactions t ON t.user_id = u.id
            GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 bg-slate-50">

        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/settings/index.php"
               class="p-2 text-slate-500 hover:text-slate-800 hover:bg-white rounded-xl border border-slate-200 transition">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-xl font-black text-slate-900">Manajemen Pengguna</h1>
                <p class="text-xs text-slate-500">Kelola akun admin dan kasir yang dapat mengakses sistem.</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4">
            <?php foreach ($errors as $err): ?>
                <p class="text-xs text-rose-600"><i class="fa-solid fa-circle-exclamation mr-1.5"></i><?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Form Add/Edit User -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100">
                    <i class="fa-solid fa-<?= $edit_user ? 'user-pen' : 'user-plus' ?> text-emerald-600 mr-2"></i>
                    <?= $edit_user ? 'Edit Pengguna' : 'Tambah Pengguna' ?>
                </h3>
                <form method="POST" class="space-y-4">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                    <?php endif; ?>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap *</label>
                        <input type="text" name="name" required
                               value="<?= htmlspecialchars($edit_user['name'] ?? '') ?>"
                               placeholder="Nama lengkap pengguna"
                               class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username *</label>
                        <input type="text" name="username" required
                               value="<?= htmlspecialchars($edit_user['username'] ?? '') ?>"
                               placeholder="username login"
                               class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Password <?= $edit_user ? '(kosongkan = tidak diubah)' : '*' ?>
                        </label>
                        <input type="password" name="password"
                               placeholder="<?= $edit_user ? 'Biarkan kosong jika tidak diubah' : 'Minimal 6 karakter' ?>"
                               class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role</label>
                            <select name="role" class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none bg-white">
                                <option value="kasir" <?= ($edit_user['role'] ?? 'kasir') === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                <option value="admin" <?= ($edit_user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin / Pemilik</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">No. HP</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($edit_user['phone'] ?? '') ?>"
                                   placeholder="08xx..."
                                   class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                    </div>
                    <button type="submit"
                            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition">
                        <i class="fa-solid fa-<?= $edit_user ? 'floppy-disk' : 'plus' ?> mr-1.5"></i>
                        <?= $edit_user ? 'Simpan Perubahan' : 'Tambah Pengguna' ?>
                    </button>
                    <?php if ($edit_user): ?>
                    <a href="<?= BASE_URL ?>/settings/users.php"
                       class="block w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition text-center">
                        Batal
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Users List -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-800">Daftar Pengguna (<?= count($users) ?>)</h3>
                </div>
                <?php if (empty($users)): ?>
                    <div class="text-center py-16 text-slate-400">
                        <i class="fa-solid fa-users text-5xl mb-3 opacity-20"></i>
                        <p class="font-semibold">Belum ada pengguna</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-50">
                        <?php foreach ($users as $u):
                            $cur = auth_user();
                            $is_self = $u['id'] == ($cur['id'] ?? 0);
                        ?>
                        <div class="flex items-center justify-between p-4 hover:bg-slate-50 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl <?= $u['role'] === 'admin' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' ?> flex items-center justify-center font-black text-sm">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">
                                        <?= htmlspecialchars($u['name']) ?>
                                        <?php if ($is_self): ?>
                                            <span class="text-[10px] font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded ml-1">Anda</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        @<?= htmlspecialchars($u['username']) ?> ·
                                        <?= $u['total_trx'] ?> transaksi
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 text-[11px] font-bold rounded-lg <?= $u['role'] === 'admin' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                    <?= strtoupper($u['role']) ?>
                                </span>
                                <?php if ($u['is_active']): ?>
                                    <span class="px-2 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-600">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-[11px] font-bold rounded-lg bg-slate-100 text-slate-500">Nonaktif</span>
                                <?php endif; ?>
                                <a href="?edit=<?= $u['id'] ?>"
                                   class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </a>
                                <?php if (!$is_self): ?>
                                <a href="?toggle=<?= $u['id'] ?>"
                                   onclick="return confirm('<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> pengguna ini?')"
                                   class="p-1.5 <?= $u['is_active'] ? 'text-rose-500 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' ?> rounded-lg transition">
                                    <i class="fa-solid fa-<?= $u['is_active'] ? 'ban' : 'check' ?> text-xs"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
