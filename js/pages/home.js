/* 
   home.js — เอฟเฟกต์หน้าแรก
   1. พิมพ์คำอัตโนมัติ (ผลงาน → ทักษะ → ...)
   2. วงกลมพื้นหลังขยับตามเมาส์ (parallax)
    */

/* ---------- 1. Typing effect ---------- */
(function typingEffect() {
  const target = document.getElementById("typedWord");
  if (!target) return;

  const words = ["ผลงาน", "ทักษะ", "ประสบการณ์", "การเติบโต"];

  // ตัดคำเป็นตัวอักษรแบบไม่แยกสระ/วรรณยุกต์ไทยออกจากพยัญชนะ
  const segmenter = window.Intl?.Segmenter ? new Intl.Segmenter("th", { granularity: "grapheme" }) : null;
  const toChars = word => (segmenter ? Array.from(segmenter.segment(word), s => s.segment) : Array.from(word));

  // ผู้ใช้ตั้งค่าลดการเคลื่อนไหว → เปลี่ยนคำเฉย ๆ ทุก 2.5 วินาที
  if (UI.reducedMotion) {
    let i = 0;
    setInterval(() => { target.textContent = words[++i % words.length]; }, 2500);
    return;
  }

  let wordIndex = 0;
  let charCount = toChars(words[0]).length;
  let deleting = true;

  function tick() {
    const chars = toChars(words[wordIndex]);
    charCount += deleting ? -1 : 1;
    target.textContent = chars.slice(0, charCount).join("");

    let delay = deleting ? 60 : 110;

    if (!deleting && charCount === chars.length) {
      deleting = true;
      delay = 1800; // พิมพ์ครบแล้ว ค้างไว้สักพัก
    } else if (deleting && charCount === 0) {
      deleting = false;
      wordIndex = (wordIndex + 1) % words.length;
      delay = 300;
    }
    setTimeout(tick, delay);
  }

  setTimeout(tick, 2200);
})();

/* ---------- 2. Parallax blobs ---------- */
(function parallax() {
  const blobs = document.querySelectorAll(".blob[data-depth]");
  const finePointer = window.matchMedia("(pointer: fine)").matches; // เฉพาะเมาส์ ไม่ทำบนมือถือ
  if (!blobs.length || UI.reducedMotion || !finePointer) return;

  let frame = null;

  window.addEventListener("pointermove", event => {
    if (frame) return;
    frame = requestAnimationFrame(() => {
      const x = event.clientX / window.innerWidth - 0.5;  // -0.5 ถึง 0.5
      const y = event.clientY / window.innerHeight - 0.5;
      blobs.forEach(blob => {
        const depth = Number(blob.dataset.depth);
        blob.style.setProperty("--px", `${x * depth}px`);
        blob.style.setProperty("--py", `${y * depth}px`);
      });
      frame = null;
    });
  });
})();
