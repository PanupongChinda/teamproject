/* =========================================================
   create.js — หน้าสร้าง Portfolio (pages/create.html)
   1. สร้าง Portfolio  → fetch POST backend/portfolios.php (FormData จากฟอร์ม)
   2. สร้าง/แก้ไข Item → fetch POST backend/items.php
      แก้ไข: create.html?portfolio=<id>&edit=<itemId>
   3. Import JSON
      - Item เดียว      → เติมลงฟอร์มให้ตรวจ/เพิ่ม Reflection ก่อนกดบันทึก
      - หลาย Item       → เพิ่มทีละชิ้น
      - Portfolio ทั้งก้อน → สร้าง Portfolio แล้วเพิ่ม Item ทีละชิ้น
   หน้าไม่ reload: ข้อความที่พิมพ์และ Portfolio ที่เลือกยังอยู่ แม้บันทึกไม่สำเร็จ
   ========================================================= */
(async function () {
  const $ = id => document.getElementById(id);

  const portfolioForm = $("portfolioForm");
  const itemForm = $("itemForm");
  const targetSelect = $("portfolioId");
  const typeSelect = $("type");
  const otherTypeField = $("otherTypeField");
  const otherTypeInput = $("otherType");
  const dateInput = $("date");
  const evidenceInput = $("evidence");
  const itemSubmitBtn = $("itemSubmitBtn");

  const importFile = $("importFile");
  const importJson = $("importJson");
  const importError = $("importError");
  const dropZone = $("dropZone");

  const ITEM_TYPES = Array.from(typeSelect.options).map(o => o.value).filter(Boolean);
  const DEFAULT_SUBMIT_TEXT = itemSubmitBtn.textContent;

  /** สถานะฟอร์ม Item: create | edit | import */
  let formState = { mode: "create" };

  function showError(element, message) {
    element.textContent = message;
    element.hidden = !message;
  }

  /* ---------- โหลดข้อมูลจาก server ---------- */
  try {
    await PortfolioData.loadPortfolios();
  } catch (error) {
    UI.toast(error.message, "error", 8000);
  }

  /* =====================================================
     1. สร้าง Portfolio
     ===================================================== */
  function validateField(field) {
    const input = field.querySelector("input, textarea");
    field.classList.toggle("invalid", input.value.trim() === "");
  }

  portfolioForm.addEventListener("input", event => {
    const field = event.target.closest(".field");
    if (field?.classList.contains("invalid")) validateField(field);
  });

  portfolioForm.addEventListener("submit", async event => {
    event.preventDefault(); // 1. ไม่ให้ฟอร์มเปลี่ยนหน้า
    portfolioForm.querySelectorAll(".field").forEach(validateField);
    const firstInvalid = portfolioForm.querySelector(".invalid input, .invalid textarea");
    if (firstInvalid) {
      firstInvalid.focus();
      return;
    }

    const formData = new FormData(portfolioForm); // 2. เก็บทุกช่องที่มี name
    try {
      const portfolio = await UI.withBusy(portfolioForm.querySelector('[type="submit"]'),
        () => PortfolioData.createPortfolio(formData)); // 3. POST → PHP บันทึก → โหลดใหม่
      showError($("portfolioError"), "");
      portfolioForm.reset();
      renderTargetSelect();
      UI.toast(`สร้าง Portfolio "${portfolio.displayName}" แล้ว — เพิ่ม Item ได้เลย`);
      if (window.innerWidth < 992) $("itemSection").scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (error) {
      showError($("portfolioError"), error.message); // error แสดงใต้ฟอร์ม ข้อความที่พิมพ์ยังอยู่
    }
  });

  /* =====================================================
     2. เลือก Portfolio ที่จะเพิ่ม Item
     ===================================================== */
  function renderTargetSelect() {
    const portfolios = PortfolioData.getAll();

    if (portfolios.length === 0) {
      targetSelect.innerHTML = '<option value="">ยังไม่มี Portfolio — สร้างทางซ้ายก่อน</option>';
      targetSelect.disabled = true;
      return;
    }

    targetSelect.disabled = formState.mode === "edit"; // ระหว่างแก้ไข ห้ามย้าย Portfolio
    targetSelect.innerHTML = portfolios
      .map(p => `<option value="${UI.escapeHtml(p.id)}">${UI.escapeHtml(p.displayName)} (${p.items.length} รายการ)</option>`)
      .join("");
    targetSelect.value = formState.mode === "edit" ? formState.portfolioId : PortfolioData.getSelectedId();
  }

  targetSelect.addEventListener("change", () => PortfolioData.select(targetSelect.value));

  /* =====================================================
     3. ฟอร์ม Portfolio Item
     ===================================================== */
  dateInput.max = UI.getTodayDate();

  function updateOtherTypeField() {
    const isOther = typeSelect.value === "Other";
    otherTypeField.hidden = !isOther;
    otherTypeInput.required = isOther;
    if (!isOther) otherTypeInput.value = "";
  }

  typeSelect.addEventListener("change", updateOtherTypeField);

  function clearInvalidMarks() {
    itemForm.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    showError($("itemError"), "");
  }

  itemForm.addEventListener("reset", () => setTimeout(() => {
    updateOtherTypeField();
    clearInvalidMarks();
    renderTargetSelect();
  }));

  // ---------- ตรวจความถูกต้อง ----------
  const getEvidenceLinks = () => evidenceInput.value.split("\n").map(s => s.trim()).filter(Boolean);
  const isValidLink = url => /^https?:\/\/\S+$/i.test(url);

  function applyCustomRules() {
    itemForm.querySelectorAll("input, textarea").forEach(el => {
      el.setCustomValidity(el.required && el.value.trim() === "" ? "empty" : "");
    });
    const links = getEvidenceLinks();
    evidenceInput.setCustomValidity(links.length > 0 && links.every(isValidLink) ? "" : "invalid");
    dateInput.setCustomValidity(dateInput.value > UI.getTodayDate() ? "future" : "");
  }

  function markInvalidFields() {
    applyCustomRules();
    itemForm.querySelectorAll("input, select, textarea").forEach(el => {
      el.classList.toggle("is-invalid", !el.disabled && !el.checkValidity());
    });
  }

  ["input", "change"].forEach(type => {
    itemForm.addEventListener(type, event => {
      const el = event.target;
      if (!el.classList.contains("is-invalid")) return;
      applyCustomRules();
      el.classList.toggle("is-invalid", !el.checkValidity());
    });
  });

  // ---------- เติมข้อมูลลงฟอร์ม (แก้ไข / นำเข้า) ----------
  function fillItemForm(item) {
    const fields = PortfolioData.itemToFields(item);
    ["title", "role", "description", "skills", "whatIDid", "whatILearned", "challenge", "nextStep"]
      .forEach(key => { $(key).value = fields[key] ?? ""; });

    evidenceInput.value = String(fields.evidence ?? "").split("\n").filter(isValidLink).join("\n");
    dateInput.value = /^\d{4}-\d{2}-\d{2}$/.test(fields.date || "") ? fields.date : "";

    // ประเภทที่ไม่อยู่ในรายการ → Other + พิมพ์ชื่อประเภทให้
    const type = String(fields.type ?? "");
    typeSelect.value = ITEM_TYPES.includes(type) ? type : (type ? "Other" : "");
    updateOtherTypeField();
    if (typeSelect.value === "Other" && type !== "Other") otherTypeInput.value = type;
    clearInvalidMarks();
  }

  // ---------- โหมดของฟอร์ม ----------
  function setFormMode(state) {
    formState = state;
    const text = $("modeBannerText");

    if (state.mode === "edit") {
      text.innerHTML = `✏️ กำลังแก้ไข: <strong>${UI.escapeHtml(state.title)}</strong>`;
      $("itemFormTitle").textContent = "Edit Portfolio Item";
      itemSubmitBtn.textContent = "บันทึกการแก้ไข";
    } else if (state.mode === "import") {
      text.innerHTML = `📥 นำเข้า: <strong>${UI.escapeHtml(state.title)}</strong> — ตรวจสอบ/เพิ่ม Reflection แล้วกดบันทึก`;
      $("itemFormTitle").textContent = "Import Portfolio Item";
      itemSubmitBtn.textContent = "บันทึกลง Portfolio ของฉัน";
    } else {
      $("itemFormTitle").textContent = "Create Portfolio Item";
      itemSubmitBtn.textContent = DEFAULT_SUBMIT_TEXT;
    }
    $("modeBanner").hidden = state.mode === "create";
    renderTargetSelect();
  }

  $("cancelModeBtn").addEventListener("click", () => {
    if (formState.mode === "edit") {
      location.href = "portfolio.html";
      return;
    }
    itemForm.reset();
    setFormMode({ mode: "create" });
  });

  function setupEditFromUrl() {
    const params = new URLSearchParams(location.search);
    const portfolio = PortfolioData.getById(params.get("portfolio") || "");
    const item = portfolio?.items.find(i => i.id === params.get("edit"));
    if (!item) {
      if (params.has("edit")) history.replaceState(null, "", location.pathname);
      return;
    }
    PortfolioData.select(portfolio.id);
    fillItemForm(item);
    setFormMode({ mode: "edit", portfolioId: portfolio.id, itemId: item.id, title: item.title });
  }

  // ---------- บันทึก Item ----------
  itemForm.addEventListener("submit", async event => {
    event.preventDefault();

    const portfolioId = formState.mode === "edit" ? formState.portfolioId : targetSelect.value;
    const portfolio = PortfolioData.getById(portfolioId);
    if (!portfolio) {
      UI.toast("กรุณาสร้างหรือเลือก Portfolio ก่อน", "error");
      return;
    }

    markInvalidFields();
    if (!itemForm.checkValidity()) {
      itemForm.querySelector(".is-invalid")?.focus();
      UI.toast("กรอกข้อมูลให้ครบและถูกต้องก่อนบันทึก", "error");
      return;
    }

    const formData = new FormData(itemForm);
    formData.set("portfolioId", portfolioId); // select ถูกปิดตอนแก้ไข → ใส่เอง
    const mode = formState.mode;

    try {
      const item = await UI.withBusy(itemSubmitBtn, () => mode === "edit"
        ? PortfolioData.updateItem(formData, formState.itemId)
        : PortfolioData.addItem(formData));

      if (mode === "edit") {
        UI.toast("บันทึกการแก้ไขแล้ว");
        setTimeout(() => { location.href = "portfolio.html"; }, 700);
        return;
      }
      itemForm.reset();
      setFormMode({ mode: "create" });
      UI.toast(`เพิ่ม "${item.title}" ลงใน ${portfolio.displayName} แล้ว`);
    } catch (error) {
      showError($("itemError"), error.message); // ข้อมูลในฟอร์มยังอยู่ แก้แล้วกดใหม่ได้
    }
  });

  /* =====================================================
     4. Import JSON
     ===================================================== */
  function resetImportBox() {
    showError(importError, "");
    importJson.value = "";
    importFile.value = "";
    $("importFileName").textContent = "หรือลากไฟล์มาวางที่นี่";
  }

  function readFile(file) {
    if (!file) return;
    if (!/\.json$/i.test(file.name) && file.type !== "application/json") {
      showError(importError, "รองรับเฉพาะไฟล์ .json");
      return;
    }
    if (file.size > 1024 * 1024) {
      showError(importError, "ไฟล์ใหญ่เกินไป (สูงสุด 1 MB)");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      importJson.value = reader.result;
      $("importFileName").textContent = file.name;
      showError(importError, "");
    };
    reader.readAsText(file);
  }

  importFile.addEventListener("change", () => readFile(importFile.files[0]));

  ["dragenter", "dragover"].forEach(type => dropZone.addEventListener(type, event => {
    event.preventDefault();
    dropZone.classList.add("is-dragover");
  }));
  ["dragleave", "drop"].forEach(type => dropZone.addEventListener(type, () => {
    dropZone.classList.remove("is-dragover");
  }));
  dropZone.addEventListener("drop", event => {
    event.preventDefault();
    readFile(event.dataTransfer.files[0]);
  });

  $("importBtn").addEventListener("click", async () => {
    const raw = importJson.value.trim();
    if (!raw) {
      showError(importError, "โปรดเลือกไฟล์หรือวางข้อความ JSON ก่อน");
      return;
    }

    let data;
    try {
      data = JSON.parse(raw);
    } catch {
      showError(importError, "รูปแบบ JSON ไม่ถูกต้อง");
      return;
    }

    const list = (Array.isArray(data) ? data : [data]).filter(entry => entry && typeof entry === "object");
    const first = list[0] || {};
    const isItem = "title" in first && !("displayName" in first) && !("name" in first);
    const isPortfolio = "displayName" in first || "name" in first;

    try {
      if (isItem) {
        const items = list.filter(entry => String(entry.title ?? "").trim());
        const target = PortfolioData.getById(targetSelect.value);
        if (!target) return showError(importError, "สร้าง Portfolio ของตัวเองก่อน แล้วค่อย Import Item");
        if (items.length === 0) return showError(importError, "Item ต้องมี title");

        if (items.length === 1) {
          // Item เดียว → เติมฟอร์มให้ตรวจก่อน (ยังไม่ส่งไป server)
          fillItemForm(items[0]);
          setFormMode({ mode: "import", title: items[0].title });
          resetImportBox();
          $("itemSection").scrollIntoView({ behavior: "smooth", block: "start" });
          UI.toast("ตรวจสอบข้อมูลและเพิ่ม Reflection แล้วกดบันทึก", "info", 4000);
          return;
        }

        await UI.withBusy($("importBtn"), () => PortfolioData.importItems(target.id, items));
        resetImportBox();
        renderTargetSelect();
        UI.toast(`นำเข้า ${items.length} Item ลงใน ${target.displayName} แล้ว`);
        return;
      }

      if (isPortfolio) {
        const names = [];
        await UI.withBusy($("importBtn"), async () => {
          for (const entry of list) names.push((await PortfolioData.importPortfolio(entry)).displayName);
        });
        resetImportBox();
        renderTargetSelect();
        UI.toast(`นำเข้า Portfolio "${names.join(", ")}" แล้ว`);
        return;
      }

      showError(importError, "ไม่รู้จักรูปแบบนี้: ต้องเป็น Portfolio Item (มี title) หรือ Portfolio (มี displayName)");
    } catch (error) {
      showError(importError, error.message);
    }
  });

  /* ---------- เริ่มต้น ---------- */
  setFormMode({ mode: "create" });
  setupEditFromUrl();
})();
