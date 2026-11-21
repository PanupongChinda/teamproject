/* 
   storage.js — จัดการข้อมูล Portfolio
   ใช้ร่วมกันทั้งหน้า "สร้าง Portfolio" และ "ดู Portfolio"

   มี 2 โหมด (เลือกให้อัตโนมัติตอนโหลด):
   - "server" : มี PHP (api/portfolios.php) → อ่าน/บันทึกเป็นไฟล์ JSON บน server
   - "local"  : ไม่มี PHP (เช่นเปิดด้วย Live Server) → เก็บใน localStorage ของ browser

   รูปแบบข้อมูลภายในเว็บ:
   { id, name, enjoy, tried, curious,
     items: [{ id, title, type, typeDetails, date, role, description,
               skills[], whatIDid, whatILearned, challenge, nextStep, evidence[] }] }

   รูปแบบแลกเปลี่ยน (ไฟล์ JSON / server) — ดู toExchange()
    */
const PortfolioStore = (() => {
  const KEYS = { portfolios: "portfolios", selected: "selectedId" };

  // หาโฟลเดอร์จากตำแหน่งไฟล์นี้ (js/core/) → ใช้ได้ทุกหน้า
  const SCRIPT_URL = document.currentScript.src;
  const DATA_DIR = new URL("../../data/", SCRIPT_URL);
  const API_URL = new URL("../../api/portfolios.php", SCRIPT_URL);
  const EXAMPLE_FILES = ["portfolio-example-1.json", "portfolio-example-2.json"];

  let portfolios = [];
  let selectedId = null;
  let mode = "local";
  let loadError = "";

  /* =====================================================
     แปลงข้อมูล
     ===================================================== */
  const text = value => (typeof value === "string" || typeof value === "number" ? String(value).trim() : "");
  const list = value => (Array.isArray(value) ? value : String(value ?? "").split(/[,\n]/))
    .map(text)
    .filter(Boolean);
  const isHttpUrl = url => /^https?:\/\/\S+$/i.test(url);

  function createId(prefix = "p") {
    return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  }

  function normalizeItem(raw) {
    if (!raw || typeof raw !== "object") return null;
    const reflection = raw.reflection || {};
    const title = text(raw.title);
    if (!title) return null;

    return {
      id: /^[A-Za-z0-9_-]{1,64}$/.test(text(raw.id)) ? text(raw.id) : createId("item"),
      title,
      type: text(raw.type),
      typeDetails: text(raw.typeDetails),
      date: /^\d{4}-\d{2}-\d{2}$/.test(text(raw.date)) ? text(raw.date) : "",
      role: text(raw.role),
      description: text(raw.description),
      skills: list(raw.skills),
      whatIDid: text(raw.whatIDid ?? reflection.whatIDid),
      whatILearned: text(raw.whatILearned ?? reflection.whatILearned),
      challenge: text(raw.challenge ?? reflection.challenge),
      nextStep: text(raw.nextStep ?? reflection.nextStep),
      evidence: list(raw.evidence).filter(isHttpUrl) // กันลิงก์อันตราย เช่น javascript:
    };
  }

  /** รับได้ทั้งรูปแบบภายในเว็บ และรูปแบบไฟล์ JSON (whatIEnjoy, portfolioId, ...) */
  function normalizePortfolio(raw) {
    if (!raw || typeof raw !== "object" || Array.isArray(raw)) return null;
    const name = text(raw.name);
    if (!name) return null;

    return {
      id: text(raw.id ?? raw.portfolioId) || createId(),
      name,
      enjoy: text(raw.enjoy ?? raw.whatIEnjoy),
      tried: text(raw.tried ?? raw.whatIveTried),
      curious: text(raw.curious ?? raw.whatImCuriousAbout),
      items: Array.isArray(raw.items) ? raw.items.map(normalizeItem).filter(Boolean) : []
    };
  }

  /** Item → Portfolio Item JSON (รูปแบบแลกเปลี่ยน) */
  function toExchangeItem(item) {
    return {
      id: item.id,
      title: item.title,
      type: item.type === "Other" && item.typeDetails ? item.typeDetails : item.type,
      date: item.date,
      role: item.role,
      description: item.description,
      skills: [...item.skills],
      reflection: {
        whatIDid: item.whatIDid,
        whatILearned: item.whatILearned,
        challenge: item.challenge,
        nextStep: item.nextStep
      },
      evidence: [...item.evidence]
    };
  }

  /** Portfolio → Portfolio JSON (รูปแบบแลกเปลี่ยน / my-portfolio.json) */
  function toExchange(portfolio) {
    return {
      portfolioId: portfolio.id,
      name: portfolio.name,
      whatIEnjoy: portfolio.enjoy,
      whatIveTried: portfolio.tried,
      whatImCuriousAbout: portfolio.curious,
      items: portfolio.items.map(toExchangeItem)
    };
  }

  /** ตรวจว่า JSON ที่ได้มาเป็น Portfolio ทั้งก้อน หรือเป็น Portfolio Item */
  function detectKind(raw) {
    const first = Array.isArray(raw) ? raw[0] : raw;
    if (!first || typeof first !== "object") return null;
    if ("title" in first && !("name" in first)) return "item";
    if ("name" in first) return "portfolio";
    return null;
  }

  /* =====================================================
     Server API
     ===================================================== */
  async function api(method, id, body) {
    const url = new URL(API_URL);
    if (id) url.searchParams.set("id", id);

    const response = await fetch(url, {
      method,
      cache: "no-store",
      headers: body ? { "Content-Type": "application/json" } : {},
      body: body ? JSON.stringify(body) : undefined
    });

    let json;
    try {
      json = await response.json();
    } catch {
      throw new Error("เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง");
    }
    if (!response.ok || !json.ok) throw new Error(json.error || `เซิร์ฟเวอร์ผิดพลาด (${response.status})`);
    return json.data;
  }

  /** ลองเรียก API: ได้ JSON กลับมา = มี PHP server */
  async function tryServer() {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 6000);
    try {
      const response = await fetch(API_URL, { cache: "no-store", signal: controller.signal });
      const json = await response.json(); // ไม่มี PHP → ได้ source code / 404 HTML → parse ไม่ผ่าน
      return json;
    } catch {
      return null;
    } finally {
      clearTimeout(timer);
    }
  }

  /* =====================================================
     บันทึก / โหลด
     ===================================================== */
  function persist() {
    try {
      localStorage.setItem(KEYS.selected, JSON.stringify(selectedId));
      if (mode === "local") localStorage.setItem(KEYS.portfolios, JSON.stringify(portfolios));
    } catch (error) {
      console.error("บันทึกข้อมูลไม่สำเร็จ:", error);
    }
  }

  async function loadExamples() {
    const results = await Promise.all(EXAMPLE_FILES.map(async file => {
      try {
        const response = await fetch(new URL(file, DATA_DIR));
        if (!response.ok) throw new Error(`โหลด ${file} ไม่สำเร็จ`);
        return normalizePortfolio(await response.json());
      } catch (error) {
        console.warn(error);
        return null;
      }
    }));
    return results.filter(Boolean);
  }

  async function loadLocal() {
    try {
      const saved = JSON.parse(localStorage.getItem(KEYS.portfolios)) || [];
      portfolios = saved.map(normalizePortfolio).filter(Boolean);
    } catch {
      portfolios = [];
    }
    const examples = await loadExamples();
    const existingIds = new Set(portfolios.map(p => p.id));
    portfolios = [...examples.filter(e => !existingIds.has(e.id)), ...portfolios];
  }

  async function load() {
    try {
      selectedId = JSON.parse(localStorage.getItem(KEYS.selected));
      if (selectedId !== null) selectedId = String(selectedId);
    } catch {
      selectedId = null;
    }

    const result = await tryServer();
    if (result) {
      mode = "server";
      if (result.ok) {
        portfolios = result.data.map(normalizePortfolio).filter(Boolean);
      } else {
        portfolios = [];
        loadError = result.error || "โหลดข้อมูลจากเซิร์ฟเวอร์ไม่สำเร็จ";
      }
    } else {
      mode = "local";
      await loadLocal();
    }

    if (!portfolios.some(p => p.id === selectedId)) {
      selectedId = portfolios[0]?.id ?? null;
    }
    persist();
    document.dispatchEvent(new CustomEvent("store:mode", { detail: mode }));
  }

  const clone = value => JSON.parse(JSON.stringify(value));

  /* =====================================================
     API สำหรับหน้าเว็บ (ฟังก์ชันที่แก้ข้อมูลเป็น async ทั้งหมด)
     ===================================================== */
  return {
    load,
    normalizePortfolio,
    normalizeItem,
    toExchange,
    toExchangeItem,
    detectKind,
    createId,

    getMode: () => mode,
    getLoadError: () => loadError,
    getAll: () => portfolios,
    getById: id => portfolios.find(p => p.id === id) || null,
    getSelected: () => portfolios.find(p => p.id === selectedId) || null,
    getSelectedId: () => selectedId,

    select(id) {
      selectedId = id;
      persist();
    },

    /** สร้าง Portfolio ใหม่ (รับได้ทั้งรูปแบบภายในและรูปแบบ JSON) */
    async add(data) {
      let portfolio = normalizePortfolio(data);
      if (!portfolio) return null;
      if (portfolios.some(p => p.id === portfolio.id)) portfolio.id = createId(); // กัน id ซ้ำ

      if (mode === "server") {
        portfolio = normalizePortfolio(await api("POST", null, toExchange(portfolio)));
      }
      portfolios.push(portfolio);
      selectedId = portfolio.id;
      persist();
      return portfolio;
    },

    /**
     * แก้ไข Portfolio: ส่งฟังก์ชันที่แก้ข้อมูลเข้ามา
     * เช่น update(id, p => p.items.push(item))
     * ถ้าบันทึกบน server ไม่สำเร็จ ข้อมูลในหน้าเว็บจะไม่เปลี่ยน
     */
    async update(id, change) {
      const index = portfolios.findIndex(p => p.id === id);
      if (index === -1) throw new Error("ไม่พบ Portfolio");

      const draft = clone(portfolios[index]);
      change(draft);

      const saved = mode === "server"
        ? normalizePortfolio(await api("PUT", id, toExchange(draft)))
        : draft;

      portfolios[index] = saved;
      persist();
      return saved;
    },

    async remove(id) {
      if (mode === "server") await api("DELETE", id);
      portfolios = portfolios.filter(p => p.id !== id);
      if (selectedId === id) selectedId = portfolios[0]?.id ?? null;
      persist();
    }
  };
})();
