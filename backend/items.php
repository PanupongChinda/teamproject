<?php
/* 
   backend/items.php — "โต๊ะ Item": ทำกับ Item ทีละ 1 ชิ้น ใน Portfolio ที่มี id นั้น
   POST portfolioId, title, ...                   → เพิ่ม Item ใหม่
   POST action=update, portfolioId, itemId, ...   → แก้ไข Item
   POST action=delete, portfolioId, itemId        → ลบ Item

   ช่องที่รับ (ชื่อตาม Data Contract): title, type, otherType, date, role, description,
   skills ("HTML, CSS"), whatIDid, whatILearned, challenge, nextStep, evidence (บรรทัดละ 1 ลิงก์)
    */
require 'storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'ใช้ได้แค่ POST'], 405);
}

$action = $_POST['action'] ?? 'create';
$portfolioId = (string)($_POST['portfolioId'] ?? '');
$itemId = (string)($_POST['itemId'] ?? '');

// สร้าง Item จากฟอร์มก่อน (ยกเว้นตอนลบ) แล้วตรวจว่ากรอกครบ
if ($action !== 'delete') {
    $item = buildItem($_POST);
    if ($item === null) {
        sendJson(['error' => 'ต้องกรอก title'], 400);
    }
    // Item ที่นำเข้าทีละหลายชิ้น (import=1) อาจกรอกไม่ครบ → ตรวจแค่ title
    $error = ($_POST['import'] ?? '') === '1' ? '' : checkItem($item, $_POST);
    if ($error !== '') {
        sendJson(['error' => $error], 400);
    }
}

// 1. อ่าน
$portfolios = readPortfolios();
$p = findPortfolioIndex($portfolios, $portfolioId);
if ($p === -1) {
    sendJson(['error' => 'ไม่พบ Portfolio'], 404);
}

// 2. เปลี่ยน 1 ชิ้น
if ($action === 'create') {
    $portfolios[$p]['items'][] = $item;
    $status = 201;
} else {
    $i = findItemIndex($portfolios[$p]['items'], $itemId);
    if ($i === -1) {
        sendJson(['error' => 'ไม่พบ Item'], 404);
    }
    if ($action === 'update') {
        $item['id'] = $itemId;                         // แก้ไขแล้วใช้ id เดิม
        $portfolios[$p]['items'][$i] = $item;
    } elseif ($action === 'delete') {
        $item = $portfolios[$p]['items'][$i];
        array_splice($portfolios[$p]['items'], $i, 1);
    } else {
        sendJson(['error' => 'action ไม่ถูกต้อง'], 400);
    }
    $status = 200;
}

// 3. เขียน
writePortfolios($portfolios);
sendJson($item, $status);
