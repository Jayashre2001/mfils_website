<?php
// blog.php — Mfills Blog
require_once __DIR__ . '/includes/functions.php';
startSession();
$pageTitle = 'Blog — Mfills Wellness';
require_once __DIR__ . '/includes/header.php';

/* ── Category definitions ── */
$categories = [
  'all'       => ['label' => 'All Posts',                'icon' => '📰', 'color' => 'green'],
  'health'    => ['label' => 'Health & Wellness',        'icon' => '🌿', 'color' => 'jade'],
  'nutrition' => ['label' => 'Nutrition & Supplements',  'icon' => '💊', 'color' => 'gold'],
  'business'  => ['label' => 'Business & Direct Selling','icon' => '📈', 'color' => 'indigo'],
  'success'   => ['label' => 'Success Stories',          'icon' => '🏆', 'color' => 'gold'],
  'training'  => ['label' => 'Training & Education',     'icon' => '🎓', 'color' => 'jade'],
  'updates'   => ['label' => 'Company Updates',          'icon' => '📣', 'color' => 'green'],
];

/* ── Sample posts — replace with DB query ── */
$posts = [
  [
    'id'       => 1,
    'slug'     => 'whey-protein-complete-guide',
    'cat'      => 'nutrition',
    'title'    => 'The Complete Guide to Whey Protein: What Every Fitness Enthusiast Needs to Know',
    'excerpt'  => 'From concentrate to isolate — we break down protein types, optimal timing, and how to choose the right formula for your goals.',
    'author'   => 'Mfills Team',
    'date'     => '2025-06-10',
    'read'     => '7 min read',
    'featured' => true,
    'img'      => 'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=900&q=70',
  ],
  [
    'id'       => 2,
    'slug'     => 'ashwagandha-stress-science',
    'cat'      => 'health',
    'title'    => 'Ashwagandha & Stress: What the Science Actually Says',
    'excerpt'  => 'Adaptogenic herbs are everywhere — but does KSM-66 ashwagandha really lower cortisol? We review the clinical evidence.',
    'author'   => 'Wellness Desk',
    'date'     => '2025-06-05',
    'read'     => '5 min read',
    'featured' => false,
    'img'      => 'https://images.unsplash.com/photo-1611073615830-9b2be67a4e72?w=600&q=70',
  ],
  [
    'id'       => 3,
    'slug'     => 'mbp-first-month-success',
    'cat'      => 'success',
    'title'    => '"My First ₹15,000 PSB in 30 Days" — Riya\'s Mfills Story',
    'excerpt'  => 'A college student from Bhubaneswar shares how she built a 12-member network and earned her first significant PSB in just one month.',
    'author'   => 'Mfills Community',
    'date'     => '2025-06-01',
    'read'     => '4 min read',
    'featured' => false,
    'img'      => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=600&q=70',
  ],
  [
    'id'       => 4,
    'slug'     => 'direct-selling-india-opportunity',
    'cat'      => 'business',
    'title'    => 'Why Direct Selling Is India\'s Fastest-Growing Business Model in 2025',
    'excerpt'  => 'IDSA reports, regulatory clarity, and real income data — understanding why millions of Indians are choosing direct selling.',
    'author'   => 'Business Desk',
    'date'     => '2025-05-28',
    'read'     => '6 min read',
    'featured' => false,
    'img'      => 'https://images.unsplash.com/photo-1556761175-4b46a572b786?w=600&q=70',
  ],
  [
    'id'       => 5,
    'slug'     => 'vitamin-d3-k2-why-together',
    'cat'      => 'nutrition',
    'title'    => 'Vitamin D3 + K2: Why These Two Nutrients Are Better Together',
    'excerpt'  => 'India has one of the world\'s highest Vitamin D deficiency rates. Here\'s why pairing it with K2 matters.',
    'author'   => 'Nutrition Team',
    'date'     => '2025-05-22',
    'read'     => '5 min read',
    'featured' => false,
    'img'      => 'https://images.unsplash.com/photo-1550572017-edd951b55104?w=600&q=70',
  ],
  [
    'id'       => 6,
    'slug'     => 'mfills-mshop-plus-launch',
    'cat'      => 'updates',
    'title'    => 'Introducing MShop Plus: Premium Products, Enhanced Rewards',
    'excerpt'  => 'MShop Plus brings a curated selection of premium wellness products with enhanced BV and exclusive partner benefits.',
    'author'   => 'Mfills Team',
    'date'     => '2025-05-18',
    'read'     => '3 min read',
    'featured' => false,
    'img'      => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=70',
  ],
  [
    'id'       => 7,
    'slug'     => 'onboarding-new-mbp-guide',
    'cat'      => 'training',
    'title'    => 'New MBP Onboarding: Your First 7 Days with Mfills',
    'excerpt'  => 'Just registered? This step-by-step guide walks you through your first week — profile setup to sharing your referral link effectively.',
    'author'   => 'Training Team',
    'date'     => '2025-05-14',
    'read'     => '8 min read',
    'featured' => false,
    'img'      => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=600&q=70',
  ],
  [
    'id'       => 8,
    'slug'     => 'omega-3-brain-heart-health',
    'cat'      => 'health',
    'title'    => 'Omega-3 and Your Heart: A Cardiologist\'s Perspective',
    'excerpt'  => 'Fish oil supplements are one of the most studied nutrients on the planet. We examine the evidence for cardiovascular and cognitive benefits.',
    'author'   => 'Health Desk',
    'date'     => '2025-05-10',
    'read'     => '6 min read',
    'featured' => false,
    'img'      => 'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?w=600&q=70',
  ],
  [
    'id'       => 9,
    'slug'     => 'psb-7-level-explained',
    'cat'      => 'business',
    'title'    => 'Understanding the 7-Level PSB Structure — With Real Numbers',
    'excerpt'  => 'We break down exactly how Partner Sales Bonus flows through 7 downline levels, with worked examples on what each level earns.',
    'author'   => 'Business Desk',
    'date'     => '2025-05-06',
    'read'     => '9 min read',
    'featured' => false,
    'img'      => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=600&q=70',
  ],
];

$activeCat = isset($_GET['cat']) && array_key_exists($_GET['cat'], $categories) ? $_GET['cat'] : 'all';
$filtered  = $activeCat === 'all' ? $posts : array_values(array_filter($posts, fn($p) => $p['cat'] === $activeCat));
$featured  = null;
$grid      = $filtered;
if ($activeCat === 'all') {
  foreach ($filtered as $i => $p) {
    if ($p['featured']) { $featured = $p; unset($grid[$i]); break; }
  }
  $grid = array_values($grid);
}

function catColor(string $cat): string {
  return match($cat) {
    'health','training'  => 'jade',
    'nutrition','success'=> 'gold',
    'business'           => 'indigo',
    default              => 'green',
  };
}
function fmtDate(string $d): string { return date('j M Y', strtotime($d)); }
function catLabel(string $cat, array $cats): string { return $cats[$cat]['label'] ?? ucfirst($cat); }
?>
<style>
/* ══ BLOG PAGE ══ */
.blog-hero {
  background: linear-gradient(135deg, var(--green-dd) 0%, var(--green-d) 55%, var(--green-m) 100%);
  position: relative; overflow: hidden;
  padding: 4rem 0 3.5rem; border-bottom: 3px solid var(--gold);
}
.blog-hero::before {
  content:''; position:absolute; inset:0; pointer-events:none;
  background-image:radial-gradient(circle,rgba(200,146,42,.08) 1.5px,transparent 1.5px);
  background-size:24px 24px;
}
.blog-hero-inner {
  position:relative; z-index:1; max-width:1200px; margin:0 auto; padding:0 1.5rem;
  display:flex; align-items:flex-end; justify-content:space-between; gap:1.5rem;
}
.blog-hero-tag {
  display:inline-flex; align-items:center; gap:.6rem;
  font-size:.6rem; font-weight:700; letter-spacing:.18em; text-transform:uppercase;
  color:var(--gold-ll); margin-bottom:1rem;
}
.blog-hero-tag::before,.blog-hero-tag::after{content:'';width:22px;height:1px;background:var(--gold-ll);}
.blog-hero h1 {
  font-family:'DM Serif Display',serif; font-size:clamp(2rem,4vw,3.2rem);
  font-weight:400; color:#fff; line-height:1.12; letter-spacing:-.025em; margin-bottom:.75rem;
}
.blog-hero h1 em{font-style:italic;color:var(--gold-ll);}
.blog-hero-sub{font-size:.88rem;color:rgba(255,255,255,.55);font-weight:300;max-width:480px;}
.blog-hero-right{display:flex;gap:2rem;flex-shrink:0;}
.blog-hero-stat-val{
  font-family:'Cinzel',serif;font-size:1.5rem;font-weight:900;color:var(--gold-ll);line-height:1;
}
.blog-hero-stat-label{
  font-size:.58rem;color:rgba(255,255,255,.45);letter-spacing:.1em;text-transform:uppercase;margin-top:.25rem;
}

/* ── Filter bar ── */
.blog-filter-bar {
  background:rgba(255,255,255,.97); border-bottom:1.5px solid var(--b);
  backdrop-filter:blur(12px); position:sticky; top:var(--navbar-h); z-index:100;
  overflow-x:auto; scrollbar-width:none;
}
.blog-filter-bar::-webkit-scrollbar{display:none;}
.blog-filter-inner {
  max-width:1200px; margin:0 auto; padding:0 1.5rem;
  display:flex; align-items:center;
}
.blog-filter-link {
  display:inline-flex; align-items:center; gap:.4rem;
  font-size:.62rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase;
  color:var(--t3); padding:.88rem 1rem;
  border-bottom:2px solid transparent;
  transition:color .18s,border-color .18s; white-space:nowrap; text-decoration:none;
}
.blog-filter-link .fi{font-size:.85rem;}
.blog-filter-link:hover{color:var(--green);}
.blog-filter-link.active{color:var(--green);border-bottom-color:var(--green);}
.blog-filter-sep{width:1px;height:18px;background:var(--b);flex-shrink:0;margin:0 .2rem;}
.blog-search-wrap{margin-left:auto;flex-shrink:0;padding:.55rem 0;}
.blog-search-input{
  border:1.5px solid var(--b);border-radius:20px;padding:.38rem 1rem;
  font-size:.78rem;color:var(--t);background:var(--g1);outline:none;
  font-family:'Outfit',sans-serif;transition:border-color .2s,box-shadow .2s;width:180px;
}
.blog-search-input:focus{border-color:var(--green-l);box-shadow:0 0 0 3px rgba(26,59,34,.08);background:#fff;}

/* ── Wrap ── */
.blog-wrap{max-width:1200px;margin:0 auto;padding:2.5rem 1.5rem 5rem;}

/* ── Featured ── */
.blog-featured {
  display:grid; grid-template-columns:1.1fr 1fr; gap:0;
  border-radius:18px; overflow:hidden; border:1.5px solid var(--b);
  box-shadow:0 4px 24px rgba(26,59,34,.08); margin-bottom:3rem; background:#fff;
  text-decoration:none; color:inherit;
  transition:box-shadow .25s,transform .25s;
}
.blog-featured:hover{box-shadow:0 14px 44px rgba(26,59,34,.16);transform:translateY(-3px);}
.blog-featured-img{position:relative;min-height:360px;overflow:hidden;}
.blog-featured-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s;display:block;}
.blog-featured:hover .blog-featured-img img{transform:scale(1.04);}
.blog-featured-overlay{
  position:absolute;inset:0;
  background:linear-gradient(to right,rgba(18,42,24,.3) 0%,transparent 70%);
}
.blog-featured-badge{
  position:absolute;top:1.25rem;left:1.25rem;
  background:var(--gold);color:#fff;
  font-size:.55rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
  padding:.28rem .75rem;border-radius:2px;
}
.blog-featured-body{padding:2.25rem 2rem;display:flex;flex-direction:column;justify-content:center;}
.blog-featured-cat{
  font-size:.58rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;margin-bottom:.75rem;
}
.fcat-jade{color:var(--jade);} .fcat-gold{color:var(--gold-d);} .fcat-green{color:var(--green-m);} .fcat-indigo{color:var(--green-d);}
.blog-featured-title{
  font-family:'DM Serif Display',serif;
  font-size:clamp(1.3rem,2.2vw,1.85rem);font-weight:400;color:var(--t);
  line-height:1.25;letter-spacing:-.02em;margin-bottom:1rem;
}
.blog-featured-excerpt{font-size:.85rem;color:var(--t3);line-height:1.75;font-weight:300;margin-bottom:1.5rem;}
.blog-featured-meta{display:flex;align-items:center;gap:1rem;font-size:.7rem;color:var(--t3);flex-wrap:wrap;}
.blog-read-btn{
  display:inline-flex;align-items:center;gap:.4rem;
  font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  color:var(--green);border-bottom:1.5px solid var(--green-l);padding-bottom:1px;margin-top:1.5rem;
  transition:color .15s,border-color .15s;
}
.blog-featured:hover .blog-read-btn{color:var(--green-m);border-color:var(--green);}

/* ── Section head ── */
.blog-section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem;gap:1rem;}
.blog-section-title{
  font-family:'DM Serif Display',serif;font-size:1.4rem;font-weight:400;color:var(--t);
  display:flex;align-items:center;gap:.6rem;white-space:nowrap;
}
.blog-section-title::after{content:'';flex:1;height:1px;background:var(--b);}
.blog-section-count{font-size:.68rem;font-weight:600;color:var(--t3);letter-spacing:.06em;white-space:nowrap;}

/* ── Grid ── */
.blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:2rem;}

/* ── Card ── */
.blog-card{
  background:#fff;border:1.5px solid var(--b);border-radius:16px;overflow:hidden;
  transition:transform .22s,box-shadow .22s;
  text-decoration:none;color:inherit;display:flex;flex-direction:column;
}
.blog-card:hover{transform:translateY(-5px);box-shadow:0 12px 36px rgba(26,59,34,.13);}
.blog-card-img{position:relative;height:185px;overflow:hidden;}
.blog-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s;display:block;}
.blog-card:hover .blog-card-img img{transform:scale(1.06);}
.blog-card-pill{
  position:absolute;top:.85rem;left:.85rem;
  font-size:.55rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
  padding:.25rem .65rem;border-radius:2px;
}
.pill-jade{background:rgba(15,123,92,.9);color:#fff;}
.pill-gold{background:rgba(184,128,24,.9);color:#fff;}
.pill-green{background:rgba(26,59,34,.88);color:#fff;}
.pill-indigo{background:rgba(26,59,34,.88);color:#fff;}
.blog-card-body{padding:1.25rem 1.2rem;flex:1;display:flex;flex-direction:column;}
.blog-card-title{
  font-family:'DM Serif Display',serif;
  font-size:1rem;font-weight:400;color:var(--t);
  line-height:1.3;letter-spacing:-.01em;margin-bottom:.65rem;flex:1;
  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;
}
.blog-card-excerpt{
  font-size:.77rem;color:var(--t3);line-height:1.65;font-weight:300;margin-bottom:1rem;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.blog-card-meta{
  display:flex;align-items:center;justify-content:space-between;
  font-size:.65rem;color:var(--t4);
  border-top:1px solid var(--g2);padding-top:.75rem;margin-top:auto;
}
.blog-card-meta-left{display:flex;align-items:center;gap:.5rem;}
.blog-card-dot{
  width:22px;height:22px;border-radius:50%;
  background:var(--green);color:#fff;
  font-family:'Cinzel',serif;font-size:.6rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.blog-card-read{font-size:.62rem;font-weight:600;color:var(--green-m);letter-spacing:.04em;}

/* ── Load more ── */
.blog-load-more{text-align:center;margin-top:1.25rem;}
.blog-load-btn{
  display:inline-flex;align-items:center;gap:.5rem;
  background:transparent;border:1.5px solid var(--b);border-radius:8px;padding:.72rem 2rem;
  font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  color:var(--t2);cursor:pointer;font-family:'Outfit',sans-serif;
  transition:background .18s,border-color .18s,color .18s;
}
.blog-load-btn:hover{background:var(--g1);border-color:var(--green-l);color:var(--green);}

/* ── Empty ── */
.blog-empty{
  text-align:center;padding:4rem 1rem;
  background:var(--g1);border-radius:16px;border:1.5px solid var(--b);
}
.blog-empty-icon{font-size:2.5rem;margin-bottom:1rem;}
.blog-empty-title{font-family:'Cinzel',serif;font-size:1rem;color:var(--green-d);margin-bottom:.5rem;}
.blog-empty-sub{font-size:.82rem;color:var(--t3);}

/* ── Newsletter ── */
.blog-newsletter{
  background:linear-gradient(135deg,var(--green-dd),var(--green-d));
  border-radius:16px;padding:2.5rem 2rem;
  display:flex;align-items:center;justify-content:space-between;gap:2rem;
  margin-top:3rem;border:1.5px solid rgba(200,146,42,.2);
  position:relative;overflow:hidden;
}
.blog-newsletter::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background-image:radial-gradient(circle,rgba(200,146,42,.06) 1.5px,transparent 1.5px);
  background-size:22px 22px;
}
.blog-newsletter-left{position:relative;z-index:1;}
.blog-newsletter-title{
  font-family:'DM Serif Display',serif;font-size:1.5rem;font-weight:400;
  color:#fff;margin-bottom:.4rem;
}
.blog-newsletter-title em{font-style:italic;color:var(--gold-ll);}
.blog-newsletter-sub{font-size:.8rem;color:rgba(255,255,255,.55);font-weight:300;}
.blog-newsletter-form{display:flex;gap:.5rem;flex-shrink:0;position:relative;z-index:1;}
.blog-newsletter-input{
  border:1.5px solid rgba(200,146,42,.3);border-radius:8px;
  padding:.65rem 1.1rem;font-size:.82rem;background:rgba(255,255,255,.08);
  color:#fff;outline:none;font-family:'Outfit',sans-serif;width:240px;
  transition:border-color .2s,background .2s;
}
.blog-newsletter-input::placeholder{color:rgba(255,255,255,.35);}
.blog-newsletter-input:focus{border-color:var(--gold-l);background:rgba(255,255,255,.14);}
.blog-newsletter-btn{
  background:var(--gold);color:var(--t);border:none;border-radius:8px;
  padding:.65rem 1.4rem;font-size:.75rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;
  cursor:pointer;font-family:'Outfit',sans-serif;transition:background .18s;
}
.blog-newsletter-btn:hover{background:var(--gold-l);}

/* ── Responsive ── */
@media(max-width:1024px){.blog-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:900px){
  .blog-hero-right{display:none;}
  .blog-featured{grid-template-columns:1fr;}
  .blog-featured-img{min-height:240px;}
}
@media(max-width:768px){
  .blog-wrap{padding:2rem 1rem 3.5rem;}
  .blog-grid{grid-template-columns:repeat(2,1fr);gap:1rem;}
  .blog-newsletter{flex-direction:column;}
  .blog-newsletter-form{width:100%;}
  .blog-newsletter-input{flex:1;width:auto;}
  .blog-featured-body{padding:1.5rem 1.25rem;}
}
@media(max-width:540px){
  .blog-grid{grid-template-columns:1fr;}
  .blog-filter-link{padding:.88rem .75rem;font-size:.56rem;}
}
</style>

<!-- HERO -->
<div class="blog-hero">
  <div class="blog-hero-inner">
    <div>
      <div class="blog-hero-tag">Mfills Knowledge Hub</div>
      <h1>The Mfills <em>Blog</em></h1>
      <p class="blog-hero-sub">Wellness insights, business guidance, success stories, and company news — everything you need to thrive with Mfills.</p>
    </div>
    <div class="blog-hero-right">
      <div>
        <div class="blog-hero-stat-val"><?= count($posts) ?>+</div>
        <div class="blog-hero-stat-label">Articles</div>
      </div>
      <div>
        <div class="blog-hero-stat-val"><?= count($categories) - 1 ?></div>
        <div class="blog-hero-stat-label">Categories</div>
      </div>
      <div>
        <div class="blog-hero-stat-val">Weekly</div>
        <div class="blog-hero-stat-label">New Posts</div>
      </div>
    </div>
  </div>
</div>

<!-- FILTER BAR -->
<div class="blog-filter-bar">
  <div class="blog-filter-inner">
    <?php foreach ($categories as $slug => $cat): ?>
      <a href="<?= APP_URL ?>/blog.php<?= $slug !== 'all' ? '?cat=' . $slug : '' ?>"
         class="blog-filter-link <?= $activeCat === $slug ? 'active' : '' ?>">
        <span class="fi"><?= $cat['icon'] ?></span><?= $cat['label'] ?>
      </a>
      <?php if ($slug === 'all'): ?><div class="blog-filter-sep"></div><?php endif; ?>
    <?php endforeach; ?>
    <div class="blog-search-wrap">
      <input type="text" class="blog-search-input" id="blogSearch" placeholder="Search posts…" autocomplete="off">
    </div>
  </div>
</div>

<!-- CONTENT -->
<div class="blog-wrap">

  <?php if ($activeCat === 'all' && $featured): ?>
  <!-- Featured -->
  <a href="<?= APP_URL ?>/blog_detail.php?slug=<?= $featured['slug'] ?>" class="blog-featured rv">
    <div class="blog-featured-img">
      <img src="<?= $featured['img'] ?>" alt="<?= htmlspecialchars($featured['title']) ?>" loading="eager">
      <div class="blog-featured-overlay"></div>
      <div class="blog-featured-badge">✦ Featured</div>
    </div>
    <div class="blog-featured-body">
      <div class="blog-featured-cat fcat-<?= catColor($featured['cat']) ?>">
        <?= $categories[$featured['cat']]['icon'] ?> &nbsp;<?= catLabel($featured['cat'], $categories) ?>
      </div>
      <div class="blog-featured-title"><?= htmlspecialchars($featured['title']) ?></div>
      <div class="blog-featured-excerpt"><?= htmlspecialchars($featured['excerpt']) ?></div>
      <div class="blog-featured-meta">
        <span>✍&thinsp;<?= htmlspecialchars($featured['author']) ?></span>
        <span>📅&thinsp;<?= fmtDate($featured['date']) ?></span>
        <span>⏱&thinsp;<?= $featured['read'] ?></span>
      </div>
      <div class="blog-read-btn">Read Article &rarr;</div>
    </div>
  </a>
  <?php endif; ?>

  <!-- Post grid heading -->
  <div class="blog-section-head">
    <div class="blog-section-title">
      <?= $activeCat === 'all' ? 'Latest Articles' : htmlspecialchars(catLabel($activeCat, $categories)) ?>
    </div>
    <div class="blog-section-count"><?= count($filtered) ?> post<?= count($filtered) !== 1 ? 's' : '' ?></div>
  </div>

  <?php if (empty($grid)): ?>
  <div class="blog-empty">
    <div class="blog-empty-icon">📭</div>
    <div class="blog-empty-title">No posts yet in this category</div>
    <div class="blog-empty-sub">Check back soon — new content is added every week.</div>
  </div>
  <?php else: ?>
  <div class="blog-grid" id="blogGrid">
    <?php foreach ($grid as $i => $post): ?>
    <a href="<?= APP_URL ?>/blog_detail.php?slug=<?= $post['slug'] ?>"
       class="blog-card rv"
       data-title="<?= strtolower(htmlspecialchars($post['title'])) ?>">
      <div class="blog-card-img">
        <img src="<?= $post['img'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
        <div class="blog-card-pill pill-<?= catColor($post['cat']) ?>">
          <?= $categories[$post['cat']]['icon'] ?> <?= catLabel($post['cat'], $categories) ?>
        </div>
      </div>
      <div class="blog-card-body">
        <div class="blog-card-title"><?= htmlspecialchars($post['title']) ?></div>
        <div class="blog-card-excerpt"><?= htmlspecialchars($post['excerpt']) ?></div>
        <div class="blog-card-meta">
          <div class="blog-card-meta-left">
            <div class="blog-card-dot"><?= strtoupper(substr($post['author'], 0, 1)) ?></div>
            <span><?= htmlspecialchars($post['author']) ?> &middot; <?= fmtDate($post['date']) ?></span>
          </div>
          <span class="blog-card-read">⏱ <?= $post['read'] ?></span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Newsletter -->
  <div class="blog-newsletter rv">
    <div class="blog-newsletter-left">
      <div class="blog-newsletter-title">Stay <em>Informed</em></div>
      <div class="blog-newsletter-sub">Get the latest wellness articles and Mfills updates delivered weekly.</div>
    </div>
    <form class="blog-newsletter-form" onsubmit="return blogSubscribe(event)">
      <input type="email" class="blog-newsletter-input" placeholder="Your email address" required>
      <button type="submit" class="blog-newsletter-btn">Subscribe</button>
    </form>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
const ro = new IntersectionObserver(entries => {
  entries.forEach(e => { if (!e.isIntersecting) return; e.target.classList.add('on'); ro.unobserve(e.target); });
}, { threshold: .06 });
document.querySelectorAll('.rv').forEach(el => ro.observe(el));

(function () {
  var inp = document.getElementById('blogSearch');
  if (!inp) return;
  inp.addEventListener('input', function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('#blogGrid .blog-card').forEach(function (c) {
      c.style.display = (!q || c.dataset.title.includes(q)) ? '' : 'none';
    });
  });
})();

function blogSubscribe(e) {
  e.preventDefault();
  var btn = e.target.querySelector('button');
  btn.textContent = '✓ Subscribed!';
  btn.style.background = 'var(--jade)'; btn.style.color = '#fff'; btn.disabled = true;
  return false;
}
</script>