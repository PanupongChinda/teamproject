<?php
//delete_item.php — รับฟอร์มปุ่ม "ลบ" ของแต่ละ Item (method="post")
require __DIR__ . '/lib.php';

$back = '../pages/portfolio.php';
require_post($back);

$portfolioId = $_POST['portfolio_id'] ?? '';
$index = $_POST['index'] ?? '';
$portfolio = load_portfolio($portfolioId);

if ($portfolio === null) {
    redirect_to($back, ['msg' => 'not_found']);
}
if (!is_string($index) || !ctype_digit($index) || !isset($portfolio['items'][(int)$index])) {
    redirect_to($back, ['id' => $portfolioId]);
}

$removed = $portfolio['items'][(int)$index];
array_splice($portfolio['items'], (int)$index, 1);

if (!save_portfolio($portfolio)) {
    redirect_to($back, ['id' => $portfolioId, 'msg' => 'storage_error']);
}

redirect_to($back, ['id' => $portfolioId, 'msg' => 'item_deleted', 'name' => $removed['title']]);
