<?php
/* 
   backend/storage.php — "ฝ่ายหลังร้าน": คนเดียวที่เปิดสมุดข้อมูล
   (browser ไม่เปิดไฟล์นี้ตรง ๆ — ไฟล์อื่นใช้ด้วย require 'storage.php')

   - DATA_DIR    : โฟลเดอร์เก็บข้อมูล (อยู่นอกโฟลเดอร์ public)
                   laptop (php -S) → local-data/   ·   EC2 (Apache) → /var/www/data
   - lockData()  : เขียนได้ทีละคน (อ่าน → เพิ่ม → เขียน)
   - readPortfolios() / writePortfolios()
   - sendJson()  : ตอบกลับเป็น JSON

   รูปแบบข้อมูลตาม Data Contract ของวิชา:
   portfolios.json = [
     { "id", "displayName", "whatIEnjoy", "whatIveTried", "whatImCuriousAbout",
       "items": [ { "id", "title", "type", "date", "role", "description",
                    "skills": [], "reflection": { "whatIDid", "whatILearned",
                    "challenge", "nextStep" }, "evidence": [] } ] }
   ]
    */

// ห้ามเปิดไฟล์นี้ตรง ๆ จาก browser
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

date_default_timezone_set('Asia/Bangkok'); // "วันนี้" ตรงกับเวลาไทย

/* ---------- ที่เก็บข้อมูล ---------- */
if (php_sapi_name() === 'cli-server') {
    define('DATA_DIR', __DIR__ . '/../local-data');   // laptop: php -S localhost:8000
} else {
    define('DATA_DIR', '/var/www/data');               // EC2: Apache
}
define('DATA_FILE', DATA_DIR . '/portfolios.json');
define('EXAMPLES_DIR', __DIR__ . '/../data');

/* ---------- ตอบกลับ ---------- */

/** ตอบกลับเป็น JSON แล้วจบการทำงาน (200 OK · 201 Created · 400 Bad request · 404 Not found) */
function sendJson($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- ล็อกข้อมูล: เขียนได้ทีละคน ---------- */

/** รอคิว — ล็อกจะถูกปล่อยเองเมื่อ PHP ทำงานจบ */
function lockData(): void
{
    static $lock = null;
    if ($lock !== null) {
        return; // ได้คิวแล้ว
    }
    if (!is_dir(DATA_DIR) && !@mkdir(DATA_DIR, 0775, true)) {
        sendJson(['error' => 'สร้างโฟลเดอร์ข้อมูลไม่ได้: ' . DATA_DIR], 500);
    }
    $lock = @fopen(DATA_DIR . '/portfolios.lock', 'c');
    if ($lock === false) {
        sendJson(['error' => 'เขียนโฟลเดอร์ข้อมูลไม่ได้ (sudo chown www-data:www-data ' . DATA_DIR . ')'], 500);
    }
    flock($lock, LOCK_EX);
}

/* ---------- อ่าน / เขียนสมุด ---------- */

function readPortfolios(): array
{
    lockData();
    if (!file_exists(DATA_FILE)) {
        return seedExamples(); // ครั้งแรก: เริ่มจาก Portfolio ตัวอย่างใน data/
    }
    $portfolios = json_decode(file_get_contents(DATA_FILE), true);
    return is_array($portfolios) ? $portfolios : [];
}

function writePortfolios(array $portfolios): void
{
    lockData();
    $json = json_encode(array_values($portfolios), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // เขียนไฟล์ชั่วคราวก่อน แล้วค่อยเปลี่ยนชื่อทับ (ไฟล์ไม่พังถ้าเขียนไม่จบ)
    $tmp = DATA_FILE . '.tmp';
    if (file_put_contents($tmp, $json) === false || !rename($tmp, DATA_FILE)) {
        sendJson(['error' => 'บันทึกไฟล์ไม่สำเร็จ (sudo chown www-data:www-data ' . DATA_DIR . ')'], 500);
    }
}

/** สร้างไฟล์ข้อมูลครั้งแรกจาก Portfolio ตัวอย่าง */
function seedExamples(): array
{
    $portfolios = [];
    foreach (glob(EXAMPLES_DIR . '/*.json') ?: [] as $file) {
        $raw = json_decode(file_get_contents($file), true);
        if (!is_array($raw)) {
            continue;
        }
        $portfolio = buildPortfolio($raw);
        if ($portfolio === null) {
            continue;
        }
        foreach (is_array($raw['items'] ?? null) ? $raw['items'] : [] as $rawItem) {
            $item = buildItem(is_array($rawItem) ? flattenItem($rawItem) : []);
            if ($item !== null) {
                $portfolio['items'][] = $item;
            }
        }
        $portfolios[] = $portfolio;
    }
    writePortfolios($portfolios);
    return $portfolios;
}

/* ---------- ช่วยหาข้อมูล ---------- */

/** หาลำดับของ Portfolio จาก id — ไม่เจอคืนค่า -1 */
function findPortfolioIndex(array $portfolios, string $id): int
{
    foreach ($portfolios as $i => $portfolio) {
        if (($portfolio['id'] ?? '') === $id) {
            return $i;
        }
    }
    return -1;
}

/** หาลำดับของ Item จาก id — ไม่เจอคืนค่า -1 */
function findItemIndex(array $items, string $id): int
{
    foreach ($items as $i => $item) {
        if (($item['id'] ?? '') === $id) {
            return $i;
        }
    }
    return -1;
}

/* ---------- ทำความสะอาดข้อมูลจากฟอร์ม ---------- */

/** ตัดช่องว่าง และจำกัดความยาว */
function cleanText($value, int $max = 3000): string
{
    if (!is_scalar($value)) {
        return '';
    }
    $text = trim((string)$value);
    return function_exists('mb_substr') ? mb_substr($text, 0, $max, 'UTF-8') : substr($text, 0, $max);
}

/** "HTML, CSS" → ["HTML", "CSS"] */
function splitList($value, string $separator): array
{
    if (is_array($value)) {
        $parts = $value;
    } else {
        $parts = explode($separator, (string)$value);
    }
    return array_values(array_filter(array_map(fn($part) => cleanText($part, 1000), $parts), fn($part) => $part !== ''));
}

function isHttpUrl(string $url): bool
{
    return preg_match('~^https?://\S+$~i', $url) === 1;
}

/** สร้าง Portfolio ใหม่จากค่าที่ส่งมา — ไม่มีชื่อคืนค่า null (server สร้าง id เอง) */
function buildPortfolio(array $input): ?array
{
    $name = cleanText($input['displayName'] ?? $input['name'] ?? '', 120);
    if ($name === '') {
        return null;
    }
    return [
        'id' => uniqid('p'),
        'displayName' => $name,
        'whatIEnjoy' => cleanText($input['whatIEnjoy'] ?? ''),
        'whatIveTried' => cleanText($input['whatIveTried'] ?? ''),
        'whatImCuriousAbout' => cleanText($input['whatImCuriousAbout'] ?? ''),
        'items' => [],
    ];
}

/** Item ในไฟล์ตัวอย่างมี reflection { } ซ้อนอยู่ → ดึงออกมาเป็นช่องเดียวกับฟอร์ม */
function flattenItem(array $raw): array
{
    $reflection = is_array($raw['reflection'] ?? null) ? $raw['reflection'] : [];
    return array_merge($raw, $reflection, [
        'evidence' => is_array($raw['evidence'] ?? null) ? implode("\n", $raw['evidence']) : ($raw['evidence'] ?? ''),
    ]);
}

/**
 * สร้าง Item จากช่องในฟอร์ม ($_POST) ให้อยู่ในรูปแบบ Data Contract
 * - "HTML, CSS"       → skills ["HTML", "CSS"]
 * - whatIDid ...      → reflection { }
 * - ลิงก์บรรทัดละ 1   → evidence [ ]
 * ไม่มี title คืนค่า null
 */
function buildItem(array $input): ?array
{
    $title = cleanText($input['title'] ?? '', 200);
    if ($title === '') {
        return null;
    }

    $type = cleanText($input['type'] ?? '', 100);
    if ($type === 'Other' && cleanText($input['otherType'] ?? '') !== '') {
        $type = cleanText($input['otherType'], 100); // ประเภทอื่น ๆ → ใช้ข้อความที่พิมพ์
    }

    $date = cleanText($input['date'] ?? '', 10);

    return [
        'id' => uniqid('item'),
        'title' => $title,
        'type' => $type,
        'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '',
        'role' => cleanText($input['role'] ?? '', 200),
        'description' => cleanText($input['description'] ?? '', 5000),
        'skills' => splitList($input['skills'] ?? '', ','),
        'reflection' => [
            'whatIDid' => cleanText($input['whatIDid'] ?? ''),
            'whatILearned' => cleanText($input['whatILearned'] ?? ''),
            'challenge' => cleanText($input['challenge'] ?? ''),
            'nextStep' => cleanText($input['nextStep'] ?? ''),
        ],
        // เก็บเฉพาะลิงก์ http(s) (กันลิงก์อันตราย เช่น javascript:)
        'evidence' => array_values(array_filter(splitList($input['evidence'] ?? '', "\n"), 'isHttpUrl')),
    ];
}

/** ตรวจว่า Item กรอกครบ — คืนข้อความ error หรือ '' ถ้าผ่าน */
function checkItem(array $item, array $input): string
{
    $required = [
        'type' => $item['type'], 'date' => $item['date'], 'role' => $item['role'],
        'description' => $item['description'], 'whatIDid' => $item['reflection']['whatIDid'],
        'whatILearned' => $item['reflection']['whatILearned'], 'challenge' => $item['reflection']['challenge'],
        'nextStep' => $item['reflection']['nextStep'],
    ];
    foreach ($required as $field => $value) {
        if ($value === '') {
            return "กรอกช่อง $field ให้ครบ";
        }
    }
    if ($item['skills'] === []) {
        return 'กรอก skills อย่างน้อย 1 อย่าง';
    }
    if ($item['date'] > date('Y-m-d')) {
        return 'วันที่ต้องไม่เป็นวันในอนาคต';
    }
    $links = splitList($input['evidence'] ?? '', "\n");
    if ($links === [] || count($links) !== count($item['evidence'])) {
        return 'evidence ต้องเป็นลิงก์ที่ขึ้นต้นด้วย http:// หรือ https://';
    }
    return '';
}
