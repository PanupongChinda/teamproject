<?php
/* 
   import.php — รับฟอร์ม "Import JSON" (method="post")
   ช่อง json = ข้อความ JSON ที่วางมา หรือที่อ่านจากไฟล์
   - Portfolio Item (มี title) → เพิ่มเข้า Portfolio ที่เลือก (portfolio_id)
   - Portfolio (มี name)       → สร้างเป็น Portfolio ใหม่
    */
require __DIR__ . '/lib.php';

$back = '../pages/create.php';
require_post($back);

$portfolioId = $_POST['portfolio_id'] ?? '';

// 1) แปลงข้อความเป็นข้อมูล (json_decode = ฟังก์ชันในตัวของ PHP)
$data = json_decode((string)($_POST['json'] ?? ''), true);
if (!is_array($data) || $data === []) {
    redirect_to($back, ['id' => $portfolioId, 'msg' => 'import_invalid']);
}

// รับได้ทั้งก้อนเดียว และ [ หลายก้อน ]
$entries = array_is_list($data) ? $data : [$data];
$first = is_array($entries[0] ?? null) ? $entries[0] : [];
$isItem = isset($first['title']) && !isset($first['name']);

if (!ensure_storage()) {
    redirect_to($back, ['msg' => 'storage_error']);
}

// 2) กรณี Portfolio Item → เพิ่มเข้า Portfolio ที่เลือก
if ($isItem) {
    $portfolio = load_portfolio($portfolioId);
    if ($portfolio === null) {
        redirect_to($back, ['msg' => 'need_portfolio']);
    }

    $items = array_values(array_filter(array_map('clean_item', $entries)));
    if ($items === []) {
        redirect_to($back, ['id' => $portfolioId, 'msg' => 'import_empty']);
    }

    $portfolio['items'] = unique_item_ids([...$portfolio['items'], ...$items]);
    if (!save_portfolio($portfolio)) {
        redirect_to($back, ['id' => $portfolioId, 'msg' => 'storage_error']);
    }
    redirect_to($back, ['id' => $portfolioId, 'msg' => 'imported_items', 'name' => (string)count($items)]);
}

// 3) กรณี Portfolio ทั้งก้อน → สร้างใหม่ (id ซ้ำกับที่มีอยู่ = สร้าง id ใหม่)
$names = [];
$lastId = '';
foreach ($entries as $raw) {
    $id = $raw['portfolioId'] ?? '';
    if (!is_valid_id($id) || load_portfolio($id) !== null) {
        $id = new_id('p');
    }
    $portfolio = clean_portfolio($raw, $id);
    if ($portfolio !== null && save_portfolio($portfolio)) {
        $names[] = $portfolio['name'];
        $lastId = $id;
    }
}

if ($names === []) {
    redirect_to($back, ['id' => $portfolioId, 'msg' => 'import_empty']);
}
redirect_to($back, ['id' => $lastId, 'msg' => 'imported_portfolio', 'name' => implode(', ', $names)]);
