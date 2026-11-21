<?php
/* 
   parts/navbar.php — แถบเมนูด้านบน + เมนูด้านข้าง (ใช้ร่วมกันทุกหน้า)
   ก่อน include ให้ตั้งค่า $activePage = 'create' | 'portfolio'
   และ $selectedId (ถ้ามี) เพื่อให้ลิงก์จำ Portfolio ที่เลือกไว้
    */
if (!function_exists('e')) {
    exit; // เปิดไฟล์นี้ตรง ๆ ไม่ได้ ต้อง include จากหน้าเว็บ
}
$activePage = $activePage ?? '';
$idQuery = !empty($selectedId) ? '?id=' . urlencode($selectedId) : '';
$current = fn(string $page) => $activePage === $page ? ' aria-current="page"' : '';
?>
    <header class="site-navbar">
        <button type="button" class="theme-toggle" id="themeToggle" role="switch" aria-checked="false" aria-label="โหมดมืด" title="เปิดโหมดมืด">
            <span class="theme-toggle-thumb" aria-hidden="true"></span>
        </button>

        <a href="../index.html" class="site-brand">PORTFOLIO</a>

        <button type="button" class="btn btn-primary menu-btn justify-self-end" data-bs-toggle="offcanvas" data-bs-target="#pageMenu" aria-controls="pageMenu" aria-label="เปิดเมนู">☰</button>
    </header>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="pageMenu" aria-labelledby="pageMenuLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold" id="pageMenuLabel">MENU</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="ปิด"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="d-grid gap-3">
                <a href="../index.html" class="btn btn-outline-primary menu-link">🏠 HOME</a>
                <a href="create.php<?= $idQuery ?>" class="btn btn-outline-primary menu-link"<?= $current('create') ?>>✍️ สร้าง Portfolio</a>
                <a href="portfolio.php<?= $idQuery ?>" class="btn btn-outline-primary menu-link"<?= $current('portfolio') ?>>📁 PORTFOLIO</a>
                <a href="game.html" class="btn btn-outline-primary menu-link">🎮 เล่น Blackjack</a>
            </nav>
        </div>
    </div>
<?php if (!empty($flash)): ?>
    <div id="flashMessage" hidden data-type="<?= e($flash[0]) ?>" data-message="<?= e($flash[1]) ?>"></div>
<?php endif; ?>
<?php if (!empty($storageError)): ?>
    <div class="alert alert-danger rounded-0 mb-0 text-center" role="alert">
        เขียนไฟล์ในโฟลเดอร์ <code>storage/</code> ไม่ได้ — ให้สิทธิ์ web server ก่อน (<code>sudo chown -R www-data:www-data storage</code>)
    </div>
<?php endif; ?>
