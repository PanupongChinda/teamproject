<?php
/* =========================================================
   save_portfolio.php — รับฟอร์ม "Create Portfolio" (method="post")
   แล้วบันทึกเป็นไฟล์ JSON ใหม่ จากนั้นกลับไปหน้าสร้าง Portfolio
   ========================================================= */
require __DIR__ . '/lib.php';

$back = '../pages/create.php';
require_post($back);

// 1) รับค่าจากฟอร์ม
$name    = $_POST['name'] ?? '';
$enjoy   = $_POST['enjoy'] ?? '';
$tried   = $_POST['tried'] ?? '';
$curious = $_POST['curious'] ?? '';

// 2) ตรวจว่ากรอกครบทุกช่อง
$portfolio = clean_portfolio([
    'name' => $name,
    'whatIEnjoy' => $enjoy,
    'whatIveTried' => $tried,
    'whatImCuriousAbout' => $curious,
    'items' => [],
], new_id('p'));

if ($portfolio === null || $portfolio['whatIEnjoy'] === '' || $portfolio['whatIveTried'] === '' || $portfolio['whatImCuriousAbout'] === '') {
    redirect_to($back, ['msg' => 'portfolio_invalid']);
}

// 3) บันทึกไฟล์
if (!ensure_storage() || !save_portfolio($portfolio)) {
    redirect_to($back, ['msg' => 'storage_error']);
}

redirect_to($back, ['id' => $portfolio['portfolioId'], 'msg' => 'portfolio_created', 'name' => $portfolio['name']]);
