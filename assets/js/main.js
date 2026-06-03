/* =====================================================================
   ADIC – main interactivity
   i18n toggle • slideshow • filters/search • modal • reveal • form
   ===================================================================== */
(function () {
  "use strict";
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const LS_KEY = "adic_lang";
  const I18N = window.ADIC_I18N;
  const PRODUCTS = window.ADIC_PRODUCTS || [];
  const BRANDS = window.ADIC_BRANDS || {};
  const TAGS = window.ADIC_TAGS || {};
  const C = window.ADIC_CONTACT;

  // language precedence: ?lang= in URL  →  saved choice  →  Arabic default
  const urlLang = new URLSearchParams(location.search).get("lang");
  let lang = (urlLang && I18N[urlLang]) ? urlLang
           : (localStorage.getItem(LS_KEY) || "ar");
  if (!I18N[lang]) lang = "ar";
  localStorage.setItem(LS_KEY, lang);
  const t = (k) => (I18N[lang] && I18N[lang][k] != null ? I18N[lang][k] : k);

  /* keep the chosen language when navigating between local pages */
  function langHref(href) {
    if (!href || href.indexOf(".html") === -1) return href;
    if (/^https?:|^mailto:|^tel:/.test(href)) return href;
    let hash = "", base = href;
    const hi = base.indexOf("#");
    if (hi !== -1) { hash = base.slice(hi); base = base.slice(0, hi); }
    let [path, query] = base.split("?");
    const params = new URLSearchParams(query || "");
    params.set("lang", lang);
    return path + "?" + params.toString() + hash;
  }
  function updateInternalLinks() {
    $$("a[href]").forEach((a) => {
      const h = a.getAttribute("href");
      if (h && h.indexOf(".html") !== -1) a.setAttribute("href", langHref(h));
    });
  }

  /* ---------------------- i18n application ----------------------- */
  function applyI18n() {
    const dict = I18N[lang];
    document.documentElement.lang = dict.html_lang;
    document.documentElement.dir = dict.dir;

    $$("[data-i18n]").forEach((el) => {
      const key = el.getAttribute("data-i18n");
      const val = dict[key];
      if (val == null) return;
      const attr = el.getAttribute("data-i18n-attr");
      if (attr) el.setAttribute(attr, val);
      else el.textContent = val;
    });

    // language toggle label shows the OTHER language
    const lb = $("#langBtn span");
    if (lb) lb.textContent = dict.lang_toggle;
  }

  /* ------------------------ contact links ------------------------ */
  function wireContactLinks() {
    const waMsg = encodeURIComponent(
      lang === "ar"
        ? "مرحباً ADIC، أرغب بالاستفسار عن منتجاتكم."
        : "Hello ADIC, I would like to inquire about your products."
    );
    const map = {
      tel: "tel:" + C.phone,
      wa: `https://wa.me/${C.whatsappRaw}?text=${waMsg}`,
      email: "mailto:" + C.email,
      web: C.websiteUrl,
    };
    $$("[data-contact]").forEach((a) => {
      const k = a.getAttribute("data-contact");
      if (map[k]) a.setAttribute("href", map[k]);
      if (k === "wa" || k === "web") {
        a.setAttribute("target", "_blank");
        a.setAttribute("rel", "noopener");
      }
    });
    const wf = $("#waFloat");
    if (wf) wf.setAttribute("href", map.wa);
  }

  /* ----------------------- product helpers ----------------------- */
  const brandName = (key) => (BRANDS[key] ? BRANDS[key][lang] : key);
  const catBadge = (p) => (p.category === "maxi" ? t("badge_maxi") : t("badge_facial"));
  const packBadge = (p) => t("badge_" + p.pack);

  function productCardHTML(p, i) {
    const specs = p.specs[lang].map((s) => `<li>${s}</li>`).join("");
    const tags = (p.tags || [])
      .map((tg) => {
        const lbl = TAGS[tg] ? TAGS[tg][lang] : tg;
        const cls = tg === "premium" ? "tag tag--gold" : tg === "value" ? "tag tag--blue" : "tag";
        return `<span class="${cls}">${lbl}</span>`;
      })
      .join("");
    return `
    <article class="pcard" style="animation-delay:${Math.min(i, 8) * 60}ms" data-pid="${p.id}" tabindex="0">
      <div class="pcard__media">
        <span class="pcard__cat ${p.category === "maxi" ? "maxi" : ""}">${catBadge(p)}</span>
        <span class="pcard__pack">${packBadge(p)}</span>
        <img src="assets/img/products/${p.img}.png" alt="${p.name[lang]}" loading="lazy">
      </div>
      <div class="pcard__body">
        <div class="pcard__brand"><span class="bdot"></span>${brandName(p.brand)}</div>
        <h3 class="pcard__title">${p.name[lang]}</h3>
        <ul class="pcard__specs">${specs}</ul>
        <div class="pcard__tags">${tags}</div>
        <div class="pcard__foot">
          <span class="pcard__btn">${t("card_inquire")}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </span>
        </div>
      </div>
    </article>`;
  }

  /* ---------------------- preview grid (home) -------------------- */
  function renderPreview() {
    const grid = $("#previewGrid");
    if (!grid) return;
    const picks = ["p02", "p25", "p08", "p11", "p17", "p03", "p23", "p26"];
    const list = picks.map((id) => PRODUCTS.find((p) => p.id === id)).filter(Boolean);
    grid.innerHTML = list.map((p, i) => productCardHTML(p, i)).join("");
  }

  /* ------------------- products page (filters) ------------------- */
  const state = { category: "all", brand: "all", search: "" };

  function renderProducts() {
    const grid = $("#productsGrid");
    if (!grid) return;
    let list = PRODUCTS.filter((p) => {
      if (state.category !== "all" && p.category !== state.category) return false;
      if (state.brand !== "all" && p.brand !== state.brand) return false;
      if (state.search) {
        const hay = (
          p.name.ar + " " + p.name.en + " " +
          p.specs.ar.join(" ") + " " + p.specs.en.join(" ") + " " +
          brandName(p.brand) + " " + BRANDS[p.brand].ar + " " + BRANDS[p.brand].en
        ).toLowerCase();
        if (!hay.includes(state.search.toLowerCase())) return false;
      }
      return true;
    });
    const cnt = $("#prodCount");
    if (cnt) cnt.textContent = list.length;
    grid.innerHTML = list.length
      ? list.map((p, i) => productCardHTML(p, i)).join("")
      : `<p class="no-results">${t("pp_none")}</p>`;
  }

  function buildBrandSelect() {
    const sel = $("#brandSelect");
    if (!sel) return;
    const opts =
      `<option value="all">${t("filter_brand_all")}</option>` +
      Object.keys(BRANDS).map((k) => `<option value="${k}">${BRANDS[k][lang]}</option>`).join("");
    sel.innerHTML = opts;
    sel.value = state.brand;
  }

  function initProductsPage() {
    const grid = $("#productsGrid");
    if (!grid) return;
    // hash → category
    const h = (location.hash || "").replace("#", "");
    if (h === "facial" || h === "maxi") state.category = h;

    const tabs = $("#filterTabs");
    if (tabs) {
      $$("button", tabs).forEach((b) => {
        b.classList.toggle("active", b.dataset.filter === state.category);
        b.addEventListener("click", () => {
          state.category = b.dataset.filter;
          $$("button", tabs).forEach((x) => x.classList.remove("active"));
          b.classList.add("active");
          renderProducts();
        });
      });
    }
    buildBrandSelect();
    const sel = $("#brandSelect");
    if (sel) sel.addEventListener("change", () => { state.brand = sel.value; renderProducts(); });

    const search = $("#searchInput");
    if (search) {
      let tmo;
      search.addEventListener("input", () => {
        clearTimeout(tmo);
        tmo = setTimeout(() => { state.search = search.value.trim(); renderProducts(); }, 160);
      });
    }
    renderProducts();
  }

  /* --------------------------- brands ---------------------------- */
  function renderBrands() {
    const track = $("#brandsTrack");
    if (track) {
      const set = Object.keys(BRANDS)
        .map((k) => {
          const b = BRANDS[k];
          const initial = b.en.charAt(0);
          return `<div class="brand-chip"><span class="bc-dot">${initial}</span>
                  <div><b>${b[lang]}</b><br><span>${lang === "ar" ? b.en : b.ar}</span></div></div>`;
        })
        .join("");
      track.innerHTML = set + set; // duplicate for seamless marquee
    }
    const fb = $("#footBrands");
    if (fb) {
      fb.innerHTML = Object.keys(BRANDS)
        .map((k) => `<li><a href="products.html">${BRANDS[k][lang]}</a></li>`)
        .join("");
    }
  }

  /* -------------------- interest select (contact) ---------------- */
  function buildInterestSelect() {
    const sel = $("#cInterest");
    if (!sel) return;
    const blank = lang === "ar" ? "— اختر منتجاً —" : "— Select a product —";
    sel.innerHTML =
      `<option value="">${blank}</option>` +
      PRODUCTS.map((p) => {
        const label = `${p.name[lang]} — ${p.specs[lang][0]}`;
        return `<option value="${label.replace(/"/g, "&quot;")}">${label}</option>`;
      }).join("");
  }

  /* ----------------------------- modal --------------------------- */
  let lastFocus = null;
  function openModal(p) {
    const modal = $("#productModal");
    if (!modal) return;
    lastFocus = document.activeElement;
    $("#mImg").src = `assets/img/products/${p.img}.png`;
    $("#mImg").alt = p.name[lang];
    $("#mBrand").textContent = brandName(p.brand);
    $("#mTitle").textContent = p.name[lang];
    $("#mSpecs").innerHTML = p.specs[lang].map((s) => `<li>${s}</li>`).join("");
    $("#mNote").textContent = t("modal_note");

    const detail = p.name[lang] + " (" + p.specs[lang].join(" • ") + ")";
    const waMsg = encodeURIComponent(
      (lang === "ar"
        ? "مرحباً ADIC، أرغب بالاستفسار عن المنتج: "
        : "Hello ADIC, I would like to inquire about: ") + detail
    );
    $("#mWa").href = `https://wa.me/${C.whatsappRaw}?text=${waMsg}`;
    const subject = encodeURIComponent(
      (lang === "ar" ? "استفسار عن منتج: " : "Product inquiry: ") + p.name[lang]
    );
    const body = encodeURIComponent(
      (lang === "ar" ? "أرغب بالاستفسار عن المنتج التالي:\n" : "I would like to inquire about:\n") + detail
    );
    $("#mEmail").href = `mailto:${C.email}?subject=${subject}&body=${body}`;

    modal.classList.add("open");
    document.body.style.overflow = "hidden";
    $(".modal__close", modal).focus();
  }
  function closeModal() {
    const modal = $("#productModal");
    if (!modal) return;
    modal.classList.remove("open");
    document.body.style.overflow = "";
    if (lastFocus) lastFocus.focus();
  }
  function wireModal() {
    const modal = $("#productModal");
    if (!modal) return;
    $$("[data-close]", modal).forEach((el) => el.addEventListener("click", closeModal));
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") closeModal(); });
    // delegate clicks on product cards
    document.addEventListener("click", (e) => {
      const card = e.target.closest(".pcard");
      if (!card) return;
      const p = PRODUCTS.find((x) => x.id === card.dataset.pid);
      if (p) openModal(p);
    });
    document.addEventListener("keydown", (e) => {
      if (e.key !== "Enter") return;
      const card = document.activeElement.closest && document.activeElement.classList.contains("pcard")
        ? document.activeElement : null;
      if (card) { const p = PRODUCTS.find((x) => x.id === card.dataset.pid); if (p) openModal(p); }
    });
  }

  /* --------------------------- slideshow ------------------------- */
  function initSlideshow() {
    const wrap = $("#heroSlides");
    if (!wrap) return;
    const slides = $$(".slide", wrap);
    const dotsWrap = $("#slideDots");
    let idx = 0, timer;
    slides.forEach((_, i) => {
      const b = document.createElement("button");
      b.className = i === 0 ? "active" : "";
      b.addEventListener("click", () => { go(i); reset(); });
      dotsWrap.appendChild(b);
    });
    const dots = $$("button", dotsWrap);
    function go(n) {
      slides[idx].classList.remove("active");
      dots[idx].classList.remove("active");
      idx = (n + slides.length) % slides.length;
      slides[idx].classList.add("active");
      dots[idx].classList.add("active");
    }
    function next() { go(idx + 1); }
    function reset() { clearInterval(timer); timer = setInterval(next, 4200); }
    reset();
    const hero = $(".hero__visual");
    if (hero) {
      hero.addEventListener("mouseenter", () => clearInterval(timer));
      hero.addEventListener("mouseleave", reset);
    }
  }

  /* ------------------------ scroll reveal ------------------------ */
  let revealObserver;
  function initReveal() {
    if (!("IntersectionObserver" in window)) {
      $$("[data-reveal]").forEach((el) => el.classList.add("in"));
      return;
    }
    revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((en) => {
          if (en.isIntersecting) {
            en.target.classList.add("in");
            revealObserver.unobserve(en.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );
    $$("[data-reveal]").forEach((el) => revealObserver.observe(el));
  }

  /* --------------------------- counters -------------------------- */
  function initCounters() {
    const nums = $$("[data-count]");
    if (!nums.length) return;
    const animate = (el) => {
      const raw = (el.textContent || "").replace(/[^\d]/g, "");
      const target = parseInt(raw, 10);
      if (!target) return;
      const suffix = el.getAttribute("data-suffix") || "";
      const dur = 1500;
      const start = performance.now();
      const isYear = target > 1900;
      const from = isYear ? target - 40 : 0;
      function step(now) {
        const p = Math.min((now - start) / dur, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const val = Math.round(from + (target - from) * eased);
        el.textContent = val + suffix;
        if (p < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    };
    if (!("IntersectionObserver" in window)) { nums.forEach(animate); return; }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { animate(en.target); io.unobserve(en.target); }
      });
    }, { threshold: 0.6 });
    nums.forEach((n) => io.observe(n));
  }

  /* --------------------------- header ---------------------------- */
  function initHeader() {
    const header = $("#header");
    const onScroll = () => {
      if (header) header.classList.toggle("scrolled", window.scrollY > 30);
      const tt = $("#toTop");
      if (tt) tt.classList.toggle("show", window.scrollY > 500);
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();

    const burger = $("#burger");
    const links = $("#navLinks");
    const overlay = $("#navOverlay");
    function toggleNav(open) {
      burger.classList.toggle("open", open);
      links.classList.toggle("open", open);
      document.body.classList.toggle("menu-open", open);
      if (overlay) overlay.classList.toggle("show", open);
      burger.setAttribute("aria-expanded", open ? "true" : "false");
    }
    if (burger) burger.addEventListener("click", () => toggleNav(!links.classList.contains("open")));
    if (overlay) overlay.addEventListener("click", () => toggleNav(false));
    $$("#navLinks a").forEach((a) => a.addEventListener("click", () => toggleNav(false)));

    const tt = $("#toTop");
    if (tt) tt.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));
  }

  /* ------------------------- contact form ------------------------ */
  function initContactForm() {
    const form = $("#contactForm");
    if (!form) return;
    const status = $("#formStatus");
    const setInvalid = (field, on, msg) => {
      field.classList.toggle("invalid", on);
    };
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      let ok = true;
      const required = $$("[required]", form);
      required.forEach((inp) => {
        const field = inp.closest(".field");
        const empty = !inp.value.trim();
        let bad = empty;
        if (inp.type === "email" && inp.value.trim()) {
          bad = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(inp.value.trim());
        }
        field.classList.toggle("invalid", bad);
        if (bad) ok = false;
      });
      if (!ok) {
        const first = $(".field.invalid input, .field.invalid textarea", form);
        if (first) first.focus();
        return;
      }
      // build mailto
      const get = (n) => (form.elements[n] ? form.elements[n].value.trim() : "");
      const subjPrefix = lang === "ar" ? "رسالة من الموقع: " : "Website message: ";
      const subject = encodeURIComponent(subjPrefix + get("subject"));
      const L = lang === "ar"
        ? { name: "الاسم", email: "البريد", phone: "الجوال", company: "الشركة", interest: "المنتج", msg: "الرسالة" }
        : { name: "Name", email: "Email", phone: "Phone", company: "Company", interest: "Product", msg: "Message" };
      const lines = [
        `${L.name}: ${get("name")}`,
        `${L.email}: ${get("email")}`,
        get("phone") ? `${L.phone}: ${get("phone")}` : "",
        get("company") ? `${L.company}: ${get("company")}` : "",
        get("interest") ? `${L.interest}: ${get("interest")}` : "",
        "",
        `${L.msg}:`,
        get("message"),
      ].filter((x) => x !== null);
      const body = encodeURIComponent(lines.join("\n"));
      if (status) { status.classList.add("show"); }
      const btnText = $("#submitBtn span");
      const orig = btnText ? btnText.textContent : "";
      if (btnText) btnText.textContent = t("cp_sending");
      setTimeout(() => {
        window.location.href = `mailto:${C.email}?subject=${subject}&body=${body}`;
        if (btnText) btnText.textContent = orig;
      }, 500);
    });
    // clear invalid on input
    $$("input,textarea", form).forEach((inp) =>
      inp.addEventListener("input", () => inp.closest(".field").classList.remove("invalid"))
    );
  }

  /* --------------------- language switch flow -------------------- */
  function renderDynamic() {
    renderBrands();
    renderPreview();
    if ($("#productsGrid")) { buildBrandSelect(); renderProducts(); }
    buildInterestSelect();
    wireContactLinks();
    updateInternalLinks();
  }
  function setLang(next) {
    lang = next;
    localStorage.setItem(LS_KEY, lang);
    try {
      const u = new URL(location.href);
      u.searchParams.set("lang", lang);
      history.replaceState(null, "", u);
    } catch (e) {}
    applyI18n();
    renderDynamic();
  }

  /* ----------------------------- init ---------------------------- */
  document.addEventListener("DOMContentLoaded", () => {
    const y = $("#year");
    if (y) y.textContent = new Date().getFullYear();

    applyI18n();
    renderDynamic();

    const lb = $("#langBtn");
    if (lb) lb.addEventListener("click", () => setLang(lang === "ar" ? "en" : "ar"));

    initHeader();
    initSlideshow();
    initProductsPage();
    wireModal();
    initContactForm();
    initReveal();
    initCounters();

    window.addEventListener("hashchange", () => {
      const h = (location.hash || "").replace("#", "");
      if ((h === "facial" || h === "maxi") && $("#productsGrid")) {
        state.category = h;
        const tabs = $("#filterTabs");
        if (tabs) $$("button", tabs).forEach((b) => b.classList.toggle("active", b.dataset.filter === h));
        renderProducts();
      }
    });
  });
})();
