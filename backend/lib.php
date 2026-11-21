<?php
/* =========================================================
   backend/lib.php — ฟังก์ชันที่ใช้ร่วมกันทุกไฟล์ PHP
   (ไฟล์นี้ถูก require จากหน้าเว็บและไฟล์รับฟอร์ม ไม่ได้เปิดตรง ๆ)

   - อ่าน/เขียนไฟล์ JSON ในโฟลเดอร์ storage/portfolios/ (1 Portfolio = 1 ไฟล์)
   - แปลงค่าจากฟอร์ม ($_POST) ให้เป็นข้อมูล Portfolio
   - ข้อความแจ้งผลหลังบันทึก

   รูปแบบไฟล์ที่บันทึก (Portfolio JSON ตามรูปแบบของวิชา):
   {
     "portfolioId": "...", "name": "...",
     "whatIEnjoy": "...", "whatIveTried": "...", "whatImCuriousAbout": "...",
     "items": [{
       "id", "title", "type", "date", "role", "description", "skills": [],
       "reflection": { "whatIDid", "whatILearned", "challenge", "nextStep" },
       "evidence": []
     }]
   }
   ========================================================= */

// ห้ามเปิดไฟล์นี้ตรง ๆ จาก browser
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

/* ---------- ตั้งค่า ---------- */
date_default_timezone_set('Asia/Bangkok'); // วันที่ "วันนี้" ตรงกับเวลาไทย
$dataDir = getenv('PORTFOLIO_DATA_DIR');
define('DATA_DIR', $dataDir ? rtrim($dataDir, '/') : dirname(__DIR__) . '/storage/portfolios');
define('EXAMPLES_DIR', dirname(__DIR__) . '/data');
define('ITEM_TYPES', ['Course Assignment', 'Personal Project', 'Activity', 'Workshop', 'Volunteer', 'Other']);

/* ---------- ข้อความแจ้งผล (ส่งมาทาง ?msg=...) ---------- */
define('MESSAGES', [
    'portfolio_created'  => ['success', 'สร้าง Portfolio "%s" แล้ว — เพิ่ม Item ได้เลย'],
    'portfolio_deleted'  => ['info', 'ลบ Portfolio "%s" แล้ว'],
    'portfolio_invalid'  => ['error', 'กรอกข้อมูล Portfolio ให้ครบทุกช่อง'],
    'item_added'         => ['success', 'เพิ่ม "%s" ลงใน Portfolio แล้ว'],
    'item_updated'       => ['success', 'บันทึกการแก้ไข "%s" แล้ว'],
    'item_deleted'       => ['info', 'ลบ "%s" แล้ว'],
    'item_invalid'       => ['error', 'กรอกข้อมูล Item ให้ครบและถูกต้องก่อนบันทึก'],
    'imported_portfolio' => ['success', 'นำเข้า Portfolio "%s" แล้ว'],
    'imported_items'     => ['success', 'นำเข้า %s Item แล้ว'],
    'import_invalid'     => ['error', 'รูปแบบ JSON ไม่ถูกต้อง'],
    'import_empty'       => ['error', 'ไม่พบข้อมูลที่นำเข้าได้ (Item ต้องมี title, Portfolio ต้องมี name)'],
    'need_portfolio'     => ['error', 'สร้างหรือเลือก Portfolio ก่อน'],
    'not_found'          => ['error', 'ไม่พบ Portfolio ที่เลือก'],
    'storage_error'      => ['error', 'บันทึกไฟล์ไม่สำเร็จ: ตรวจสอบสิทธิ์ของโฟลเดอร์ storage/'],
]);

/** คืนค่า [ประเภท, ข้อความ] จาก ?msg= หรือ null */
function flash_message(): ?array
{
    $code = $_GET['msg'] ?? '';
    if (!is_string($code) || !isset(MESSAGES[$code])) {
        return null;
    }
    [$type, $text] = MESSAGES[$code];
    $name = clean_text($_GET['name'] ?? '', 120);
    return [$type, str_contains($text, '%s') ? sprintf($text, $name) : $text];
}

/* ---------- ตัวช่วยทั่วไป ---------- */

/** แสดงข้อความใน HTML อย่างปลอดภัย (กันโค้ดแปลกปลอม) */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** ส่งผู้ใช้ไปหน้าอื่น พร้อมค่าใน URL แล้วจบการทำงาน */
function redirect_to(string $page, array $params = []): void
{
    $query = http_build_query(array_filter($params, fn($v) => $v !== null && $v !== ''));
    header('Location: ' . $page . ($query ? '?' . $query : ''));
    exit;
}

/** ไฟล์รับฟอร์มยอมรับเฉพาะการส่งแบบ POST */
function require_post(string $backTo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        redirect_to($backTo);
    }
}

function format_thai_date(string $date): string
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
        return $date;
    }
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return (int)$m[3] . ' ' . ($months[(int)$m[2]] ?? '') . ' ' . ((int)$m[1] + 543);
}

/* ---------- ทำความสะอาดข้อมูล ---------- */

function is_valid_id(mixed $id): bool
{
    // a-z A-Z 0-9 _ - เท่านั้น เพื่อกันการอ่าน/เขียนไฟล์นอกโฟลเดอร์ (path traversal)
    return is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id) === 1;
}

function new_id(string $prefix): string
{
    return $prefix . '-' . time() . '-' . bin2hex(random_bytes(3));
}

function clean_text(mixed $value, int $max): string
{
    if (!is_scalar($value)) {
        return '';
    }
    $text = trim((string)$value);
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $max, 'UTF-8');
    }
    return preg_match('/^.{0,' . $max . '}/su', $text, $m) ? $m[0] : '';
}

/** รับได้ทั้ง array และข้อความคั่นด้วย , หรือขึ้นบรรทัดใหม่ */
function clean_list(mixed $value, int $maxItems, int $maxLength): array
{
    if (is_string($value)) {
        $value = preg_split('/[,\n]/', $value) ?: [];
    }
    if (!is_array($value)) {
        return [];
    }
    $result = [];
    foreach ($value as $entry) {
        $text = clean_text($entry, $maxLength);
        if ($text !== '') {
            $result[] = $text;
        }
    }
    return array_slice($result, 0, $maxItems);
}

function is_http_url(string $url): bool
{
    return preg_match('~^https?://\S+$~i', $url) === 1;
}

/** แปลงข้อมูล Item (จากฟอร์มหรือไฟล์ JSON) ให้อยู่ในรูปแบบมาตรฐาน — ไม่มี title คืนค่า null */
function clean_item(mixed $raw): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $title = clean_text($raw['title'] ?? '', 200);
    if ($title === '') {
        return null;
    }

    $reflection = is_array($raw['reflection'] ?? null) ? $raw['reflection'] : [];
    $pick = fn(string $key) => clean_text($reflection[$key] ?? $raw[$key] ?? '', 3000);

    $date = clean_text($raw['date'] ?? '', 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = '';
    }

    return [
        'id' => is_valid_id($raw['id'] ?? null) ? $raw['id'] : new_id('item'),
        'title' => $title,
        'type' => clean_text($raw['type'] ?? '', 100),
        'date' => $date,
        'role' => clean_text($raw['role'] ?? '', 200),
        'description' => clean_text($raw['description'] ?? '', 5000),
        'skills' => clean_list($raw['skills'] ?? [], 30, 60),
        'reflection' => [
            'whatIDid' => $pick('whatIDid'),
            'whatILearned' => $pick('whatILearned'),
            'challenge' => $pick('challenge'),
            'nextStep' => $pick('nextStep'),
        ],
        // Evidence ต้องเป็นลิงก์ http(s) เท่านั้น (กันลิงก์อันตราย เช่น javascript:)
        'evidence' => array_values(array_filter(clean_list($raw['evidence'] ?? [], 20, 1000), 'is_http_url')),
    ];
}

/** แปลงข้อมูล Portfolio ให้อยู่ในรูปแบบมาตรฐาน — ไม่มีชื่อคืนค่า null */
function clean_portfolio(mixed $raw, string $id): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $name = clean_text($raw['name'] ?? '', 120);
    if ($name === '') {
        return null;
    }

    $items = [];
    foreach (is_array($raw['items'] ?? null) ? $raw['items'] : [] as $rawItem) {
        $item = clean_item($rawItem);
        if ($item !== null) {
            $items[] = $item;
        }
    }

    return [
        'portfolioId' => $id,
        'name' => $name,
        'whatIEnjoy' => clean_text($raw['whatIEnjoy'] ?? '', 3000),
        'whatIveTried' => clean_text($raw['whatIveTried'] ?? '', 3000),
        'whatImCuriousAbout' => clean_text($raw['whatImCuriousAbout'] ?? '', 3000),
        'items' => unique_item_ids(array_slice($items, 0, 200)),
    ];
}

/** กัน id ของ Item ซ้ำกันใน Portfolio เดียว */
function unique_item_ids(array $items): array
{
    $seen = [];
    foreach ($items as $i => $item) {
        if (isset($seen[$item['id']])) {
            $items[$i]['id'] = new_id('item');
        }
        $seen[$items[$i]['id']] = true;
    }
    return $items;
}

/**
 * อ่านค่าจากฟอร์ม Item ($_POST) แล้วแปลงเป็น Item
 * คืนค่า null ถ้ากรอกไม่ครบ (ตรวจซ้ำฝั่ง server แม้ JavaScript ตรวจแล้ว)
 */
function item_from_form(array $form): ?array
{
    $type = clean_text($form['type'] ?? '', 100);
    if ($type === 'Other') {
        $type = clean_text($form['other_type'] ?? '', 100); // ประเภทอื่น ๆ → ใช้ข้อความที่พิมพ์
    }

    // Evidence: 1 บรรทัด = 1 ลิงก์
    $evidenceLines = array_values(array_filter(array_map('trim', explode("\n", (string)($form['evidence'] ?? ''))), 'strlen'));

    $item = clean_item([
        'id' => $form['item_id'] ?? '',
        'title' => $form['title'] ?? '',
        'type' => $type,
        'date' => $form['date'] ?? '',
        'role' => $form['role'] ?? '',
        'description' => $form['description'] ?? '',
        'skills' => (string)($form['skills'] ?? ''),
        'reflection' => [
            'whatIDid' => $form['what_i_did'] ?? '',
            'whatILearned' => $form['what_i_learned'] ?? '',
            'challenge' => $form['challenge'] ?? '',
            'nextStep' => $form['next_step'] ?? '',
        ],
        'evidence' => array_slice($evidenceLines, 0, 20),
    ]);
    if ($item === null) {
        return null;
    }

    $required = [$item['type'], $item['date'], $item['role'], $item['description'], ...array_values($item['reflection'])];
    $allLinksValid = count($evidenceLines) > 0 && count($item['evidence']) === count($evidenceLines);
    $notFuture = $item['date'] <= date('Y-m-d');

    if (in_array('', $required, true) || $item['skills'] === [] || !$allLinksValid || !$notFuture) {
        return null;
    }
    return $item;
}

/* ---------- ไฟล์ ---------- */

function portfolio_path(string $id): ?string
{
    return is_valid_id($id) ? DATA_DIR . '/' . $id . '.json' : null;
}

/** เตรียมโฟลเดอร์เก็บข้อมูล — คืนค่า false ถ้าเขียนไม่ได้ */
function ensure_storage(): bool
{
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }
    if (!is_dir(DATA_DIR) || !is_writable(DATA_DIR)) {
        return false;
    }
    seed_examples();
    return true;
}

/** ครั้งแรกที่ใช้งาน: คัดลอก Portfolio ตัวอย่างจาก data/ มาไว้ (ทำครั้งเดียว) */
function seed_examples(): void
{
    $marker = DATA_DIR . '/.seeded';
    if (file_exists($marker)) {
        return;
    }
    foreach (glob(EXAMPLES_DIR . '/*.json') ?: [] as $file) {
        $raw = json_decode((string)file_get_contents($file), true);
        $id = is_valid_id($raw['portfolioId'] ?? null) ? $raw['portfolioId'] : new_id('p');
        $portfolio = clean_portfolio($raw, $id);
        if ($portfolio !== null && !is_file(portfolio_path($id))) {
            save_portfolio($portfolio);
        }
    }
    @touch($marker);
}

function load_portfolio(mixed $id): ?array
{
    $path = is_string($id) ? portfolio_path($id) : null;
    if ($path === null || !is_file($path)) {
        return null;
    }
    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function list_portfolios(): array
{
    $result = [];
    foreach (glob(DATA_DIR . '/*.json') ?: [] as $file) {
        $data = load_portfolio(basename($file, '.json'));
        if ($data !== null) {
            $result[] = $data;
        }
    }
    usort($result, fn($a, $b) => strcmp($a['createdAt'] ?? '', $b['createdAt'] ?? ''));
    return $result;
}

/** บันทึกไฟล์: เขียนไฟล์ชั่วคราวก่อน แล้วค่อยเปลี่ยนชื่อทับ (ไฟล์ไม่พังถ้าเขียนไม่จบ) */
function save_portfolio(array $portfolio): bool
{
    $path = portfolio_path($portfolio['portfolioId']);
    if ($path === null) {
        return false;
    }
    $previous = load_portfolio($portfolio['portfolioId']);
    $portfolio['createdAt'] = $previous['createdAt'] ?? date('c');
    $portfolio['updatedAt'] = date('c');

    $json = json_encode($portfolio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';

    if ($json === false || file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function delete_portfolio(string $id): bool
{
    $path = portfolio_path($id);
    return $path !== null && is_file($path) && unlink($path);
}

/** ข้อมูลสำหรับส่งออก (ตัดเวลาสร้าง/แก้ไขออก) */
function export_portfolio(array $portfolio): array
{
    unset($portfolio['createdAt'], $portfolio['updatedAt']);
    return $portfolio;
}
