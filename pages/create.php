<?php
/* 
   create.php — หน้าสร้าง Portfolio
   - ฟอร์ม Create Portfolio  → POST ไป backend/save_portfolio.php
   - ฟอร์ม Portfolio Item    → POST ไป backend/save_item.php
   - ฟอร์ม Import JSON       → POST ไป backend/import.php
   - แก้ไข Item: create.php?id=<portfolio>&edit=<ลำดับ>  (PHP เติมค่าเดิมลงฟอร์ม)
    */
require __DIR__ . '/parts/page_setup.php';

$activePage = 'create';

// ---------- โหมดแก้ไข ----------
$editIndex = $_GET['edit'] ?? '';
$editItem = null;
if ($selected && is_string($editIndex) && ctype_digit($editIndex) && isset($selected['items'][(int)$editIndex])) {
    $editItem = $selected['items'][(int)$editIndex];
}
$isEdit = $editItem !== null;

// ค่าเริ่มต้นของช่องในฟอร์ม Item (โหมดแก้ไข = ค่าเดิม)
$item = $editItem ?? [];
$reflection = $item['reflection'] ?? [];
$type = $item['type'] ?? '';
$otherType = '';
if ($type !== '' && !in_array($type, ITEM_TYPES, true)) {
    $otherType = $type; // ประเภทที่ไม่อยู่ในรายการ → เลือก Other แล้วใส่ข้อความ
    $type = 'Other';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>สร้าง Portfolio | PowerUP-TeamProject</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/create.css">

    <script src="../js/core/theme.js"></script>
</head>

<body class="has-navbar">

<?php include __DIR__ . '/parts/navbar.php'; ?>

    <main class="container-xxl px-3 px-md-4 py-4">
        <div class="row g-4 align-items-start">

            <!-- ========== ซ้าย: สร้าง Portfolio + Import ========== -->
            <aside class="col-lg-4">
                <div class="create-sidebar d-grid gap-4">

                    <section class="app-card" data-reveal>
                        <h2 class="section-title"><span class="step-badge">1</span> Create Portfolio</h2>

                        <form id="portfolioForm" action="../backend/save_portfolio.php" method="post" novalidate>
                            <div class="field mb-3">
                                <label for="displayName" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" id="displayName" name="name" class="form-control" maxlength="120" placeholder="ระบุชื่อจริงหรือนามแฝง" required>
                                <div class="error-msg invalid-feedback">โปรดระบุชื่อที่ต้องการแสดง</div>
                            </div>

                            <div class="field mb-3">
                                <label for="enjoy" class="form-label">What I Enjoy <span class="text-danger">*</span></label>
                                <textarea id="enjoy" name="enjoy" class="form-control" rows="2" placeholder="ระบุสิ่งที่คุณชื่นชอบ" required></textarea>
                                <div class="error-msg invalid-feedback">โปรดระบุสิ่งที่คุณชอบทำ</div>
                            </div>

                            <div class="field mb-3">
                                <label for="tried" class="form-label">What I've Tried <span class="text-danger">*</span></label>
                                <textarea id="tried" name="tried" class="form-control" rows="2" placeholder="ระบุสิ่งที่คุณเคยลองทำ" required></textarea>
                                <div class="error-msg invalid-feedback">โปรดระบุสิ่งที่คุณเคยลองทำ</div>
                            </div>

                            <div class="field mb-3">
                                <label for="curious" class="form-label">What I'm Curious About <span class="text-danger">*</span></label>
                                <textarea id="curious" name="curious" class="form-control" rows="2" placeholder="ระบุสิ่งที่คุณสนใจหรืออยากรู้เพิ่ม" required></textarea>
                                <div class="error-msg invalid-feedback">โปรดระบุสิ่งที่คุณสนใจหรืออยากรู้เพิ่ม</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2">บันทึก Portfolio</button>
                        </form>
                    </section>

                    <section class="app-card" id="importSection" data-reveal>
                        <h2 class="section-title">📥 Import JSON</h2>
                        <p class="small text-body-secondary mb-3">
                            รับ <strong>Portfolio Item JSON</strong> จากเพื่อน (เพิ่มเข้า Portfolio ที่เลือกไว้)
                            หรือ <strong>Portfolio JSON</strong> ทั้งก้อน — ระบบตรวจให้อัตโนมัติ
                        </p>

                        <form id="importForm" action="../backend/import.php" method="post" novalidate>
                            <input type="hidden" name="portfolio_id" id="importPortfolioId" value="<?= e($selectedId) ?>">

                            <label for="importFile" class="drop-zone mb-3" id="dropZone">
                                <span class="drop-zone__icon" aria-hidden="true">📄</span>
                                <span class="fw-bold">เลือกไฟล์ .json</span>
                                <small id="importFileName">หรือลากไฟล์มาวางที่นี่</small>
                            </label>
                            <input type="file" id="importFile" class="visually-hidden" accept=".json,application/json">

                            <label for="importJson" class="form-label">หรือวางข้อความ JSON (Paste JSON)</label>
                            <textarea id="importJson" name="json" class="form-control json-preview mb-3" rows="5" placeholder='{ "title": "...", "reflection": { ... } }' required></textarea>

                            <p class="text-danger small mb-3" id="importError" role="alert" hidden></p>

                            <button type="submit" class="btn btn-outline-primary w-100 py-2" id="importBtn">Import</button>
                        </form>
                    </section>
                </div>
            </aside>

            <!-- ========== ขวา: สร้าง / แก้ไข Portfolio Item ========== -->
            <section class="col-lg-8">
                <div class="app-card" id="itemSection" data-reveal>
                    <h2 class="section-title"><span class="step-badge">2</span> <span id="itemFormTitle"><?= $isEdit ? 'Edit Portfolio Item' : 'Create Portfolio Item' ?></span></h2>

                    <div class="edit-banner mb-3" id="modeBanner"<?= $isEdit ? '' : ' hidden' ?>>
                        <span id="modeBannerText"><?php if ($isEdit): ?>✏️ กำลังแก้ไข: <strong><?= e($editItem['title']) ?></strong><?php endif; ?></span>
                        <a href="portfolio.php?id=<?= e(urlencode($selectedId)) ?>" class="btn btn-sm btn-outline-secondary" id="cancelModeBtn">ยกเลิก</a>
                    </div>

                    <form id="itemForm" action="../backend/save_item.php" method="post" novalidate>
                        <input type="hidden" name="item_id" id="itemId" value="<?= e($item['id'] ?? '') ?>">
                        <input type="hidden" name="edit_index" value="<?= $isEdit ? e($editIndex) : '' ?>">

                        <div class="target-box mb-4">
                            <label for="targetPortfolio" class="form-label fw-bold mb-1">เพิ่ม Item ให้ Portfolio</label>
<?php if ($portfolios === []): ?>
                            <select id="targetPortfolio" class="form-select" disabled>
                                <option>ยังไม่มี Portfolio — สร้างทางซ้ายก่อน</option>
                            </select>
<?php elseif ($isEdit): ?>
                            <!-- ระหว่างแก้ไข ห้ามย้าย Portfolio -->
                            <input type="hidden" name="portfolio_id" value="<?= e($selectedId) ?>">
                            <select id="targetPortfolio" class="form-select" disabled>
                                <option><?= e($selected['name']) ?></option>
                            </select>
<?php else: ?>
                            <select id="targetPortfolio" name="portfolio_id" class="form-select">
<?php foreach ($portfolios as $portfolio): ?>
                                <option value="<?= e($portfolio['portfolioId']) ?>"<?= $portfolio['portfolioId'] === $selectedId ? ' selected' : '' ?>><?= e($portfolio['name']) ?> (<?= count($portfolio['items']) ?> รายการ)</option>
<?php endforeach; ?>
                            </select>
<?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title" class="form-control" maxlength="200" placeholder="ระบุชื่อผลงาน" value="<?= e($item['title'] ?? '') ?>" required>
                                <div class="invalid-feedback">โปรดระบุชื่อผลงาน</div>
                            </div>

                            <div class="col-md-6">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select id="type" name="type" class="form-select" required>
                                    <option value="">เลือกประเภท</option>
<?php foreach (ITEM_TYPES as $option): ?>
                                    <option value="<?= e($option) ?>"<?= $option === $type ? ' selected' : '' ?>><?= e($option) ?></option>
<?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">โปรดเลือกประเภท</div>
                            </div>

                            <div class="col-12" id="otherTypeField"<?= $type === 'Other' ? '' : ' hidden' ?>>
                                <label for="otherType" class="form-label">ระบุประเภทอื่น ๆ <span class="text-danger">*</span></label>
                                <input type="text" id="otherType" name="other_type" class="form-control" maxlength="100" placeholder="พิมพ์รายละเอียดประเภทผลงาน" value="<?= e($otherType) ?>"<?= $type === 'Other' ? ' required' : '' ?>>
                                <div class="invalid-feedback">โปรดระบุประเภทผลงาน</div>
                            </div>

                            <div class="col-md-6">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" id="date" name="date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= e($item['date'] ?? '') ?>" required>
                                <div class="invalid-feedback">โปรดเลือกวันที่ (ต้องไม่เป็นวันในอนาคต)</div>
                            </div>

                            <div class="col-md-6">
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <input type="text" id="role" name="role" class="form-control" maxlength="200" placeholder="ระบุหน้าที่" value="<?= e($item['role'] ?? '') ?>" required>
                                <div class="invalid-feedback">โปรดระบุหน้าที่</div>
                            </div>

                            <div class="col-12">
                                <label for="skills" class="form-label mb-0">Skills <span class="text-danger">*</span></label>
                                <div class="form-text mt-0 mb-2">คั่นแต่ละทักษะด้วยเครื่องหมายจุลภาค ( , )</div>
                                <input type="text" id="skills" name="skills" class="form-control" placeholder="เช่น HTML, CSS, JavaScript" value="<?= e(implode(', ', $item['skills'] ?? [])) ?>" required>
                                <div class="invalid-feedback">โปรดระบุทักษะที่ใช้</div>
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea id="description" name="description" class="form-control" rows="4" placeholder="ระบุรายละเอียดงาน" required><?= e($item['description'] ?? '') ?></textarea>
                                <div class="invalid-feedback">โปรดระบุรายละเอียดงาน</div>
                            </div>
                        </div>

                        <h3 class="h5 fw-bold mt-4 mb-3">Reflection</h3>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="whatIDid" class="form-label">What I did <span class="text-danger">*</span></label>
                                <textarea id="whatIDid" name="what_i_did" class="form-control" rows="3" placeholder="ระบุสิ่งที่ทำ" required><?= e($reflection['whatIDid'] ?? '') ?></textarea>
                                <div class="invalid-feedback">โปรดระบุสิ่งที่ทำ</div>
                            </div>

                            <div class="col-md-6">
                                <label for="whatILearned" class="form-label">What I learned <span class="text-danger">*</span></label>
                                <textarea id="whatILearned" name="what_i_learned" class="form-control" rows="3" placeholder="ระบุสิ่งที่เรียนรู้" required><?= e($reflection['whatILearned'] ?? '') ?></textarea>
                                <div class="invalid-feedback">โปรดระบุสิ่งที่เรียนรู้</div>
                            </div>

                            <div class="col-md-6">
                                <label for="challenge" class="form-label">Challenge <span class="text-danger">*</span></label>
                                <textarea id="challenge" name="challenge" class="form-control" rows="3" placeholder="ระบุความท้าทาย" required><?= e($reflection['challenge'] ?? '') ?></textarea>
                                <div class="invalid-feedback">โปรดระบุความท้าทาย</div>
                            </div>

                            <div class="col-md-6">
                                <label for="nextStep" class="form-label">Next step <span class="text-danger">*</span></label>
                                <textarea id="nextStep" name="next_step" class="form-control" rows="3" placeholder="ระบุสิ่งที่ต้องการทำต่อในอนาคต" required><?= e($reflection['nextStep'] ?? '') ?></textarea>
                                <div class="invalid-feedback">โปรดระบุสิ่งที่ต้องการทำต่อ</div>
                            </div>

                            <div class="col-12">
                                <label for="evidence" class="form-label mb-0">Evidence <span class="text-danger">*</span></label>
                                <div class="form-text mt-0 mb-2">ใส่บรรทัดละ 1 ลิงก์ ต้องขึ้นต้นด้วย http:// หรือ https://</div>
                                <textarea id="evidence" name="evidence" class="form-control" rows="3" placeholder="https://..." required><?= e(implode("\n", $item['evidence'] ?? [])) ?></textarea>
                                <div class="invalid-feedback">ต้องมีอย่างน้อย 1 ลิงก์ และทุกบรรทัดต้องขึ้นต้นด้วย http:// หรือ https://</div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                            <button type="submit" class="btn btn-primary px-4 py-2" id="itemSubmitBtn"<?= $portfolios === [] ? ' disabled' : '' ?>><?= $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก Portfolio Item' ?></button>
<?php if (!$isEdit): ?>
                            <button type="reset" class="btn btn-link text-secondary">ล้างฟอร์ม</button>
<?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/core/ui.js"></script>
    <script src="../js/pages/create.js"></script>
</body>
</html>
