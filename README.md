# ADIC — Aqd Darin Industrial Co. | Company Website

A professional, bilingual (Arabic / English), animated and fully responsive
website for **ADIC – Aqd Darin Industrial Co.** (شركة عقد دارين الصناعية).

Built with plain **HTML + CSS + JavaScript** — no build step, no dependencies.

---

## 📁 Structure

```
site/
├── index.html          # Home page
├── products.html       # Products catalogue (filter / search / modal)
├── contact.html        # Contact page (form + info + map)
├── README.md
└── assets/
    ├── css/
    │   └── styles.css          # Full design system + animations + responsive
    ├── js/
    │   ├── data.js             # 30 products (bilingual) + brands + tags
    │   ├── i18n.js             # All UI text (AR / EN) + contact constants
    │   └── main.js             # Interactivity (language, slideshow, filters, modal, form…)
    └── img/
        ├── logo.png            # Navy logo (light backgrounds)
        ├── logo-white.png      # White logo (dark footer / header on hero)
        ├── vision.png / vision-white.png
        ├── saudi-made.png / saudi-made-white.png
        └── products/           # p01 … p30  (individual product photos)
```

## 🌐 Languages
- **Default: Arabic (RTL)** with **Tajawal** font.
- **English (LTR)** with **Montserrat** font.
- Toggle with the **EN / عربي** button in the header. The choice is saved in the
  browser (localStorage) and applied automatically on the next visit.

## 🎨 Brand colours (from the logo)
- Primary navy **#013068**
- Accent blue **#1864ab / #3f93dd**
- Premium gold **#d4a23a**
- Saudi green **#1f7a44**

## 🛒 Products
- 30 products across two categories: **Facial Tissue** and **Maxi Roll**.
- Prices are intentionally **not shown** — every product has an **Inquire** action
  that opens WhatsApp / Email pre‑filled with the product name & specs.
- Brands: عقد دارين (Aqd Darin), فاينكس (Finex), لوسيل (Lucille), هايبر (Hyper),
  هاي كلاس (High Class).

## ✉️ Contact
- Phone: +966 11 455 5582  ·  WhatsApp: +966 53 993 7299
- Email: info@adic.sa  ·  Website: www.adic.sa
- The contact form opens the visitor's email app addressed to `info@adic.sa`.
  > To receive submissions automatically instead, connect the form to a service
  > such as **Formspree**, **Web3Forms**, or **EmailJS** (see note in `main.js`).

## 🚀 How to run / publish
- **Locally:** just open `index.html` in a browser, or serve the folder:
  ```bash
  # optional local server
  npx serve .
  ```
- **Publish:** upload the whole `site/` folder to any static host
  (Netlify, Vercel, GitHub Pages, cPanel, etc.). No server code required.
- Make sure the device is **online** the first time so the Google Fonts and the
  contact map load (both are loaded from the web).

## ✨ Features
- Animated hero with product slideshow, aurora background & floating shapes
- Scroll‑reveal animations, animated counters, brand marquee
- Product grid with category tabs, brand filter, live search & detail modal
- Sticky shrinking header, mobile slide‑in menu, back‑to‑top, WhatsApp button
- Fully responsive (desktop / tablet / mobile) + reduced‑motion support
