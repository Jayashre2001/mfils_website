<?php
// best_sellers.php
$pageTitle = 'Best Sellers — Mfills';
require_once __DIR__ . '/includes/header.php';

// ── Fetch best-seller products ──────────────────────────────
// If your getProducts() supports a 'bestseller' flag, use it.
// Fallback: load all mshop products, sort by sales/rating.
$allProducts = function_exists('getProducts') ? getProducts('mshop') : [];

// Demo data shown when DB is empty / during dev
$demoProducts = [
  ['id'=>1,'name'=>'Whey Protein Isolate 90%','price'=>2499,'original_price'=>2999,'image_url'=>'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=600&q=70','badge'=>'#1 Bestseller','category'=>'Protein','rating'=>4.8,'reviews'=>1241,'description'=>'Ultra-pure 90% isolate, zero fat, 27g protein per scoop.'],
  ['id'=>2,'name'=>'Vitamin D3 + K2 (5000 IU)','price'=>549,'original_price'=>799,'image_url'=>'https://images.unsplash.com/photo-1550572017-edd951b55104?w=600&q=70','badge'=>'Top Rated','category'=>'Vitamins','rating'=>4.9,'reviews'=>892,'description'=>'Optimal D3/K2 ratio for bone health & immunity.'],
  ['id'=>3,'name'=>'Ashwagandha KSM-66 (600mg)','price'=>499,'original_price'=>699,'image_url'=>'https://images.unsplash.com/photo-1611073615830-9b2be67a4e72?w=600&q=70','badge'=>'Trending','category'=>'Ayurvedic','rating'=>4.7,'reviews'=>764,'description'=>'Clinically proven KSM-66 extract for stress & strength.'],
  ['id'=>4,'name'=>'Omega-3 Fish Oil (1000mg)','price'=>399,'original_price'=>599,'image_url'=>'https://images.unsplash.com/photo-1585435465943-bc70a3f75b2b?w=600&q=70','badge'=>'Hot Pick','category'=>'Wellness','rating'=>4.6,'reviews'=>521,'description'=>'Pharmaceutical-grade EPA/DHA for heart & brain health.'],
  ['id'=>5,'name'=>'Mass Gainer Pro 5KG','price'=>3499,'original_price'=>4299,'image_url'=>'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=70','badge'=>'Fan Favourite','category'=>'Protein','rating'=>4.5,'reviews'=>438,'description'=>'2500 kcal per serving with 100g complex carbs.'],
  ['id'=>6,'name'=>'Multivitamin Daily Pack','price'=>349,'original_price'=>499,'image_url'=>'https://images.unsplash.com/photo-1512069772995-ec65ed45afd6?w=600&q=70','badge'=>'Best Value','category'=>'Vitamins','rating'=>4.7,'reviews'=>987,'description'=>'23 essential nutrients in one daily strip pack.'],
  ['id'=>7,'name'=>'CLA 1000mg (90 Softgels)','price'=>799,'original_price'=>1099,'image_url'=>'https://images.unsplash.com/photo-1467453678174-768ec283a940?w=600&q=70','badge'=>'Slim Pick','category'=>'Weight','rating'=>4.4,'reviews'=>316,'description'=>'Conjugated Linoleic Acid for lean body composition.'],
  ['id'=>8,'name'=>'Creatine Monohydrate 300g','price'=>699,'original_price'=>899,'image_url'=>'https://images.unsplash.com/photo-1554481923-a6918bd997bc?w=600&q=70','badge'=>'Gym Essential','category'=>'Performance','rating'=>4.8,'reviews'=>1102,'description'=>'Micronised 100% pure creatine for strength & power.'],
];

$products = !empty($allProducts) ? array_slice($allProducts, 0, 8) : $demoProducts;

// Category filter list
$categories = ['All','Protein','Vitamins','Ayurvedic','Wellness','Weight','Performance'];
?>

<style>
/* ══════════════════════════════════════════
   BEST SELLERS PAGE — extends header.php vars
══════════════════════════════════════════ */

/* ── Hero ── */
.bs-hero{
  background:linear-gradient(135deg,#0c1f10 0%,var(--green-dd) 45%,#1a3322 100%);
  padding:3.5rem 0 3rem;position:relative;overflow:hidden;
}
.bs-hero::before{
  content:'';position:absolute;inset:0;
  background-image:
    radial-gradient(circle at 20% 50%,rgba(184,128,24,.08) 0%,transparent 55%),
    radial-gradient(circle at 80% 20%,rgba(78,154,96,.07) 0%,transparent 45%),
    radial-gradient(circle,rgba(200,146,42,.06) 1px,transparent 1px);
  background-size:100% 100%,100% 100%,28px 28px;
}
.bs-hero-inner{
  max-width:1200px;margin:0 auto;padding:0 1.5rem;
  display:flex;align-items:center;justify-content:space-between;gap:2rem;
  position:relative;z-index:1;
}
.bs-hero-left{flex:1}
.bs-hero-eyebrow{
  display:inline-flex;align-items:center;gap:.5rem;
  font-size:.58rem;font-weight:800;letter-spacing:.22em;text-transform:uppercase;
  color:var(--gold-l);margin-bottom:.85rem;
}
.bs-hero-eyebrow::before{content:'';width:22px;height:1.5px;background:var(--gold);display:block;}
.bs-hero-title{
  font-family:'Cinzel',serif;font-size:clamp(2rem,4vw,3.2rem);
  font-weight:900;color:#fff;line-height:1.08;letter-spacing:-.01em;
  margin-bottom:.85rem;
}
.bs-hero-title span{
  background:linear-gradient(90deg,var(--gold-l),var(--gold-ll));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.bs-hero-sub{font-size:.95rem;color:rgba(255,255,255,.55);max-width:480px;line-height:1.7;margin-bottom:1.75rem;}
.bs-hero-stats{display:flex;gap:2rem;flex-wrap:wrap;}
.bs-hero-stat{}
.bs-hero-stat-num{font-family:'Cinzel',serif;font-size:1.6rem;font-weight:900;color:var(--gold-l);line-height:1;}
.bs-hero-stat-lbl{font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-top:.2rem;}
.bs-hero-badges{
  display:flex;flex-direction:column;gap:.65rem;flex-shrink:0;
}
.bs-hero-badge-pill{
  background:rgba(255,255,255,.04);border:1px solid rgba(200,146,42,.22);
  padding:.6rem 1.1rem .6rem .7rem;
  display:flex;align-items:center;gap:.7rem;
  font-size:.75rem;color:rgba(255,255,255,.7);font-weight:600;
  letter-spacing:.03em;white-space:nowrap;
}
.bs-hero-badge-pill svg{color:var(--gold-l);flex-shrink:0;}

/* ── Filters ── */
.bs-filter-bar{
  background:#fff;border-bottom:1.5px solid var(--b);
  position:sticky;top:var(--navbar-h);z-index:100;
  box-shadow:0 2px 12px rgba(26,59,34,.06);
}
.bs-filter-inner{
  max-width:1200px;margin:0 auto;padding:.85rem 1.5rem;
  display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;
}
.bs-filter-tabs{display:flex;gap:.35rem;flex-wrap:wrap;}
.bs-filter-tab{
  background:var(--g1);border:1.5px solid var(--b);
  color:var(--t3);padding:.38rem .9rem;
  font-size:.7rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  cursor:pointer;transition:all .18s;border-radius:4px;
}
.bs-filter-tab:hover{border-color:var(--green-l);color:var(--green);}
.bs-filter-tab.active{background:var(--green);border-color:var(--green);color:#fff;}
.bs-filter-sort{display:flex;align-items:center;gap:.5rem;}
.bs-filter-sort label{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--t3);}
.bs-sort-select{
  background:var(--g1);border:1.5px solid var(--b);
  color:var(--t);padding:.38rem .75rem;font-size:.78rem;font-family:'Outfit',sans-serif;
  cursor:pointer;outline:none;transition:border-color .18s;border-radius:4px;
}
.bs-sort-select:focus{border-color:var(--green-l);}

/* ── Product Grid ── */
.bs-main{max-width:1200px;margin:0 auto;padding:2.5rem 1.5rem 5rem;}
.bs-count{font-size:.78rem;color:var(--muted);margin-bottom:1.5rem;font-weight:500;}
.bs-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:1.5rem;
}

/* ── Product Card ── */
.bsp-card{
  background:#fff;border:1.5px solid var(--b);
  overflow:hidden;transition:transform .25s,box-shadow .25s;
  position:relative;display:flex;flex-direction:column;
  border-radius:2px; /* sharp corners for premium feel */
}
.bsp-card:hover{transform:translateY(-5px);box-shadow:0 16px 40px rgba(26,59,34,.14);}
.bsp-img-wrap{
  position:relative;aspect-ratio:1/1;overflow:hidden;
  background:var(--g1);
}
.bsp-img-wrap img{
  width:100%;height:100%;object-fit:cover;object-position:center;
  transition:transform .45s ease;display:block;
}
.bsp-card:hover .bsp-img-wrap img{transform:scale(1.07);}
.bsp-badge{
  position:absolute;top:.7rem;left:.7rem;
  background:var(--green-dd);color:var(--gold-l);
  padding:.22rem .65rem;font-size:.58rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
  border:1px solid rgba(200,146,42,.3);
  z-index:2;
}
.bsp-discount{
  position:absolute;top:.7rem;right:.7rem;
  background:var(--gold);color:var(--t);
  width:38px;height:38px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:.62rem;font-weight:900;line-height:1;text-align:center;
  border:2px solid rgba(255,255,255,.3);
  z-index:2;
}
.bsp-overlay{
  position:absolute;bottom:0;left:0;right:0;
  background:linear-gradient(0deg,rgba(12,31,16,.88) 0%,transparent 100%);
  padding:2.5rem .9rem .75rem;
  display:flex;align-items:flex-end;justify-content:flex-end;
  opacity:0;transition:opacity .25s;
  z-index:3;
}
.bsp-card:hover .bsp-overlay{opacity:1;}
.bsp-quick-buy{
  background:var(--gold);color:var(--t);
  padding:.48rem 1rem;font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;
  border:none;cursor:pointer;transition:background .15s;font-family:'Outfit',sans-serif;
}
.bsp-quick-buy:hover{background:var(--gold-l);}
.bsp-body{padding:1.1rem;display:flex;flex-direction:column;gap:.35rem;flex:1;}
.bsp-cat{font-size:.58rem;font-weight:800;letter-spacing:.15em;text-transform:uppercase;color:var(--green-m);}
.bsp-name{font-family:'Cinzel',serif;font-size:.88rem;font-weight:700;color:var(--t);line-height:1.3;}
.bsp-desc{font-size:.74rem;color:var(--t3);line-height:1.55;flex:1;}
.bsp-stars{display:flex;align-items:center;gap:.3rem;}
.bsp-star-fill{color:var(--gold);font-size:.7rem;letter-spacing:.1em;}
.bsp-rev-count{font-size:.65rem;color:var(--t3);font-weight:600;}
.bsp-pricing{display:flex;align-items:baseline;gap:.5rem;margin-top:.1rem;}
.bsp-price{font-family:'Cinzel',serif;font-size:1.15rem;font-weight:900;color:var(--green);}
.bsp-original{font-size:.78rem;color:var(--t4);text-decoration:line-through;}
.bsp-savings{font-size:.65rem;font-weight:700;color:var(--jade);letter-spacing:.04em;}
.bsp-cta{
  margin-top:.85rem;
  background:var(--green);color:#fff;
  padding:.62rem;text-align:center;
  font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  border:none;cursor:pointer;font-family:'Outfit',sans-serif;
  transition:background .18s;display:block;width:100%;
}
.bsp-cta:hover{background:var(--green-m);}

/* Logged-in: show add-to-cart directly */
.bsp-cta-link{
  margin-top:.85rem;
  background:var(--green);color:#fff;
  padding:.62rem;text-align:center;
  font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  display:block;text-decoration:none;transition:background .18s;
}
.bsp-cta-link:hover{background:var(--green-m);color:#fff;}

/* ── Empty state ── */
.bs-empty{text-align:center;padding:4rem 1rem;color:var(--muted);}
.bs-empty-icon{font-size:3.5rem;margin-bottom:1rem;opacity:.4;}
.bs-empty h3{font-family:'Cinzel',serif;color:var(--green);margin-bottom:.5rem;}

/* ── Trust Strip ── */
.bs-trust{
  background:var(--green-dd);border-top:2px solid var(--gold-d);
  padding:1.5rem 0;
}
.bs-trust-inner{
  max-width:1200px;margin:0 auto;padding:0 1.5rem;
  display:flex;align-items:center;justify-content:space-around;gap:1.5rem;flex-wrap:wrap;
}
.bs-trust-item{display:flex;align-items:center;gap:.65rem;color:rgba(255,255,255,.7);}
.bs-trust-item svg{color:var(--gold-l);flex-shrink:0;}
.bs-trust-item strong{display:block;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.9);}
.bs-trust-item span{display:block;font-size:.62rem;color:rgba(255,255,255,.45);margin-top:1px;}

/* ── Responsive ── */
@media(max-width:1100px){.bs-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:820px){
  .bs-grid{grid-template-columns:repeat(2,1fr);gap:1rem;}
  .bs-hero-inner{flex-direction:column;}
  .bs-hero-badges{flex-direction:row;flex-wrap:wrap;}
  .bs-filter-inner{flex-direction:column;align-items:flex-start;gap:.75rem;}
}
@media(max-width:480px){
  .bs-grid{grid-template-columns:repeat(2,1fr);gap:.75rem;}
  .bsp-body{padding:.85rem;}
  .bsp-name{font-size:.8rem;}
  .bs-hero-stats{gap:1.25rem;}
  .bs-main{padding:1.5rem 1rem 4rem;}
}
@media(max-width:360px){.bs-grid{grid-template-columns:1fr;}}

/* ── Reveal animation ── */
.bs-reveal{opacity:0;transform:translateY(20px);transition:opacity .55s ease,transform .55s ease;}
.bs-reveal.visible{opacity:1;transform:none;}
</style>

<!-- ══ HERO ══ -->
<section class="bs-hero" id="best-sellers">
  <div class="bs-hero-inner">
    <div class="bs-hero-left">
      <div class="bs-hero-eyebrow">Most Loved Products</div>
      <h1 class="bs-hero-title">
        Our <span>Best Sellers</span><br>This Season
      </h1>
      <p class="bs-hero-sub">
        Trusted by thousands of health-conscious Indians. Every product is third-party tested, FSSAI approved, and earns you PSB Points on every order.
      </p>
      <div class="bs-hero-stats">
        <div class="bs-hero-stat">
          <div class="bs-hero-stat-num">50K+</div>
          <div class="bs-hero-stat-lbl">Happy Customers</div>
        </div>
        <div class="bs-hero-stat">
          <div class="bs-hero-stat-num">4.8★</div>
          <div class="bs-hero-stat-lbl">Avg Rating</div>
        </div>
        <div class="bs-hero-stat">
          <div class="bs-hero-stat-num">100%</div>
          <div class="bs-hero-stat-lbl">Authentic</div>
        </div>
      </div>
    </div>
    <div class="bs-hero-badges">
      <div class="bs-hero-badge-pill">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        FSSAI Certified Products
      </div>
      <div class="bs-hero-badge-pill">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Free Delivery ₹599+
      </div>
      <div class="bs-hero-badge-pill">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Same-Day Dispatch
      </div>
      <div class="bs-hero-badge-pill">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Earn PSB on Every Order
      </div>
    </div>
  </div>
</section>

<!-- ══ FILTER BAR ══ -->
<div class="bs-filter-bar">
  <div class="bs-filter-inner">
    <div class="bs-filter-tabs" id="bsFilterTabs">
      <?php foreach($categories as $i => $cat): ?>
      <button class="bs-filter-tab <?= $i===0?'active':'' ?>" data-cat="<?= $cat ?>"><?= $cat ?></button>
      <?php endforeach; ?>
    </div>
    <div class="bs-filter-sort">
      <label for="bsSortSelect">Sort:</label>
      <select class="bs-sort-select" id="bsSortSelect">
        <option value="popular">Most Popular</option>
        <option value="rating">Highest Rated</option>
        <option value="price_asc">Price: Low → High</option>
        <option value="price_desc">Price: High → Low</option>
        <option value="discount">Biggest Discount</option>
      </select>
    </div>
  </div>
</div>

<!-- ══ PRODUCT GRID ══ -->
<div class="bs-main">
  <p class="bs-count" id="bsCount"><?= count($products) ?> products</p>

  <div class="bs-grid" id="bsGrid">
    <?php foreach($products as $idx => $p):
      $orig  = $p['original_price'] ?? round($p['price'] * 1.25);
      $disc  = $orig > $p['price'] ? round(100 - ($p['price']/$orig*100)) : 0;
      $save  = $orig - $p['price'];
      $cat   = $p['category'] ?? 'Wellness';
      $badge = $p['badge'] ?? 'Best Seller';
      $rat   = $p['rating'] ?? 4.7;
      $rev   = $p['reviews'] ?? 500;
      $stars = '';
      for($s=1;$s<=5;$s++) $stars .= $s<=$rat ? '★' : ($s-$rat<1 ? '½' : '☆');
    ?>
    <div class="bsp-card bs-reveal"
         style="transition-delay:<?= $idx*55 ?>ms"
         data-cat="<?= htmlspecialchars($cat) ?>"
         data-price="<?= $p['price'] ?>"
         data-rating="<?= $rat ?>"
         data-discount="<?= $disc ?>">

      <div class="bsp-img-wrap">
        <?php if(!empty($p['image_url'])): ?>
        <img src="<?= htmlspecialchars($p['image_url']) ?>"
             alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
        <?php else: ?>
        <div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;opacity:.3;">💊</div>
        <?php endif; ?>
        <div class="bsp-badge"><?= htmlspecialchars($badge) ?></div>
        <?php if($disc>0): ?><div class="bsp-discount"><?= $disc ?>%<br>OFF</div><?php endif; ?>
        <div class="bsp-overlay">
          <?php if($loggedIn): ?>
          <a href="<?= APP_URL ?>/shop.php?add=<?= $p['id'] ?>" class="bsp-quick-buy">Add to Cart</a>
          <?php else: ?>
          <button class="bsp-quick-buy"
                  onclick="glmOpen('<?= addslashes($p['name']) ?>','<?= number_format($p['price'],0) ?>','<?= htmlspecialchars($p['image_url']??'') ?>')">
            Buy Now
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="bsp-body">
        <div class="bsp-cat"><?= htmlspecialchars($cat) ?></div>
        <div class="bsp-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="bsp-desc"><?= htmlspecialchars($p['description'] ?? '') ?></div>

        <div class="bsp-stars">
          <span class="bsp-star-fill"><?= $stars ?></span>
          <span class="bsp-rev-count">(<?= number_format($rev) ?>)</span>
        </div>

        <div class="bsp-pricing">
          <span class="bsp-price">₹<?= number_format($p['price']) ?></span>
          <?php if($orig > $p['price']): ?>
          <span class="bsp-original">₹<?= number_format($orig) ?></span>
          <span class="bsp-savings">Save ₹<?= number_format($save) ?></span>
          <?php endif; ?>
        </div>

        <?php if($loggedIn): ?>
        <a href="<?= APP_URL ?>/shop.php?add=<?= $p['id'] ?>" class="bsp-cta-link">Add to Cart</a>
        <?php else: ?>
        <button class="bsp-cta"
                onclick="glmOpen('<?= addslashes($p['name']) ?>','<?= number_format($p['price'],0) ?>','<?= htmlspecialchars($p['image_url']??'') ?>')">
          Buy Now — Login Required
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bs-empty" id="bsEmpty" style="display:none;">
    <div class="bs-empty-icon">🔍</div>
    <h3>No Products Found</h3>
    <p>Try selecting a different category.</p>
  </div>
</div>

<!-- ══ TRUST STRIP ══ -->
<div class="bs-trust">
  <div class="bs-trust-inner">
    <div class="bs-trust-item">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <div><strong>100% Authentic</strong><span>Directly sourced, no fakes</span></div>
    </div>
    <div class="bs-trust-item">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      <div><strong>Fast Delivery</strong><span>PAN India in 2–4 days</span></div>
    </div>
    <div class="bs-trust-item">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      <div><strong>Earn PSB Points</strong><span>Rewards on every purchase</span></div>
    </div>
    <div class="bs-trust-item">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 9a16 16 0 0 0 6.08 6.08l1.14-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
      <div><strong>24/7 Support</strong><span>Real humans, fast replies</span></div>
    </div>
    <div class="bs-trust-item">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
      <div><strong>Free Returns</strong><span>7-day hassle-free policy</span></div>
    </div>
  </div>
</div>

<script>
/* ── Reveal on scroll ── */
(function(){
  var cards=document.querySelectorAll('.bs-reveal');
  var io=new IntersectionObserver(function(entries){
    entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('visible');}});
  },{threshold:.1});
  cards.forEach(function(c){io.observe(c);});
})();

/* ── Filter by category ── */
(function(){
  var tabs=document.querySelectorAll('.bs-filter-tab');
  var cards=document.querySelectorAll('.bsp-card');
  var countEl=document.getElementById('bsCount');
  var emptyEl=document.getElementById('bsEmpty');

  function filterCards(cat){
    var visible=0;
    cards.forEach(function(c){
      var show=(cat==='All'||c.dataset.cat===cat);
      c.style.display=show?'':'none';
      if(show)visible++;
    });
    countEl.textContent=visible+' product'+(visible!==1?'s':'');
    emptyEl.style.display=visible===0?'block':'none';
  }

  tabs.forEach(function(t){
    t.addEventListener('click',function(){
      tabs.forEach(function(x){x.classList.remove('active');});
      t.classList.add('active');
      filterCards(t.dataset.cat);
    });
  });
})();

/* ── Sort ── */
(function(){
  var sel=document.getElementById('bsSortSelect');
  var grid=document.getElementById('bsGrid');
  if(!sel||!grid)return;
  sel.addEventListener('change',function(){
    var cards=Array.from(grid.querySelectorAll('.bsp-card'));
    var sort=sel.value;
    cards.sort(function(a,b){
      if(sort==='price_asc')  return +a.dataset.price - +b.dataset.price;
      if(sort==='price_desc') return +b.dataset.price - +a.dataset.price;
      if(sort==='rating')     return +b.dataset.rating - +a.dataset.rating;
      if(sort==='discount')   return +b.dataset.discount - +a.dataset.discount;
      return 0; // popular — keep original order
    });
    cards.forEach(function(c,i){
      c.style.transitionDelay=(i*40)+'ms';
      grid.appendChild(c);
    });
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>