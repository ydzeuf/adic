/* =====================================================================
   ADIC – Aqd Darin Industrial Co.
   Product catalogue data (bilingual AR / EN)
   Extracted from the official product sheets (products_1..4)
   ===================================================================== */

const ADIC_BRANDS = {
  aqddarin: { ar: "عقد دارين", en: "Aqd Darin" },
  finex:    { ar: "فاينكس",    en: "Finex" },
  lucille:  { ar: "لوسيل",     en: "Lucille" },
  hyper:    { ar: "هايبر",     en: "Hyper" },
  highclass:{ ar: "هاي كلاس",  en: "High Class" },
};

const ADIC_TAGS = {
  value:   { ar: "توفير هائل",      en: "Great Value" },
  economy: { ar: "اقتصادي",         en: "Economical" },
  absorb:  { ar: "شديد الامتصاص",   en: "Highly Absorbent" },
  premium: { ar: "جودة ممتازة",     en: "Premium Quality" },
  ply3:    { ar: "3 طبقات",          en: "3-Ply" },
};

/* category: "facial" (مناديل الوجه) | "maxi" (ماكس رول)
   pack:     "soft" (شنط/أكياس) | "box" (علب) | "roll"  – used for sub-filtering badges */
const ADIC_PRODUCTS = [
  /* ---------- Sheet 1 : Facial tissue (soft & boxed) ---------- */
  { id:"p01", img:"p01", category:"facial", pack:"soft", brand:"finex",
    name:{ar:"مناديل فاينكس",en:"Finex Facial Tissues"},
    specs:{ar:["300 منديل مفرد","3 طبقات","شد 10 حبة × 5 شنطة"],
            en:["300 single tissues","3-Ply","Pack 10 × 5 bundles"]},
    tags:["ply3","economy"] },

  { id:"p02", img:"p02", category:"facial", pack:"soft", brand:"finex",
    name:{ar:"مناديل فاينكس",en:"Finex Facial Tissues"},
    specs:{ar:["500 منديل مفرد","3 طبقات","شد 10 حبة × 4 شنطة"],
            en:["500 single tissues","3-Ply","Pack 10 × 4 bundles"]},
    tags:["ply3","value"] },

  { id:"p03", img:"p03", category:"facial", pack:"soft", brand:"lucille",
    name:{ar:"مناديل لوسيل",en:"Lucille Facial Tissues"},
    specs:{ar:["250 منديل مفرد","3 طبقات","شد 10 حبة × 5 شنطة"],
            en:["250 single tissues","3-Ply","Pack 10 × 5 bundles"]},
    tags:["ply3","economy"] },

  { id:"p04", img:"p04", category:"facial", pack:"soft", brand:"finex",
    name:{ar:"مناديل فاينكس",en:"Finex Facial Tissues"},
    specs:{ar:["600 منديل مفرد","شد 10 حبة × 4 شنطة"],
            en:["600 single tissues","Pack 10 × 4 bundles"]},
    tags:["value"] },

  { id:"p05", img:"p05", category:"facial", pack:"soft", brand:"lucille",
    name:{ar:"مناديل لوسيل",en:"Lucille Facial Tissues"},
    specs:{ar:["400 منديل","شد 10 × 4 ربطة"],
            en:["400 tissues","Pack 10 × 4 bundles"]},
    tags:["value"] },

  { id:"p06", img:"p06", category:"facial", pack:"soft", brand:"finex",
    name:{ar:"مناديل فاينكس",en:"Finex Facial Tissues"},
    specs:{ar:["200 منديل مزدوج","شد 10 حبة × 5 شنطة"],
            en:["200 two-ply tissues","Pack 10 × 5 bundles"]},
    tags:["economy"] },

  /* ---------- Sheet 2 : Maxi Roll ---------- */
  { id:"p07", img:"p07", category:"maxi", pack:"roll", brand:"hyper",
    name:{ar:"ماكس رول هايبر الأخضر",en:"Hyper Green Maxi Roll"},
    specs:{ar:["موديل 300","شد 6 × 1"],
            en:["Model 300","Pack 6 × 1"]},
    tags:["value","economy","absorb"] },

  { id:"p08", img:"p08", category:"maxi", pack:"roll", brand:"hyper",
    name:{ar:"ماكس رول هايبر",en:"Hyper Maxi Roll"},
    specs:{ar:["موديل 300","شد 6 × 1"],
            en:["Model 300","Pack 6 × 1"]},
    tags:["value","economy","absorb"] },

  { id:"p09", img:"p09", category:"maxi", pack:"roll", brand:"hyper",
    name:{ar:"ماكس رول هايبر",en:"Hyper Maxi Roll"},
    specs:{ar:["موديل 350","شد 6 × 1"],
            en:["Model 350","Pack 6 × 1"]},
    tags:["value","economy","absorb"] },

  { id:"p10", img:"p10", category:"maxi", pack:"roll", brand:"lucille",
    name:{ar:"ماكس رول لوسيل بلاس",en:"Lucille Plus Maxi Roll"},
    specs:{ar:["موديل 150","شد 6 × 1"],
            en:["Model 150","Pack 6 × 1"]},
    tags:["value","absorb"] },

  { id:"p11", img:"p11", category:"maxi", pack:"roll", brand:"lucille",
    name:{ar:"ماكس رول لوسيل بلاس",en:"Lucille Plus Maxi Roll"},
    specs:{ar:["موديل 300","شد 6 × 1"],
            en:["Model 300","Pack 6 × 1"]},
    tags:["value","absorb"] },

  { id:"p12", img:"p12", category:"maxi", pack:"roll", brand:"lucille",
    name:{ar:"ماكس رول لوسيل بلاس",en:"Lucille Plus Maxi Roll"},
    specs:{ar:["موديل 350","شد 6 × 1"],
            en:["Model 350","Pack 6 × 1"]},
    tags:["value","absorb"] },

  { id:"p13", img:"p13", category:"maxi", pack:"roll", brand:"aqddarin",
    name:{ar:"ماكس رول عقد دارين",en:"Aqd Darin Maxi Roll"},
    specs:{ar:["شد 6 × 1"],
            en:["Pack 6 × 1"]},
    tags:["economy","absorb"] },

  { id:"p14", img:"p14", category:"maxi", pack:"roll", brand:"aqddarin",
    name:{ar:"ماكس رول عقد دارين",en:"Aqd Darin Maxi Roll"},
    specs:{ar:["شنطة 2 × 3"],
            en:["Bag 2 × 3"]},
    tags:["economy","absorb"] },

  { id:"p15", img:"p15", category:"maxi", pack:"roll", brand:"lucille",
    name:{ar:"ماكس رول لوسيل",en:"Lucille Maxi Roll"},
    specs:{ar:["150 متر","شد 2 × 6"],
            en:["150 meters","Pack 2 × 6"]},
    tags:["value","absorb"] },

  /* ---------- Sheet 3 : Maxi Roll ---------- */
  { id:"p16", img:"p16", category:"maxi", pack:"roll", brand:"aqddarin",
    name:{ar:"ماكس رول عقد دارين",en:"Aqd Darin Maxi Roll"},
    specs:{ar:["300 متر","شد 6 × 1"],
            en:["300 meters","Pack 6 × 1"]},
    tags:["value","absorb"] },

  { id:"p17", img:"p17", category:"maxi", pack:"roll", brand:"finex",
    name:{ar:"ماكس رول فاينكس الأحمر",en:"Finex Red Maxi Roll"},
    specs:{ar:["180 متر مضغوط","شد 12"],
            en:["180 m compressed","Pack 12"]},
    tags:["value","absorb"] },

  { id:"p18", img:"p18", category:"maxi", pack:"roll", brand:"finex",
    name:{ar:"ماكس رول فاينكس الأزرق",en:"Finex Blue Maxi Roll"},
    specs:{ar:["180 متر مضغوط","شد 12 × 1"],
            en:["180 m compressed","Pack 12 × 1"]},
    tags:["value","absorb"] },

  { id:"p19", img:"p19", category:"maxi", pack:"roll", brand:"aqddarin",
    name:{ar:"ماكس رول عقد دارين الاقتصادي",en:"Aqd Darin Economy Maxi Roll"},
    specs:{ar:["العبوة الاقتصادية","شديد الامتصاص"],
            en:["Economy pack","Highly absorbent"]},
    tags:["economy","absorb"] },

  { id:"p20", img:"p20", category:"maxi", pack:"roll", brand:"aqddarin",
    name:{ar:"ماكس رول عقد دارين",en:"Aqd Darin Maxi Roll"},
    specs:{ar:["150 متر","شد 6 × 1"],
            en:["150 meters","Pack 6 × 1"]},
    tags:["economy","absorb"] },

  { id:"p21", img:"p21", category:"maxi", pack:"roll", brand:"aqddarin",
    name:{ar:"ماكس رول عقد دارين",en:"Aqd Darin Maxi Roll"},
    specs:{ar:["350 متر","شد 6 × 1"],
            en:["350 meters","Pack 6 × 1"]},
    tags:["value","absorb"] },

  { id:"p22", img:"p22", category:"maxi", pack:"roll", brand:"aqddarin",
    name:{ar:"ماكس رول عقد دارين",en:"Aqd Darin Maxi Roll"},
    specs:{ar:["180 متر","شد 6 × 1"],
            en:["180 meters","Pack 6 × 1"]},
    tags:["economy","absorb"] },

  { id:"p23", img:"p23", category:"maxi", pack:"roll", brand:"finex",
    name:{ar:"ماكس رول فاينكس جامبو",en:"Finex Jumbo Maxi Roll"},
    specs:{ar:["800 غرام","شد 6 × 1"],
            en:["800 grams","Pack 6 × 1"]},
    tags:["value","absorb"] },

  { id:"p24", img:"p24", category:"maxi", pack:"roll", brand:"hyper",
    name:{ar:"ماكس رول هايبر",en:"Hyper Maxi Roll"},
    specs:{ar:["150 متر","شد 6 × 1"],
            en:["150 meters","Pack 6 × 1"]},
    tags:["economy","absorb"] },

  /* ---------- Sheet 4 : Boxed facial tissue ---------- */
  { id:"p25", img:"p25", category:"facial", pack:"box", brand:"finex",
    name:{ar:"مناديل فاينكس",en:"Finex Facial Tissues"},
    specs:{ar:["90 منديل مزدوج","شد 6 × 6"],
            en:["90 two-ply tissues","Pack 6 × 6"]},
    tags:["premium"] },

  { id:"p26", img:"p26", category:"facial", pack:"box", brand:"aqddarin",
    name:{ar:"مناديل دارين كلاسيك",en:"Darin Classic Tissues"},
    specs:{ar:["100 منديل مزدوج","شد 6 × 6"],
            en:["100 two-ply tissues","Pack 6 × 6"]},
    tags:["premium"] },

  { id:"p27", img:"p27", category:"facial", pack:"box", brand:"highclass",
    name:{ar:"مناديل هاي كلاس",en:"High Class Tissues"},
    specs:{ar:["50 منديل مزدوج","شد 36 حبة"],
            en:["50 two-ply tissues","Pack of 36"]},
    tags:["premium"] },

  { id:"p28", img:"p28", category:"facial", pack:"box", brand:"aqddarin",
    name:{ar:"مناديل دارين",en:"Darin Tissues"},
    specs:{ar:["65 منديل مزدوج","شد 6 × 6"],
            en:["65 two-ply tissues","Pack 6 × 6"]},
    tags:["premium"] },

  { id:"p29", img:"p29", category:"facial", pack:"soft", brand:"aqddarin",
    name:{ar:"مناديل عقد دارين",en:"Aqd Darin Tissues"},
    specs:{ar:["200 منديل مزدوج","شد 10 حبة × 5 شنطة","العبوة الاقتصادية"],
            en:["200 two-ply tissues","Pack 10 × 5 bundles","Economy pack"]},
    tags:["economy"] },

  { id:"p30", img:"p30", category:"facial", pack:"soft", brand:"finex",
    name:{ar:"مناديل فاينكس",en:"Finex Facial Tissues"},
    specs:{ar:["200 منديل مزدوج","شد 10 حبة × 5 شنطة","العبوة الاقتصادية"],
            en:["200 two-ply tissues","Pack 10 × 5 bundles","Economy pack"]},
    tags:["economy"] },
];

/* expose */
window.ADIC_PRODUCTS = ADIC_PRODUCTS;
window.ADIC_BRANDS = ADIC_BRANDS;
window.ADIC_TAGS = ADIC_TAGS;
