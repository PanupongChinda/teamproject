<?php
/* =========================================================
   export.php — ดาวน์โหลดไฟล์ JSON (กดจากลิงก์ ค่ามาทาง GET)
   export.php?id=...           → my-portfolio.json
   export.php?id=...&item=2    → portfolio-item-<ชื่อ>.json (Item ลำดับที่ 2)
   ========================================================= */
require __DIR__ . '/lib.php';

$portfolio = load_portfolio($_GET['id'] ?? '');
if ($portfolio === null) {
    redirect_to('../pages/portfolio.php', ['msg' => 'not_found']);
}

$itemIndex = $_GET['item'] ?? null;

if (is_string($itemIndex) && ctype_digit($itemIndex) && isset($portfolio['items'][(int)$itemIndex])) {
    $item = $portfolio['items'][(int)$itemIndex];
    $data = $item;
    $slug = trim(preg_replace('/[\s\\\\\/:*?"<>|]+/u', '-', $item['title']), '-') ?: 'item';
    $filename = 'portfolio-item-' . $slug . '.json';
} else {
    $data = export_portfolio($portfolio);
    $filename = 'my-portfolio.json';
}

// บอก browser ให้ดาวน์โหลดเป็นไฟล์ (ชื่อภาษาไทยใช้ filename*)
header('Content-Type: application/json; charset=utf-8');
header("Content-Disposition: attachment; filename=\"" . (preg_match('/^[\x20-\x7E]+$/', $filename) ? $filename : 'portfolio-item.json') . "\"; filename*=UTF-8''" . rawurlencode($filename));
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
