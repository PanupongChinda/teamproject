<?php
/* =========================================================
   save_item.php — รับฟอร์ม "Portfolio Item" (method="post")
   - ไม่มี edit_index : เพิ่ม Item ใหม่ (รวมถึง Item ที่ import จากเพื่อน)
   - มี edit_index    : แก้ไข Item เดิมตามลำดับที่ส่งมา
   ========================================================= */
require __DIR__ . '/lib.php';

require_post('../pages/create.php');

// 1) หา Portfolio ที่จะบันทึก
$portfolioId = $_POST['portfolio_id'] ?? '';
$portfolio = load_portfolio($portfolioId);
if ($portfolio === null) {
    redirect_to('../pages/create.php', ['msg' => 'need_portfolio']);
}

$editIndex = $_POST['edit_index'] ?? '';
$isEdit = is_string($editIndex) && ctype_digit($editIndex) && isset($portfolio['items'][(int)$editIndex]);

// 2) อ่านค่าจากฟอร์มและตรวจความถูกต้อง
$item = item_from_form($_POST);
if ($item === null) {
    redirect_to('../pages/create.php', [
        'id' => $portfolioId,
        'edit' => $isEdit ? $editIndex : null,
        'msg' => 'item_invalid',
    ]);
}

// 3) เพิ่มหรือแก้ไข
if ($isEdit) {
    $item['id'] = $portfolio['items'][(int)$editIndex]['id']; // แก้ไขแล้ว id เดิม
    $portfolio['items'][(int)$editIndex] = $item;
} else {
    $portfolio['items'][] = $item;
    $portfolio['items'] = unique_item_ids($portfolio['items']);
}

if (!save_portfolio($portfolio)) {
    redirect_to('../pages/create.php', ['id' => $portfolioId, 'msg' => 'storage_error']);
}

// 4) กลับไปหน้าที่เหมาะสม
if ($isEdit) {
    redirect_to('../pages/portfolio.php', ['id' => $portfolioId, 'msg' => 'item_updated', 'name' => $item['title']]);
}
redirect_to('../pages/create.php', ['id' => $portfolioId, 'msg' => 'item_added', 'name' => $item['title']]);
