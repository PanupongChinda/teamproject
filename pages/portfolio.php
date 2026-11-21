<?php
/* 
   portfolio.php — หน้าดู Portfolio
   - เลือก Portfolio ด้วยลิงก์ portfolio.php?id=...  (ค่ามาทาง GET)
   - ปุ่มลบ = ฟอร์ม POST ไป backend/delete_*.php
   - ส่งออก = PHP ใส่ข้อมูล Portfolio ไว้ในหน้า แล้ว JavaScript สร้างไฟล์ให้ Copy / Download
    */
require __DIR__ . '/parts/page_setup.php';

$activePage = 'portfolio';
$avatarColors = ['#2a4d88', '#1f8a70', '#c0632f', '#7b4fb5', '#b8445e', '#2d7fa8'];

/** ตัวอักษรแรกของชื่อ */
function initial(string $name): string
{
    $name = trim($name);
    $first = function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
    return function_exists('mb_strtoupper') ? mb_strtoupper($first, 'UTF-8') : strtoupper($first);
}

/** ชื่อเดียวกันได้สีเดียวกันเสมอ */
function avatar_color(string $name, array $colors): string
{
    return $colors[crc32($name) % count($colors)];
}

$selectedQuery = urlencode($selectedId);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ดู Portfolio | PowerUP-TeamProject</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/portfolio.css">

    <script src="../js/core/theme.js"></script>
</head>

<body class="has-navbar">

<?php include __DIR__ . '/parts/navbar.php'; ?>

    <main class="container-xxl px-3 px-md-4 py-4">
        <div class="row g-4 align-items-start">

            <!-- ========== ซ้าย: รายการ Portfolio ========== -->
            <aside class="col-lg-4">
                <section class="app-card portfolio-sidebar" data-reveal>
                    <h2 class="section-title">
                        PORTFOLIO ที่บันทึกแล้ว
                        <span class="count-badge"><?= count($portfolios) ?></span>
                    </h2>

                    <ul class="portfolio-list" aria-label="รายการ Portfolio">
<?php if ($portfolios === []): ?>
                        <li class="list-empty">ยังไม่มี Portfolio ที่บันทึก</li>
<?php endif; ?>
<?php foreach ($portfolios as $portfolio): ?>
<?php $isActive = $portfolio['portfolioId'] === $selectedId; ?>
                        <li>
                            <a class="portfolio-item<?= $isActive ? ' active' : '' ?>" href="?id=<?= e(urlencode($portfolio['portfolioId'])) ?>#detailArea"<?= $isActive ? ' aria-current="true"' : '' ?>>
                                <span class="avatar" style="--avatar-color:<?= avatar_color($portfolio['name'], $avatarColors) ?>" aria-hidden="true"><?= e(initial($portfolio['name'])) ?></span>
                                <span class="min-w-0">
                                    <span class="name"><?= e($portfolio['name']) ?></span>
                                    <span class="count"><?= count($portfolio['items']) ?> รายการ</span>
                                </span>
                            </a>
                        </li>
<?php endforeach; ?>
                    </ul>

                    <a href="create.php" class="btn btn-primary w-100 mt-3">+ สร้าง Portfolio ใหม่</a>
                </section>
            </aside>

            <!-- ========== ขวา: รายละเอียด + Items ========== -->
            <div class="col-lg-8 d-grid gap-4" id="detailArea">

<?php if ($selected === null): ?>
                <section class="app-card" data-reveal>
                    <div class="empty-state">
                        <div class="empty-state__icon" aria-hidden="true">📭</div>
                        <p class="mb-3">ยังไม่มี Portfolio</p>
                        <a href="create.php" class="btn btn-primary">เริ่มสร้าง Portfolio</a>
                    </div>
                </section>
<?php else: ?>
                <section class="app-card" data-reveal>
                    <div class="detail-header">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <span class="avatar" style="--avatar-color:<?= avatar_color($selected['name'], $avatarColors) ?>" aria-hidden="true"><?= e(initial($selected['name'])) ?></span>
                            <h1 class="fs-3 fw-bold text-break mb-0"><?= e($selected['name']) ?></h1>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="exportPortfolioBtn">⬇️ Export my-portfolio.json</button>
                            <form action="../backend/delete_portfolio.php" method="post" data-confirm="ลบ &quot;<?= e($selected['name']) ?>&quot; ใช่หรือไม่?">
                                <input type="hidden" name="portfolio_id" value="<?= e($selectedId) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ Portfolio</button>
                            </form>
                        </div>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-box">
                            <h3>What I Enjoy</h3>
                            <p><?= e($selected['whatIEnjoy'] ?: '-') ?></p>
                        </div>
                        <div class="detail-box">
                            <h3>What I've Tried</h3>
                            <p><?= e($selected['whatIveTried'] ?: '-') ?></p>
                        </div>
                        <div class="detail-box">
                            <h3>What I'm Curious About</h3>
                            <p><?= e($selected['whatImCuriousAbout'] ?: '-') ?></p>
                        </div>
                    </div>
                </section>

                <section class="app-card" data-reveal>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="section-title mb-0">
                            PORTFOLIO ITEMS
                            <span class="count-badge"><?= count($selected['items']) ?></span>
                        </h2>
                        <a href="create.php?id=<?= e($selectedQuery) ?>" class="btn btn-sm btn-outline-primary">+ เพิ่ม Item</a>
                    </div>

                    <div class="items-container">
<?php if ($selected['items'] === []): ?>
                        <p class="text-body-secondary mb-0">ยังไม่มี Portfolio Item</p>
<?php endif; ?>
<?php foreach ($selected['items'] as $index => $item): ?>
<?php $r = $item['reflection']; ?>
                        <article class="portfolio-item-card" data-reveal>
                            <h3 class="item-title"><?= e($item['title']) ?></h3>
                            <div class="item-meta">
<?php if ($item['type'] !== ''): ?>
                                <span class="meta-chip meta-chip--type"><?= e($item['type']) ?></span>
<?php endif; ?>
<?php if ($item['date'] !== ''): ?>
                                <span class="meta-chip">📅 <?= e(format_thai_date($item['date'])) ?></span>
<?php endif; ?>
<?php if ($item['role'] !== ''): ?>
                                <span class="meta-chip">👤 <?= e($item['role']) ?></span>
<?php endif; ?>
                            </div>

                            <p class="mb-0"><?= e($item['description']) ?></p>

<?php if ($item['skills'] !== []): ?>
                            <div class="item-skills">
<?php foreach ($item['skills'] as $skill): ?>
                                <span><?= e($skill) ?></span>
<?php endforeach; ?>
                            </div>
<?php endif; ?>

                            <div class="item-reflection">
                                <div><h4>What I did</h4><p><?= e($r['whatIDid'] ?: '-') ?></p></div>
                                <div><h4>What I learned</h4><p><?= e($r['whatILearned'] ?: '-') ?></p></div>
                                <div><h4>Challenge</h4><p><?= e($r['challenge'] ?: '-') ?></p></div>
                                <div><h4>Next step</h4><p><?= e($r['nextStep'] ?: '-') ?></p></div>
                            </div>

<?php if ($item['evidence'] !== []): ?>
                            <div class="item-evidence">
                                <h4 class="small fw-bold text-body-secondary mb-1">Evidence</h4>
                                <ul>
<?php foreach ($item['evidence'] as $url): ?>
                                    <li><a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($url) ?></a></li>
<?php endforeach; ?>
                                </ul>
                            </div>
<?php endif; ?>

                            <div class="item-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-export-index="<?= $index ?>">📤 ส่งออก JSON</button>
                                <a href="create.php?id=<?= e($selectedQuery) ?>&amp;edit=<?= $index ?>" class="btn btn-sm btn-edit">✏️ แก้ไข</a>
                                <form action="../backend/delete_item.php" method="post" data-confirm="ลบ &quot;<?= e($item['title']) ?>&quot; ใช่หรือไม่?">
                                    <input type="hidden" name="portfolio_id" value="<?= e($selectedId) ?>">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">🗑 ลบ</button>
                                </form>
                            </div>
                        </article>
<?php endforeach; ?>
                    </div>
                </section>
<?php endif; ?>

            </div>
        </div>
    </main>

<?php if ($selected !== null): ?>
    <!-- ข้อมูล Portfolio สำหรับปุ่มส่งออก (PHP ใส่ไว้ในหน้าตอนสร้างหน้า ไม่ต้องโหลดเพิ่ม) -->
    <script type="application/json" id="portfolioData"><?= json_encode(export_portfolio($selected), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

    <!-- ========== หน้าต่างแสดง JSON (Copy / Download) ========== -->
    <div class="modal fade" id="jsonModal" tabindex="-1" aria-labelledby="jsonModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5 fw-bold" id="jsonModalTitle">Portfolio Item JSON</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-body-secondary" id="jsonModalHint"></p>
                    <textarea class="form-control json-preview" id="jsonModalText" rows="14" readonly aria-label="ข้อมูล JSON"></textarea>
                </div>
                <div class="modal-footer flex-column flex-sm-row">
                    <span class="small text-body-secondary me-sm-auto" id="jsonModalFile"></span>
                    <button type="button" class="btn btn-outline-primary w-100 w-sm-auto" id="jsonCopyBtn">📋 Copy</button>
                    <button type="button" class="btn btn-primary w-100 w-sm-auto" id="jsonDownloadBtn">⬇️ Download</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/core/ui.js"></script>
    <script src="../js/pages/portfolio.js"></script>
</body>
</html>
