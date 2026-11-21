<?php
/* 
   parts/page_setup.php — เตรียมข้อมูลก่อนสร้างหน้า (ใช้ทั้ง create.php และ portfolio.php)
   ได้ตัวแปร:
     $portfolios   รายการ Portfolio ทั้งหมด (อ่านจากไฟล์ JSON)
     $selected     Portfolio ที่เลือกอยู่ (หรือ null)
     $selectedId   id ของ Portfolio ที่เลือก
     $flash        ข้อความแจ้งผลจาก ?msg=
     $storageError true ถ้าโฟลเดอร์ storage เขียนไม่ได้
    */
require __DIR__ . '/../../backend/lib.php';

$storageError = !ensure_storage();
$portfolios = $storageError ? [] : list_portfolios();
$flash = flash_message();

// Portfolio ที่เลือก: ?id= ใน URL → ที่จำไว้ใน cookie → อันแรก
$wantedId = $_GET['id'] ?? $_COOKIE['selectedId'] ?? '';
$selected = null;
foreach ($portfolios as $portfolio) {
    if ($portfolio['portfolioId'] === $wantedId) {
        $selected = $portfolio;
    }
}
$selected = $selected ?? ($portfolios[0] ?? null);
$selectedId = $selected['portfolioId'] ?? '';

// จำ Portfolio ที่เลือกไว้ 30 วัน เพื่อให้เปลี่ยนหน้าแล้วยังเป็นอันเดิม
if ($selectedId !== '') {
    setcookie('selectedId', $selectedId, ['expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'samesite' => 'Lax']);
}

