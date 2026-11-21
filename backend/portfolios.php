<?php
/* 
   backend/portfolios.php — "โต๊ะ Portfolio"
   GET                          → ส่ง Portfolio ทั้งหมด
   POST displayName, ...        → เพิ่ม Portfolio ใหม่ 1 คน
   POST action=delete, id       → ลบ Portfolio นั้น

   รับค่าด้วย $_POST (JavaScript ส่งด้วย fetch + FormData) แล้วตอบกลับเป็น JSON
    */
require 'storage.php';

// GET: แสดงสมุดทั้งเล่ม
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendJson(readPortfolios());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'ใช้ได้แค่ GET หรือ POST'], 405);
}

$action = $_POST['action'] ?? 'create';

// POST action=delete: ลบ Portfolio 1 คน
if ($action === 'delete') {
    $portfolios = readPortfolios();                       // 1. อ่าน
    $index = findPortfolioIndex($portfolios, (string)($_POST['id'] ?? ''));
    if ($index === -1) {
        sendJson(['error' => 'ไม่พบ Portfolio'], 404);
    }
    $removed = $portfolios[$index];
    array_splice($portfolios, $index, 1);                 // 2. เอาออก 1 คน
    writePortfolios($portfolios);                         // 3. เขียน
    sendJson($removed);
}

// POST: เพิ่ม Portfolio ใหม่ 1 คน
$portfolio = buildPortfolio($_POST);
if ($portfolio === null) {
    sendJson(['error' => 'ต้องกรอก Display name'], 400);
}
foreach (['whatIEnjoy', 'whatIveTried', 'whatImCuriousAbout'] as $field) {
    if ($portfolio[$field] === '' && ($_POST['import'] ?? '') !== '1') {
        sendJson(['error' => "ต้องกรอก $field"], 400);
    }
}

$portfolios = readPortfolios();   // 1. อ่าน
$portfolios[] = $portfolio;       // 2. เพิ่ม 1 คน
writePortfolios($portfolios);     // 3. เขียน
sendJson($portfolio, 201);
