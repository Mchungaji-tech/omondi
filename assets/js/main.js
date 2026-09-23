/**
 * Beacon Gospel Centre — Main Public Portal Javascript
 */

(function () {
  "use strict";

  const currencyRate = 130;
  function renderMoney(mode) {
    document.querySelectorAll('[data-money]').forEach((el) => {
      const amount = Number(el.dataset.money || 0);
      if (mode === 'USD') {
        el.textContent = '$' + (amount / currencyRate).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      } else if (el.dataset.moneyFormat === 'compact') {
        const suffix = amount >= 1000000 ? (amount / 1000000).toFixed(amount % 1000000 ? 1 : 0) + 'M' : amount >= 1000 ? (amount / 1000).toFixed(amount % 1000 ? 1 : 0) + 'K' : Math.round(amount).toLocaleString('en-US');
        el.textContent = 'KSh ' + suffix;
      } else {
        el.textContent = 'KSh ' + Math.round(amount).toLocaleString('en-US');
      }
    });
    document.querySelectorAll('[data-currency-toggle]').forEach((button) => {
      button.textContent = mode === 'USD' ? 'USD / KSh' : 'KSh / USD';
      button.dataset.currency = mode;
    });
  }
  const currencyMode = localStorage.getItem('currency_mode') || 'KES';
  renderMoney(currencyMode);
  document.querySelectorAll('[data-currency-toggle]').forEach((button) => button.addEventListener('click', () => {
    const next = (localStorage.getItem('currency_mode') || 'KES') === 'KES' ? 'USD' : 'KES';
    localStorage.setItem('currency_mode', next);
    renderMoney(next);
  }));

  const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const pad = (n) => String(n).padStart(2, "0");

  /* ============ 1. LIVE CLOCK (ELDORET / EAT) ============ */
  const clockEl = document.getElementById("clock");
  function tickClock() {
    if (!clockEl) return;
    try {
      const t = new Intl.DateTimeFormat("en-KE", {
        timeZone: "Africa/Nairobi",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
      }).format(new Date());
      clockEl.innerHTML = "ELD · <b>" + t + "</b> EAT";
    } catch (e) {
      clockEl.textContent = "Eldoret, Kenya";
    }
  }
  tickClock();
  setInterval(tickClock, 20000);

  /* ============ 2. SUNDAY COUNTDOWN ============ */
  function nextSunday9() {
    const now = new Date(),
      t = new Date(now);
    t.setDate(now.getDate() + ((7 - now.getDay()) % 7));
    t.setHours(9, 0, 0, 0);
    if (t <= now) t.setDate(t.getDate() + 7);
    return t;
  }
  const target = nextSunday9();
  function countdown() {
    const dEl = document.getElementById("cd-d");
    if (!dEl) return;
    const s = Math.max(Math.floor((target - Date.now()) / 1000), 0);
    dEl.textContent = pad(Math.floor(s / 86400));
    document.getElementById("cd-h").textContent = pad(
      Math.floor((s % 86400) / 3600)
    );
    document.getElementById("cd-m").textContent = pad(
      Math.floor((s % 3600) / 60)
    );
    document.getElementById("cd-s").textContent = pad(s % 60);
  }
  countdown();
  setInterval(countdown, 1000);

  /* ============ 3. MOBILE MENU ============ */
  const burger = document.getElementById("burger");
  const menuClose = document.getElementById("menuClose");
  if (burger) {
    burger.addEventListener("click", () =>
      document.body.classList.add("menu-open")
    );
  }
  if (menuClose) {
    menuClose.addEventListener("click", () =>
      document.body.classList.remove("menu-open")
    );
  }
  document.querySelectorAll("#menu a").forEach((a) =>
    a.addEventListener("click", () =>
      document.body.classList.remove("menu-open")
    )
  );

  const posterName = document.getElementById("posterName");
  if (posterName) {
    requestAnimationFrame(() => posterName.classList.add("in"));
  }

  /* ============ 4. INTERSECTION OBSERVER & COUNTERS ============ */
  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        en.target.classList.add("in");
        if (en.target.hasAttribute("data-count")) runCounter(en.target);
        if (en.target.classList.contains("fund")) fillFund(en.target);
        io.unobserve(en.target);
      });
    },
    { threshold: 0.15 }
  );

  document
    .querySelectorAll(".reveal, [data-count], .fund")
    .forEach((el) => io.observe(el));

  function runCounter(el) {
    const end = parseInt(el.dataset.count, 10);
    if (reduced || isNaN(end)) {
      el.textContent = (end || 0).toLocaleString();
      return;
    }
    const t0 = performance.now();
    (function step(t) {
      const p = Math.min((t - t0) / 1600, 1);
      el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))).toLocaleString();
      if (p < 1) requestAnimationFrame(step);
    })(t0);
  }

  function fillFund(el) {
    const pct = +el.dataset.pct || 0;
    const bar = el.querySelector(".bar i");
    if (bar) bar.style.width = pct + "%";
    const money = el.querySelector("[data-money]");
    if (money) countMoney(money);
  }

  function countMoney(el) {
    const val = parseFloat(el.dataset.val),
      pre = el.dataset.pre || "",
      suf = el.dataset.suf || "";
    const dec = String(el.dataset.val).includes(".") ? 1 : 0;
    if (reduced || isNaN(val)) {
      el.textContent = pre + (val || 0).toFixed(dec) + suf;
      return;
    }
    const t0 = performance.now();
    (function step(t) {
      const p = Math.min((t - t0) / 1600, 1);
      el.textContent = pre + (val * (1 - Math.pow(1 - p, 3))).toFixed(dec) + suf;
      if (p < 1) requestAnimationFrame(step);
    })(t0);
  }

  /* ============ 5. SERMON LIST: VIDEO EXPAND/COLLAPSE (TAP ▶) ============ */
  const filters = document.getElementById("filters");
  const sermonList = document.getElementById("sermonList");
  if (filters && sermonList) {
    filters.addEventListener("click", (e) => {
      const b = e.target.closest("button, a");
      if (!b) return;
      filters.querySelectorAll("button, a").forEach((x) => x.classList.remove("on"));
      b.classList.add("on");
      const f = b.dataset.f || (new URL(b.href || window.location.href, window.location.href)).searchParams.get("cat") || "all";
      sermonList.querySelectorAll(".srow").forEach((row, idx) => {
        const show = f === "all" || (row.dataset.cat || "") === f;
        row.classList.toggle("hide", !show);
        if (show) {
          row.style.animation = "none";
          void row.offsetWidth;
          row.style.animation = "fadeIn .4s ease both";
          row.style.animationDelay = idx * 30 + "ms";
        }
      });
    });
  }

  function collapseAllSermons(except) {
    sermonList && sermonList.querySelectorAll(".srow.expanded").forEach((r) => {
      if (r === except) return;
      r.classList.remove("expanded");
      const p = r.querySelector(".play");
      if (p) p.textContent = "▶";
      // Also reset iframe src to stop playback
      r.querySelectorAll("iframe").forEach((ifr) => {
        ifr.src = ifr.src;
      });
    });
  }

  if (sermonList) {
    sermonList.addEventListener("click", (e) => {
      const btn = e.target.closest(".play");
      if (!btn) return;
      const row = btn.closest(".srow");
      if (!row) return;
      const isOpen = row.classList.contains("expanded");
      collapseAllSermons(null);
      if (isOpen) {
        // Just collapsed all — nothing more to do
      } else {
        row.classList.add("expanded");
        btn.textContent = "❚❚";
        // Scroll to make video visible
        setTimeout(() => row.scrollIntoView({ behavior: "smooth", block: "nearest" }), 120);
      }
    });
  }

  /* ============ 6. LIVE STREAM: TAB SWITCHING + VIEWERS + CHAT ============ */
  const frame = document.getElementById("liveframe");
  const viewersEl = document.getElementById("viewers");
  let viewers = 1243;

  function bump(n) {
    if (!viewersEl) return;
    viewers = Math.max(900, Math.min(2200, viewers + n));
    viewersEl.textContent = viewers.toLocaleString();
  }

  // --- Stream tabs (YouTube ↔ Facebook) ---
  function initStreamTabs(root) {
    if (!root) return;
    const tabBtns = root.querySelectorAll(".stream-tab, .stream-tabs button");
    const iframes = root.querySelectorAll(".liveiframes [data-plat]");
    if (tabBtns.length === 0 || iframes.length === 0) return;
    tabBtns.forEach((btn) => {
      btn.addEventListener("click", () => {
        const plat = btn.dataset.tab || btn.dataset.plat || btn.textContent.trim().toLowerCase();
        if (plat.includes("you") || plat.includes("yt") || plat === "yt") activateTab("yt");
        else if (plat.includes("face") || plat.includes("fb") || plat === "fb") activateTab("fb");
      });
    });
    function activateTab(plat) {
      // Update buttons
      tabBtns.forEach((b) => {
        const isYt = (b.dataset.tab || b.dataset.plat || b.textContent.trim().toLowerCase());
        const matches = plat === "yt"
          ? (isYt.includes("you") || isYt.includes("yt") || isYt === "yt")
          : (isYt.includes("face") || isYt.includes("fb") || isYt === "fb");
        if (matches) {
          b.classList.add("on");
          b.style.background = "#fff";
          b.style.color = "#222";
          b.style.boxShadow = "0 2px 8px rgba(0,0,0,.2)";
          b.style.backdropFilter = "none";
        } else {
          b.classList.remove("on");
          b.style.background = "rgba(255,255,255,.2)";
          b.style.color = "#fff";
          b.style.boxShadow = "none";
          b.style.backdropFilter = "blur(6px)";
        }
      });
      // Swap iframes
      iframes.forEach((wrap) => {
        const show = wrap.dataset.plat === plat;
        wrap.style.display = show ? "" : "none";
        if (!show) {
          // Pause iframe by resetting src
          wrap.querySelectorAll("iframe").forEach((ifr) => { ifr.src = ifr.src; });
        }
      });
    }
  }
  initStreamTabs(document);

  setInterval(() => bump(Math.floor(Math.random() * 34) - 14), 3000);

  // Live Chat Integration with API
  const chatlog = document.getElementById("chatlog");
  const chatform = document.getElementById("chatform");
  const chatinput = document.getElementById("chatinput");

  function addMsg(name, place, text, me) {
    if (!chatlog) return;
    const d = document.createElement("div");
    d.className = "msg" + (me ? " me" : "");
    d.innerHTML =
      "<b>" +
      escapeHtml(name) +
      (place ? " · " + escapeHtml(place) : "") +
      "</b><br>" +
      escapeHtml(text);
    chatlog.appendChild(d);
    while (chatlog.children.length > 40) chatlog.removeChild(chatlog.firstChild);
    chatlog.scrollTop = chatlog.scrollHeight;
  }

  function escapeHtml(s) {
    return String(s ?? "").replace(/[&<>"']/g, (c) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[c]));
  }

  // Load chat messages via procedural API endpoint
  function fetchChat() {
    fetch("api/chat.php")
      .then((res) => res.json())
      .then((data) => {
        if (data && data.messages && chatlog && chatlog.children.length === 0) {
          data.messages.forEach((m) => {
            addMsg(m.sender_name, m.location, m.message, false);
          });
        }
      })
      .catch((_) => {});
  }
  fetchChat();

  if (chatform) {
    chatform.addEventListener("submit", (e) => {
      e.preventDefault();
      const v = chatinput ? chatinput.value.trim() : "";
      if (!v) return;

      addMsg("You", "Online", v, true);
      if (chatinput) chatinput.value = "";

      // Post to API
      const fd = new FormData();
      fd.append("sender_name", "You");
      fd.append("location", "Online");
      fd.append("message", v);

      fetch("api/chat.php", {
        method: "POST",
        body: fd,
      }).catch((_) => {});
    });
  }

  // Stream schedules countdown
  function nextOccur(day, hour) {
    const now = new Date();
    for (let a = 0; a < 8; a++) {
      const t = new Date(now);
      t.setDate(now.getDate() + a);
      t.setHours(hour, 0, 0, 0);
      if (t.getDay() === day && t > now) return t;
    }
  }
  const streams = {
    sun: nextOccur(0, 9),
    wed: nextOccur(3, 19),
    fri: nextOccur(5, 19),
  };

  function updSched() {
    const now = Date.now();
    for (const k in streams) {
      const el = document.querySelector('[data-cd="' + k + '"]');
      if (!el || !streams[k]) continue;
      const ms = streams[k] - now;
      if (ms <= 0) {
        el.textContent = "LIVE NOW";
        continue;
      }
      const s = Math.floor(ms / 1000);
      el.textContent =
        "in " +
        Math.floor(s / 86400) +
        "d " +
        pad(Math.floor((s % 86400) / 3600)) +
        "h " +
        pad(Math.floor((s % 3600) / 60)) +
        "m";
    }
  }
  updSched();
  setInterval(updSched, 30000);

  document.querySelectorAll(".remind").forEach((b) => {
    b.addEventListener("click", () => {
      const set = b.classList.toggle("set");
      b.textContent = set ? "✓ Reminder set" : "Remind me";
    });
  });

  /* ============ 7. COPY BUTTONS ============ */
  document.querySelectorAll(".copy").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const txt = btn.dataset.copy;
      try {
        await navigator.clipboard.writeText(txt);
      } catch (e) {
        const ta = document.createElement("textarea");
        ta.value = txt;
        document.body.appendChild(ta);
        ta.select();
        try {
          document.execCommand("copy");
        } catch (_) {}
        ta.remove();
      }
      btn.textContent = "Copied ✓";
      btn.classList.add("done");
      setTimeout(() => {
        btn.textContent = "Copy";
        btn.classList.remove("done");
      }, 1800);
    });
  });

  /* ============ 8. FLOCK TESTIMONIES STACK ============ */
  const tcards = Array.from(document.querySelectorAll("#tstack .tcard"));
  if (tcards.length > 0) {
    const pos = ["p0", "p1", "p2", "pfar"];
    let order = tcards.map((_, i) => i),
      tAuto = null;
    const applyStack = () =>
      order.forEach((c, s) => {
        tcards[c].className = "tcard " + (pos[s] || "pfar");
      });
    const tNext = () => {
      order.push(order.shift());
      applyStack();
    };
    const tPrev = () => {
      order.unshift(order.pop());
      applyStack();
    };

    const tNextBtn = document.getElementById("tNext");
    const tPrevBtn = document.getElementById("tPrev");
    if (tNextBtn)
      tNextBtn.addEventListener("click", () => {
        tNext();
        resetAuto();
      });
    if (tPrevBtn)
      tPrevBtn.addEventListener("click", () => {
        tPrev();
        resetAuto();
      });

    function resetAuto() {
      clearInterval(tAuto);
      if (!reduced) tAuto = setInterval(tNext, 6000);
    }
    applyStack();
    resetAuto();
  }

  /* ============ 9. EVENT REGISTRATION TRIGGER ============ */
  document.querySelectorAll(".book").forEach((b) => {
    b.addEventListener("click", () => {
      const sel = document.getElementById("ftype");
      if (b.dataset.event && sel) sel.value = b.dataset.event;
      const target = document.getElementById("invite");
      if (target) target.scrollIntoView({ behavior: reduced ? "auto" : "smooth" });
    });
  });

  /* ============ 10. PRAYER REQUEST AJAX SUBMISSION ============ */
  const prForm = document.getElementById("prayerForm");
  const prAnon = document.getElementById("prAnon");
  if (prAnon) {
    prAnon.addEventListener("change", (e) => {
      const nameField = document.getElementById("prName");
      if (nameField) {
        nameField.disabled = e.target.checked;
        nameField.value = "";
        nameField.closest(".field").style.opacity = e.target.checked ? ".45" : "1";
      }
    });
  }

  if (prForm) {
    prForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const txt = document.getElementById("prText");
      const bad = !txt.value.trim();
      txt.closest(".field").classList.toggle("err", bad);
      if (bad) return;

      const btn = prForm.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.textContent = "Submitting with prayer...";
      }

      const fd = new FormData();
      fd.append("sender_name", document.getElementById("prName")?.value || "");
      fd.append("contact", document.getElementById("prContact")?.value || "");
      fd.append("category", document.getElementById("prCat")?.value || "general");
      fd.append("request_text", txt.value.trim());
      fd.append("is_anonymous", prAnon && prAnon.checked ? "1" : "0");

      fetch("api/prayer.php", {
        method: "POST",
        body: fd,
      })
        .then((r) => r.json())
        .then((res) => {
          if (res.success) {
            document.getElementById("prRef").textContent = res.ref_no;
            prForm.style.display = "none";
            document.getElementById("prSuccess").classList.add("show");
          } else {
            alert(res.error || "Could not submit prayer request. Please try again.");
            if (btn) {
              btn.disabled = false;
              btn.textContent = "Send My Prayer Request ✦";
            }
          }
        })
        .catch((_) => {
          // Fallback reference code display
          document.getElementById("prRef").textContent =
            "PRAY-2026-" + Math.floor(1000 + Math.random() * 9000);
          prForm.style.display = "none";
          document.getElementById("prSuccess").classList.add("show");
        });
    });

    prForm.addEventListener("input", (e) => {
      const f = e.target.closest(".field");
      if (f) f.classList.remove("err");
    });
  }

  /* ============ 11. INVITATION AJAX SUBMISSION ============ */
  const inviteForm = document.getElementById("inviteForm");
  if (inviteForm) {
    inviteForm.addEventListener("submit", (e) => {
      e.preventDefault();
      let ok = true;
      ["fname", "fchurch", "fphone", "ftype"].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        const bad = !el.value.trim();
        el.closest(".field").classList.toggle("err", bad);
        if (bad) ok = false;
      });

      const phone = document.getElementById("fphone")?.value.trim() || "";
      if (phone.includes("@") && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(phone)) {
        document.getElementById("fphone").closest(".field").classList.add("err");
        ok = false;
      }
      if (!ok) return;

      const btn = inviteForm.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.textContent = "Sending invitation...";
      }

      const fd = new FormData();
      fd.append("contact_name", document.getElementById("fname")?.value || "");
      fd.append("church_org", document.getElementById("fchurch")?.value || "");
      fd.append("phone_email", phone);
      fd.append("town_county", document.getElementById("fcity")?.value || "");
      fd.append("preferred_date", document.getElementById("fdate")?.value || "");
      fd.append("service_type", document.getElementById("ftype")?.value || "");
      fd.append("message", document.getElementById("fmsg")?.value || "");

      fetch("api/invite.php", {
        method: "POST",
        body: fd,
      })
        .then((r) => r.json())
        .then((res) => {
          if (res.success) {
            document.getElementById("refNo").textContent = res.ref_no;
            inviteForm.style.display = "none";
            document.getElementById("successBox").classList.add("show");
          } else {
            alert(res.error || "Could not send invitation. Please try again.");
            if (btn) {
              btn.disabled = false;
              btn.textContent = "Send Invitation ✦";
            }
          }
        })
        .catch((_) => {
          document.getElementById("refNo").textContent =
            "INV-2026-" + Math.floor(1000 + Math.random() * 9000);
          inviteForm.style.display = "none";
          document.getElementById("successBox").classList.add("show");
        });
    });

    inviteForm.addEventListener("input", (e) => {
      const f = e.target.closest(".field");
      if (f) f.classList.remove("err");
    });
  }

  /* ==================================================================
     FEATURED VIDEO RAIL — HOMEPAGE HORIZONTAL SCROLLER
     ================================================================== */
  (function initVrail() {
    const rail = document.querySelector('.vrail');
    if (!rail) return;
    const scroll = rail.querySelector('.vrail-scroll');
    const bar = rail.querySelector('.vrail-progress i');
    const prev = rail.querySelector('[data-vrail="prev"]');
    const next = rail.querySelector('[data-vrail="next"]');
    if (!scroll) return;

    // update progress bar
    function upd() {
      if (!bar) return;
      const max = scroll.scrollWidth - scroll.clientWidth;
      const p = max > 0 ? (scroll.scrollLeft / max) * 100 : 0;
      bar.style.width = p + '%';
    }
    scroll.addEventListener('scroll', upd, { passive: true });
    window.addEventListener('resize', upd);
    setTimeout(upd, 30);

    // prev/next buttons
    if (prev) prev.addEventListener('click', () => {
      scroll.scrollBy({ left: -Math.round(scroll.clientWidth * .8), behavior: 'smooth' });
    });
    if (next) next.addEventListener('click', () => {
      scroll.scrollBy({ left: Math.round(scroll.clientWidth * .8), behavior: 'smooth' });
    });

    // auto slow scroll when idle (cinematic)
    let idleTimer = null;
    let dir = 1;
    function idleScroll() {
      const max = scroll.scrollWidth - scroll.clientWidth;
      if (max <= 0) return;
      if (scroll.scrollLeft + 5 >= max) dir = -1;
      if (scroll.scrollLeft <= 0) dir = 1;
      scroll.scrollBy({ left: dir * 2, behavior: 'auto' });
    }
    function kickIdle() {
      clearInterval(idleTimer);
      idleTimer = setInterval(idleScroll, 70);
    }
    kickIdle();
    rail.addEventListener('mouseenter', () => clearInterval(idleTimer));
    rail.addEventListener('mouseleave', kickIdle);
    rail.addEventListener('touchstart', () => clearInterval(idleTimer), { passive: true });

    // drag to scroll
    let isDown = false, startX = 0, startScroll = 0;
    scroll.addEventListener('pointerdown', (e) => {
      isDown = true;
      scroll.classList.add('drag');
      startX = e.pageX;
      startScroll = scroll.scrollLeft;
      scroll.setPointerCapture(e.pointerId);
    });
    scroll.addEventListener('pointermove', (e) => {
      if (!isDown) return;
      e.preventDefault();
      scroll.scrollLeft = startScroll - (e.pageX - startX);
    });
    const endDrag = (e) => {
      isDown = false;
      scroll.classList.remove('drag');
      try { scroll.releasePointerCapture(e.pointerId); } catch(_) {}
    };
    scroll.addEventListener('pointerup', endDrag);
    scroll.addEventListener('pointercancel', endDrag);
    scroll.addEventListener('pointerleave', endDrag);

    // click → navigate to sermons library with sermon selected (via URL hash id)
    rail.querySelectorAll('.vcard').forEach((card) => {
      card.addEventListener('click', (e) => {
        // Skip if started drag
        if (Math.abs(scroll.scrollLeft - startScroll) > 6) return;
        const id = card.dataset.sid;
        if (!id) return;
        const go = new URL(BASE_URL + 'sermons.php', window.location.href);
        go.searchParams.set('watch', id);
        window.location.href = go.toString();
      });
      // Keyboard
      card.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
      });
    });
  })();

  /* ==================================================================
     SERMON LIBRARY — YOUTUBE-STYLE PLAYER SWAP + QUEUE
     ================================================================== */
  (function initSermonYt() {
    const grid = document.querySelector('.sermon-grid');
    if (!grid) return;

    const playerWrap = grid.querySelector('.big-vid-wrap');
    const playerEmpty = grid.querySelector('.big-vid-empty');
    const qcards = grid.querySelectorAll('.qcard');
    const nextBtn = grid.querySelector('.qnext-btn');

    // Read from URL ?watch=ID
    const params = new URLSearchParams(window.location.search);
    const want = params.get('watch');
    let currentId = null;

    function clearPlayer() {
      // remove existing iframes (stop playback)
      playerWrap.querySelectorAll('iframe').forEach((f) => f.remove());
    }

    function activate(qcard) {
      if (!qcard) return;
      const id = qcard.dataset.sid;
      if (!id) return;
      qcards.forEach((q) => q.classList.remove('active'));
      qcard.classList.add('active');
      currentId = id;

      // Push URL state without reload
      const u = new URL(window.location.href);
      u.searchParams.set('watch', id);
      window.history.replaceState({}, '', u.href.toString());

      // Move the data to player metadata
      const ser = qcard.querySelector('.qser')?.textContent || '';
      const t = qcard.querySelector('.qt')?.textContent || '';
      const ref = qcard.querySelector('.qref')?.textContent || '';
      const date = qcard.querySelector('.qm .date')?.textContent || '';
      const dur = qcard.querySelector('.qthumb .qd')?.textContent || '';
      const src = qcard.dataset.vurl || '';

      grid.querySelector('.vseries-top') && (grid.querySelector('.vseries-top').textContent = ser);
      const vtitle = grid.querySelector('h2.vtitle');
      if (vtitle) vtitle.textContent = t;
      const vsr = grid.querySelector('.vscripture');
      if (vsr) vsr.textContent = ref;
      const srow = grid.querySelector('.vstat-row');
      if (srow) {
        const statDate = srow.querySelector('.sd');
        const statDur = srow.querySelector('.sdur');
        if (statDate) statDate.innerHTML = '📅 <b>' + date + '</b>';
        if (statDur) statDur.innerHTML = '⏱ <b>' + dur + '</b>';
      }

      // Render new iframe or empty state
      clearPlayer();
      if (src) {
        // Use video_embed_src-like helper inline
        let embed = '';
        if (/youtube|youtu\.be|youtube\.com\/shorts/i.test(src)) {
          let m = src.match(/(?:v=|youtu\.be\/|embed\/|shorts\/)([A-Za-z0-9_-]{6,})/i);
          if (m) embed = 'https://www.youtube.com/embed/' + m[1] + '?autoplay=1&mute=0&rel=0&modestbranding=1&playsinline=1&origin=' + encodeURIComponent(location.hostname);
          else embed = src;
        } else if (/facebook|fb\.watch|fb\./i.test(src)) {
          embed = 'https://www.facebook.com/plugins/video.php?href=' + encodeURIComponent(src) + '&show_text=0&width=1200&autoplay=true';
        } else if (/vimeo/i.test(src)) {
          let m = src.match(/vimeo\.com\/(?:video\/)?(\d{5,})/i);
          embed = m ? 'https://player.vimeo.com/video/' + m[1] + '?autoplay=1&title=0&byline=0' : src;
        } else {
          embed = src;
        }
        const wrap = document.createElement('div');
        wrap.className = 'videowrap';
        wrap.style.cssText = 'aspect-ratio:auto;width:100%;height:100%;border:0;border-radius:0;';
        wrap.innerHTML = '<iframe src="' + encodeURIComponent ? embed : embed + '"'
          + ' title="Video player"'
          + ' style="width:100%;height:100%;border:0;display:block;"'
          + ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
          + ' allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>';
        // Actually write safely
        wrap.innerHTML = '';
        const ifr = document.createElement('iframe');
        ifr.src = embed;
        ifr.title = 'Video player';
        ifr.style.cssText = 'width:100%;height:100%;border:0;display:block;';
        ifr.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        ifr.allowFullscreen = true;
        ifr.referrerPolicy = 'strict-origin-when-cross-origin';
        wrap.appendChild(ifr);
        playerWrap.appendChild(wrap);
        if (playerEmpty) playerEmpty.style.display = 'none';
      } else {
        if (playerEmpty) playerEmpty.style.display = '';
      }

      // Scroll player into view (smooth, top visible)
      grid.scrollIntoView({ behavior: 'smooth', block: 'start' });

      // Reload comments for new sermon id
      loadComments(id);
    }

    qcards.forEach((q) => {
      q.addEventListener('click', () => activate(q));
      q.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); q.click(); }
      });
    });

    nextBtn && nextBtn.addEventListener('click', () => {
      const list = Array.from(qcards);
      const idx = list.findIndex((q) => q.classList.contains('active'));
      const nxt = list[(idx + 1) % list.length];
      if (nxt) activate(nxt);
    });

    // Activate initial ?watch= or first
    if (want) {
      const q = Array.from(qcards).find((x) => x.dataset.sid === want);
      if (q) { setTimeout(() => activate(q), 100); return; }
    }
    const first = qcards[0];
    if (first) setTimeout(() => activate(first), 200);
  })();

  /* ==================================================================
     SERMON COMMENTS — AJAX LOAD / POST / LIKE / REPLY
     ================================================================== */
  let currentSermonId = null;
  function setCurrentSermonId(id) { currentSermonId = id; }

  function cmToast(msg, kind) {
    let t = document.querySelector('.cm-toast');
    if (!t) {
      t = document.createElement('div');
      t.className = 'cm-toast';
      document.body.appendChild(t);
    }
    t.className = 'cm-toast show ' + (kind || '');
    t.textContent = msg;
    clearTimeout(cmToast._tm);
    cmToast._tm = setTimeout(() => t.classList.remove('show'), 3200);
  }

  function loadComments(sermonId) {
    setCurrentSermonId(sermonId);
    const root = document.querySelector('.section-comments');
    if (!root) return;
    const counter = root.querySelector('.cm-count');
    const list = root.querySelector('.cm-list');
    if (counter) counter.textContent = '…';
    if (list) list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--ink2);font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;">Loading conversation…</div>';
    const u = new URL(BASE_URL + 'api/sermon_comments.php', window.location.href);
    u.searchParams.set('action', 'list');
    u.searchParams.set('sermon_id', sermonId);
    fetch(u.toString())
      .then((r) => r.json())
      .then((data) => {
        if (!data.ok) throw new Error(data.error || 'Error');
        if (counter) counter.textContent = data.count.toLocaleString();
        if (list) list.innerHTML = data.html;
        bindCommentInteractions(list);
      })
      .catch((err) => {
        console.error(err);
        if (list) list.innerHTML = '<div style="text-align:center;padding:20px;color:#c0392b;">Could not load comments. Reload to try again.</div>';
      });
  }

  function bindCommentInteractions(scopeEl) {
    if (!scopeEl) scopeEl = document;
    // Reply buttons → reveal inline reply form
    scopeEl.querySelectorAll('.cmt-reply').forEach((btn) => {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', () => {
        const pid = btn.dataset.parent;
        scopeEl.querySelectorAll('.cmt-reply-form').forEach((f) => { if (f.dataset.parent !== pid) f.hidden = true; });
        const f = scopeEl.querySelector('.cmt-reply-form[data-parent="' + pid + '"]');
        if (f) {
          f.hidden = !f.hidden;
          if (!f.hidden) f.querySelector('textarea')?.focus();
        }
      });
    });
    // Cancel reply form
    scopeEl.querySelectorAll('.cmt-rf-cancel').forEach((btn) => {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', () => {
        const f = btn.closest('.cmt-reply-form');
        if (f) { f.hidden = true; f.reset(); }
      });
    });
    // Like buttons (count increment + visual)
    scopeEl.querySelectorAll('.cmt-like').forEach((btn) => {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', () => {
        if (btn.classList.contains('liked')) {
          // Visual pop only (no dedupe-like server-side)
          btn.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.35)' }, { transform: 'scale(1)' }], { duration: 300 });
          return;
        }
        const cid = btn.dataset.cid;
        const ct = btn.querySelector('.ct');
        fetch(BASE_URL + 'api/sermon_comments.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8' },
          body: 'action=like&comment_id=' + encodeURIComponent(cid),
        })
          .then((r) => r.json())
          .then((d) => {
            btn.classList.add('liked');
            if (d.ok && ct) ct.textContent = d.likes.toLocaleString();
            btn.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.4)' }, { transform: 'scale(1)' }], { duration: 420 });
          })
          .catch(() => {
            btn.classList.add('liked');
            const n = Number(ct.textContent || 0) + 1;
            if (ct) ct.textContent = n.toLocaleString();
          });
      });
    });
    // Reply form submit → POST comment
    scopeEl.querySelectorAll('.cmt-reply-form').forEach((f) => {
      if (f.dataset.bound) return;
      f.dataset.bound = '1';
      f.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!currentSermonId) return;
        const fd = new FormData(f);
        fd.set('action', 'add');
        fd.set('sermon_id', currentSermonId);
        const submit = f.querySelector('button[type=submit]');
        if (submit) submit.disabled = true;
        fetch(BASE_URL + 'api/sermon_comments.php', {
          method: 'POST',
          body: fd,
        })
          .then((r) => r.json())
          .then((d) => {
            f.reset();
            f.hidden = true;
            if (submit) submit.disabled = false;
            if (!d.ok) { cmToast(d.error || 'Could not post reply', 'err'); return; }
            const root = document.querySelector('.section-comments');
            const counter = root?.querySelector('.cm-count');
            const list = root?.querySelector('.cm-list');
            if (counter) counter.textContent = d.count.toLocaleString();
            if (list) list.innerHTML = d.html;
            bindCommentInteractions(list);
            cmToast(d.new_approved ? 'Reply posted ✔' : 'Reply awaiting moderation', d.new_approved ? 'ok' : 'warn');
          })
          .catch(() => { if (submit) submit.disabled = false; cmToast('Network error', 'err'); });
      });
    });
  }

  // Top-level "Add comment" form (outside list)
  (function initTopCommentForm() {
    const f = document.querySelector('.cm-add form');
    if (!f) return;
    f.addEventListener('submit', (e) => {
      e.preventDefault();
      if (!currentSermonId) { cmToast('Select a sermon first', 'warn'); return; }
      const fd = new FormData(f);
      fd.set('action', 'add');
      fd.set('sermon_id', currentSermonId);
      fd.set('parent_id', 0);
      const submit = f.querySelector('button[type=submit]');
      if (submit) { submit.disabled = true; submit.textContent = 'Posting…'; }
      fetch(BASE_URL + 'api/sermon_comments.php', {
        method: 'POST',
        body: fd,
      })
        .then((r) => r.json())
        .then((d) => {
          f.reset();
          if (submit) { submit.disabled = false; submit.textContent = 'Post Comment'; }
          if (!d.ok) { cmToast(d.error || 'Could not post', 'err'); return; }
          const root = document.querySelector('.section-comments');
          const counter = root?.querySelector('.cm-count');
          const list = root?.querySelector('.cm-list');
          if (counter) counter.textContent = d.count.toLocaleString();
          if (list) list.innerHTML = d.html;
          bindCommentInteractions(list);
          cmToast(d.new_approved ? 'Comment posted ✔ Share your take' : 'Comment awaiting moderation — thanks!', d.new_approved ? 'ok' : 'warn');
          list && list.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(() => {
          if (submit) { submit.disabled = false; submit.textContent = 'Post Comment'; }
          cmToast('Network error — try again', 'err');
        });
    });
  })();

  // Sort buttons (client-side order only, keeps things light)
  (function initCommentSort() {
    const btns = document.querySelectorAll('.cm-sort button');
    if (!btns.length) return;
    btns.forEach((b) => {
      b.addEventListener('click', () => {
        btns.forEach((x) => x.classList.remove('on'));
        b.classList.add('on');
        const list = document.querySelector('.cm-list');
        if (!list) return;
        const nodes = Array.from(list.children).filter((el) => el.classList && el.classList.contains('cmt'));
        if (!nodes.length) return;
        if (b.dataset.sort === 'oldest') nodes.reverse();
        if (b.dataset.sort === 'top') {
          nodes.sort((a, b) => {
            const la = Number(a.querySelector('.cmt-like .ct')?.textContent || 0);
            const lb = Number(b.querySelector('.cmt-like .ct')?.textContent || 0);
            return lb - la;
          });
        }
        nodes.forEach((n) => list.appendChild(n));
      });
    });
  })();

  /* ============ JOURNEY SLIDER & SCROLL SYNC ============ */
  (function initJourneySlider() {
    const slider = document.getElementById("journeySlider");
    if (!slider) return;

    const slides = Array.from(slider.querySelectorAll(".jslide"));
    const dots = Array.from(slider.querySelectorAll(".jdot"));
    const counter = document.getElementById("jcurrent");
    const prevBtn = document.getElementById("jprev");
    const nextBtn = document.getElementById("jnext");
    const timelineItems = Array.from(document.querySelectorAll("#journeyTimeline .titem"));

    if (!slides.length) return;

    let currentIndex = 0;
    let isUserManualInteraction = false;
    let manualTimeout = null;

    function goToSlide(index, scrollTimeline = false) {
      if (index < 0) index = slides.length - 1;
      if (index >= slides.length) index = 0;

      currentIndex = index;

      slides.forEach((s, idx) => {
        if (idx === index) {
          s.classList.add("active");
        } else {
          s.classList.remove("active");
        }
      });

      dots.forEach((d, idx) => {
        if (idx === index) {
          d.classList.add("active");
        } else {
          d.classList.remove("active");
        }
      });

      if (counter) {
        counter.textContent = String(index + 1);
      }

      timelineItems.forEach((item, idx) => {
        if (idx === index) {
          item.classList.add("active-stage");
        } else {
          item.classList.remove("active-stage");
        }
      });

      if (scrollTimeline && timelineItems[index]) {
        isUserManualInteraction = true;
        clearTimeout(manualTimeout);
        timelineItems[index].scrollIntoView({ behavior: "smooth", block: "center" });
        manualTimeout = setTimeout(() => {
          isUserManualInteraction = false;
        }, 800);
      }
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", () => {
        goToSlide(currentIndex - 1, true);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        goToSlide(currentIndex + 1, true);
      });
    }

    dots.forEach((dot) => {
      dot.addEventListener("click", () => {
        const target = Number(dot.dataset.target || 0);
        goToSlide(target, true);
      });
    });

    timelineItems.forEach((item, idx) => {
      item.style.cursor = "pointer";
      item.addEventListener("click", () => {
        goToSlide(idx, false);
      });
    });

    // Scroll spy: update photo slider as user scrolls past timeline stages
    if ("IntersectionObserver" in window && timelineItems.length > 0) {
      const observerOptions = {
        root: null,
        rootMargin: "-20% 0px -40% 0px",
        threshold: 0.15,
      };

      const stageObserver = new IntersectionObserver((entries) => {
        if (isUserManualInteraction) return;

        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const stageIdx = Number(entry.target.dataset.stageIndex ?? -1);
            if (stageIdx >= 0 && stageIdx !== currentIndex) {
              goToSlide(stageIdx, false);
            }
          }
        });
      }, observerOptions);

      timelineItems.forEach((item) => stageObserver.observe(item));
    }
  })();
})();
