<?php
// blog-detail.php — Mfills Blog Detail Page
require_once __DIR__ . '/includes/functions.php';
startSession();

// ── Category map ─────────────────────────────────────────────────────────────
$categories = [
  'health'    => ['label' => 'Health & Wellness',       'icon' => '🌿', 'color' => '#0F7B5C'],
  'nutrition' => ['label' => 'Nutrition & Supplements', 'icon' => '💊', 'color' => '#B88018'],
  'business'  => ['label' => 'Business & Direct Selling','icon' => '📈', 'color' => '#1C3D24'],
  'success'   => ['label' => 'Success Stories',         'icon' => '⭐', 'color' => '#C89230'],
  'training'  => ['label' => 'Training & Education',    'icon' => '🎓', 'color' => '#2E6244'],
  'updates'   => ['label' => 'Company Updates',         'icon' => '📢', 'color' => '#4E9A60'],
];

// ── Post database (matches blog.php — in production query DB by slug) ────────
$postDb = [
  'power-of-ashwagandha' => [
    'cat'     => 'health',
    'title'   => 'The Power of Ashwagandha: India\'s Ancient Adaptogen Meets Modern Science',
    'excerpt' => 'Discover how KSM-66 Ashwagandha supports stress relief, energy, and hormonal balance backed by over 20 clinical trials.',
    'author'  => 'Mfills Wellness Team',
    'date'    => 'March 12, 2026',
    'read'    => '6 min read',
    'img'     => 'https://images.unsplash.com/photo-1611073615830-9b2be67a4e72?w=1200&q=80',
    'tags'    => ['Ashwagandha','Adaptogens','Stress Relief','Ayurveda'],
    'content' => [
      ['type' => 'lead', 'text' => 'For thousands of years, Ayurvedic physicians have prescribed Ashwagandha — <em>Withania somnifera</em> — as a <strong>rasayana</strong>: a rejuvenating herb that promotes longevity, vitality, and resilience. Today, modern science is catching up.'],
      ['type' => 'h2',   'text' => 'What Makes KSM-66 Different?'],
      ['type' => 'p',    'text' => 'Not all ashwagandha extracts are created equal. KSM-66 is a patented, full-spectrum root extract standardised to ≥5% withanolides — the active compounds responsible for ashwagandha\'s therapeutic effects. It is produced using a proprietary extraction process that preserves the natural balance of the root\'s constituents without chemical solvents.'],
      ['type' => 'p',    'text' => 'With over 22 published, peer-reviewed clinical trials, KSM-66 is the most extensively studied ashwagandha extract on the market — used in the Mfills Ashwagandha formulation precisely for this reason.'],
      ['type' => 'h2',   'text' => 'Evidence-Backed Benefits'],
      ['type' => 'list', 'items' => [
        '<strong>Stress & Anxiety:</strong> Reduces serum cortisol levels by up to 27.9% in stressed adults (Chandrasekhar et al., 2012)',
        '<strong>Energy & Endurance:</strong> Significantly improves VO₂ max and cardiorespiratory endurance in healthy athletes',
        '<strong>Muscle Recovery:</strong> Increases muscle mass and strength when combined with resistance training',
        '<strong>Cognitive Function:</strong> Improves memory, reaction time, and cognitive task performance in healthy adults',
        '<strong>Sleep Quality:</strong> Reduces time to fall asleep and improves overall sleep quality in insomnia patients',
        '<strong>Hormonal Balance:</strong> Supports healthy testosterone levels and reproductive health in men',
      ]],
      ['type' => 'h2',   'text' => 'How to Take It'],
      ['type' => 'p',    'text' => 'Most clinical studies use dosages of 300–600 mg of KSM-66 extract daily, taken with meals. Ashwagandha is an adaptogen — meaning its effects build over time. Most people notice meaningful changes in stress resilience and energy levels after 4–8 weeks of consistent use.'],
      ['type' => 'callout', 'text' => '💡 <strong>Mfills Tip:</strong> Ashwagandha is best taken with a small meal containing healthy fats for optimal withanolide absorption. Consistency matters more than timing.'],
      ['type' => 'h2',   'text' => 'Who Should Consider It?'],
      ['type' => 'p',    'text' => 'Ashwagandha is suitable for most healthy adults experiencing stress, fatigue, or performance demands. It is particularly beneficial for working professionals, athletes, students, and anyone navigating high-pressure periods. As always, consult your healthcare provider if you are pregnant, breastfeeding, or on medication for thyroid or autoimmune conditions.'],
      ['type' => 'p',    'text' => 'The Mfills Ashwagandha KSM-66 capsule delivers a full 600 mg of the clinically validated extract per serving — available now on MShop.'],
    ],
    'related' => ['vitamin-d3-deficiency','omega3-benefits','gut-health-guide'],
  ],
  'whey-protein-guide' => [
    'cat'     => 'nutrition',
    'title'   => 'Whey Protein 101: Concentrate vs Isolate vs Hydrolysate',
    'excerpt' => 'Breaking down every form of whey protein so you can make the right choice for your goals.',
    'author'  => 'Dr. Priya Nair',
    'date'    => 'March 8, 2026',
    'read'    => '5 min read',
    'img'     => 'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=1200&q=80',
    'tags'    => ['Protein','Nutrition','Muscle','Whey'],
    'content' => [
      ['type' => 'lead', 'text' => 'Walk into any supplement store and you\'ll find dozens of whey protein products. But <strong>which form is right for your goals</strong> — and what does the label actually mean?'],
      ['type' => 'h2',   'text' => 'The Three Forms of Whey'],
      ['type' => 'p',    'text' => 'Whey is a by-product of cheese production. It is separated from the curd, filtered, and processed into three distinct forms depending on how many times it is filtered and whether it undergoes further enzymatic treatment.'],
      ['type' => 'h2',   'text' => 'Whey Concentrate (WPC)'],
      ['type' => 'p',    'text' => 'Contains 70–80% protein by weight, with small amounts of lactose and fat retained. The most cost-effective option, with a creamy texture and good amino acid profile. Ideal for most people with no lactose sensitivity.'],
      ['type' => 'h2',   'text' => 'Whey Isolate (WPI)'],
      ['type' => 'p',    'text' => 'Further filtered to ≥90% protein by weight with minimal lactose and fat. Digests faster and is a better choice for lactose-sensitive individuals or those in a strict caloric deficit. The Mfills Whey Protein Concentrate is formulated at a 80% protein purity level for the optimal balance of quality and value.'],
      ['type' => 'h2',   'text' => 'Whey Hydrolysate (WPH)'],
      ['type' => 'p',    'text' => 'Pre-digested via enzymatic hydrolysis for the fastest absorption rate. Premium-priced and often used in medical nutrition or by competitive athletes needing ultra-rapid post-workout recovery.'],
      ['type' => 'callout', 'text' => '💡 <strong>Mfills Tip:</strong> For most people pursuing fitness and wellness, a high-quality Whey Concentrate offers the best value. Unless you have lactose intolerance or specific athletic needs, WPI or WPH are unlikely to produce meaningfully better results.'],
    ],
    'related' => ['omega3-benefits','power-of-ashwagandha','gut-health-guide'],
  ],
];

// ── Resolve post from slug ────────────────────────────────────────────────────
$slug = trim($_GET['slug'] ?? '');
$post = $postDb[$slug] ?? null;

// If slug not found, 404-style fallback
if (!$post) {
  $pageTitle = 'Post Not Found — Mfills Blog';
  require_once __DIR__ . '/includes/header.php';
  echo '<div class="container page-wrap" style="text-align:center;padding:5rem 1.5rem">
    <div style="font-size:3rem;margin-bottom:1rem">📭</div>
    <h2 style="font-family:Cinzel,serif;color:var(--green);margin-bottom:.5rem">Post Not Found</h2>
    <p style="color:var(--t3);margin-bottom:1.5rem">This article does not exist or may have been moved.</p>
    <a href="' . APP_URL . '/blog.php" class="btn btn-primary">← Back to Blog</a>
  </div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

$cat       = $categories[$post['cat']];
$pageTitle = strip_tags($post['title']) . ' — Mfills Blog';

// ── Resolve related posts from the same $postDb ───────────────────────────────
$relatedPosts = [];
foreach (($post['related'] ?? []) as $rs) {
  if (isset($postDb[$rs])) {
    $relatedPosts[$rs] = $postDb[$rs];
  }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ══════════════════════════════════════
   BLOG DETAIL PAGE
══════════════════════════════════════ */

/* ── Hero ── */
.bd-hero {
  position: relative; overflow: hidden;
  height: 480px;
}
.bd-hero-img {
  width: 100%; height: 100%; object-fit: cover; object-position: center;
  display: block;
}
.bd-hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to bottom,
    rgba(18,42,24,.15) 0%,
    rgba(18,42,24,.45) 50%,
    rgba(18,42,24,.88) 100%);
}
.bd-hero-content {
  position: absolute; bottom: 0; left: 0; right: 0;
  padding: 2.5rem 0 2.25rem;
  border-bottom: 3px solid var(--gold);
}
.bd-hero-inner {
  max-width: 860px; margin: 0 auto; padding: 0 1.5rem;
}
.bd-hero-cat {
  display: inline-flex; align-items: center; gap: .4rem;
  font-size: .6rem; font-weight: 700; letter-spacing: .14em;
  text-transform: uppercase; color: var(--gold-ll); margin-bottom: .85rem;
}
.bd-hero-title {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(1.6rem, 3.5vw, 2.6rem);
  color: #fff; font-weight: 400; line-height: 1.15;
  letter-spacing: -.025em; margin-bottom: 1.1rem;
}
.bd-hero-meta {
  display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.5rem;
  font-size: .75rem; color: rgba(255,255,255,.6);
}
.bd-hero-meta span { display: flex; align-items: center; gap: .35rem; }

/* ── Breadcrumb ── */
.bd-breadcrumb {
  background: var(--g1); border-bottom: 1px solid var(--b);
  padding: .65rem 0;
}
.bd-breadcrumb-inner {
  max-width: 860px; margin: 0 auto; padding: 0 1.5rem;
  display: flex; align-items: center; gap: .5rem;
  font-size: .7rem; color: var(--t3);
}
.bd-breadcrumb a { color: var(--t3); transition: color .15s; }
.bd-breadcrumb a:hover { color: var(--green); }
.bd-breadcrumb-sep { opacity: .4; }

/* ── Layout ── */
.bd-layout {
  max-width: 1100px; margin: 0 auto;
  padding: 3rem 1.5rem 5rem;
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 3.5rem;
  align-items: start;
}

/* ── Article body ── */
.bd-article {}

/* Article lead paragraph */
.bd-lead {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(1.05rem, 1.8vw, 1.25rem);
  color: var(--t); line-height: 1.75; font-weight: 400;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1.5px solid var(--b);
}

/* Article headings */
.bd-h2 {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(1.3rem, 2.2vw, 1.7rem);
  color: var(--green-d); font-weight: 400;
  line-height: 1.2; letter-spacing: -.02em;
  margin: 2.25rem 0 .9rem;
}

/* Article paragraphs */
.bd-p {
  font-size: .95rem; color: var(--t2); line-height: 1.95;
  font-weight: 300; margin-bottom: 1.25rem;
}
.bd-p strong { color: var(--t); font-weight: 600; }
.bd-p em { font-style: italic; color: var(--green-d); }

/* Article list */
.bd-list {
  margin: .5rem 0 1.5rem 0;
  padding: 0; list-style: none;
}
.bd-list li {
  display: flex; gap: .75rem; align-items: flex-start;
  font-size: .92rem; color: var(--t2); line-height: 1.75;
  font-weight: 300; margin-bottom: .65rem;
  padding-bottom: .65rem; border-bottom: 1px solid var(--g2);
}
.bd-list li:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.bd-list li::before {
  content: ''; width: 7px; height: 7px; border-radius: 50%;
  background: var(--green-l); flex-shrink: 0; margin-top: .55rem;
}

/* Callout block */
.bd-callout {
  background: linear-gradient(135deg, rgba(26,59,34,.06), rgba(26,59,34,.03));
  border-left: 4px solid var(--green);
  border-radius: 0 12px 12px 0;
  padding: 1.25rem 1.5rem;
  margin: 2rem 0;
  font-size: .9rem; color: var(--t2); line-height: 1.75;
}
.bd-callout strong { color: var(--green-d); font-weight: 600; }

/* ── Tags ── */
.bd-tags {
  display: flex; flex-wrap: wrap; gap: .45rem;
  margin: 2.5rem 0 2rem;
  padding-top: 1.5rem;
  border-top: 1.5px solid var(--b);
}
.bd-tag {
  font-size: .62rem; font-weight: 700; letter-spacing: .07em;
  text-transform: uppercase; background: var(--g1); color: var(--t3);
  padding: .28rem .7rem; border-radius: 20px; border: 1px solid var(--b);
  transition: background .15s, color .15s;
}
.bd-tag:hover { background: rgba(26,59,34,.08); color: var(--green-m); }

/* ── Author box ── */
.bd-author {
  display: flex; gap: 1rem; align-items: center;
  background: var(--g1); border: 1.5px solid var(--b);
  border-radius: 14px; padding: 1.25rem 1.35rem;
  margin-top: 2.5rem;
}
.bd-author-avatar {
  width: 52px; height: 52px; border-radius: 50%;
  background: linear-gradient(135deg, var(--green-dd), var(--green-m));
  display: flex; align-items: center; justify-content: center;
  font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700;
  color: var(--gold-ll); flex-shrink: 0;
}
.bd-author-name {
  font-size: .85rem; font-weight: 700; color: var(--t); margin-bottom: .18rem;
}
.bd-author-role { font-size: .72rem; color: var(--t3); }

/* ── Share strip ── */
.bd-share {
  display: flex; align-items: center; gap: .75rem;
  margin-top: 2rem; padding-top: 1.5rem;
  border-top: 1px solid var(--b);
}
.bd-share-label {
  font-size: .68rem; font-weight: 700; letter-spacing: .08em;
  text-transform: uppercase; color: var(--t3);
}
.bd-share-btn {
  display: inline-flex; align-items: center; gap: .4rem;
  font-size: .7rem; font-weight: 600; letter-spacing: .05em;
  padding: .42rem .85rem; border-radius: 8px; border: 1.5px solid var(--b);
  color: var(--t2); background: #fff; cursor: pointer;
  transition: all .18s; text-decoration: none;
}
.bd-share-btn:hover { background: var(--green); color: #fff; border-color: var(--green); }

/* ── Sidebar ── */
.bd-sidebar { position: sticky; top: calc(var(--navbar-h) + 1.5rem); }

.bd-sidebar-card {
  background: #fff; border: 1.5px solid var(--b);
  border-radius: 14px; overflow: hidden; margin-bottom: 1.5rem;
}
.bd-sidebar-head {
  background: var(--g2); padding: .75rem 1.1rem;
  font-family: 'Cinzel', serif; font-size: .72rem; font-weight: 700;
  color: var(--green-d); letter-spacing: .05em; text-transform: uppercase;
  border-bottom: 1px solid var(--b);
}
.bd-sidebar-body { padding: 1rem 1.1rem; }

/* Sidebar: in this article / ToC */
.bd-toc { list-style: none; padding: 0; }
.bd-toc li {
  padding: .5rem 0; border-bottom: 1px solid var(--g2);
  font-size: .8rem; color: var(--t2);
}
.bd-toc li:last-child { border-bottom: none; }
.bd-toc a { color: var(--t2); transition: color .15s; }
.bd-toc a:hover { color: var(--green); }
.bd-toc-num {
  font-family: 'Cinzel', serif; font-size: .65rem; font-weight: 700;
  color: var(--gold); margin-right: .5rem;
}

/* Sidebar: category badge */
.bd-cat-pill {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .55rem 1rem; border-radius: 8px;
  background: rgba(26,59,34,.07); color: var(--green-d);
  font-size: .78rem; font-weight: 600; width: 100%;
  text-decoration: none; transition: background .15s;
}
.bd-cat-pill:hover { background: rgba(26,59,34,.13); color: var(--green-d); }

/* Sidebar: CTA */
.bd-sidebar-cta {
  background: linear-gradient(135deg, var(--green-dd), var(--green-d));
  border-radius: 14px; padding: 1.6rem 1.25rem; text-align: center;
  border: 1.5px solid rgba(200,146,42,.2);
}
.bd-sidebar-cta-icon { font-size: 2rem; margin-bottom: .65rem; }
.bd-sidebar-cta h4 {
  font-family: 'Cinzel', serif; font-size: .82rem; font-weight: 700;
  color: var(--gold-ll); letter-spacing: .04em; margin-bottom: .5rem;
}
.bd-sidebar-cta p { font-size: .75rem; color: rgba(255,255,255,.55); margin-bottom: 1rem; }

/* ── Related posts ── */
.bd-related { margin-top: 3.5rem; }
.bd-related-head {
  font-family: 'Cinzel', serif; font-size: .82rem; font-weight: 700;
  color: var(--green); letter-spacing: .06em; text-transform: uppercase;
  display: flex; align-items: center; gap: .6rem; margin-bottom: 1.5rem;
}
.bd-related-head::after {
  content: ''; flex: 1; height: 1.5px; background: var(--b);
}
.bd-related-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
}
.bd-related-card {
  background: #fff; border: 1.5px solid var(--b);
  border-radius: 14px; overflow: hidden;
  transition: transform .2s, box-shadow .2s; display: block;
  text-decoration: none; color: inherit;
}
.bd-related-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 28px rgba(26,59,34,.11);
}
.bd-related-img { width: 100%; height: 140px; object-fit: cover; display: block; }
.bd-related-body { padding: 1rem 1.1rem; }
.bd-related-cat {
  font-size: .58rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--green-m); margin-bottom: .35rem;
}
.bd-related-title {
  font-family: 'DM Serif Display', serif;
  font-size: .92rem; font-weight: 400; color: var(--t);
  line-height: 1.35; letter-spacing: -.01em;
  display: -webkit-box; -webkit-line-clamp: 3;
  -webkit-box-orient: vertical; overflow: hidden;
}
.bd-related-meta {
  font-size: .68rem; color: var(--t3); margin-top: .6rem;
}

/* ── Progress bar ── */
.bd-progress {
  position: fixed; top: var(--navbar-h); left: 0; right: 0;
  height: 3px; background: transparent; z-index: 150;
}
.bd-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--green), var(--gold));
  width: 0%; transition: width .1s linear;
}

/* ── Responsive ── */
@media(max-width:900px) {
  .bd-layout { grid-template-columns: 1fr; gap: 2rem; }
  .bd-sidebar { position: static; }
  .bd-hero { height: 360px; }
  .bd-related-grid { grid-template-columns: repeat(2, 1fr); }
}
@media(max-width:600px) {
  .bd-hero { height: 280px; }
  .bd-hero-title { font-size: 1.4rem; }
  .bd-layout { padding: 2rem 1rem 3.5rem; }
  .bd-related-grid { grid-template-columns: 1fr; }
  .bd-share { flex-wrap: wrap; }
}
</style>

<!-- ══ READING PROGRESS BAR ══ -->
<div class="bd-progress"><div class="bd-progress-bar" id="bdProgress"></div></div>

<!-- ══ HERO ══ -->
<div class="bd-hero">
  <img class="bd-hero-img" src="<?= htmlspecialchars($post['img']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
  <div class="bd-hero-overlay"></div>
  <div class="bd-hero-content">
    <div class="bd-hero-inner">
      <div class="bd-hero-cat"><?= $cat['icon'] ?> &nbsp;<?= $cat['label'] ?></div>
      <h1 class="bd-hero-title"><?= htmlspecialchars($post['title']) ?></h1>
      <div class="bd-hero-meta">
        <span>✍️ <?= htmlspecialchars($post['author']) ?></span>
        <span>📅 <?= $post['date'] ?></span>
        <span>⏱️ <?= $post['read'] ?></span>
      </div>
    </div>
  </div>
</div>

<!-- ══ BREADCRUMB ══ -->
<div class="bd-breadcrumb">
  <div class="bd-breadcrumb-inner">
    <a href="<?= APP_URL ?>/">Home</a>
    <span class="bd-breadcrumb-sep">›</span>
    <a href="<?= APP_URL ?>/blog.php">Blog</a>
    <span class="bd-breadcrumb-sep">›</span>
    <a href="<?= APP_URL ?>/blog.php?cat=<?= $post['cat'] ?>"><?= $cat['label'] ?></a>
    <span class="bd-breadcrumb-sep">›</span>
    <span><?= mb_strimwidth(strip_tags($post['title']), 0, 50, '…') ?></span>
  </div>
</div>

<!-- ══ LAYOUT ══ -->
<div class="bd-layout">

  <!-- ── ARTICLE ── -->
  <article class="bd-article" id="bdArticle">

    <?php foreach ($post['content'] as $block): ?>

    <?php if ($block['type'] === 'lead'): ?>
      <p class="bd-lead"><?= $block['text'] ?></p>

    <?php elseif ($block['type'] === 'h2'): ?>
      <h2 class="bd-h2"><?= htmlspecialchars($block['text']) ?></h2>

    <?php elseif ($block['type'] === 'p'): ?>
      <p class="bd-p"><?= $block['text'] ?></p>

    <?php elseif ($block['type'] === 'list'): ?>
      <ul class="bd-list">
        <?php foreach ($block['items'] as $item): ?>
        <li><?= $item ?></li>
        <?php endforeach; ?>
      </ul>

    <?php elseif ($block['type'] === 'callout'): ?>
      <div class="bd-callout"><?= $block['text'] ?></div>

    <?php endif; ?>

    <?php endforeach; ?>

    <!-- Tags -->
    <div class="bd-tags">
      <?php foreach ($post['tags'] as $tag): ?>
      <span class="bd-tag"><?= htmlspecialchars($tag) ?></span>
      <?php endforeach; ?>
    </div>

    <!-- Author -->
    <div class="bd-author">
      <?php
        $initials = implode('', array_map(fn($w) => strtoupper($w[0]),
          array_filter(explode(' ', $post['author']), fn($w) => strlen($w) > 0)));
        $initials = mb_substr($initials, 0, 2);
      ?>
      <div class="bd-author-avatar"><?= $initials ?></div>
      <div>
        <div class="bd-author-name"><?= htmlspecialchars($post['author']) ?></div>
        <div class="bd-author-role">Mfills Contributor · <?= $post['date'] ?></div>
      </div>
    </div>

    <!-- Share -->
    <div class="bd-share">
      <span class="bd-share-label">Share</span>
      <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' — ' . APP_URL . '/blog-detail.php?slug=' . $slug) ?>"
         target="_blank" rel="noopener" class="bd-share-btn">📲 WhatsApp</a>
      <button class="bd-share-btn" onclick="copyLink()">🔗 Copy Link</button>
    </div>

    <!-- Related posts -->
    <?php if (!empty($relatedPosts)): ?>
    <div class="bd-related">
      <div class="bd-related-head">You Might Also Like</div>
      <div class="bd-related-grid">
        <?php foreach ($relatedPosts as $rs => $rp):
          $rc = $categories[$rp['cat']];
        ?>
        <a class="bd-related-card"
           href="<?= APP_URL ?>/blog-detail.php?slug=<?= $rs ?>">
          <img class="bd-related-img"
               src="<?= htmlspecialchars($rp['img']) ?>"
               alt="<?= htmlspecialchars($rp['title']) ?>"
               loading="lazy">
          <div class="bd-related-body">
            <div class="bd-related-cat"><?= $rc['icon'] ?> <?= $rc['label'] ?></div>
            <div class="bd-related-title"><?= htmlspecialchars($rp['title']) ?></div>
            <div class="bd-related-meta"><?= $rp['date'] ?> · <?= $rp['read'] ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </article>

  <!-- ── SIDEBAR ── -->
  <aside class="bd-sidebar">

    <!-- Table of Contents -->
    <?php
    $h2Blocks = array_values(array_filter($post['content'], fn($b) => $b['type'] === 'h2'));
    if (!empty($h2Blocks)):
    ?>
    <div class="bd-sidebar-card">
      <div class="bd-sidebar-head">In This Article</div>
      <div class="bd-sidebar-body">
        <ol class="bd-toc">
          <?php foreach ($h2Blocks as $i => $hb): ?>
          <li>
            <span class="bd-toc-num"><?= sprintf('%02d', $i + 1) ?></span>
            <?= htmlspecialchars($hb['text']) ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
    <?php endif; ?>

    <!-- Category -->
    <div class="bd-sidebar-card">
      <div class="bd-sidebar-head">Category</div>
      <div class="bd-sidebar-body">
        <a class="bd-cat-pill"
           href="<?= APP_URL ?>/blog.php?cat=<?= $post['cat'] ?>">
          <?= $cat['icon'] ?> <?= $cat['label'] ?>
        </a>
      </div>
    </div>

    <!-- CTA -->
    <div class="bd-sidebar-cta">
      <div class="bd-sidebar-cta-icon">🌿</div>
      <h4>Shop Mfills Wellness</h4>
      <p>Browse science-backed products — delivered directly to you.</p>
      <a href="<?= APP_URL ?>/shop.php" class="btn btn-gold btn-sm" style="display:block;text-align:center">
        Explore MShop →
      </a>
    </div>

  </aside>

</div><!-- /.bd-layout -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
/* ── Reading progress bar ── */
(function () {
  var bar = document.getElementById('bdProgress');
  var art = document.getElementById('bdArticle');
  if (!bar || !art) return;
  function update() {
    var artTop    = art.getBoundingClientRect().top + window.scrollY;
    var artBottom = artTop + art.offsetHeight;
    var scrolled  = window.scrollY - artTop;
    var total     = artBottom - artTop - window.innerHeight;
    var pct       = Math.min(100, Math.max(0, (scrolled / total) * 100));
    bar.style.width = pct + '%';
  }
  window.addEventListener('scroll', update, { passive: true });
  update();
})();

/* ── Copy link ── */
function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(function () {
    var btns = document.querySelectorAll('.bd-share-btn');
    btns.forEach(function (b) {
      if (b.textContent.includes('Copy')) {
        var orig = b.textContent;
        b.textContent = '✓ Copied!';
        b.style.background = 'var(--jade)';
        b.style.color = '#fff';
        b.style.borderColor = 'var(--jade)';
        setTimeout(function () {
          b.textContent = orig;
          b.style.background = '';
          b.style.color = '';
          b.style.borderColor = '';
        }, 2000);
      }
    });
  });
}
</script>