/**
 * Beacon Gospel Centre — Admin Console Javascript
 */

(function () {
  "use strict";

  const $ = (s) => document.querySelector(s);
  const $$ = (s) => [...document.querySelectorAll(s)];

  const currencyRate = 130;
  function renderMoney(mode) {
    document.querySelectorAll('[data-money]').forEach((el) => {
      const amount = Number(el.dataset.money || 0);
      el.textContent = mode === 'USD' ? '$' + (amount / currencyRate).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'KSh ' + Math.round(amount).toLocaleString('en-US');
    });
    document.querySelectorAll('[data-currency-toggle]').forEach((button) => button.textContent = mode === 'USD' ? 'USD / KSh' : 'KSh / USD');
  }
  renderMoney(localStorage.getItem('currency_mode') || 'KES');
  document.querySelectorAll('[data-currency-toggle]').forEach((button) => button.addEventListener('click', () => {
    const next = (localStorage.getItem('currency_mode') || 'KES') === 'KES' ? 'USD' : 'KES';
    localStorage.setItem('currency_mode', next);
    renderMoney(next);
  }));

  // Toast notification helper
  window.showToast = function (msg, isError = false) {
    const root = document.getElementById("toastRoot") || document.body;
    const t = document.createElement("div");
    t.className = "toast" + (isError ? " error" : "");
    t.textContent = msg;
    root.appendChild(t);
    setTimeout(() => t.remove(), 2800);
  };

  /* ============ 1. MODAL CONTROLS ============ */
  window.openAdminModal = function (modalId) {
    const m = document.getElementById(modalId);
    if (m) m.hidden = false;
  };

  window.closeAdminModal = function (modalId) {
    const m = document.getElementById(modalId);
    if (m) m.hidden = true;
  };

  // Close modals when clicking outside modal box
  document.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      e.target.hidden = true;
    }
  });

  /* ============ 3. CONFIRMATION DELETIONS ============ */
  $$("[data-confirm]").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const msg = btn.dataset.confirm || "Are you sure you want to proceed?";
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  /* ============ 4. CUSTOM FILE UPLOADER (styled button + preview + URL toggle) ============ */
  function initUploaders() {
    $$(".field .uploader").forEach((wrap) => {
      const input = wrap.querySelector('input[type="file"].up-input');
      const btn = wrap.querySelector(".up-btn");
      const fileNameEl = wrap.querySelector(".up-filename");
      const previewEl = wrap.querySelector(".up-preview img");
      const urlToggle = wrap.querySelector('[data-toggle-url]');
      const urlRow = wrap.closest(".field").querySelector(".up-url-row");

      if (!input || !btn) return;

      btn.addEventListener("click", () => input.click());
      input.addEventListener("change", () => {
        const f = input.files && input.files[0];
        if (!f) return;
        if (fileNameEl) fileNameEl.textContent = "✓ " + f.name + " · " + (f.size > 1048576 ? (f.size / 1048576).toFixed(1) + " MB" : Math.round(f.size / 1024) + " KB");
        if (previewEl && f.type.startsWith("image/")) {
          const r = new FileReader();
          r.onload = (ev) => previewEl.src = ev.target.result;
          r.readAsDataURL(f);
        }
      });

      if (urlToggle && urlRow) {
        urlToggle.addEventListener("click", (e) => {
          e.preventDefault();
          urlRow.classList.toggle("show");
        });
      }
    });
  }

  document.addEventListener("DOMContentLoaded", initUploaders);
  if (document.readyState === "interactive" || document.readyState === "complete") initUploaders();
})();
