/* =========================================================
   theme.js — โหมดสว่าง / มืด
   - โหลดใน <head> เพื่อใส่ธีมก่อนหน้าเว็บแสดงผล (ไม่กระพริบ)
   - ปุ่มสลับ (#themeToggle) มีเฉพาะหน้าด้านใน ไม่มีในหน้า Home
   ========================================================= */
(function () {
  const STORAGE_KEY = "portfolioTheme";

  function getSavedTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY) === "dark" ? "dark" : "light";
    } catch {
      return "light";
    }
  }

  function applyTheme(theme) {
    document.documentElement.dataset.bsTheme = theme; // Bootstrap ใช้ attribute นี้

    const toggle = document.getElementById("themeToggle");
    if (!toggle) return;
    const isDark = theme === "dark";
    toggle.setAttribute("aria-checked", String(isDark));
    toggle.title = isDark ? "เปิดโหมดสว่าง" : "เปิดโหมดมืด";
  }

  // 1) ใส่ธีมทันที
  applyTheme(getSavedTheme());

  // 2) ผูกปุ่มสลับเมื่อหน้าโหลดเสร็จ
  document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.getElementById("themeToggle");
    applyTheme(getSavedTheme());
    if (!toggle) return;

    toggle.addEventListener("click", () => {
      const next = document.documentElement.dataset.bsTheme === "dark" ? "light" : "dark";
      try { localStorage.setItem(STORAGE_KEY, next); } catch { /* ignore */ }
      applyTheme(next);
    });
  });
})();
