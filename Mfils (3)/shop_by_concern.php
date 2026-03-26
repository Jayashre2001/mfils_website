<?php
// shop_by_concern.php
$pageTitle = 'Shop by Concern — Mfills';
require_once __DIR__ . '/includes/header.php';

// ── All concerns ───────────────────────────────────────────
$concerns = [
  ['slug'=>'immunity',  'label'=>'Immunity & Defence',   'icon'=>'🛡️','color'=>'#0F7B5C',
   'desc'=>"Strengthen your body's natural defences with proven immune-boosting nutrients.",
   'tags'=>['Vitamin C','Zinc','Elderberry','D3+K2','Echinacea'],
   'products'=>['Vitamin C 1000mg','Zinc Bisglycinate','Vitamin D3 + K2','Elderberry Extract','Multivitamin Daily'],
   'img'=>'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&q=70','count'=>12,'popular'=>'Vitamin C 1000mg'],

  ['slug'=>'energy',    'label'=>'Energy & Stamina',     'icon'=>'⚡','color'=>'#B88018',
   'desc'=>'Fuel your day naturally — from morning energy to sustained athletic performance.',
   'tags'=>['B-Complex','Iron','CoQ10','Ashwagandha','Maca'],
   'products'=>['Vitamin B Complex','CoQ10 200mg','Iron + Folic Acid','Ashwagandha KSM-66','Maca Root'],
   'img'=>'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&q=70','count'=>9,'popular'=>'Ashwagandha KSM-66'],

  ['slug'=>'muscle',    'label'=>'Muscle & Strength',    'icon'=>'💪','color'=>'#1C3D24',
   'desc'=>'Build lean muscle, enhance recovery, and perform at your peak every session.',
   'tags'=>['Whey Protein','Creatine','BCAA','Pre-Workout','Glutamine'],
   'products'=>['Whey Protein Isolate','Creatine Monohydrate','BCAA 2:1:1','Mass Gainer Pro','L-Glutamine'],
   'img'=>'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&q=70','count'=>15,'popular'=>'Whey Protein Isolate'],

  ['slug'=>'weight',    'label'=>'Weight Management',    'icon'=>'⚖️','color'=>'#6B4C9A',
   'desc'=>'Science-backed support for healthy weight goals — without crash diets.',
   'tags'=>['CLA','Garcinia','Green Tea','Detox','Fibre'],
   'products'=>['CLA 1000mg','Garcinia Cambogia','Green Tea Extract','Apple Cider Vinegar','Psyllium Husk'],
   'img'=>'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&q=70','count'=>11,'popular'=>'CLA 1000mg'],

  ['slug'=>'stress',    'label'=>'Stress & Sleep',       'icon'=>'🌙','color'=>'#2D4A7A',
   'desc'=>'Calm your mind, improve sleep quality, and wake up restored.',
   'tags'=>['Melatonin','Magnesium','L-Theanine','Ashwagandha','5-HTP'],
   'products'=>['Melatonin 10mg','Magnesium Glycinate','L-Theanine 200mg','Ashwagandha KSM-66','5-HTP 100mg'],
   'img'=>'https://images.unsplash.com/photo-1511295742362-92c96b1cf484?w=800&q=70','count'=>7,'popular'=>'Magnesium Glycinate'],

  ['slug'=>'digestion', 'label'=>'Gut & Detox',          'icon'=>'🌿','color'=>'#2E6244',
   'desc'=>'Balance your gut microbiome for better digestion, immunity and mood.',
   'tags'=>['Probiotics','Prebiotics','Digestive Enzymes','Triphala','ACV'],
   'products'=>['Probiotic 50 Billion','Digestive Enzyme Complex','Triphala Churna','Apple Cider Vinegar','Psyllium Husk'],
   'img'=>'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=800&q=70','count'=>10,'popular'=>'Probiotic 50 Billion'],

  ['slug'=>'bone',      'label'=>'Immunity & Bones',     'icon'=>'🦴','color'=>'#C07830',
   'desc'=>'Protect your joints and keep your bones strong for the long game.',
   'tags'=>['Calcium','D3','Magnesium','Collagen','Glucosamine'],
   'products'=>['Calcium + D3','Magnesium Glycinate','Collagen Peptides','Glucosamine & Chondroitin','Vitamin K2'],
   'img'=>'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800&q=70','count'=>8,'popular'=>'Collagen Peptides'],

  ['slug'=>'heart',     'label'=>'Heart & Brain',        'icon'=>'❤️','color'=>'#C0392B',
   'desc'=>'Support cardiovascular health and sharpen cognition with clinically validated nutrients.',
   'tags'=>['Omega-3','CoQ10','Bacopa','Ginkgo','Niacin'],
   'products'=>['Omega-3 Fish Oil','CoQ10 200mg','Bacopa Monnieri','Ginkgo Biloba','Niacin B3'],
   'img'=>'https://images.unsplash.com/photo-1505576399279-565b52d4ac71?w=800&q=70','count'=>9,'popular'=>'Omega-3 Fish Oil'],

  ['slug'=>'skin',      'label'=>'Skin, Hair & Nails',   'icon'=>'✨','color'=>'#A0522D',
   'desc'=>'Nourish from within for glowing skin, stronger hair, and resilient nails.',
   'tags'=>['Biotin','Collagen','Vitamin E','Hyaluronic Acid','Keratin'],
   'products'=>['Biotin 10000mcg','Collagen Peptides','Vitamin E 400 IU','Hyaluronic Acid','Hair Gummies'],
   'img'=>'https://images.unsplash.com/photo-1515377905703-c4788e51af15?w=800&q=70','count'=>13,'popular'=>'Biotin 10000mcg'],

  ['slug'=>'mens',      'label'=>"Men's Wellness",       'icon'=>'♂️','color'=>'#1C3D24',
   'desc'=>'Targeted support for testosterone, vitality, and male reproductive health.',
   'tags'=>['Zinc','Ashwagandha','Shilajit','Tribulus','Saw Palmetto'],
   'products'=>['Shilajit Pure Resin','Ashwagandha KSM-66','Zinc Bisglycinate','Tribulus Terrestris',"Men's Multivitamin"],
   'img'=>'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&q=70','count'=>10,'popular'=>'Shilajit Pure Resin'],

  ['slug'=>'diabetes',  'label'=>'Blood Sugar Balance',  'icon'=>'🩸','color'=>'#7B241C',
   'desc'=>'Natural support for healthy glucose metabolism and insulin sensitivity.',
   'tags'=>['Berberine','Chromium','Cinnamon','Bitter Melon','Alpha Lipoic Acid'],
   'products'=>['Berberine 500mg','Chromium Picolinate','Cinnamon Extract','Bitter Melon','Alpha Lipoic Acid'],
   'img'=>'https://images.unsplash.com/photo-1576671414121-aa2d60f93631?w=800&q=70','count'=>7,'popular'=>'Berberine 500mg'],

  ['slug'=>'ayurvedic', 'label'=>'Ayurvedic Wellness',   'icon'=>'🌱','color'=>'#5C3D1E',
   'desc'=>'Time-tested Ayurvedic herbs standardised to clinical potency for modern health.',
   'tags'=>['Ashwagandha','Triphala','Shatavari','Brahmi','Tulsi'],
   'products'=>['Ashwagandha KSM-66','Triphala Herbal Complex','Shatavari Extract','Brahmi 500mg','Tulsi Drops'],
   'img'=>'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&q=70','count'=>14,'popular'=>'Ashwagandha KSM-66'],
];

// Active concern via URL
$activeConcern = isset($_GET['concern']) ? trim($_GET['concern']) : null;
$activeData    = null;
foreach ($concerns as $c) {
  if ($c['slug'] === $activeConcern) { $activeData = $c; break; }
}
?>
<style>
/* ═══════════════════════════════════════════════════
   SHOP BY CONCERN — uses index.php cat-tile design
   All vars from header.php
═══════════════════════════════════════════════════ */

/* ── scroll strip (same as index) ── */
.scroll-strip{height:36px;overflow:hidden;display:flex;align-items:center;border-bottom:1px solid var(--b);background:var(--w)}
.scroll-track{display:flex;width:max-content;animation:scrollMove 32s linear infinite}
.scroll-strip:hover .scroll-track{animation-play-state:paused}
@keyframes scrollMove{from{transform:translateX(0)}to{transform:translateX(-50%)}}
.si{display:inline-flex;align-items:center;gap:.55rem;padding:0 2rem;font-size:.6rem;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--t4);white-space:nowrap}
.si-dot{width:2px;height:2px;border-radius:50%;background:var(--green-l);flex-shrink:0}

/* ── Page heading row ── */
.sbc-heading-row{
  padding:2.5rem 1.5rem 1.5rem;
  display:flex;align-items:flex-end;justify-content:space-between;
  flex-wrap:wrap;gap:1rem;border-bottom:1px solid var(--b);
}
.slabel{font-size:.58rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);display:block;margin-bottom:.5rem}
.sh{font-family:'DM Serif Display',serif;font-size:clamp(1.55rem,2.5vw,2.2rem);font-weight:400;color:var(--t);line-height:1.12;letter-spacing:-.015em}
.sh em{font-style:italic;color:var(--green)}
.sbc-heading-sub{font-size:.8rem;color:var(--t3);margin-top:.35rem;font-weight:300;line-height:1.65;max-width:440px}
.sbc-heading-right{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap}

/* Search bar */
.sbc-search-bar{display:flex;align-items:center;background:var(--g1);border:1.5px solid var(--b);overflow:hidden}
.sbc-search-bar input{background:none;border:none;outline:none;padding:.48rem .85rem;font-family:'Outfit',sans-serif;font-size:.82rem;color:var(--t);width:190px}
.sbc-search-bar input::placeholder{color:var(--t4)}
.sbc-search-bar button{background:var(--green);border:none;padding:.48rem .72rem;cursor:pointer;color:#fff;display:flex;align-items:center;transition:background .15s}
.sbc-search-bar button:hover{background:var(--green-m)}

/* View all link */
.sbc-viewall{font-size:.62rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--green);border-bottom:1.5px solid var(--green);padding-bottom:2px;text-decoration:none;white-space:nowrap;transition:color .15s,border-color .15s}
.sbc-viewall:hover{color:var(--green-m);border-color:var(--green-m)}

/* Trust bar — exact copy of bsr-trust */
.sbc-trust{padding:.75rem 1.5rem;background:var(--g1);border-top:1px solid var(--b);border-bottom:1px solid var(--b);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap}
.sbc-trust-item{font-size:.62rem;font-weight:500;letter-spacing:.06em;text-transform:uppercase;color:var(--t3)}
.sbc-trust-sep{color:var(--b2);font-size:.8rem}

/* ── Concern tile grid — exact cat-tile style from index ── */
.sbc-section{padding:2.5rem 1.5rem 3rem;border-bottom:1px solid var(--b)}

.sbc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:1.25rem}

.sbc-tile{position:relative;overflow:hidden;aspect-ratio:3/2;display:flex;align-items:flex-end;cursor:pointer;text-decoration:none;transition:box-shadow .28s}
.sbc-tile:hover{box-shadow:0 14px 40px rgba(0,0,0,.22)}
.sbc-tile-bg{position:absolute;inset:0;overflow:hidden}
.sbc-tile-bg img{width:100%;height:100%;object-fit:cover;object-position:center;display:block;transition:transform .55s ease}
.sbc-tile:hover .sbc-tile-bg img{transform:scale(1.06)}
.sbc-tile-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.62) 0%,rgba(0,0,0,.1) 52%,transparent 100%)}
.sbc-tile-label{position:relative;z-index:1;padding:1.1rem 1.25rem;width:100%}
.sbc-tile-icon{font-size:1.25rem;line-height:1;margin-bottom:.28rem;display:block;filter:drop-shadow(0 1px 4px rgba(0,0,0,.55))}
.sbc-tile-name{font-family:'DM Serif Display',serif;font-size:1.1rem;font-weight:400;color:#fff;line-height:1.2;margin-bottom:.3rem;text-shadow:0 1px 6px rgba(0,0,0,.4)}
.sbc-tile-tags{display:flex;flex-wrap:wrap;gap:.28rem;margin-bottom:.45rem}
.sbc-tile-tag{font-size:.52rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);color:rgba(255,255,255,.88);padding:.14rem .46rem;backdrop-filter:blur(3px)}
.sbc-tile-foot{display:flex;align-items:center;justify-content:space-between}
.sbc-tile-link{font-size:.58rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.75);border-bottom:1px solid rgba(255,255,255,.38);padding-bottom:1px;display:inline-block;transition:color .15s,border-color .15s}
.sbc-tile:hover .sbc-tile-link{color:#fff;border-color:#fff}
.sbc-tile-count{font-size:.52rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;background:rgba(0,0,0,.42);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.7);padding:.14rem .5rem}

/* ── Expanded concern panel ── */
.sbc-panel{background:#fff;border:1.5px solid var(--b);margin-bottom:2rem;overflow:hidden;animation:panelIn .28s ease}
@keyframes panelIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:none}}
.sbc-panel-inner{display:grid;grid-template-columns:300px 1fr;min-height:200px}
.sbc-panel-img{background:var(--g1);overflow:hidden;position:relative}
.sbc-panel-img img{width:100%;height:100%;object-fit:cover;display:block}
.sbc-panel-body{padding:1.5rem 1.75rem}
.sbc-panel-eyebrow{font-size:.58rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--t3);margin-bottom:.45rem}
.sbc-panel-title{font-family:'DM Serif Display',serif;font-size:1.6rem;font-weight:400;color:var(--t);line-height:1.08;letter-spacing:-.02em;margin-bottom:.35rem}
.sbc-panel-title em{font-style:italic;color:var(--green)}
.sbc-panel-desc{font-size:.82rem;color:var(--t3);line-height:1.75;font-weight:300;margin-bottom:1rem;max-width:500px}
.sbc-panel-tags{display:flex;flex-wrap:wrap;gap:.38rem;margin-bottom:1.1rem}
.sbc-panel-tag{font-size:.62rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;background:var(--g1);border:1.5px solid var(--b2);color:var(--t2);padding:.28rem .72rem;transition:all .15s;cursor:pointer}
.sbc-panel-tag:hover{border-color:var(--green);color:var(--green)}
.sbc-panel-actions{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;margin-bottom:1.1rem}
.sbc-panel-btn{background:var(--green);color:#fff;padding:.55rem 1.35rem;font-size:.7rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;border:none;cursor:pointer;font-family:'Outfit',sans-serif;transition:background .18s;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem}
.sbc-panel-btn:hover{background:var(--green-m);color:#fff}
.sbc-panel-close{background:none;border:1.5px solid var(--b2);color:var(--t3);padding:.53rem .9rem;font-size:.65rem;font-weight:700;letter-spacing:.04em;cursor:pointer;font-family:'Outfit',sans-serif;transition:all .15s}
.sbc-panel-close:hover{border-color:#c44030;color:#c44030}
.sbc-prods-label{font-size:.58rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);margin-bottom:.6rem}
.sbc-prod-pills{display:flex;flex-wrap:wrap;gap:.4rem}
.sbc-prod-pill{display:inline-flex;align-items:center;gap:.4rem;background:var(--g1);border:1.5px solid var(--b);padding:.38rem .8rem;font-size:.75rem;font-weight:500;color:var(--t2);cursor:pointer;transition:all .15s;text-decoration:none}
.sbc-prod-pill:hover{border-color:var(--green);color:var(--green);background:rgba(26,59,34,.04)}
.sbc-prod-pill svg{color:var(--green-l);flex-shrink:0}

/* Empty state */
.sbc-empty{text-align:center;padding:3rem 1rem;display:none}
.sbc-empty-icon{font-size:3rem;opacity:.28;margin-bottom:.75rem}
.sbc-empty h3{font-family:'DM Serif Display',serif;color:var(--green);font-size:1.4rem;margin-bottom:.35rem}
.sbc-empty p{font-size:.82rem;color:var(--t3)}

/* ── Trust / marquee / CTA — same as index ── */
.trust{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--b);border-bottom:1px solid var(--b)}
.ti{padding:1.5rem 1.25rem;border-right:1px solid var(--b);display:flex;align-items:flex-start;gap:.85rem}
.ti:last-child{border-right:none}
.ti-ico{font-size:1.5rem;flex-shrink:0;margin-top:.1rem}
.ti-h{font-size:.75rem;font-weight:600;color:var(--t);margin-bottom:.18rem}
.ti-p{font-size:.62rem;color:var(--t3);line-height:1.5;font-weight:300}
.marquee-sec{background:var(--green);height:38px;overflow:hidden;display:flex;align-items:center}
.marquee-track{display:flex;width:max-content;animation:scrollMove 20s linear infinite}
.marquee-sec:hover .marquee-track{animation-play-state:paused}
.mi{display:inline-flex;align-items:center;gap:.7rem;padding:0 2rem;font-size:.62rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.75);white-space:nowrap}
.mi-dot{width:3px;height:3px;border-radius:50%;background:var(--gold-ll);flex-shrink:0}
.cta{background:var(--t);text-align:center;padding:4.5rem 1.5rem;position:relative;overflow:hidden}
.cta-wm{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-family:'DM Serif Display',serif;font-size:clamp(8rem,16vw,20rem);font-weight:400;color:rgba(255,255,255,.028);white-space:nowrap;pointer-events:none;letter-spacing:-.04em}
.cta-inner{position:relative;z-index:1;max-width:500px;margin:0 auto}
.cta-lbl{font-size:.58rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--gold);margin-bottom:1.25rem;display:flex;align-items:center;justify-content:center;gap:.5rem}
.cta-lbl::before,.cta-lbl::after{content:'';width:18px;height:1px;background:var(--gold)}
.cta-h{font-family:'DM Serif Display',serif;font-size:clamp(2.2rem,4vw,3.8rem);font-weight:400;color:#fff;line-height:1.08;letter-spacing:-.025em;margin-bottom:.85rem}
.cta-h em{font-style:italic;color:var(--gold-ll)}
.cta-p{font-size:.8rem;color:rgba(255,255,255,.38);line-height:1.85;margin-bottom:2rem;font-weight:300}
.cta-btns{display:flex;align-items:center;justify-content:center;gap:.85rem;flex-wrap:wrap}

/* ── Mobile slider ── */
.sbc-slide-outer{position:relative;margin-top:1.25rem;display:none}
.sbc-slide-vp{overflow:hidden;touch-action:pan-y}
.sbc-slide-track{display:flex;gap:.75rem;transition:transform .35s cubic-bezier(.4,0,.2,1);will-change:transform}
.sbc-slide-track .sbc-tile{flex:0 0 calc(85vw - 2rem);max-width:340px;aspect-ratio:4/3}
.sbc-slide-dots{display:flex;align-items:center;justify-content:center;gap:.45rem;margin-top:.9rem}
.sbc-slide-dot{width:6px;height:6px;border-radius:50%;background:rgba(26,61,36,.22);border:none;padding:0;transition:all .28s;cursor:pointer}
.sbc-slide-dot.on{width:20px;border-radius:3px;background:var(--green)}
.sbc-slide-arr{position:absolute;top:42%;transform:translateY(-50%);z-index:5;width:34px;height:34px;background:rgba(255,255,255,.92);border:1px solid var(--b);border-radius:50%;font-size:1.1rem;color:var(--t);display:flex;align-items:center;justify-content:center;transition:background .18s,box-shadow .18s;box-shadow:0 2px 8px rgba(0,0,0,.1);cursor:pointer}
.sbc-slide-arr:hover{background:#fff;box-shadow:0 4px 14px rgba(0,0,0,.15)}
.sbc-slide-prev{left:-10px}.sbc-slide-next{right:-10px}

/* Reveal */
.rv{opacity:0;transform:translateY(16px);transition:opacity .6s ease,transform .6s ease}
.rv.on{opacity:1;transform:none}

/* ── Responsive ── */
@media(min-width:641px){.sbc-grid{display:grid}.sbc-slide-outer{display:none!important}}
@media(max-width:900px){.sbc-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:640px){
  .sbc-grid{display:none!important}.sbc-slide-outer{display:block}
  .sbc-section{padding:2rem 1rem 2.5rem}
  .sbc-heading-row{padding:1.75rem 1rem 1.25rem}
  .sbc-panel-inner{grid-template-columns:1fr}
  .sbc-panel-img{height:180px}
  .sbc-panel-body{padding:1.1rem 1.1rem .9rem}
  .trust{grid-template-columns:repeat(2,1fr)}
  .ti:nth-child(2){border-right:none}
  .ti:nth-child(3),.ti:nth-child(4){border-top:1px solid var(--b)}
}
@media(max-width:480px){.sbc-heading-right{display:none}}
@media(max-width:400px){
  .trust{grid-template-columns:1fr}
  .ti{border-right:none!important}
  .ti+.ti{border-top:1px solid var(--b)}
}
</style>

<!-- SCROLL STRIP -->
<div class="scroll-strip">
  <div class="scroll-track">
    <?php
    $sis=['Immunity','Energy & Stamina','Muscle & Strength','Weight Management','Stress & Sleep','Gut & Detox','Immunity & Bones','Heart & Brain','Skin Hair & Nails',"Men's Wellness",'Blood Sugar','Ayurvedic Wellness'];
    for($r=0;$r<4;$r++) foreach($sis as $s):
    ?><span class="si"><span class="si-dot"></span><?= htmlspecialchars($s) ?></span><?php endforeach; ?>
  </div>
</div>

<!-- PAGE HEADING -->
<div class="sbc-heading-row">
  <div>
    <span class="slabel">Personalised Wellness</span>
    <h1 class="sh">Find Your <em>Health Goal</em></h1>
    <p class="sbc-heading-sub">Stop guessing. Start targeted. Every concern is matched to products that actually work.</p>
  </div>
  <div class="sbc-heading-right">
    <form class="sbc-search-bar" onsubmit="return false">
      <input type="text" id="sbcQ" placeholder="Search concern…" autocomplete="off">
      <button type="button">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
    </form>
    <a href="<?= APP_URL ?>/shop.php" class="sbc-viewall">All Products →</a>
  </div>
</div>

<!-- TRUST BAR -->
<div class="sbc-trust">
  <span class="sbc-trust-item">12 Health Concerns</span><span class="sbc-trust-sep">·</span>
  <span class="sbc-trust-item">120+ Products</span><span class="sbc-trust-sep">·</span>
  <span class="sbc-trust-item">100% Science-Backed</span><span class="sbc-trust-sep">·</span>
  <span class="sbc-trust-item">FSSAI Certified</span><span class="sbc-trust-sep">·</span>
  <span class="sbc-trust-item">3rd-Party Lab Tested</span><span class="sbc-trust-sep">·</span>
  <span class="sbc-trust-item">Browse Free — Login to Buy</span>
</div>

<!-- EXPANDED PANEL (if ?concern=slug) -->
<?php if ($activeData): ?>
<div style="padding:1.5rem 1.5rem 0" id="sbcPanelWrap">
  <div class="sbc-panel">
    <div class="sbc-panel-inner">
      <div class="sbc-panel-img">
        <img src="<?= htmlspecialchars($activeData['img']) ?>" alt="<?= htmlspecialchars($activeData['label']) ?>" loading="lazy">
      </div>
      <div class="sbc-panel-body">
        <div class="sbc-panel-eyebrow"><?= $activeData['icon'] ?> &nbsp;Health Concern</div>
        <h2 class="sbc-panel-title"><em><?= htmlspecialchars($activeData['label']) ?></em></h2>
        <p class="sbc-panel-desc"><?= htmlspecialchars($activeData['desc']) ?></p>
        <div class="sbc-panel-tags">
          <?php foreach($activeData['tags'] as $tag): ?>
          <span class="sbc-panel-tag"><?= htmlspecialchars($tag) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="sbc-panel-actions">
          <?php if($loggedIn): ?>
          <a href="<?= APP_URL ?>/shop.php?concern=<?= $activeData['slug'] ?>" class="sbc-panel-btn">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Shop <?= htmlspecialchars($activeData['label']) ?>
          </a>
          <?php else: ?>
          <button class="sbc-panel-btn" onclick="glmOpen('<?= addslashes($activeData['label']) ?> Products','','<?= htmlspecialchars($activeData['img']) ?>')">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Shop — Login Required
          </button>
          <?php endif; ?>
          <button class="sbc-panel-close" onclick="window.location='<?= APP_URL ?>/shop_by_concern.php'">✕ Close</button>
        </div>
        <div class="sbc-prods-label">Top Products for this Concern</div>
        <div class="sbc-prod-pills">
          <?php foreach($activeData['products'] as $prod): ?>
          <?php if($loggedIn): ?>
          <a href="<?= APP_URL ?>/shop.php?q=<?= urlencode($prod) ?>" class="sbc-prod-pill">
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            <?= htmlspecialchars($prod) ?>
          </a>
          <?php else: ?>
          <button class="sbc-prod-pill" onclick="glmOpen('<?= addslashes($prod) ?>','','')">
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            <?= htmlspecialchars($prod) ?>
          </button>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- CONCERN TILES — index.php cat-tile style -->
<div class="sbc-section rv" id="categories">

  <!-- Desktop grid -->
  <div class="sbc-grid" id="sbcGrid">
    <?php foreach($concerns as $c): ?>
    <a href="<?= APP_URL ?>/shop_by_concern.php?concern=<?= $c['slug'] ?>"
       class="sbc-tile"
       data-label="<?= strtolower($c['label']) ?>"
       data-tags="<?= strtolower(implode(' ',$c['tags'])) ?>">
      <div class="sbc-tile-bg">
        <img src="<?= htmlspecialchars($c['img']) ?>"
             alt="<?= htmlspecialchars($c['label']) ?>"
             loading="lazy"
             onerror="this.parentElement.style.background='#1c3d24'">
      </div>
      <div class="sbc-tile-overlay"></div>
      <div class="sbc-tile-label">
        <span class="sbc-tile-icon"><?= $c['icon'] ?></span>
        <div class="sbc-tile-name"><?= htmlspecialchars($c['label']) ?></div>
        <div class="sbc-tile-tags">
          <?php foreach(array_slice($c['tags'],0,3) as $tag): ?>
          <span class="sbc-tile-tag"><?= htmlspecialchars($tag) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="sbc-tile-foot">
          <span class="sbc-tile-link">Shop Now</span>
          <span class="sbc-tile-count"><?= $c['count'] ?> products</span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Empty state -->
  <div class="sbc-empty" id="sbcEmpty">
    <div class="sbc-empty-icon">🔍</div>
    <h3>No concerns found</h3>
    <p>Try a different keyword or <a href="<?= APP_URL ?>/shop.php" style="color:var(--green);font-weight:600">browse all products</a>.</p>
  </div>

  <!-- Mobile swipe slider -->
  <div class="sbc-slide-outer" id="sbcSlideOuter">
    <button class="sbc-slide-arr sbc-slide-prev" id="sbcPrev">&#x2039;</button>
    <button class="sbc-slide-arr sbc-slide-next" id="sbcNext">&#x203a;</button>
    <div class="sbc-slide-vp" id="sbcVp">
      <div class="sbc-slide-track" id="sbcTrack">
        <?php foreach($concerns as $c): ?>
        <a href="<?= APP_URL ?>/shop_by_concern.php?concern=<?= $c['slug'] ?>" class="sbc-tile">
          <div class="sbc-tile-bg">
            <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['label']) ?>" loading="lazy" onerror="this.parentElement.style.background='#1c3d24'">
          </div>
          <div class="sbc-tile-overlay"></div>
          <div class="sbc-tile-label">
            <span class="sbc-tile-icon"><?= $c['icon'] ?></span>
            <div class="sbc-tile-name"><?= htmlspecialchars($c['label']) ?></div>
            <div class="sbc-tile-foot">
              <span class="sbc-tile-link">Shop Now</span>
              <span class="sbc-tile-count"><?= $c['count'] ?> products</span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="sbc-slide-dots" id="sbcDots">
      <?php foreach($concerns as $i=>$c): ?>
      <button class="sbc-slide-dot <?= $i===0?'on':'' ?>" data-i="<?= $i ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- TRUST -->
<div class="trust rv">
  <?php foreach([
    ['🌿','Science-Backed',    'Clinically studied ingredients in every product'],
    ['₹0','Zero Join Fee',     'Register free, get MBPIN, start earning instantly'],
    ['⚡','Instant PSB',       'Auto-credited on every downline purchase'],
    ['🔒','Secure & Transparent','Full KYC, wallet history, easy withdrawal'],
  ] as $t): ?>
  <div class="ti">
    <span class="ti-ico"><?= $t[0] ?></span>
    <div><div class="ti-h"><?= $t[1] ?></div><div class="ti-p"><?= $t[2] ?></div></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- MARQUEE -->
<div class="marquee-sec"><div class="marquee-track">
  <?php for($r=0;$r<4;$r++): ?>
  <span class="mi"><span class="mi-dot"></span>Free Registration</span>
  <span class="mi"><span class="mi-dot"></span>Genuine Products</span>
  <span class="mi"><span class="mi-dot"></span>7-Level PSB</span>
  <span class="mi"><span class="mi-dot"></span>Instant MBPIN</span>
  <span class="mi"><span class="mi-dot"></span>Business Club</span>
  <span class="mi"><span class="mi-dot"></span>MShop Plus</span>
  <span class="mi"><span class="mi-dot"></span>Rising Star Club</span>
  <span class="mi"><span class="mi-dot"></span>Chairman Club</span>
  <?php endfor; ?>
</div></div>

<!-- CTA -->
<div class="cta rv">
  <div class="cta-wm">MFILLS</div>
  <div class="cta-inner">
    <div class="cta-lbl">Start Today</div>
    <h2 class="cta-h">Your Journey<br>Begins <em>Now.</em></h2>
    <p class="cta-p">Free registration. Instant MBPIN. Genuine supplements. Real income through PSB. Everything to build a growing business with Mfills.</p>
    <div class="cta-btns">
      <a href="<?= APP_URL ?>/register.php" class="btn-gld">Register Free — Get MBPIN</a>
      <a href="<?= APP_URL ?>/login.php"    class="btn-ghost">Already an MBP? Login</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
/* ── Scroll reveal ── */
(function(){
  var ro=new IntersectionObserver(function(e){e.forEach(function(x){if(x.isIntersecting){x.target.classList.add('on');ro.unobserve(x.target);}});},{threshold:.08});
  document.querySelectorAll('.rv').forEach(function(el){ro.observe(el);});
})();

/* ── Live search ── */
document.getElementById('sbcQ').addEventListener('input',function(){
  var q=this.value.trim().toLowerCase();
  var tiles=document.querySelectorAll('#sbcGrid .sbc-tile');
  var vis=0;
  tiles.forEach(function(t){
    var show=!q||t.dataset.label.includes(q)||t.dataset.tags.includes(q);
    t.style.display=show?'':'none';
    if(show)vis++;
  });
  document.getElementById('sbcEmpty').style.display=(vis===0&&q)?'block':'none';
});

/* ── Mobile slider ── */
(function(){
  var track=document.getElementById('sbcTrack'),vp=document.getElementById('sbcVp'),
      dotsEl=document.querySelectorAll('#sbcDots .sbc-slide-dot'),
      prevBtn=document.getElementById('sbcPrev'),nextBtn=document.getElementById('sbcNext');
  if(!track)return;
  var total=dotsEl.length,cur=0,sx=0,isDrag=false;
  function sw(){var t=track.querySelector('.sbc-tile');return t?t.offsetWidth+12:0;}
  function goTo(n){
    cur=Math.max(0,Math.min(n,total-1));
    track.style.transform='translateX(-'+(cur*sw())+'px)';
    dotsEl.forEach(function(d,i){d.classList.toggle('on',i===cur);});
    prevBtn.style.opacity=cur===0?'.35':'1';
    nextBtn.style.opacity=cur===total-1?'.35':'1';
  }
  prevBtn.addEventListener('click',function(){goTo(cur-1);});
  nextBtn.addEventListener('click',function(){goTo(cur+1);});
  dotsEl.forEach(function(d){d.addEventListener('click',function(){goTo(+d.dataset.i);});});
  vp.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;isDrag=false;},{passive:true});
  vp.addEventListener('touchmove', function(e){if(Math.abs(e.touches[0].clientX-sx)>8)isDrag=true;},{passive:true});
  vp.addEventListener('touchend',  function(e){
    if(!isDrag)return;
    var dx=e.changedTouches[0].clientX-sx;
    if(Math.abs(dx)>40)goTo(dx<0?cur+1:cur-1);
    isDrag=false;
  },{passive:true});
  track.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(e){if(isDrag)e.preventDefault();});});
  goTo(0);
  window.addEventListener('resize',function(){goTo(cur);},{passive:true});
})();

/* ── Auto-scroll to panel ── */
<?php if($activeData): ?>
document.addEventListener('DOMContentLoaded',function(){
  var w=document.getElementById('sbcPanelWrap');
  if(w)setTimeout(function(){w.scrollIntoView({behavior:'smooth',block:'start'});},150);
});
<?php endif; ?>
</script>