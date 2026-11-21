<?php
//delete_portfolio.php — รับฟอร์มปุ่ม "ลบ Portfolio" (method="post")
require __DIR__ . '/lib.php';

$back = '../pages/portfolio.php';
require_post($back);

$portfolioId = $_POST['portfolio_id'] ?? '';
$portfolio = load_portfolio($portfolioId);
if ($portfolio === null) {
    redirect_to($back, ['msg' => 'not_found']);
}

if (!delete_portfolio($portfolioId)) {
    redirect_to($back, ['id' => $portfolioId, 'msg' => 'storage_error']);
}

redirect_to($back, ['msg' => 'portfolio_deleted', 'name' => $portfolio['name']]);
