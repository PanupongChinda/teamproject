# Team Project – Portfolio Website

## 1. เกี่ยวกับเว็บไซต์ (About Website)

เว็บไซต์นี้จัดทำขึ้นเพื่อให้ผู้ใช้สามารถสร้าง จัดเก็บ และจัดการแฟ้มสะสมผลงานของตนเองได้ โดยสามารถเพิ่มข้อมูลส่วนตัว เพิ่มรายการผลงาน และดูรายละเอียดของผลงานได้ ข้อมูลบันทึกบน Server (PHP เก็บเป็นไฟล์ JSON) และรองรับการแลกเปลี่ยน **Portfolio Item JSON** กับเพื่อนสำหรับกิจกรรม Final Demo

## 2. เว็บไซต์แฟ้มสะสมผลงาน (Portfolio Website)

เว็บไซต์เผยแพร่ผ่าน GitHub Pages สามารถเข้าใช้งานได้ที่

[https://saesuenghabes.github.io/teamproject/](https://saesuenghabes.github.io/teamproject/)

> GitHub Pages ส่งได้แค่ไฟล์และไม่รัน PHP — เวอร์ชันที่บันทึกข้อมูลได้จริงต้องเปิดบน AWS EC2 (Apache + PHP) ที่ `http://<public-ip>/`

## 3. ความสามารถของเว็บไซต์ (Features)

- สร้าง / ลบ Portfolio และ เพิ่ม / แก้ไข / ลบ Portfolio Item
- บันทึกข้อมูลบน Server: JavaScript ส่งด้วย `fetch()` + `FormData` → PHP อ่าน `$_POST` → บันทึกลงไฟล์ `portfolios.json` (หน้าเว็บไม่ reload)
- เปิดจาก Browser / อุปกรณ์อื่น หรือหลัง restart server ข้อมูลยังอยู่ครบ
- **ส่งออก Portfolio Item JSON** ให้เพื่อน (Copy หรือ Download) และ **Export my-portfolio.json**
- **Import** Portfolio Item จากเพื่อน (วาง JSON / เลือกไฟล์ / ลากไฟล์) → เติมลงฟอร์มให้ตรวจ/เพิ่ม Reflection ก่อนบันทึก หรือ Import ทั้ง Portfolio
- Light / Dark Mode และรองรับคอมพิวเตอร์ แท็บเล็ต มือถือ

## 4. โครงสร้างไฟล์ (Project Structure)

```
index.html              หน้าแรก
pages/
  create.html           หน้าสร้าง Portfolio + Item + Import
  portfolio.html        หน้าดู Portfolio + ส่งออก JSON
  game.html             เกม Blackjack
backend/                ไฟล์ PHP (ฝั่ง server)
  storage.php           "หลังร้าน": อ่าน/เขียน portfolios.json, lockData(), sendJson()
  portfolios.php        "โต๊ะ Portfolio": GET = ทั้งหมด · POST = เพิ่ม 1 คน · POST action=delete
  items.php             "โต๊ะ Item": POST = เพิ่ม 1 ชิ้น · POST action=update / delete
css/                    base.css + ไฟล์ของแต่ละหน้า
js/
  core/data.js          fetch() คุยกับ backend/ (โหลด / เพิ่ม / แก้ / ลบ)
  core/ui.js            ข้อความแจ้งเตือน, ปุ่มหมุนรอ, เอฟเฟกต์, คัดลอกข้อความ
  core/theme.js         โหมดสว่าง/มืด
  pages/*.js            โค้ดของแต่ละหน้า
data/                   Portfolio ตัวอย่าง — ใช้เป็นข้อมูลเริ่มต้นครั้งแรก
local-data/             ไฟล์ข้อมูลตอนรันบน laptop (php -S) — ไม่อยู่ใน git
```

### การส่งข้อมูล (Data flow)

| เมื่อ… | JavaScript ส่ง | PHP ทำ |
|---|---|---|
| เปิดหน้า | `GET backend/portfolios.php` | `readPortfolios()` → ตอบ JSON ทั้งหมด |
| สร้าง Portfolio | `POST backend/portfolios.php` + `FormData(form)` | อ่าน `$_POST` → อ่านไฟล์ → เพิ่ม 1 คน → เขียนไฟล์ → ตอบ 201 |
| เพิ่ม Item | `POST backend/items.php` + `FormData` + `portfolioId` | `"HTML, CSS"` → `["HTML","CSS"]`, reflection `{}`, evidence `[]` → เพิ่ม 1 ชิ้น |
| แก้ไข / ลบ | `POST` + `action=update` / `action=delete` | เปลี่ยนแค่ชิ้นนั้น (ไม่บันทึกทับทั้งรายการ) |
| หลังบันทึก | โหลด `GET` ใหม่ → แสดงข้อมูลจาก server | — |

- ชื่อ `name="..."` ของทุกช่องในฟอร์มตรงกับ Data Contract (`displayName`, `whatIEnjoy`, `title`, `whatIDid`, …)
- Server เป็นคนสร้าง `id` ทุกตัว (`uniqid()`), ตอบ error เป็น `{ "error": "..." }` พร้อม 400 / 404 และหน้าเว็บแสดงข้อความใต้ฟอร์ม
- `lockData()` ทำให้เขียนไฟล์ได้ทีละคน — วัน Demo มีหลายคนใช้พร้อมกันข้อมูลก็ไม่หาย

### รูปแบบข้อมูล (Data Contract)

```json
[
  {
    "id": "p6b01...",
    "displayName": "Alex Student",
    "whatIEnjoy": "...", "whatIveTried": "...", "whatImCuriousAbout": "...",
    "items": [
      {
        "id": "item6b01...", "title": "Paper Bridge", "type": "Challenge",
        "date": "2026-11-21", "role": "Participant", "description": "...",
        "skills": ["design", "teamwork"],
        "reflection": { "whatIDid": "...", "whatILearned": "...", "challenge": "...", "nextStep": "..." },
        "evidence": ["https://..."]
      }
    ]
  }
]
```

## 5. วิธีรันและ Deploy

**บน laptop (Development)** — Live Server ใช้ไม่ได้ เพราะไม่รัน PHP

```bash
php -S localhost:8000      # รันที่โฟลเดอร์โปรเจกต์ แล้วเปิด http://localhost:8000
```

ข้อมูลจะถูกเก็บที่ `local-data/portfolios.json`

**บน EC2 (Production)** — ข้อมูลเก็บที่ `/var/www/data/portfolios.json` (นอกโฟลเดอร์ public)

```bash
sudo mkdir -p /var/www/data                      # ทำครั้งเดียว
sudo chown www-data:www-data /var/www/data
cd ~/teamproject && git pull
sudo cp -r ~/teamproject/* /var/www/html/
```

ตรวจข้อมูล: `cat /var/www/data/portfolios.json` · ดู error: `sudo tail /var/log/apache2/error.log`

## 6. วิธีใช้งาน (How to Use)

**6.1** หน้าแรกกด "เริ่มสร้าง Portfolio" → กรอก Create Portfolio → กด "บันทึก Portfolio"

**6.2** เลือก Portfolio ในกล่อง "เพิ่ม Item ให้ Portfolio" → กรอก Portfolio Item → กด "บันทึก Portfolio Item"

**6.3** รับ Item จากเพื่อน: วาง JSON หรือเลือกไฟล์ในกล่อง Import JSON → กด "Import" → ตรวจสอบ/เพิ่ม Reflection → กด "บันทึกลง Portfolio ของฉัน"

**6.4** ส่ง Item ให้เพื่อน / ส่งงาน: หน้า "PORTFOLIO" กด "📤 ส่งออก JSON" ที่ Item หรือ "Export my-portfolio.json" แล้วเลือก Copy หรือ Download

**6.5** แก้ไข / ลบ: กด "✏️ แก้ไข", "🗑 ลบ" ที่ Item หรือ "ลบ Portfolio"
