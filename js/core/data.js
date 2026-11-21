/* 
   data.js — คุยกับ PHP ด้วย fetch() (ใช้ร่วมกันทุกหน้า)

   โหลด : GET  backend/portfolios.php            → Portfolio ทั้งหมด (JSON)
   เพิ่ม : POST backend/portfolios.php + FormData → Portfolio ใหม่ 1 คน
          POST backend/items.php      + FormData → Item ใหม่ 1 ชิ้น
   แก้/ลบ: POST + action=update / action=delete

   หลังบันทึกทุกครั้ง โหลดข้อมูลจาก server ใหม่ (loadPortfolios) เพื่อให้เห็นข้อมูลจริง
   localStorage ใช้จำแค่ว่าเลือก Portfolio ไหนอยู่ (ไม่ได้เก็บข้อมูล)
    */
const PortfolioData = (() => {
  // หาโฟลเดอร์ backend/ จากตำแหน่งไฟล์นี้ (js/core/) → ใช้ได้ทุกหน้า
  const BACKEND = new URL("../../backend/", document.currentScript.src);
  const SELECTED_KEY = "selectedPortfolioId";

  let portfolios = [];
  let selectedId = localStorage.getItem(SELECTED_KEY);

  /** ส่ง request แล้วอ่านคำตอบ JSON — ถ้า PHP ตอบ error ให้โยนข้อความนั้นออกไป */
  async function send(file, formData) {
    const options = formData ? { method: "POST", body: formData } : { cache: "no-store" };
    const response = await fetch(new URL(file, BACKEND), options);

    let data;
    try {
      data = await response.json();
    } catch {
      throw new Error("เซิร์ฟเวอร์ไม่ได้ตอบเป็น JSON — เปิดเว็บผ่าน php -S หรือ Apache หรือยัง?");
    }
    if (!response.ok) throw new Error(data.error || `เซิร์ฟเวอร์ผิดพลาด (${response.status})`);
    return data;
  }

  /** สร้าง FormData จาก object ธรรมดา */
  function toFormData(fields) {
    const formData = new FormData();
    Object.entries(fields).forEach(([key, value]) => formData.append(key, value ?? ""));
    return formData;
  }

  /** Portfolio Item (Data Contract) → ช่องแบบเดียวกับฟอร์ม Item */
  function itemToFields(item) {
    const r = item.reflection || {};
    const list = value => (Array.isArray(value) ? value : String(value ?? "").split(/[,\n]/)).map(s => String(s).trim()).filter(Boolean);
    return {
      title: item.title,
      type: item.type,
      date: item.date,
      role: item.role,
      description: item.description,
      skills: list(item.skills).join(", "),
      whatIDid: r.whatIDid ?? item.whatIDid,
      whatILearned: r.whatILearned ?? item.whatILearned,
      challenge: r.challenge ?? item.challenge,
      nextStep: r.nextStep ?? item.nextStep,
      evidence: list(item.evidence).join("\n")
    };
  }

  function rememberSelected() {
    if (!portfolios.some(p => p.id === selectedId)) selectedId = portfolios[0]?.id ?? null;
    if (selectedId) localStorage.setItem(SELECTED_KEY, selectedId);
  }

  return {
    itemToFields,

    getAll: () => portfolios,
    getById: id => portfolios.find(p => p.id === id) || null,
    getSelected: () => portfolios.find(p => p.id === selectedId) || null,
    getSelectedId: () => selectedId,

    select(id) {
      selectedId = id;
      rememberSelected();
    },

    /** GET: โหลด Portfolio ทั้งหมดจาก server */
    async loadPortfolios() {
      portfolios = await send("portfolios.php");
      rememberSelected();
      return portfolios;
    },

    /** POST: เพิ่ม Portfolio ใหม่ (formData จากฟอร์ม หรือ object) */
    async createPortfolio(formData) {
      const portfolio = await send("portfolios.php", formData instanceof FormData ? formData : toFormData(formData));
      selectedId = portfolio.id;
      await this.loadPortfolios();
      return portfolio;
    },

    async deletePortfolio(id) {
      const removed = await send("portfolios.php", toFormData({ action: "delete", id }));
      await this.loadPortfolios();
      return removed;
    },

    /** POST: เพิ่ม Item ใหม่ให้ Portfolio ที่มี portfolioId ในฟอร์ม */
    async addItem(formData) {
      const item = await send("items.php", formData);
      await this.loadPortfolios();
      return item;
    },

    async updateItem(formData, itemId) {
      formData.set("action", "update");
      formData.set("itemId", itemId);
      const item = await send("items.php", formData);
      await this.loadPortfolios();
      return item;
    },

    async deleteItem(portfolioId, itemId) {
      const item = await send("items.php", toFormData({ action: "delete", portfolioId, itemId }));
      await this.loadPortfolios();
      return item;
    },

    /**
     * นำเข้าทั้ง Portfolio (จากไฟล์ JSON ของเพื่อน):
     * POST Portfolio ก่อน แล้ว POST Item ทีละชิ้น (เพิ่มทีละอย่าง — ไม่บันทึกทับทั้งไฟล์)
     */
    async importPortfolio(raw) {
      const portfolio = await send("portfolios.php", toFormData({
        displayName: raw.displayName ?? raw.name,
        whatIEnjoy: raw.whatIEnjoy,
        whatIveTried: raw.whatIveTried,
        whatImCuriousAbout: raw.whatImCuriousAbout,
        import: "1"
      }));
      for (const item of Array.isArray(raw.items) ? raw.items : []) {
        await send("items.php", toFormData({ ...itemToFields(item), portfolioId: portfolio.id, import: "1" }));
      }
      selectedId = portfolio.id;
      await this.loadPortfolios();
      return portfolio;
    },

    /** นำเข้า Item หลายชิ้นพร้อมกัน */
    async importItems(portfolioId, items) {
      for (const item of items) {
        await send("items.php", toFormData({ ...itemToFields(item), portfolioId, import: "1" }));
      }
      await this.loadPortfolios();
    }
  };
})();
