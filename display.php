<?php
/**
 * CuanLog - Customer Facing Display (Dual Screen Mode)
 * Sub-tema: Accelerating Digital Transformation for Business and SMEs
 */
require_once __DIR__ . '/../config/app.php';

$store_name = get_setting('store_name', 'CuanLog Mart UMKM');
$store_tagline = get_setting('store_tagline', 'Solusi Kasir Pintar & Keuangan UMKM');
$qris_merchant_name = get_setting('qris_merchant_name', 'CUANLOG MART INDONESIA');
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layar Pelanggan - <?= htmlspecialchars($store_name) ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        emerald: {
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full text-slate-100 font-sans flex flex-col justify-between overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 selection:bg-emerald-500 selection:text-white">

    <!-- Top Bar Branding -->
    <header class="h-20 bg-slate-900/80 backdrop-blur border-b border-slate-800 px-8 flex items-center justify-between shrink-0">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white text-2xl shadow-lg shadow-emerald-500/20">
                <i class="fa-solid fa-coins"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black tracking-tight text-white"><?= htmlspecialchars($store_name) ?></h1>
                <p class="text-xs text-emerald-400"><?= htmlspecialchars($store_tagline) ?></p>
            </div>
        </div>
        <div class="flex items-center space-x-3 text-xs text-slate-400">
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-ping"></span> Layar Pelanggan Terhubung
            </span>
            <button onclick="toggleFullscreen()" class="p-2 text-slate-400 hover:text-white" title="Layar Penuh (F11)">
                <i class="fa-solid fa-expand text-base"></i>
            </button>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="flex-1 flex flex-col lg:flex-row overflow-hidden p-6 gap-6">
        
        <!-- Left Side: Interactive QRIS / Promo Card -->
        <div class="w-full lg:w-5/12 bg-slate-900/70 border border-slate-800/80 rounded-3xl p-6 flex flex-col justify-between backdrop-blur shadow-2xl">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                        <i class="fa-solid fa-wallet mr-1"></i> Pembayaran Digital UMKM
                    </span>
                    <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded-full font-mono">
                        QRIS Standar BI
                    </span>
                </div>

                <!-- Dynamic QRIS Display Container -->
                <div id="qris-box" class="mt-6 text-center">
                    <div class="p-4 bg-white rounded-2xl inline-block shadow-2xl ring-4 ring-emerald-500/20">
                        <div id="customer-qrcode" class="mx-auto flex justify-center"></div>
                    </div>
                    <div class="mt-3">
                        <p class="text-sm font-black text-white tracking-wider uppercase"><?= htmlspecialchars($qris_merchant_name) ?></p>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5">Scan via GoPay, OVO, DANA, BCA, Livin, dll.</p>
                    </div>
                </div>
            </div>

            <!-- UMKM Empowerment Banner -->
            <div class="p-4 bg-gradient-to-r from-emerald-950/60 to-slate-900 border border-emerald-500/20 rounded-2xl">
                <p class="text-xs font-bold text-emerald-300 flex items-center">
                    <i class="fa-solid fa-heart mr-2 text-emerald-400"></i> Dukung Gerakan Belanja di UMKM Lokal
                </p>
                <p class="text-[11px] text-slate-400 mt-1">Setiap transaksi Anda membantu pertumbuhan ekonomi masyarakat dan digitalisasi usaha kecil.</p>
            </div>
        </div>

        <!-- Right Side: Live Items Receipt & Total -->
        <div class="flex-1 bg-slate-900/90 border border-slate-800/80 rounded-3xl flex flex-col justify-between overflow-hidden backdrop-blur shadow-2xl">
            
            <!-- Items Table Header -->
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center">
                        <i class="fa-solid fa-cart-shopping text-emerald-400 mr-2"></i> Rincian Belanja Anda
                    </h2>
                    <p class="text-xs text-slate-400"><span id="cust-item-count">0</span> barang terdaftar di kasir</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-semibold text-slate-400">Pelanggan:</span>
                    <p id="cust-name-label" class="text-xs font-bold text-emerald-300">Pelanggan Umum</p>
                </div>
            </div>

            <!-- Items List Scrollable -->
            <div id="cust-items-list" class="flex-1 overflow-y-auto p-5 space-y-2.5">
                <div id="cust-empty-notice" class="h-full flex flex-col items-center justify-center text-center text-slate-500 py-16">
                    <div class="w-16 h-16 rounded-full bg-slate-800/60 flex items-center justify-center text-slate-400 text-2xl mb-3">
                        <i class="fa-solid fa-barcode"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-400">Menunggu Kasir Memindai Produk...</p>
                    <p class="text-xs text-slate-500 mt-1">Daftar barang belanjaan Anda akan tampil secara otomatis di layar ini.</p>
                </div>
                <!-- Dynamic cart list injected via JS -->
                <div id="cust-cart-rows" class="space-y-2"></div>
            </div>

            <!-- Bottom Grand Total Banner -->
            <div class="p-6 bg-slate-950/80 border-t border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Pembayaran</span>
                    <div id="cust-grand-total" class="text-4xl lg:text-5xl font-black text-emerald-400 font-mono tracking-tight mt-1">
                        Rp 0
                    </div>
                </div>

                <div id="cust-change-box" class="hidden text-right">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kembalian</span>
                    <div id="cust-change-total" class="text-2xl lg:text-3xl font-black text-teal-300 font-mono mt-1">
                        Rp 0
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="h-10 bg-slate-950 border-t border-slate-800/80 px-8 flex items-center justify-between text-[11px] text-slate-400 shrink-0">
        <span>Powered by CuanLog Smart POS &bull; Accelerating Digital Transformation for SMEs</span>
        <span id="cust-live-time"></span>
    </footer>

    <!-- Synchronizer Script via BroadcastChannel & LocalStorage -->
    <script>
        const qrContainer = document.getElementById('customer-qrcode');
        let qrcodeInstance = null;

        function updateDisplay(data) {
            if (!data) return;

            const items = data.cart || [];
            const totals = data.totals || { grandTotal: 0, subtotal: 0, discount: 0 };
            const customer = data.customer || 'Pelanggan Umum';

            // Update Customer Name
            document.getElementById('cust-name-label').textContent = customer;

            // Update Count
            const totalQty = items.reduce((sum, it) => sum + it.qty, 0);
            document.getElementById('cust-item-count').textContent = totalQty;

            // Render Items
            const emptyNotice = document.getElementById('cust-empty-notice');
            const rowsContainer = document.getElementById('cust-cart-rows');

            if (items.length === 0) {
                emptyNotice.classList.remove('hidden');
                rowsContainer.innerHTML = '';
            } else {
                emptyNotice.classList.add('hidden');
                let html = '';
                items.forEach(it => {
                    const sub = it.price * it.qty;
                    html += `
                        <div class="p-3 bg-slate-800/60 border border-slate-700/60 rounded-2xl flex items-center justify-between animate-in fade-in duration-200">
                            <div>
                                <h4 class="text-sm font-bold text-white">${it.name}</h4>
                                <p class="text-xs text-slate-400 font-mono">${it.qty} x Rp ${Number(it.price).toLocaleString('id-ID')}</p>
                            </div>
                            <div class="text-base font-black text-emerald-400 font-mono">
                                Rp ${Number(sub).toLocaleString('id-ID')}
                            </div>
                        </div>
                    `;
                });
                rowsContainer.innerHTML = html;
            }

            // Update Grand Total
            document.getElementById('cust-grand-total').textContent = 'Rp ' + Number(totals.grandTotal).toLocaleString('id-ID');

            // Update QRIS
            qrContainer.innerHTML = '';
            const payload = `00020101021226610014ID.LINKAJA.WWW01189360091400000000000215ID10200238475920303UMI51440014ID.CO.QRIS.WWW0215ID10200238475920303UMI520454995303360540${totals.grandTotal}5802ID5919CUANLOG MART UMKM6007JAKARTA62070703A016304`;
            
            qrcodeInstance = new QRCode(qrContainer, {
                text: payload,
                width: 170,
                height: 170,
                colorDark: "#022c22",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        // 1. Listen via BroadcastChannel
        if ('BroadcastChannel' in window) {
            const channel = new BroadcastChannel('cuanlog_pos_channel');
            channel.onmessage = (event) => {
                if (event.data && event.data.type === 'SYNC_CART') {
                    updateDisplay(event.data);
                }
            };
        }

        // 2. Listen via LocalStorage fallback
        window.addEventListener('storage', (e) => {
            if (e.key === 'cuanlog_display_cart_sync') {
                try {
                    const data = JSON.parse(e.newValue);
                    updateDisplay(data);
                } catch (err) {}
            }
        });

        // 3. Initial load
        try {
            const saved = localStorage.getItem('cuanlog_display_cart_sync');
            if (saved) updateDisplay(JSON.parse(saved));
        } catch (e) {}

        // Fullscreen Toggle
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        }

        // Live Clock
        setInterval(() => {
            const now = new Date();
            document.getElementById('cust-live-time').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        }, 1000);
    </script>
</body>
</html>

