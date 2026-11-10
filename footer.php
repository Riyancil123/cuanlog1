<?php
/**
 * CuanLog - Global Footer
 */
?>
        </main><!-- /main -->

        <footer class="bg-white border-t border-slate-200 px-5 py-2.5 text-xs text-slate-400
                       flex flex-col sm:flex-row items-center justify-between gap-1 shrink-0">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-600"><?= APP_NAME ?> v<?= APP_VERSION ?></span>
                <span class="text-slate-200">|</span>
                <span>Accelerating Digital Transformation for Business & SMEs</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-emerald-600 font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-shield-halved text-[11px]"></i> Sesi Aktif
                </span>
                <span class="text-slate-200">|</span>
                <span>&copy; <?= date('Y') ?> CuanLog</span>
            </div>
        </footer>

    </div><!-- /flex-1 flex-col -->
    </div><!-- /flex h-screen overflow-hidden -->

<script>
// Mobile sidebar toggle
function toggleMobileSidebar() {
    const sb   = document.getElementById('sidebar');
    const over = document.getElementById('sidebar-overlay');
    if (!sb) return;
    sb.classList.toggle('-translate-x-full');
    if (over) over.classList.toggle('hidden');
}
</script>
<script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
</body>
</html>
