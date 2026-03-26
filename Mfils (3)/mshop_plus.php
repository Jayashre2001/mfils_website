<?php
// mshop_plus.php
$pageTitle = 'MShop Plus – Mfills';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$userId    = currentUserId();
$user      = getUser($userId);
$isClub    = isActiveClubMember($userId);
$monthlyBv = getMonthlyBv($userId);
$message   = '';
$msgType   = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_buy'])) {
    if (!$isClub) {
        $message = '❌ MShop Plus access requires active Mfills Business Club membership (2500 BV/month).';
        $msgType = 'danger';
    } else {
        $cartItems = json_decode($_POST['cart_data'] ?? '[]', true);
        $successCount = 0; $errors = [];
        if (!empty($cartItems) && is_array($cartItems)) {
            foreach ($cartItems as $item) {
                $pid = (int)($item['product_id'] ?? 0);
                $qty = max(1, (int)($item['qty'] ?? 1));
                if ($pid > 0) {
                    $result = purchaseProduct($userId, $pid, $qty, 'mshop_plus');
                    if ($result['success']) { $successCount++; } else { $errors[] = $result['message']; }
                }
            }
        }
        if ($successCount > 0 && empty($errors)) {
            $message = "✅ {$successCount} product(s) purchased successfully with MShop Plus pricing!";
            $msgType = 'success'; setFlash('success', $message);
            $monthlyBv = getMonthlyBv($userId); $isClub = isActiveClubMember($userId);
        } elseif ($successCount > 0) {
            $message = "✅ {$successCount} purchased. Some failed: " . implode(', ', $errors); $msgType = 'warning';
        } else {
            $message = "❌ Purchase failed: " . implode(', ', $errors); $msgType = 'danger';
        }
    }
}

$allProducts = getProducts('mshop_plus');
if (empty($allProducts)) $allProducts = getProducts('mshop');

$bvNeeded   = max(0, 2500 - $monthlyBv);
$bvProgress = min(100, round(($monthlyBv / 2500) * 100));

function getProductCategory(string $name): string {
    $n = strtolower($name);
    if (strpos($n,'protein') !== false || strpos($n,'whey') !== false) return 'protein';
    if (strpos($n,'vitamin') !== false || strpos($n,'omega') !== false || strpos($n,'fish oil') !== false || strpos($n,'multivit') !== false) return 'vitamins';
    if (strpos($n,'ashwagandha') !== false || strpos($n,'triphala') !== false || strpos($n,'herbal') !== false) return 'ayurvedic';
    if (strpos($n,'detox') !== false || strpos($n,'slim') !== false || strpos($n,'cla') !== false || strpos($n,'lean') !== false) return 'weightloss';
    return 'other';
}

$catMeta = [
    'protein'    => ['label'=>'Protein',     'badge_bg'=>'#1a3b22','badge_fg'=>'#c8e8d0','icon'=>'🏋️'],
    'vitamins'   => ['label'=>'Vitamins',     'badge_bg'=>'#0F7B5C','badge_fg'=>'#CCFCE8','icon'=>'🌿'],
    'ayurvedic'  => ['label'=>'Ayurvedic',    'badge_bg'=>'#a0721a','badge_fg'=>'#FDE68A','icon'=>'🌱'],
    'weightloss' => ['label'=>'Weight Loss',  'badge_bg'=>'#C0321A','badge_fg'=>'#FFD0C7','icon'=>'🔥'],
    'other'      => ['label'=>'Other',        'badge_bg'=>'#374151','badge_fg'=>'#E5E7EB','icon'=>'💊'],
];

include __DIR__ . '/includes/header.php';
?>
<style>
/* ═══════════════════════════════════════════════════
   MShop Plus — Forest Green + Gold Brand
═══════════════════════════════════════════════════ */
:root {
  --green-dd:  #0e2414; --green-d:  #1a3b22; --green:   #1d4a28;
  --green-m:   #2a6336; --green-l:  #3a8a4a; --green-ll:#4dac5e;
  --gold:      #c8922a; --gold-l:   #e0aa40; --gold-d:  #a0721a; --gold-ll:#f5c96a;
  --jade:      #0F7B5C; --jade-l:   #14A376; --jade-ll: #1CC48D;
  --coral:     #E8534A;
  --ivory:     #f8f5ef; --ivory-d:  #ede8de; --ivory-dd:#ddd5c4;
  --ink:       #152018; --muted:    #5a7a60;
}

/* ══════════════════════════════════════
   HEADER
══════════════════════════════════════ */
.plus-header {
  background: linear-gradient(135deg, var(--green-dd) 0%, var(--green-d) 50%, var(--green-m) 100%);
  padding: 2.5rem 0 2rem; position: relative; overflow: hidden;
  border-bottom: 3px solid var(--gold);
}
.plus-header::before {
  content:''; position:absolute; inset:0; pointer-events:none;
  background-image: radial-gradient(circle, rgba(200,146,42,.08) 1.5px, transparent 1.5px);
  background-size: 24px 24px;
}
.plus-header::after {
  content:''; position:absolute; inset:0; pointer-events:none;
  background-image:
    linear-gradient(rgba(255,255,255,.018) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px);
  background-size: 48px 48px;
}

/* Decorative arcs */
.plus-header-arc { position:absolute; border-radius:50%; pointer-events:none; }
.plus-header-arc-1 {
  width:360px; height:260px; bottom:-80px; left:-60px;
  border:2px solid rgba(200,146,42,.14);
  animation: arcPulse 7s ease-in-out infinite;
}
.plus-header-arc-2 {
  width:300px; height:300px; top:-100px; right:-80px;
  border:2px solid rgba(77,172,94,.12);
  animation: arcPulse 9s 2s ease-in-out infinite reverse;
}
@keyframes arcPulse { 0%,100%{transform:scale(1);opacity:.6} 50%{transform:scale(1.05);opacity:1} }

/* Glow blobs */
.plus-header-glow { position:absolute; border-radius:50%; filter:blur(70px); pointer-events:none; }
.phg-1 { width:420px;height:420px; background:radial-gradient(circle,rgba(58,138,74,.25) 0%,transparent 70%); top:-140px;right:-80px; animation:glowDrift 18s ease-in-out infinite; }
.phg-2 { width:280px;height:280px; background:radial-gradient(circle,rgba(200,146,42,.15) 0%,transparent 70%); bottom:-80px;left:10%; animation:glowDrift 14s 4s ease-in-out infinite reverse; }
@keyframes glowDrift { 0%,100%{transform:translate(0,0) scale(1)} 33%{transform:translate(20px,-16px) scale(1.05)} 66%{transform:translate(-14px,22px) scale(.96)} }

.plus-header-inner { position:relative; z-index:1; display:flex; align-items:flex-start; justify-content:space-between; gap:1.5rem; flex-wrap:wrap; }

/* Badge */
.plus-badge {
  display:inline-flex; align-items:center; gap:.4rem;
  background:linear-gradient(135deg,var(--gold-d),var(--gold-l));
  color:var(--green-dd); border-radius:20px; padding:.28rem .9rem;
  font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
  margin-bottom:.6rem; font-family:'Cinzel',serif;
  box-shadow:0 3px 14px rgba(200,146,42,.4);
  animation:badgePop .6s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes badgePop { from{transform:scale(.6);opacity:0} to{transform:scale(1);opacity:1} }

.plus-header h1 {
  font-family:'Cinzel','Georgia',serif;
  font-size:clamp(1.6rem,3.5vw,2.2rem); font-weight:900;
  color:#fff; line-height:1.2; margin-bottom:.35rem;
  animation:fadeSlideUp .5s .1s ease both;
}
.plus-header h1 em { color:var(--gold-l); font-style:italic; }
.plus-header p {
  color:rgba(255,255,255,.5); font-size:.88rem; line-height:1.6;
  animation:fadeSlideUp .5s .2s ease both;
}
@keyframes fadeSlideUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:none} }
.plus-header-back {
  color:rgba(255,255,255,.45); font-size:.8rem; font-weight:700;
  text-decoration:none; display:inline-block; margin-top:.5rem;
  font-family:'Cinzel',serif; transition:color .2s;
  animation:fadeSlideUp .5s .3s ease both;
}
.plus-header-back:hover { color:var(--gold-l); }

/* Cart FAB */
.cart-fab {
  background:linear-gradient(135deg,var(--gold-d),var(--gold-l));
  color:var(--green-dd); border:none; border-radius:50px; padding:.7rem 1.4rem;
  font-family:'Cinzel',serif; font-size:.85rem; font-weight:800;
  cursor:pointer; display:flex; align-items:center; gap:.45rem;
  box-shadow:0 4px 20px rgba(200,146,42,.45); transition:transform .2s,box-shadow .2s;
  white-space:nowrap; flex-shrink:0; align-self:flex-start;
  animation:fadeSlideUp .5s .2s ease both;
}
.cart-fab:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(200,146,42,.6); }
.cart-fab-count {
  background:var(--coral); color:#fff; font-size:.65rem; font-weight:800;
  min-width:20px; height:20px; border-radius:10px; padding:0 5px;
  display:inline-flex; align-items:center; justify-content:center;
}
.cart-fab-count.bump { animation:bump .3s ease; }
@keyframes bump { 0%{transform:scale(1)} 50%{transform:scale(1.45)} 100%{transform:scale(1)} }

/* ══════════════════════════════════════
   CLUB STATUS BANNER
══════════════════════════════════════ */
.club-status {
  border-radius:16px; padding:1.5rem; margin:1.5rem 0;
  border:2px solid; display:flex; gap:1.25rem; align-items:flex-start; flex-wrap:wrap;
  opacity:0; transform:translateY(16px);
  animation:tileEntry .6s .3s ease forwards;
}
@keyframes tileEntry { to{opacity:1;transform:translateY(0)} }
.club-status.active   { background:rgba(15,123,92,.06);  border-color:rgba(15,123,92,.3); }
.club-status.inactive { background:rgba(200,146,42,.06); border-color:rgba(200,146,42,.3); }
.club-status-icon { font-size:2.2rem; flex-shrink:0; line-height:1; transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.club-status:hover .club-status-icon { transform:scale(1.2) rotate(-8deg); }
.club-status-body { flex:1; min-width:200px; }
.club-status-title { font-family:'Cinzel','Georgia',serif; font-size:1.1rem; font-weight:700; margin-bottom:.3rem; }
.club-status.active   .club-status-title { color:var(--jade); }
.club-status.inactive .club-status-title { color:var(--gold-d); }
.club-status-desc { font-size:.84rem; color:var(--muted); line-height:1.65; margin-bottom:.85rem; font-family:'Nunito',sans-serif; }

/* BV Progress */
.bv-progress-wrap { max-width:400px; }
.bv-prog-labels { display:flex; justify-content:space-between; font-size:.72rem; font-weight:700; margin-bottom:.35rem; font-family:'Nunito',sans-serif; }
.bv-prog-labels span:first-child { color:var(--muted); }
.bv-prog-labels span:last-child  { color:var(--jade); }
.bv-prog-track { background:var(--ivory-dd); border-radius:6px; height:10px; overflow:hidden; }
.bv-prog-fill  { height:100%; border-radius:6px; background:linear-gradient(90deg,var(--green-m),var(--green-l),var(--jade-ll)); transition:width 1.2s cubic-bezier(.22,1,.36,1); position:relative; overflow:hidden; }
.bv-prog-fill::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent); animation:shimmerBar 2s linear infinite; }
@keyframes shimmerBar { from{transform:translateX(-100%)} to{transform:translateX(100%)} }
.bv-prog-sub { font-size:.72rem; color:var(--muted); margin-top:.3rem; font-family:'Nunito',sans-serif; }

/* Activate CTA */
.activate-cta {
  display:inline-flex; align-items:center; gap:.4rem;
  background:linear-gradient(135deg,var(--gold),var(--gold-l));
  color:var(--green-dd); border:none; border-radius:50px;
  padding:.6rem 1.4rem; font-size:.84rem; font-weight:800;
  font-family:'Cinzel',serif; cursor:pointer; text-decoration:none;
  box-shadow:0 4px 14px rgba(200,146,42,.4); transition:all .2s;
  animation:pulseGold 2.5s ease-in-out 1s infinite;
}
@keyframes pulseGold { 0%{box-shadow:0 0 0 0 rgba(200,146,42,.5)} 70%{box-shadow:0 0 0 12px rgba(200,146,42,0)} 100%{box-shadow:0 0 0 0 rgba(200,146,42,0)} }
.activate-cta:hover { background:linear-gradient(135deg,var(--gold-l),var(--gold-ll)); transform:translateY(-2px); color:var(--green-dd); animation:none; }

/* Club perks strip */
.perks-strip {
  display:flex; gap:.75rem; flex-wrap:wrap;
  background:linear-gradient(135deg,rgba(26,59,34,.07),rgba(200,146,42,.06));
  border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.5rem;
  border:1.5px solid rgba(200,146,42,.18);
  opacity:0; transform:translateY(12px);
  animation:tileEntry .5s .5s ease forwards;
}
.perk-item { display:flex; align-items:center; gap:.4rem; font-size:.8rem; font-weight:700; color:var(--green-d); font-family:'Nunito',sans-serif; transition:transform .25s; }
.perk-item:hover { transform:translateY(-2px); }
.perk-icon { font-size:1.05rem; transition:transform .3s cubic-bezier(.34,1.56,.64,1); }
.perk-item:hover .perk-icon { transform:scale(1.3) rotate(-5deg); }

/* ── Filter bar ── */
.filter-bar { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1.5px solid var(--ivory-dd); }
.filter-label { font-size:.68rem; color:var(--muted); font-weight:800; text-transform:uppercase; letter-spacing:.1em; font-family:'Cinzel',serif; }
.filter-chip { padding:.3rem .88rem; border-radius:20px; border:1.5px solid var(--ivory-dd); background:#fff; font-size:.78rem; font-weight:700; color:var(--muted); cursor:pointer; transition:all .2s; font-family:'Nunito',sans-serif; }
.filter-chip:hover { border-color:var(--green-l); color:var(--green-m); }
.filter-chip.active { border-color:var(--green-d); background:var(--green-d); color:#fff; }
.item-count { margin-left:auto; font-size:.8rem; color:var(--muted); font-style:italic; font-family:'Nunito',sans-serif; }

/* ══════════════════════════════════════
   PRODUCT GRID & CARDS
══════════════════════════════════════ */
.shop-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1.6rem; padding-bottom:6rem; }

.supp-card {
  background:#fff; border-radius:16px; overflow:hidden;
  border:1.5px solid var(--ivory-dd); border-top:3px solid var(--ivory-dd);
  box-shadow:0 2px 14px rgba(26,59,34,.07);
  display:flex; flex-direction:column; position:relative;
  transition:transform .3s cubic-bezier(.34,1.2,.64,1), box-shadow .3s;
  animation:cardIn .45s ease both;
}
.supp-card:hover { transform:translateY(-6px); box-shadow:0 18px 42px rgba(26,59,34,.14); }
@keyframes cardIn { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
.supp-card:nth-child(1){animation-delay:.04s} .supp-card:nth-child(2){animation-delay:.08s}
.supp-card:nth-child(3){animation-delay:.12s} .supp-card:nth-child(4){animation-delay:.16s}
.supp-card:nth-child(5){animation-delay:.20s} .supp-card:nth-child(6){animation-delay:.24s}
.supp-card:nth-child(7){animation-delay:.28s} .supp-card:nth-child(8){animation-delay:.32s}
.supp-card:nth-child(9){animation-delay:.36s} .supp-card:nth-child(10){animation-delay:.40s}
.supp-card[data-cat="protein"]    { border-top-color:var(--green-d); }
.supp-card[data-cat="vitamins"]   { border-top-color:var(--jade); }
.supp-card[data-cat="ayurvedic"]  { border-top-color:var(--gold); }
.supp-card[data-cat="weightloss"] { border-top-color:var(--coral); }
.supp-card.in-cart { box-shadow:0 0 0 2.5px var(--jade-l),0 8px 26px rgba(15,123,92,.2); }
.supp-card.in-cart::after {
  content:'✓ In Cart'; position:absolute; top:10px; right:10px; z-index:10;
  background:var(--jade); color:#fff; font-size:.65rem; font-weight:800;
  padding:.22rem .55rem; border-radius:20px; font-family:'Cinzel',serif;
}

/* Image */
.supp-img-wrap { position:relative; overflow:hidden; height:210px; flex-shrink:0; background:var(--ivory-d); }
.supp-img-wrap img { width:100%; height:100%; object-fit:cover; transition:transform .5s; display:block; }
.supp-card:hover .supp-img-wrap img { transform:scale(1.07); }

/* Category tag */
.supp-tag {
  position:absolute; top:10px; left:10px;
  font-size:.64rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
  padding:.26rem .65rem; border-radius:4px; box-shadow:0 2px 8px rgba(0,0,0,.22); font-family:'Cinzel',serif;
}

/* Discount ribbon */
.disc-ribbon {
  position:absolute; top:0; right:0;
  background:var(--coral); color:#fff;
  font-size:.68rem; font-weight:800; padding:.3rem .7rem;
  border-radius:0 0 0 10px; font-family:'Cinzel',serif;
  box-shadow:0 2px 8px rgba(232,83,74,.4);
  animation:ribbonPulse 2s ease-in-out 1s infinite;
}
@keyframes ribbonPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.05)} }

/* BV badge */
.extra-bv-badge {
  position:absolute; bottom:10px; left:10px;
  background:linear-gradient(135deg,var(--jade),var(--jade-l));
  color:#fff; font-size:.65rem; font-weight:800;
  padding:.22rem .6rem; border-radius:20px; font-family:'Cinzel',serif;
  transition:transform .25s;
}
.supp-card:hover .extra-bv-badge { transform:scale(1.08); }

/* Card body */
.supp-body { padding:1.1rem 1.15rem .95rem; flex:1; display:flex; flex-direction:column; }
.supp-name { font-family:'Cinzel','Georgia',serif; font-size:.9rem; font-weight:700; color:var(--ink); margin-bottom:.3rem; line-height:1.35; }
.supp-desc { font-size:.78rem; color:var(--muted); margin-bottom:.75rem; line-height:1.55; flex:1; font-family:'Nunito',sans-serif; }

/* Price row */
.supp-price-row { display:flex; align-items:baseline; gap:.5rem; flex-wrap:wrap; margin-bottom:.85rem; }
.supp-price-plus { font-family:'DM Serif Display',serif; font-size:1.35rem; font-weight:800; color:var(--gold-d); }
.supp-price-orig { font-size:.82rem; color:var(--muted); text-decoration:line-through; }
.supp-saving     { font-size:.7rem; background:rgba(232,83,74,.12); color:var(--coral); border-radius:4px; padding:.1rem .42rem; font-weight:800; font-family:'Cinzel',serif; }
.supp-price-reg  { font-family:'DM Serif Display',serif; font-size:1.35rem; font-weight:800; color:var(--green-d); }
.bv-tag { font-size:.7rem; color:var(--muted); margin-left:auto; font-family:'Nunito',sans-serif; }

/* BV reward row */
.bv-reward-row {
  display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;
  background:rgba(15,123,92,.06); border:1px solid rgba(15,123,92,.15);
  border-radius:8px; padding:.5rem .7rem; margin-bottom:.8rem;
}
.bv-reward-item { display:flex; align-items:center; gap:.25rem; font-size:.7rem; font-weight:700; color:var(--jade); font-family:'Nunito',sans-serif; }
.bv-reward-sep  { color:var(--ivory-dd); }

/* Action row */
.supp-action-row { display:flex; gap:.45rem; align-items:center; border-top:1px solid var(--ivory-dd); padding-top:.85rem; }
.qty-wrap { display:flex; align-items:center; border:1.5px solid var(--ivory-dd); border-radius:8px; overflow:hidden; flex-shrink:0; background:var(--ivory); }
.qty-step { width:28px; height:36px; border:none; background:transparent; color:var(--green-l); font-size:1.05rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; }
.qty-step:hover { background:var(--ivory-d); }
.qty-input { width:32px; border:none; background:transparent; font-size:.88rem; font-weight:700; text-align:center; color:var(--ink); padding:0; border-left:1px solid var(--ivory-dd); border-right:1px solid var(--ivory-dd); outline:none; font-family:'Nunito',sans-serif; }

/* Add to cart button */
.add-cart-btn {
  flex:1; display:flex; align-items:center; justify-content:center; gap:.35rem;
  background:linear-gradient(135deg,var(--green-d),var(--green-m)); color:#fff;
  border:none; border-radius:8px; padding:.58rem .75rem;
  font-family:'Cinzel',serif; font-size:.8rem; font-weight:700;
  cursor:pointer; transition:all .25s; position:relative; overflow:hidden;
}
.add-cart-btn::after { content:''; position:absolute; inset:0; background:linear-gradient(135deg,var(--green-m),var(--green-l)); opacity:0; transition:opacity .25s; }
.add-cart-btn:hover::after { opacity:1; }
.add-cart-btn:hover { box-shadow:0 4px 14px rgba(26,59,34,.35); }
.add-cart-btn span { position:relative; z-index:1; }
.add-cart-btn.added { background:linear-gradient(135deg,var(--jade),var(--jade-l)); }
.add-cart-btn.added::after { display:none; }
.add-cart-btn:disabled { opacity:.45; cursor:not-allowed; background:rgba(90,122,96,.25); }
.add-cart-btn:disabled::after { display:none; }

/* Locked overlay */
.locked-overlay {
  position:absolute; inset:0; background:rgba(248,245,239,.9);
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  z-index:5; border-radius:16px; gap:.5rem; padding:1rem; text-align:center;
  backdrop-filter:blur(3px);
}
.locked-overlay .lock-icon { font-size:2rem; animation:lockBounce 2s ease-in-out infinite; }
@keyframes lockBounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
.locked-overlay p { font-size:.8rem; color:var(--muted); line-height:1.5; font-family:'Nunito',sans-serif; }
.locked-overlay a {
  display:inline-flex; align-items:center; gap:.3rem;
  background:linear-gradient(135deg,var(--gold),var(--gold-l));
  color:var(--green-dd); border-radius:50px;
  padding:.42rem 1.1rem; font-size:.78rem; font-weight:800;
  text-decoration:none; font-family:'Cinzel',serif; transition:all .2s;
}
.locked-overlay a:hover { background:linear-gradient(135deg,var(--gold-l),var(--gold-ll)); }

/* Mobile qty */
.qty-simple { display:none; width:46px; padding:.46rem .3rem; border:1.5px solid var(--ivory-dd); border-radius:8px; font-size:.82rem; text-align:center; background:var(--ivory); color:var(--ink); outline:none; flex-shrink:0; }

/* ══════════════════════════════════════
   CART DRAWER
══════════════════════════════════════ */
.cart-overlay { position:fixed; inset:0; background:rgba(14,36,20,.55); z-index:1040; opacity:0; pointer-events:none; transition:opacity .3s; backdrop-filter:blur(2px); }
.cart-overlay.open { opacity:1; pointer-events:all; }
.cart-drawer {
  position:fixed; top:0; right:0; bottom:0; width:420px; max-width:100vw;
  background:var(--ivory); z-index:1050; display:flex; flex-direction:column;
  transform:translateX(100%); transition:transform .35s cubic-bezier(.4,0,.2,1);
  box-shadow:-8px 0 48px rgba(14,36,20,.3);
}
.cart-drawer.open { transform:translateX(0); }

/* Drawer header — gold gradient */
.cart-drawer-head {
  background:linear-gradient(135deg,var(--gold-d),var(--gold-l));
  padding:1.2rem 1.5rem; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;
}
.cart-drawer-head h2 { color:var(--green-dd); font-family:'Cinzel',serif; font-size:1.1rem; margin:0; font-weight:900; }
.cart-drawer-head h2 small { color:rgba(14,36,20,.55); font-size:.72rem; font-weight:400; font-family:'Nunito',sans-serif; display:block; margin-top:.08rem; }
.cart-close { background:rgba(14,36,20,.15); border:none; color:var(--green-dd); width:34px; height:34px; border-radius:50%; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .2s, transform .2s; }
.cart-close:hover { background:rgba(14,36,20,.28); transform:rotate(90deg); }

/* Drawer body */
.cart-body { flex:1; overflow-y:auto; padding:1rem 1.2rem; }
.cart-body::-webkit-scrollbar { width:4px; }
.cart-body::-webkit-scrollbar-thumb { background:var(--ivory-dd); border-radius:10px; }
.cart-empty { text-align:center; padding:3rem 1rem; color:var(--muted); }
.cart-empty .ce-icon { font-size:2.8rem; opacity:.3; margin-bottom:.6rem; animation:floatIcon 3s ease-in-out infinite; }
@keyframes floatIcon { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.cart-empty p { font-size:.83rem; line-height:1.6; font-family:'Nunito',sans-serif; }

/* Cart item */
.cart-item {
  display:flex; gap:.8rem; align-items:flex-start;
  background:#fff; border-radius:10px; padding:.85rem; margin-bottom:.55rem;
  box-shadow:0 1px 6px rgba(26,59,34,.06); border:1.5px solid var(--ivory-dd);
  animation:slideIn .25s cubic-bezier(.34,1.2,.64,1) both;
}
@keyframes slideIn { from{opacity:0;transform:translateX(18px)} to{opacity:1;transform:translateX(0)} }
.cart-item-img { width:58px; height:58px; border-radius:8px; object-fit:cover; flex-shrink:0; background:var(--ivory-d); }
.cart-item-info { flex:1; min-width:0; }
.cart-item-name { font-size:.83rem; font-weight:700; color:var(--ink); line-height:1.3; margin-bottom:.2rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-family:'Nunito',sans-serif; }
.cart-item-prices { font-size:.72rem; color:var(--muted); margin-bottom:.4rem; font-family:'Nunito',sans-serif; }
.cart-item-prices .disc { color:var(--gold-d); font-weight:800; }
.cart-item-controls { display:flex; align-items:center; gap:.5rem; }
.cart-qty-wrap { display:flex; align-items:center; border:1.5px solid var(--ivory-dd); border-radius:7px; overflow:hidden; background:var(--ivory); }
.cart-qty-step { width:26px; height:26px; border:none; background:transparent; color:var(--green-l); font-size:.95rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; }
.cart-qty-step:hover { background:var(--ivory-dd); }
.cart-qty-num { width:28px; border:none; background:transparent; font-size:.8rem; font-weight:700; text-align:center; color:var(--ink); border-left:1px solid var(--ivory-dd); border-right:1px solid var(--ivory-dd); outline:none; font-family:'Nunito',sans-serif; }
.cart-item-line { font-family:'DM Serif Display',serif; font-size:.92rem; font-weight:700; color:var(--gold-d); margin-left:auto; white-space:nowrap; }
.cart-item-remove { background:none; border:none; color:var(--coral); font-size:.78rem; cursor:pointer; padding:.18rem .38rem; border-radius:5px; transition:background .15s, transform .2s; font-family:'Nunito',sans-serif; margin-top:.25rem; }
.cart-item-remove:hover { background:rgba(232,83,74,.1); transform:scale(1.1); }

/* Cart summary box */
.cart-summary-box {
  background:linear-gradient(135deg,rgba(26,59,34,.07),rgba(200,146,42,.07));
  border:1.5px solid rgba(200,146,42,.2); border-radius:10px;
  padding:.85rem 1rem; margin:.6rem 0; font-size:.77rem; color:var(--ink); font-family:'Nunito',sans-serif;
}
.cart-summary-row { display:flex; justify-content:space-between; margin-bottom:.3rem; }
.cart-summary-row:last-child { margin-bottom:0; }
.cart-summary-row .lbl { color:var(--muted); }
.cart-summary-row .val { font-weight:800; color:var(--green-d); }
.cart-summary-row .val.green { color:var(--jade); }
.cart-summary-row .val.gold  { color:var(--gold-d); }

/* Cart footer */
.cart-footer { border-top:2px solid var(--ivory-dd); padding:1.1rem 1.2rem 1.4rem; flex-shrink:0; background:#fff; }
.cart-total-row { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:.85rem; }
.cart-total-label { font-size:.83rem; color:var(--muted); font-weight:700; font-family:'Nunito',sans-serif; }
.cart-total-val { font-family:'DM Serif Display',serif; font-size:1.55rem; font-weight:800; color:var(--green-d); }

/* Checkout button */
.cart-checkout-btn {
  width:100%; background:linear-gradient(135deg,var(--gold-d),var(--gold-l));
  color:var(--green-dd); border:none; border-radius:50px; padding:.85rem 1rem;
  font-family:'Cinzel',serif; font-size:.92rem; font-weight:800;
  cursor:pointer; display:flex; align-items:center; justify-content:center; gap:.5rem;
  box-shadow:0 6px 20px rgba(200,146,42,.4); transition:all .25s;
  animation:pulseGold 2.5s ease-in-out 2s infinite;
}
.cart-checkout-btn:hover { background:linear-gradient(135deg,var(--gold-l),var(--gold-ll)); box-shadow:0 10px 28px rgba(200,146,42,.55); animation:none; }
.cart-checkout-btn:disabled { opacity:.45; cursor:not-allowed; animation:none; }
.cart-clear-btn { width:100%; background:none; border:1.5px solid var(--coral); color:var(--coral); border-radius:50px; padding:.52rem 1rem; font-family:'Cinzel',serif; font-size:.8rem; font-weight:700; cursor:pointer; margin-top:.55rem; transition:all .2s; }
.cart-clear-btn:hover { background:rgba(232,83,74,.08); transform:translateY(-1px); }

#cartForm { display:none; }

/* Alerts */
.alert { padding:.85rem 1.1rem; border-radius:10px; font-size:.875rem; font-weight:600; margin-bottom:1rem; font-family:'Nunito',sans-serif; }
.alert-success { background:rgba(15,123,92,.1);  color:var(--jade);  border-left:3px solid var(--jade-ll); }
.alert-danger  { background:rgba(232,83,74,.1);  color:#9A1A09;      border-left:3px solid var(--coral); }
.alert-warning { background:rgba(200,146,42,.1); color:#92400E;      border-left:3px solid var(--gold); }

/* Empty shop */
.shop-empty { text-align:center; padding:4rem 2rem; color:var(--muted); background:#fff; border-radius:16px; border:1.5px solid var(--ivory-dd); }
.shop-empty .ei { font-size:3.5rem; opacity:.3; margin-bottom:1rem; display:block; animation:floatIcon 3s ease-in-out infinite; }
.shop-empty h3 { font-family:'Cinzel','Georgia',serif; color:var(--green-d); margin-bottom:.5rem; }

/* ── Responsive ── */
@media (max-width:768px) {
  .plus-header { padding:2rem 0 1.75rem; }
  .plus-header h1 { font-size:1.65rem; }
  .cart-drawer { width:100vw; }
  .shop-grid { grid-template-columns:repeat(2,1fr); gap:1rem; }
  .supp-img-wrap { height:150px; }
  .supp-desc { display:none; }
  .supp-name { font-size:.85rem; }
  .club-status { padding:1.1rem; }
  .perks-strip { gap:.5rem; }
}
@media (max-width:480px) {
  .shop-grid { grid-template-columns:repeat(2,1fr); gap:.7rem; }
  .supp-img-wrap { height:120px; }
  .supp-tag { font-size:.58rem; padding:.18rem .42rem; }
  .qty-wrap { display:none; }
  .qty-simple { display:block !important; }
  .perk-item { font-size:.72rem; }
}
@media (max-width:360px) {
  .shop-grid { grid-template-columns:1fr; }
  .supp-img-wrap { height:190px; }
  .supp-desc { display:block; }
}
</style>

<!-- ════════════════════════════
     HEADER
════════════════════════════ -->
<div class="plus-header">
  <div class="plus-header-glow phg-1"></div>
  <div class="plus-header-glow phg-2"></div>
  <div class="plus-header-arc plus-header-arc-1"></div>
  <div class="plus-header-arc plus-header-arc-2"></div>
  <div class="container plus-header-inner">
    <div>
      <div class="plus-badge">⭐ EXCLUSIVE · CLUB MEMBERS ONLY</div>
      <h1>MShop <em>Plus</em></h1>
      <p>Discounted products · Extra BV rewards · Available exclusively for<br>active Mfills Business Club members (2500 BV/month)</p>
      <a href="shop.php" class="plus-header-back">← Back to MShop</a>
    </div>
    <button class="cart-fab" onclick="openCart()">
      🛒 Cart <span class="cart-fab-count" id="cartCount">0</span>
    </button>
  </div>
</div>

<div class="container page-wrap" style="padding-top:1.5rem">

  <?php if ($message && empty($_SESSION['flash'])): ?>
    <div class="alert alert-<?= e($msgType) ?>"><?= e($message) ?></div>
  <?php endif; ?>

  <!-- ════════════════════════════
       CLUB STATUS BANNER
  ════════════════════════════ -->
  <?php if ($isClub): ?>
  <div class="club-status active">
    <div class="club-status-icon">✅</div>
    <div class="club-status-body">
      <div class="club-status-title">Mfills Business Club — Active Member</div>
      <div class="club-status-desc">
        Congratulations! You have purchased <strong><?= number_format($monthlyBv, 0) ?> BV</strong> this month —
        your MShop Plus access is fully unlocked. Enjoy discounted prices and extra BV rewards below.
      </div>
      <div class="bv-progress-wrap">
        <div class="bv-prog-labels">
          <span>Monthly BV: <?= number_format($monthlyBv, 0) ?> / 2500</span>
          <span>✅ Active</span>
        </div>
        <div class="bv-prog-track">
          <div class="bv-prog-fill" id="bvFill" style="width:0%" data-target="100%"></div>
        </div>
        <div class="bv-prog-sub">Maintain 2500 BV every month to keep MShop Plus access</div>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="club-status inactive">
    <div class="club-status-icon">🔒</div>
    <div class="club-status-body">
      <div class="club-status-title">Mfills Business Club — Not Yet Activated</div>
      <div class="club-status-desc">
        MShop Plus is exclusively for active club members. You need
        <strong>₹<?= number_format($bvNeeded, 0) ?> more BV</strong> this month to unlock discounts and extra BV rewards.
      </div>
      <div class="bv-progress-wrap" style="margin-bottom:.85rem">
        <div class="bv-prog-labels">
          <span>Monthly BV: <?= number_format($monthlyBv, 0) ?> / 2500</span>
          <span style="color:var(--gold-d)"><?= $bvProgress ?>%</span>
        </div>
        <div class="bv-prog-track">
          <div class="bv-prog-fill" id="bvFill" style="width:0%;background:linear-gradient(90deg,var(--gold),var(--gold-l))" data-target="<?= $bvProgress ?>%"></div>
        </div>
        <div class="bv-prog-sub">Purchase <?= number_format($bvNeeded, 0) ?> more BV on MShop to activate</div>
      </div>
      <a href="shop.php" class="activate-cta">🛒 Go to MShop to Activate →</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Club Perks Strip -->
  <div class="perks-strip">
    <div class="perk-item"><span class="perk-icon">🏷️</span> Discounted product prices</div>
    <div class="perk-item"><span class="perk-icon">📈</span> Extra BV on every purchase</div>
    <div class="perk-item"><span class="perk-icon">💰</span> PSB on full BV (not discounted price)</div>
    <div class="perk-item"><span class="perk-icon">👑</span> Contributes toward Leadership Club rank</div>
    <div class="perk-item"><span class="perk-icon">🔁</span> Maintain 2500 BV/month to keep access</div>
  </div>

  <!-- Filter bar -->
  <?php if (!empty($allProducts)): ?>
  <div class="filter-bar" id="filterBar">
    <span class="filter-label">Category:</span>
    <button class="filter-chip active" onclick="filterCards('all',this)">All</button>
    <button class="filter-chip" onclick="filterCards('protein',this)">🏋️ Protein</button>
    <button class="filter-chip" onclick="filterCards('vitamins',this)">🌿 Vitamins</button>
    <button class="filter-chip" onclick="filterCards('ayurvedic',this)">🌱 Ayurvedic</button>
    <button class="filter-chip" onclick="filterCards('weightloss',this)">🔥 Weight Loss</button>
    <span class="item-count" id="itemCount"><?= count($allProducts) ?> items</span>
  </div>
  <?php endif; ?>

  <!-- ════════════════════════════
       PRODUCT GRID
  ════════════════════════════ -->
  <?php if (empty($allProducts)): ?>
    <div class="shop-empty">
      <span class="ei">🛍️</span>
      <h3>No products available</h3>
      <p>Please add products with <code>discount_pct</code> > 0 in the database for MShop Plus.</p>
    </div>
  <?php else: ?>
  <div class="shop-grid" id="shopGrid">
    <?php foreach ($allProducts as $p):
      $cat      = getProductCategory($p['name']); $meta = $catMeta[$cat];
      $price    = (float)$p['price'];
      $discPct  = (float)($p['discount_pct'] ?? 0);
      $discPrice = $discPct > 0 ? round($price * (1 - $discPct / 100), 2) : null;
      $bv        = isset($p['bv']) ? (float)$p['bv'] : $price;
      $l1Psb     = number_format($bv * 0.15, 0);
      $saving    = $discPrice ? number_format($price - $discPrice, 0) : 0;
      $effectivePrice = $isClub && $discPrice ? $discPrice : $price;
    ?>
    <div class="supp-card" data-cat="<?= $cat ?>" data-pid="<?= (int)$p['id'] ?>" id="card-<?= (int)$p['id'] ?>">

      <?php if (!$isClub): ?>
      <div class="locked-overlay">
        <span class="lock-icon">🔒</span>
        <p>Activate Mfills Business Club<br>to purchase at discounted prices</p>
        <a href="shop.php">🛒 Activate on MShop</a>
      </div>
      <?php endif; ?>

      <div class="supp-img-wrap">
        <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        <span class="supp-tag" style="background:<?= $meta['badge_bg'] ?>;color:<?= $meta['badge_fg'] ?>"><?= $meta['icon'] ?> <?= $meta['label'] ?></span>
        <?php if ($isClub && $discPct > 0): ?>
          <div class="disc-ribbon"><?= (int)$discPct ?>% OFF</div>
        <?php endif; ?>
        <div class="extra-bv-badge">BV: <?= number_format($bv, 0) ?></div>
      </div>

      <div class="supp-body">
        <div class="supp-name"><?= e($p['name']) ?></div>
        <div class="supp-desc"><?= e($p['description']) ?></div>

        <div class="supp-price-row">
          <?php if ($isClub && $discPrice): ?>
            <span class="supp-price-plus">₹<?= number_format($discPrice, 0) ?></span>
            <span class="supp-price-orig">₹<?= number_format($price, 0) ?></span>
            <span class="supp-saving">Save ₹<?= $saving ?></span>
          <?php else: ?>
            <span class="supp-price-reg">₹<?= number_format($price, 0) ?></span>
          <?php endif; ?>
          <span class="bv-tag">BV: <?= number_format($bv, 0) ?></span>
        </div>

        <div class="bv-reward-row">
          <div class="bv-reward-item">📊 BV: <?= number_format($bv, 0) ?></div>
          <span class="bv-reward-sep">·</span>
          <div class="bv-reward-item">💰 L1 PSB: ₹<?= $l1Psb ?></div>
          <?php if ($isClub && $discPct > 0): ?>
            <span class="bv-reward-sep">·</span>
            <div class="bv-reward-item">🎁 <?= (int)$discPct ?>% Disc</div>
          <?php endif; ?>
        </div>

        <div class="supp-action-row">
          <div class="qty-wrap">
            <button type="button" class="qty-step" onclick="stepQty(this,-1)">−</button>
            <input type="number" class="qty-input" value="1" min="1" max="10">
            <button type="button" class="qty-step" onclick="stepQty(this,1)">+</button>
          </div>
          <input type="number" class="qty-simple" value="1" min="1" max="10">
          <button type="button" class="add-cart-btn" id="addbtn-<?= (int)$p['id'] ?>"
            <?= !$isClub ? 'disabled' : '' ?>
            onclick="addToCart(<?= (int)$p['id'] ?>,<?= $effectivePrice ?>,'<?= addslashes(e($p['name'])) ?>','<?= addslashes(e($p['image_url'])) ?>','<?= $cat ?>',<?= $bv ?>,<?= $discPct ?>,this)">
            <?= $isClub ? '<span>🛒 Add to Cart</span>' : '<span>🔒 Club Only</span>' ?>
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div><!-- /.container -->

<form method="POST" action="" id="cartForm">
  <input type="hidden" name="bulk_buy" value="1">
  <input type="hidden" name="shop_source" value="mshop_plus">
  <input type="hidden" name="cart_data" id="cartDataInput" value="[]">
</form>

<!-- ════════════════════════════
     CART DRAWER
════════════════════════════ -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="cart-drawer-head">
    <h2>⭐ MShop Plus Cart <small id="cartHeadCount">0 items</small></h2>
    <button class="cart-close" onclick="closeCart()">✕</button>
  </div>
  <div class="cart-body" id="cartBody">
    <div class="cart-empty" id="cartEmpty">
      <div class="ce-icon">🛍️</div>
      <p>Cart khaali hai!<br>Discounted products add karein.</p>
    </div>
    <div id="cartItemsList"></div>
    <div class="cart-summary-box" id="cartSummaryBox" style="display:none">
      <div class="cart-summary-row"><span class="lbl">Total MRP</span><span class="val" id="cSumMrp">₹0</span></div>
      <div class="cart-summary-row"><span class="lbl">Club Discount</span><span class="val green" id="cSumDisc">- ₹0</span></div>
      <div class="cart-summary-row"><span class="lbl">Total BV Generated</span><span class="val gold" id="cSumBv">0 BV</span></div>
      <div class="cart-summary-row"><span class="lbl">L1 PSB Your Sponsor Gets</span><span class="val green" id="cSumPsb">₹0</span></div>
    </div>
  </div>
  <div class="cart-footer" id="cartFooter" style="display:none">
    <div class="cart-total-row">
      <div><div class="cart-total-label">You Pay (After Discount)</div></div>
      <div class="cart-total-val" id="cartTotalVal">₹0</div>
    </div>
    <button class="cart-checkout-btn" id="checkoutBtn" onclick="checkoutCart()">
      <span>✅ Place MShop Plus Order (<span id="checkoutCount">0</span> items)</span>
    </button>
    <button class="cart-clear-btn" onclick="clearCart()">🗑 Clear Cart</button>
  </div>
</div>

<script>
var cart = {};

function openCart(){ document.getElementById('cartDrawer').classList.add('open'); document.getElementById('cartOverlay').classList.add('open'); document.body.style.overflow='hidden'; }
function closeCart(){ document.getElementById('cartDrawer').classList.remove('open'); document.getElementById('cartOverlay').classList.remove('open'); document.body.style.overflow=''; }

function addToCart(pid, price, name, img, cat, bv, discPct, btn) {
  var card = document.getElementById('card-'+pid);
  var qtyEl = card.querySelector('.qty-wrap input') || card.querySelector('.qty-simple');
  var qty = Math.min(10, Math.max(1, parseInt(qtyEl ? qtyEl.value : 1) || 1));
  var mrp = discPct > 0 ? Math.round(price / (1 - discPct/100)) : price;
  if (cart[pid]) { cart[pid].qty = qty; } else {
    cart[pid] = { product_id:pid, name:name, price:price, mrp:mrp, bv:bv||price, image_url:img, cat:cat, qty:qty, discPct:discPct };
  }
  card.classList.add('in-cart');
  btn.classList.add('added');
  btn.querySelector('span').textContent = '✓ In Cart';
  updateCartUI(); bumpCount();
  if (Object.keys(cart).length === 1) setTimeout(openCart, 350);
}

function removeFromCart(pid) {
  delete cart[pid];
  var card = document.getElementById('card-'+pid);
  if (card) { card.classList.remove('in-cart'); var btn = document.getElementById('addbtn-'+pid); if (btn) { btn.classList.remove('added'); btn.querySelector('span').textContent = '🛒 Add to Cart'; } }
  updateCartUI();
}

function updateCartQty(pid, val) { var qty = Math.min(10, Math.max(1, parseInt(val)||1)); if (cart[pid]) { cart[pid].qty = qty; updateCartUI(); } }
function cartQtyStep(pid, dir) { if (!cart[pid]) return; var nq = Math.min(10, Math.max(1, cart[pid].qty+dir)); cart[pid].qty = nq; var el = document.getElementById('cqty-'+pid); if (el) el.value = nq; updateCartTotals(); }
function clearCart() { Object.keys(cart).forEach(removeFromCart); cart = {}; updateCartUI(); }

function updateCartUI() {
  var pids = Object.keys(cart), count = pids.length;
  document.getElementById('cartCount').textContent = count;
  document.getElementById('cartHeadCount').textContent = count + ' item' + (count !== 1 ? 's' : '');
  document.getElementById('cartEmpty').style.display = count === 0 ? '' : 'none';
  document.getElementById('cartFooter').style.display = count === 0 ? 'none' : '';
  document.getElementById('cartSummaryBox').style.display = count === 0 ? 'none' : '';
  var html = '';
  pids.forEach(function(pid) {
    var item = cart[pid], line = (item.price * item.qty).toLocaleString('en-IN');
    var discLine = item.discPct > 0
      ? '<span class="disc">₹' + item.price.toLocaleString('en-IN') + ' (disc)</span> · MRP ₹' + item.mrp.toLocaleString('en-IN')
      : '₹' + item.price.toLocaleString('en-IN') + ' / unit';
    html += '<div class="cart-item" id="ci-'+pid+'">' +
      '<img class="cart-item-img" src="'+item.image_url+'" alt="">' +
      '<div class="cart-item-info">' +
        '<div class="cart-item-name">'+item.name+'</div>' +
        '<div class="cart-item-prices">'+discLine+'</div>' +
        '<div class="cart-item-controls">' +
          '<div class="cart-qty-wrap">' +
            '<button class="cart-qty-step" onclick="cartQtyStep('+pid+',-1)">−</button>' +
            '<input id="cqty-'+pid+'" class="cart-qty-num" type="number" value="'+item.qty+'" min="1" max="10" onchange="updateCartQty('+pid+',this.value)">' +
            '<button class="cart-qty-step" onclick="cartQtyStep('+pid+',1)">+</button>' +
          '</div>' +
          '<span class="cart-item-line">₹'+line+'</span>' +
        '</div>' +
        '<button class="cart-item-remove" onclick="removeFromCart('+pid+')">✕ Remove</button>' +
      '</div>' +
    '</div>';
  });
  document.getElementById('cartItemsList').innerHTML = html;
  updateCartTotals();
}

function updateCartTotals() {
  var pids = Object.keys(cart), total = 0, totalMrp = 0, totalBv = 0;
  pids.forEach(function(pid) {
    total    += cart[pid].price * cart[pid].qty;
    totalMrp += cart[pid].mrp   * cart[pid].qty;
    totalBv  += (cart[pid].bv || cart[pid].price) * cart[pid].qty;
  });
  var disc = totalMrp - total, l1Psb = Math.round(totalBv * 0.15);
  document.getElementById('cartTotalVal').textContent  = '₹' + total.toLocaleString('en-IN', {maximumFractionDigits:0});
  document.getElementById('cSumMrp').textContent       = '₹' + totalMrp.toLocaleString('en-IN', {maximumFractionDigits:0});
  document.getElementById('cSumDisc').textContent      = '- ₹' + disc.toLocaleString('en-IN', {maximumFractionDigits:0});
  document.getElementById('cSumBv').textContent        = Math.round(totalBv).toLocaleString('en-IN') + ' BV';
  document.getElementById('cSumPsb').textContent       = '₹' + l1Psb.toLocaleString('en-IN');
  document.getElementById('checkoutCount').textContent = Object.keys(cart).length;
}

function bumpCount() { var el = document.getElementById('cartCount'); el.classList.remove('bump'); void el.offsetWidth; el.classList.add('bump'); }

function checkoutCart() {
  var pids = Object.keys(cart); if (!pids.length) return;
  document.getElementById('cartDataInput').value = JSON.stringify(pids.map(function(pid) { return {product_id:parseInt(pid),qty:cart[pid].qty}; }));
  document.getElementById('cartForm').submit();
}

function filterCards(cat, el) {
  document.querySelectorAll('#shopGrid .supp-card').forEach(function(c) {
    if (cat === 'all' || c.dataset.cat === cat) {
      c.style.display = ''; c.style.animation = 'none'; void c.offsetWidth; c.style.animation = 'cardIn .4s ease both';
    } else { c.style.display = 'none'; }
  });
  document.querySelectorAll('#filterBar .filter-chip').forEach(function(c) { c.classList.remove('active'); });
  el.classList.add('active');
  document.getElementById('itemCount').textContent = document.querySelectorAll('#shopGrid .supp-card:not([style*="none"])').length + ' items';
}

function stepQty(btn, dir) {
  var i = btn.parentElement.querySelector('input[type=number]');
  i.value = Math.min(10, Math.max(1, (parseInt(i.value)||1) + dir));
  var card = btn.closest('.supp-card'); if (!card) return;
  var pid = parseInt(card.dataset.pid);
  if (cart[pid]) { cart[pid].qty = parseInt(i.value); var cq = document.getElementById('cqty-'+pid); if (cq) cq.value = cart[pid].qty; updateCartTotals(); }
}

/* ── 3D tilt on product cards ── */
document.querySelectorAll('.supp-card:not(:has(.locked-overlay))').forEach(function(card) {
  card.addEventListener('mousemove', function(e) {
    var r = card.getBoundingClientRect();
    var x = (e.clientX - r.left) / r.width  - 0.5;
    var y = (e.clientY - r.top)  / r.height - 0.5;
    card.style.transform = 'perspective(600px) rotateY('+(x*7)+'deg) rotateX('+(-y*7)+'deg) translateY(-6px)';
  });
  card.addEventListener('mouseleave', function() { card.style.transform = ''; });
});

/* ── BV progress bar ── */
window.addEventListener('load', function() {
  setTimeout(function() {
    var fill = document.getElementById('bvFill');
    if (fill) fill.style.width = fill.getAttribute('data-target');
  }, 400);
});

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCart(); });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>