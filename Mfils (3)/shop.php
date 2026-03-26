<?php
// shop.php — Mfills MShop
$pageTitle = 'MShop – Mfills';
require_once __DIR__ . '/includes/functions.php';
startSession();

$loggedIn    = isLoggedIn();
$userId      = $loggedIn ? currentUserId() : null;
$user        = $loggedIn ? getUser($userId) : null;
$message     = ''; $msgType = 'success';
$shopMode    = $_GET['mode'] ?? 'mshop';
if ($shopMode !== 'mshop_plus') $shopMode = 'mshop';
$isMshopPlus = ($shopMode === 'mshop_plus');
$isClub      = $loggedIn ? isActiveClubMember($userId) : false;

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $src = $_POST['shop_source'] ?? 'mshop';
    $r   = purchaseProduct($userId, $pid, $qty, $src);
    $message = $r['message'];
    $msgType = $r['success'] ? 'success' : 'danger';
    if ($r['success']) setFlash('success', $message);
}

$allProducts = getProducts($shopMode);
$commRates   = defined('COMMISSION_RATES') ? COMMISSION_RATES : [1=>15,2=>8,3=>6,4=>4,5=>3,6=>2,7=>2];

function getProductCategory(string $name): string {
    $n = strtolower($name);
    if (strpos($n,'protein')!==false||strpos($n,'whey')!==false||strpos($n,'mass')!==false) return 'protein';
    if (strpos($n,'vitamin')!==false||strpos($n,'omega')!==false||strpos($n,'fish oil')!==false||strpos($n,'multivit')!==false||strpos($n,'b-complex')!==false) return 'vitamins';
    if (strpos($n,'ashwagandha')!==false||strpos($n,'triphala')!==false||strpos($n,'ayurved')!==false||strpos($n,'herbal')!==false||strpos($n,'shatavari')!==false) return 'ayurvedic';
    if (strpos($n,'detox')!==false||strpos($n,'slim')!==false||strpos($n,'cla')!==false||strpos($n,'weight')!==false||strpos($n,'lean')!==false||strpos($n,'garcinia')!==false) return 'weightloss';
    return 'other';
}
$catMeta = [
    'protein'    => ['label'=>'Protein',     'badge_bg'=>'#1a3b22','badge_fg'=>'#c8e8d0','icon'=>'🏋️','accent'=>'#5db870'],
    'vitamins'   => ['label'=>'Vitamins',    'badge_bg'=>'#0F7B5C','badge_fg'=>'#CCFCE8','icon'=>'🌿','accent'=>'#14A376'],
    'ayurvedic'  => ['label'=>'Ayurvedic',   'badge_bg'=>'#a0721a','badge_fg'=>'#FDE68A','icon'=>'🌱','accent'=>'#c8922a'],
    'weightloss' => ['label'=>'Weight Loss', 'badge_bg'=>'#C0321A','badge_fg'=>'#FFD0C7','icon'=>'🔥','accent'=>'#E8534A'],
    'other'      => ['label'=>'Other',       'badge_bg'=>'#374151','badge_fg'=>'#E5E7EB','icon'=>'💊','accent'=>'#6b7280'],
];

$jsProducts = [];
foreach ($allProducts as $p) {
    $bv           = (float)($p['bv'] ?? $p['price']);
    $discPct      = (float)($p['discount_pct'] ?? 0);
    $discPrice    = ($discPct > 0) ? round($p['price'] * (1 - $discPct/100), 2) : null;
    $sellingPrice = $discPrice ?? $p['price'];
    $cat          = getProductCategory($p['name']);
    $meta         = $catMeta[$cat];
    $jsProducts[(int)$p['id']] = [
        'pid'         => (int)$p['id'],
        'name'        => $p['name'],
        'description' => $p['description'] ?? '',
        'img'         => $p['image_url'] ?? '',
        'cat'         => $cat,
        'catLabel'    => $meta['label'],
        'catIcon'     => $meta['icon'],
        'catAccent'   => $meta['accent'],
        'badgeBg'     => $meta['badge_bg'],
        'badgeFg'     => $meta['badge_fg'],
        'price'       => (float)$p['price'],
        'sellingPrice'=> (float)$sellingPrice,
        'discPct'     => (float)$discPct,
        'bv'          => $bv,
        'isMshopPlus' => $isMshopPlus,
    ];
}

include __DIR__ . '/includes/header.php';
?>
<style>
:root{
  --green-dd:#0e2414;--green-d:#1a3b22;--green-m:#2a6336;--green-l:#3a8a4a;
  --gold:#c8922a;--gold-l:#e0aa40;--gold-d:#a0721a;
  --jade:#0F7B5C;--jade-l:#14A376;--coral:#E8534A;
  --ivory:#f8f5ef;--ivory-d:#ede8de;--ivory-dd:#ddd5c4;
  --ink:#152018;--muted:#5a7a60;
}
html,body{background:#f8f5ef!important;}

/* ── GLOBAL BOX-SIZING FIX ── */
*,*::before,*::after{box-sizing:border-box;}

.shop-header{background:linear-gradient(135deg,var(--green-dd) 0%,var(--green-d) 55%,var(--green-m) 100%);padding:2.75rem 0 2.25rem;border-bottom:3px solid var(--gold);position:relative;overflow:hidden;}
.shop-header::before{content:'';position:absolute;inset:0;pointer-events:none;background-image:radial-gradient(circle,rgba(200,146,42,.07) 1.5px,transparent 1.5px);background-size:26px 26px;}
.shop-header-inner{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.shop-header h1{font-family:'Cinzel',serif;font-size:2.2rem;font-weight:900;color:#fff;}
.shop-header h1 em{color:var(--gold-l);font-style:italic;}
.shop-header p{color:rgba(255,255,255,.52);margin-top:.35rem;font-size:.9rem;}
.cart-fab{background:linear-gradient(135deg,var(--gold-d),var(--gold-l));color:var(--green-dd);border:none;border-radius:50px;padding:.72rem 1.5rem;font-family:'Cinzel',serif;font-size:.88rem;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 22px rgba(200,146,42,.45);transition:transform .2s,box-shadow .2s;white-space:nowrap;text-decoration:none;}
.cart-fab:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(200,146,42,.55);}
.cart-fab-count{background:var(--coral);color:#fff;font-size:.65rem;font-weight:800;min-width:20px;height:20px;border-radius:10px;padding:0 5px;display:inline-flex;align-items:center;justify-content:center;}
.cart-fab-count.bump{animation:bump .35s cubic-bezier(.34,1.6,.64,1);}
@keyframes bump{0%{transform:scale(1)}50%{transform:scale(1.5)}100%{transform:scale(1)}}

.guest-banner{background:linear-gradient(90deg,rgba(184,128,24,.12),rgba(26,59,34,.08),rgba(184,128,24,.12));border-bottom:2px solid rgba(200,146,42,.25);padding:.65rem 1.5rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;}
.guest-banner-text{font-size:.8rem;color:#5c3d0a;font-weight:600;}
.guest-banner-btns{display:flex;gap:.5rem;margin-left:auto;}
.gb-btn{border:none;border-radius:6px;padding:.35rem .85rem;font-size:.72rem;font-weight:700;font-family:'Cinzel',serif;text-decoration:none;display:inline-flex;align-items:center;cursor:pointer;}
.gb-login{background:var(--green-d);color:#fff;}
.gb-register{background:var(--gold-d);color:#fff;}

/* ── ATC Overlay ── */
.atc-overlay{position:fixed;inset:0;z-index:3000;background:rgba(6,15,8,.7);backdrop-filter:blur(10px);display:none;align-items:center;justify-content:center;}
.atc-overlay.show{display:flex;animation:fadeInOv .3s ease;}
.atc-card{background:linear-gradient(160deg,#0c1e11,#060f08);border:1.5px solid rgba(200,146,42,.25);border-radius:22px;padding:2.2rem 2.5rem;text-align:center;box-shadow:0 30px 80px rgba(0,0,0,.5);max-width:340px;width:90%;}
.atc-check{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--jade),var(--jade-l));display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 1rem;animation:checkPop .5s cubic-bezier(.34,1.6,.64,1) .1s both;}
@keyframes checkPop{from{transform:scale(0)}to{transform:scale(1)}}
.atc-title{font-family:'Cinzel',serif;font-size:1rem;font-weight:900;color:#fff;margin-bottom:.3rem;}
.atc-name{font-size:.78rem;color:rgba(255,255,255,.55);margin-bottom:1.1rem;line-height:1.4;}
.atc-bar-wrap{height:4px;background:rgba(255,255,255,.1);border-radius:10px;overflow:hidden;margin-bottom:.8rem;}
.atc-bar{height:100%;width:0%;background:linear-gradient(90deg,var(--gold-d),var(--gold-l));border-radius:10px;transition:width .65s linear;}
.atc-sub{font-size:.68rem;color:rgba(200,146,42,.6);font-family:'Cinzel',serif;font-weight:700;}

/* ── Filter ── */
.filter-section{padding:1.5rem 0 0;margin-bottom:1.75rem;}
.filter-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;}
.filter-heading{font-family:'Cinzel',serif;font-size:.72rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.12em;}
.item-count{font-size:.75rem;color:var(--muted);background:var(--ivory-d);padding:.2rem .65rem;border-radius:20px;border:1px solid var(--ivory-dd);}
.item-count strong{color:var(--green-d);font-weight:800;}
.filter-cards{display:flex;gap:.6rem;flex-wrap:wrap;padding-bottom:1.1rem;border-bottom:1.5px solid var(--ivory-dd);}
.fcat{display:flex;align-items:center;gap:.5rem;padding:.5rem .9rem;background:#fff;border:1.5px solid var(--ivory-dd);border-radius:12px;cursor:pointer;transition:all .22s;outline:none;}
.fcat:hover{border-color:var(--green-l);transform:translateY(-2px);}
.fcat.active{background:var(--green-d);border-color:var(--green-d);}
.fcat.active .fcat-label,.fcat.active .fcat-count{color:#fff;}
.fcat-icon{font-size:1.1rem;line-height:1;}
.fcat-label{font-family:'Cinzel',serif;font-size:.7rem;font-weight:800;color:var(--green-d);}
.fcat-count{font-size:.62rem;font-weight:600;color:var(--muted);background:var(--ivory-d);border-radius:20px;padding:.05rem .35rem;}

/* ── Toolbar ── */
.toolbar{display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;}
.search-wrap{flex:1;min-width:160px;position:relative;}
.search-wrap input{width:100%;padding:.55rem .85rem .55rem 2.2rem;border:1.5px solid var(--ivory-dd);border-radius:50px;background:#fff;font-size:.85rem;color:var(--ink);outline:none;transition:border-color .2s;}
.search-wrap input:focus{border-color:var(--green-l);}
.search-wrap::before{content:'🔍';position:absolute;left:.75rem;top:50%;transform:translateY(-50%);font-size:.8rem;pointer-events:none;}
.sort-select{padding:.52rem .9rem;border:1.5px solid var(--ivory-dd);border-radius:50px;background:#fff;font-family:'Cinzel',serif;font-size:.72rem;font-weight:700;color:var(--green-d);outline:none;cursor:pointer;}

/* ══════════════════════════════════════════════
   SHOP GRID & CARDS — FULL MOBILE FIX
══════════════════════════════════════════════ */
.shop-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(255px,1fr));
  gap:1.75rem;
  padding-bottom:6rem;
  width:100%;
}

.supp-card{
  background:#fff;border-radius:16px;overflow:hidden;
  box-shadow:0 2px 16px rgba(26,59,34,.07);
  transition:transform .3s cubic-bezier(.34,1.2,.64,1),box-shadow .3s;
  display:flex;flex-direction:column;
  animation:cardIn .5s ease both;
  position:relative;
  border:1.5px solid var(--ivory-dd);border-top:3px solid var(--ivory-dd);
  width:100%; /* FIX: card never overflows grid cell */
  min-width:0; /* FIX: prevents flex blowout */
}
.supp-card:hover{transform:translateY(-6px);box-shadow:0 18px 44px rgba(26,59,34,.15);}
@keyframes cardIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.supp-card[data-cat="protein"]{border-top-color:var(--green-d);}
.supp-card[data-cat="vitamins"]{border-top-color:var(--jade);}
.supp-card[data-cat="ayurvedic"]{border-top-color:var(--gold);}
.supp-card[data-cat="weightloss"]{border-top-color:var(--coral);}
.supp-card.in-cart{box-shadow:0 0 0 2.5px var(--jade-l),0 8px 26px rgba(15,123,92,.2);}
.supp-card.in-cart::after{content:'✓ In Cart';position:absolute;top:10px;right:10px;z-index:10;background:var(--jade);color:#fff;font-size:.65rem;font-weight:800;padding:.22rem .55rem;border-radius:20px;font-family:'Cinzel',serif;}

/* ── Product image ── */
.supp-img-wrap{
  position:relative;overflow:hidden;
  height:200px;flex-shrink:0;
  background:#fff;cursor:pointer;
  border-bottom:1px solid var(--ivory-dd);
}
.supp-img-wrap img{
  width:100%;height:100%;
  object-fit:contain;object-position:center;
  transition:transform .5s;display:block;
  padding:.75rem;
}
.supp-card:hover .supp-img-wrap img{transform:scale(1.05);}
.supp-tag{position:absolute;top:10px;left:10px;font-size:.66rem;font-weight:800;text-transform:uppercase;padding:.28rem .7rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.22);font-family:'Cinzel',serif;}
.wish-btn{position:absolute;top:10px;right:10px;z-index:5;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.88);backdrop-filter:blur(4px);border:1px solid rgba(200,146,42,.2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1rem;transition:all .2s;box-shadow:0 2px 8px rgba(0,0,0,.1);}
.wish-btn:hover{transform:scale(1.15);background:#fff;}
.wish-btn.wished{background:rgba(232,83,74,.12);border-color:rgba(232,83,74,.3);}

/* ── Card body ── */
.supp-body{
  padding:1rem;
  flex:1;display:flex;flex-direction:column;
  overflow:hidden; /* FIX: prevent body overflow */
}
.bv-tag{display:inline-flex;align-items:center;gap:.25rem;background:rgba(200,146,42,.1);border:1px solid rgba(200,146,42,.22);color:#92400E;font-size:.75rem;font-weight:800;padding:.18rem .55rem;border-radius:20px;font-family:'Cinzel',serif;}
.supp-name{
  font-family:'DM Serif Display',serif;
  font-size:.95rem;font-weight:700;color:var(--ink);
  margin-bottom:.3rem;line-height:1.3;cursor:pointer;
  /* FIX: long names won't break layout */
  word-break:break-word;
}
.supp-name:hover{color:var(--green-m);}
.supp-rating-row{display:flex;align-items:center;gap:.35rem;margin-bottom:.5rem;flex-wrap:wrap;}
.stars-display{display:flex;gap:1px;}
.star-icon{font-size:.8rem;line-height:1;color:#d1d5db;}
.star-icon.filled{color:#f59e0b;}
.rating-num{font-size:.72rem;font-weight:800;color:var(--green-d);}
.rating-count{font-size:.66rem;color:var(--muted);}
.view-detail-link{font-size:.68rem;color:var(--jade);font-weight:700;cursor:pointer;background:rgba(15,123,92,.06);padding:.1rem .4rem;border-radius:20px;border:1px solid rgba(15,123,92,.15);transition:background .2s;white-space:nowrap;}
.view-detail-link:hover{background:rgba(15,123,92,.12);}
.supp-desc{font-size:.77rem;color:var(--muted);margin-bottom:.75rem;line-height:1.5;flex:1;}
.supp-price-row{display:flex;align-items:center;gap:.45rem;margin-bottom:.85rem;flex-wrap:wrap;}

/* ══════════════════════════════════════════════
   ACTION ROW — FULL REWRITE (mobile fix)
══════════════════════════════════════════════ */
.supp-action-row{
  border-top:1px solid var(--ivory-dd);
  padding-top:.85rem;
  display:flex;
  flex-direction:column;
  gap:.5rem;
  width:100%;
}

/* Qty + Add to Cart row */
.supp-action-top{
  display:flex;
  align-items:center;
  gap:.5rem;
  width:100%;
  min-width:0;
}

/* Qty stepper — compact on mobile */
.qty-wrap{
  display:flex;align-items:center;
  border:1.5px solid var(--ivory-dd);
  border-radius:50px;overflow:hidden;
  flex-shrink:0;background:#fff;
}
.qty-step{
  width:28px;height:32px;border:none;background:transparent;
  color:var(--green-m);font-size:1rem;font-weight:700;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  user-select:none;transition:background .15s;
}
.qty-step:hover{background:var(--ivory-d);}
.qty-input{
  width:28px;border:none;background:transparent;
  font-size:.82rem;font-weight:700;text-align:center;
  color:var(--ink);padding:0;
  border-left:1px solid var(--ivory-dd);
  border-right:1px solid var(--ivory-dd);
  outline:none;-moz-appearance:textfield;
}
.qty-input::-webkit-inner-spin-button{-webkit-appearance:none;}

/* Add to Cart button — FIX: flex:1, no fixed width, text won't cut */
.add-cart-btn{
  flex:1;
  min-width:0; /* FIX: flex child shrink */
  display:flex;align-items:center;justify-content:center;gap:.35rem;
  background:var(--green-d);color:#fff;
  border:none;border-radius:50px;
  padding:.54rem .6rem;
  font-size:.75rem;font-weight:800;
  cursor:pointer;
  transition:background .22s,transform .18s;
  white-space:nowrap;
  overflow:hidden; /* FIX: text clip instead of overflow */
  box-shadow:0 2px 10px rgba(26,59,34,.2);
}
.add-cart-btn:hover{background:var(--green-m);transform:translateY(-1px);}
.add-cart-btn.adding{background:linear-gradient(90deg,var(--jade),var(--jade-l));pointer-events:none;}
.add-cart-btn .btxt{
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}

/* Buy Now button — full width */
.buy-now-btn{
  width:100%;
  display:flex;align-items:center;justify-content:center;gap:.4rem;
  background:transparent;color:var(--gold-d);
  border:1.5px solid var(--gold);border-radius:50px;
  padding:.5rem .75rem;
  font-size:.75rem;font-weight:800;
  cursor:pointer;transition:all .22s;
  position:relative;overflow:hidden;
  white-space:nowrap;
}
.buy-now-btn::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,var(--gold-d),var(--gold-l));opacity:0;transition:opacity .22s;}
.buy-now-btn:hover{color:var(--green-dd);transform:translateY(-1px);}
.buy-now-btn:hover::before{opacity:1;}
.buy-now-btn span{position:relative;z-index:1;}

/* Simple qty (shown on very small screens) */
.qty-simple{
  display:none;
  width:44px;padding:.45rem .3rem;
  border:1.5px solid var(--ivory-dd);border-radius:8px;
  font-size:.82rem;text-align:center;
  background:var(--ivory);color:var(--ink);
  outline:none;flex-shrink:0;
}

/* ── Alerts ── */
.alert{padding:.85rem 1.1rem;border-radius:10px;font-size:.875rem;font-weight:600;margin-bottom:1rem;}
.alert-success{background:rgba(15,123,92,.1);color:var(--jade);border-left:3px solid var(--jade-l);}
.alert-danger{background:rgba(232,83,74,.1);color:#9A1A09;border-left:3px solid var(--coral);}
.shop-empty{text-align:center;padding:4rem 2rem;color:var(--muted);background:#fff;border-radius:16px;border:1.5px solid var(--ivory-dd);margin-top:2rem;}

/* ══ DETAIL MODAL ══ */
@keyframes fadeInOv   { from{opacity:0} to{opacity:1} }
@keyframes slideModal { from{opacity:0;transform:translateY(36px) scale(.96)} to{opacity:1;transform:none} }
.pd-overlay{display:none;}
.pd-overlay.open{display:flex;align-items:center;justify-content:center;position:fixed;inset:0;z-index:99999;background:rgba(3,8,4,.9);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);padding:1rem;overflow-y:auto;animation:fadeInOv .28s ease;}
.pd-modal-wrapper{display:contents;}
.pd-modal{background:linear-gradient(160deg,#0d1f11 0%,#091409 100%);border-radius:22px;width:100%;max-width:920px;max-height:92vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 0 0 1px rgba(200,146,42,.18),0 50px 120px rgba(0,0,0,.7);animation:slideModal .42s cubic-bezier(.22,.68,0,1.12);position:relative;flex-shrink:0;margin:auto;}
.pd-modal::before{content:'';position:absolute;top:0;left:0;right:0;height:1.5px;background:linear-gradient(90deg,transparent 0%,rgba(200,146,42,.6) 30%,var(--gold-l) 50%,rgba(200,146,42,.6) 70%,transparent 100%);border-radius:22px 22px 0 0;z-index:10;}
.pd-topbar{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.5rem;background:rgba(6,14,7,.75);backdrop-filter:blur(8px);border-bottom:1px solid rgba(255,255,255,.055);flex-shrink:0;position:sticky;top:0;z-index:6;gap:1rem;}
.pd-topbar-left{display:flex;align-items:center;gap:.75rem;min-width:0;}
.pd-topbar-chip{font-family:'Cinzel',serif;font-size:.58rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;padding:.22rem .75rem;border-radius:20px;white-space:nowrap;flex-shrink:0;}
.pd-topbar-title{font-family:'DM Serif Display',serif;font-size:.95rem;font-weight:700;color:rgba(255,255,255,.8);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pd-close{width:36px;height:36px;border-radius:11px;flex-shrink:0;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.45);font-size:1.05rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.pd-close:hover{background:rgba(232,83,74,.18);border-color:rgba(232,83,74,.3);color:#ff8080;transform:rotate(90deg);}
.pd-hero{display:grid;grid-template-columns:40% 60%;height:360px;flex-shrink:0;}
.pd-img-side{position:relative;overflow:hidden;background:#fff;border-right:1px solid var(--ivory-dd);}
.pd-img-side img{width:100%;height:100%;object-fit:contain;object-position:center;display:block;transition:transform .7s ease;padding:1rem;}
.pd-modal:hover .pd-img-side img{transform:scale(1.04);}
.pd-img-side::after{display:none;}
.pd-img-cat{position:absolute;top:1rem;left:1rem;z-index:2;display:inline-flex;align-items:center;gap:.35rem;font-family:'Cinzel',serif;font-size:.6rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;padding:.3rem .8rem;border-radius:20px;backdrop-filter:blur(10px);box-shadow:0 2px 14px rgba(0,0,0,.35);}
.pd-img-bv{position:absolute;bottom:1rem;left:1rem;z-index:2;display:inline-flex;align-items:center;gap:.45rem;background:rgba(26,59,34,.85);border:1px solid rgba(200,146,42,.28);color:var(--gold-l);font-family:'Cinzel',serif;font-size:.72rem;font-weight:800;padding:.38rem .9rem;border-radius:20px;backdrop-filter:blur(8px);}
.pd-bv-dot{width:7px;height:7px;border-radius:50%;background:var(--gold-l);box-shadow:0 0 8px var(--gold);animation:pulse-dot 2s ease infinite;}
@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.85)}}
.pd-info-side{padding:1.4rem 1.6rem 1.2rem;display:flex;flex-direction:column;gap:.75rem;overflow-y:auto;}
.pd-info-side::-webkit-scrollbar{width:3px;}
.pd-info-side::-webkit-scrollbar-thumb{background:rgba(200,146,42,.2);border-radius:10px;}
.pd-prod-name{font-family:'DM Serif Display',serif;font-size:1.35rem;font-weight:700;color:#fff;line-height:1.25;}
.pd-prod-desc{font-size:.81rem;color:rgba(255,255,255,.38);line-height:1.7;}
.pd-price-row{display:flex;align-items:baseline;gap:.65rem;flex-wrap:wrap;padding:.8rem 1.1rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:14px;}
.pd-price-now{font-family:'Cinzel',serif;font-size:1.9rem;font-weight:900;color:var(--gold-l);line-height:1;letter-spacing:-.02em;}
.pd-price-was{font-size:.85rem;color:rgba(255,255,255,.22);text-decoration:line-through;}
.pd-price-off{font-size:.65rem;font-weight:800;font-family:'Cinzel',serif;padding:.2rem .6rem;border-radius:20px;letter-spacing:.05em;background:rgba(232,83,74,.15);color:#ff9490;border:1px solid rgba(232,83,74,.22);}
.pd-psb-head{font-family:'Cinzel',serif;font-size:.57rem;font-weight:800;color:rgba(200,146,42,.55);text-transform:uppercase;letter-spacing:.14em;margin-bottom:.45rem;}
.pd-psb-row{display:grid;grid-template-columns:repeat(7,1fr);gap:.28rem;}
.pd-psb-cell{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:9px;padding:.45rem .2rem;text-align:center;transition:all .22s;cursor:default;}
.pd-psb-cell:hover{background:rgba(200,146,42,.09);border-color:rgba(200,146,42,.22);transform:translateY(-3px);}
.pd-psb-cell.p1{background:rgba(200,146,42,.1);border-color:rgba(200,146,42,.28);}
.pd-psb-cell-l{font-size:.5rem;font-weight:700;color:rgba(255,255,255,.28);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.18rem;}
.pd-psb-cell.p1 .pd-psb-cell-l{color:rgba(200,146,42,.65);}
.pd-psb-cell-p{font-size:.58rem;font-weight:800;color:rgba(255,255,255,.38);font-family:'Cinzel',serif;margin-bottom:.12rem;}
.pd-psb-cell.p1 .pd-psb-cell-p{color:var(--gold-l);}
.pd-psb-cell-a{font-size:.74rem;font-weight:900;color:rgba(255,255,255,.72);font-family:'Cinzel',serif;}
.pd-psb-cell.p1 .pd-psb-cell-a{color:var(--gold-l);}
.pd-psb-note{font-size:.58rem;color:rgba(255,255,255,.18);font-style:italic;margin-top:.35rem;}
.pd-bottom-row{display:flex;align-items:center;gap:.85rem;margin-top:auto;padding-top:.6rem;border-top:1px solid rgba(255,255,255,.07);}
.pd-qty-box{display:flex;align-items:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:50px;overflow:hidden;flex-shrink:0;}
.pd-qty-btn{width:36px;height:38px;border:none;background:transparent;color:rgba(255,255,255,.55);font-size:1.15rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;}
.pd-qty-btn:hover{background:rgba(255,255,255,.1);color:#fff;}
.pd-qty-num{width:38px;text-align:center;border:none;background:transparent;color:#fff;font-size:.95rem;font-weight:700;outline:none;border-left:1px solid rgba(255,255,255,.1);border-right:1px solid rgba(255,255,255,.1);-moz-appearance:textfield;}
.pd-qty-num::-webkit-inner-spin-button{-webkit-appearance:none;}
.pd-act-cart{flex:1;display:flex;align-items:center;justify-content:center;gap:.5rem;background:linear-gradient(135deg,#1a3b22,#2a6336);color:#fff;border:none;border-radius:14px;padding:.82rem 1rem;font-family:'Cinzel',serif;font-size:.76rem;font-weight:800;letter-spacing:.06em;cursor:pointer;transition:all .22s;box-shadow:0 4px 18px rgba(26,59,34,.45);white-space:nowrap;}
.pd-act-cart:hover{background:linear-gradient(135deg,#2a6336,#3a8a4a);transform:translateY(-2px);}
.pd-act-buy{flex:1;display:flex;align-items:center;justify-content:center;gap:.5rem;background:linear-gradient(135deg,var(--gold-d),var(--gold));color:#0a1a0d;border:none;border-radius:14px;padding:.82rem 1rem;font-family:'Cinzel',serif;font-size:.76rem;font-weight:800;letter-spacing:.06em;cursor:pointer;transition:all .22s;box-shadow:0 4px 18px rgba(200,146,42,.38);white-space:nowrap;}
.pd-act-buy:hover{background:linear-gradient(135deg,var(--gold),var(--gold-l));transform:translateY(-2px);}

/* Reviews */
.pd-reviews{background:rgba(4,10,5,.8);border-top:1px solid rgba(255,255,255,.06);flex-shrink:0;}
.pd-tab-bar{display:flex;border-bottom:1px solid rgba(255,255,255,.06);position:sticky;top:0;z-index:2;background:rgba(6,14,7,.9);backdrop-filter:blur(10px);}
.pd-tab{flex:1;padding:.72rem 1rem;font-family:'Cinzel',serif;font-size:.66rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.28);background:transparent;border:none;cursor:pointer;transition:color .2s;position:relative;}
.pd-tab.on{color:var(--gold-l);}
.pd-tab.on::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold-l),transparent);}
.pd-tab:hover:not(.on){color:rgba(255,255,255,.5);}
.pd-panel{display:none;padding:1.2rem 1.5rem 1.5rem;}
.pd-panel.on{display:block;}
.pd-rv-top{display:flex;align-items:flex-start;gap:1.5rem;margin-bottom:1.1rem;flex-wrap:wrap;}
.pd-rv-score{font-family:'DM Serif Display',serif;font-size:3.8rem;font-weight:800;color:#fff;line-height:1;flex-shrink:0;}
.pd-rv-score-stars{display:flex;gap:2px;margin:.2rem 0 .2rem;}
.pd-rv-score-stars .si{font-size:1.05rem;color:#2a3b2c;}
.pd-rv-score-stars .si.f{color:#f59e0b;}
.pd-rv-score-label{font-size:.7rem;color:rgba(255,255,255,.25);}
.pd-rv-bars{flex:1;min-width:150px;display:flex;flex-direction:column;gap:.38rem;}
.pd-rv-bar-r{display:flex;align-items:center;gap:.5rem;}
.pd-rv-bar-l{font-size:.62rem;font-weight:700;color:rgba(255,255,255,.3);min-width:22px;}
.pd-rv-bar-t{flex:1;height:5px;background:rgba(255,255,255,.07);border-radius:10px;overflow:hidden;}
.pd-rv-bar-f{height:100%;border-radius:10px;background:linear-gradient(90deg,#f59e0b,#fbbf24);transition:width 1s cubic-bezier(.22,.68,0,1.2);width:0%;}
.pd-rv-bar-c{font-size:.6rem;font-weight:700;color:rgba(255,255,255,.22);min-width:14px;text-align:right;}
.pd-rv-list{display:flex;flex-direction:column;gap:.55rem;max-height:195px;overflow-y:auto;padding-right:.25rem;margin-bottom:.9rem;}
.pd-rv-list::-webkit-scrollbar{width:3px;}
.pd-rv-list::-webkit-scrollbar-thumb{background:rgba(200,146,42,.2);border-radius:10px;}
.pd-rv-item{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:.8rem 1rem;transition:border-color .2s;}
.pd-rv-item:hover{border-color:rgba(200,146,42,.12);}
.pd-rv-item-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:.3rem;flex-wrap:wrap;gap:.3rem;}
.pd-rv-author{font-weight:800;font-size:.8rem;color:rgba(255,255,255,.72);display:flex;align-items:center;gap:.4rem;}
.pd-rv-ver{font-size:.55rem;font-weight:800;padding:.1rem .35rem;border-radius:4px;background:rgba(93,184,112,.15);color:#5db870;font-family:'Cinzel',serif;letter-spacing:.06em;}
.pd-rv-meta{display:flex;align-items:center;gap:.4rem;}
.pd-rv-item-stars{display:flex;gap:1px;}
.pd-rv-item-stars .si{font-size:.72rem;color:#2a3b2c;}
.pd-rv-item-stars .si.f{color:#f59e0b;}
.pd-rv-date{font-size:.6rem;color:rgba(255,255,255,.2);}
.pd-rv-body{font-size:.78rem;color:rgba(255,255,255,.42);line-height:1.65;}
.pd-write-wrap{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:1.1rem 1.25rem;}
.pd-write-head{font-family:'Cinzel',serif;font-size:.62rem;font-weight:800;color:rgba(200,146,42,.65);text-transform:uppercase;letter-spacing:.14em;margin-bottom:.8rem;}
.pd-star-pick{display:flex;gap:.4rem;margin-bottom:.4rem;}
.pd-star{font-size:1.65rem;color:rgba(255,255,255,.12);cursor:pointer;transition:color .12s,transform .15s;user-select:none;}
.pd-star.h,.pd-star.s{color:#f59e0b;}
.pd-star:hover{transform:scale(1.22);}
.pd-star-hint{font-size:.7rem;color:rgba(255,255,255,.2);margin-bottom:.7rem;min-height:.85rem;}
.pd-rv-ta{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:.72rem .95rem;color:rgba(255,255,255,.72);font-size:.82rem;line-height:1.6;outline:none;resize:vertical;min-height:80px;transition:border-color .2s;}
.pd-rv-ta::placeholder{color:rgba(255,255,255,.18);}
.pd-rv-ta:focus{border-color:rgba(200,146,42,.32);}
.pd-rv-sub{margin-top:.65rem;background:linear-gradient(135deg,#1a3b22,#2a6336);color:#fff;border:none;border-radius:50px;padding:.58rem 1.4rem;font-family:'Cinzel',serif;font-size:.7rem;font-weight:800;letter-spacing:.07em;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem;}
.pd-rv-sub:hover{background:linear-gradient(135deg,#2a6336,#3a8a4a);transform:translateY(-1px);}
.pd-rv-sub:disabled{opacity:.4;cursor:not-allowed;transform:none;}
.pd-rv-done{background:rgba(93,184,112,.14);color:#5db870;border:1px solid rgba(93,184,112,.22);border-radius:8px;padding:.5rem .85rem;font-size:.78rem;font-weight:700;margin-top:.65rem;display:none;}
.pd-login-note{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:1.1rem;text-align:center;font-size:.82rem;color:rgba(255,255,255,.3);line-height:1.75;}
.pd-login-note a{color:var(--gold-l);font-weight:700;text-decoration:none;}
.pd-no-reviews{font-size:.82rem;color:rgba(255,255,255,.25);padding:.5rem 0;text-align:center;}

/* ══════════════════════════════════════════════
   RESPONSIVE BREAKPOINTS — MOBILE FIRST FIXES
══════════════════════════════════════════════ */

/* Tablet: 2 column */
@media (max-width:768px){
  .shop-header h1{font-size:1.7rem;}

  .shop-grid{
    grid-template-columns:repeat(2,1fr);
    gap:.85rem;
    padding-left:0;padding-right:0;
  }

  .supp-img-wrap{height:155px;}

  .supp-body{padding:.8rem .75rem;}

  .supp-name{font-size:.88rem;}

  .supp-desc{display:none;} /* hide desc on tablet to save space */

  .supp-rating-row{gap:.25rem;}
  .view-detail-link{display:none;} /* hide on small cards */

  /* Action row on tablet */
  .supp-action-top{gap:.4rem;}
  .qty-step{width:26px;height:30px;font-size:.95rem;}
  .qty-input{width:26px;font-size:.78rem;}
  .add-cart-btn{font-size:.72rem;padding:.5rem .45rem;}
  .buy-now-btn{font-size:.72rem;padding:.48rem .5rem;}

  .guest-banner-btns{margin-left:0;width:100%;}

  /* Filter scroll on mobile */
  .filter-cards{flex-wrap:nowrap;overflow-x:auto;padding-bottom:1rem;scrollbar-width:none;}
  .filter-cards::-webkit-scrollbar{display:none;}
  .fcat{flex-shrink:0;}
}

/* Mobile: still 2 column but tighter */
@media (max-width:540px){
  .shop-grid{
    grid-template-columns:repeat(2,1fr);
    gap:.6rem;
  }

  .supp-img-wrap{height:130px;}

  .supp-body{padding:.65rem .6rem;}

  .supp-name{font-size:.82rem;}

  .bv-tag{font-size:.68rem;padding:.12rem .4rem;}

  /* On very small: hide stepper, show simple input */
  .qty-wrap{display:none;}
  .qty-simple{display:block !important;}

  /* Action top: simple qty + button */
  .supp-action-top{gap:.4rem;}
  .add-cart-btn{font-size:.7rem;padding:.48rem .4rem;}
  .buy-now-btn{font-size:.7rem;padding:.44rem .4rem;}

  .bv-tag{font-size:.65rem;}
}

/* Very small: single column */
@media (max-width:360px){
  .shop-grid{
    grid-template-columns:1fr;
    gap:.75rem;
  }
  .supp-img-wrap{height:200px;}
  .supp-desc{display:block;}
  .supp-name{font-size:.9rem;}
  .qty-wrap{display:flex !important;} /* restore stepper on single col */
  .qty-simple{display:none !important;}
  .view-detail-link{display:inline;}
}

/* Modal mobile */
@media(max-width:700px){
  .pd-hero{grid-template-columns:1fr;height:auto;}
  .pd-img-side{height:230px;}
  .pd-psb-row{grid-template-columns:repeat(4,1fr);}
  .pd-bottom-row{flex-wrap:wrap;}
  .pd-topbar-title{max-width:180px;}
}
@media(max-width:480px){
  .pd-overlay.open{padding:.5rem;}
  .pd-modal{max-height:96vh;border-radius:18px;}
  .pd-act-cart,.pd-act-buy{font-size:.68rem;padding:.72rem .7rem;}
  .pd-rv-top{flex-direction:column;gap:.75rem;}
}
</style>

<!-- ATC Overlay -->
<div class="atc-overlay" id="atcOverlay">
  <div class="atc-card">
    <div class="atc-check">✓</div>
    <div class="atc-title">Added to Cart!</div>
    <div class="atc-name" id="atcName"></div>
    <div class="atc-bar-wrap"><div class="atc-bar" id="atcBar"></div></div>
    <div class="atc-sub">Opening your cart…</div>
  </div>
</div>

<!-- Shop Header -->
<div class="shop-header">
  <div class="container shop-header-inner">
    <div>
      <?php if ($isMshopPlus): ?>
        <h1>🛍️ MShop <em>Plus</em></h1>
        <p>Exclusive products for active Business Club members</p>
      <?php else: ?>
        <h1>🛒 <em>MShop</em></h1>
        <p>Official Mfills product store · Every purchase generates Business Volume (BV)</p>
      <?php endif; ?>
      <?php if ($loggedIn && !$isMshopPlus && $isClub): ?>
        <a href="?mode=mshop_plus" style="color:var(--gold-l);font-size:.82rem;font-weight:700;text-decoration:none;display:inline-block;margin-top:.35rem;font-family:'Cinzel',serif">✨ Switch to MShop Plus</a>
      <?php elseif ($loggedIn && $isMshopPlus): ?>
        <a href="?mode=mshop" style="color:rgba(255,255,255,.55);font-size:.8rem;font-weight:700;text-decoration:none;display:inline-block;margin-top:.35rem;font-family:'Cinzel',serif">← Back to MShop</a>
      <?php endif; ?>
    </div>
    <a href="cart.php" class="cart-fab">🛒 Cart <span class="cart-fab-count" id="cartCount">0</span></a>
  </div>
</div>

<?php if (!$loggedIn): ?>
<div class="guest-banner">
  <span style="font-size:1.1rem">💾</span>
  <span class="guest-banner-text"><strong>Cart saves automatically!</strong> Add products freely — login when ready to checkout.</span>
  <div class="guest-banner-btns">
    <a href="<?= APP_URL ?>/login.php" class="gb-btn gb-login">Login</a>
    <a href="<?= APP_URL ?>/register.php" class="gb-btn gb-register">Register Free</a>
  </div>
</div>
<?php endif; ?>

<?php if ($isMshopPlus && $loggedIn && !$isClub): ?>
<div style="background:rgba(200,146,42,.1);border-bottom:2px solid rgba(200,146,42,.3);padding:.85rem 1.5rem;font-size:.85rem;font-weight:600;color:#92400E;display:flex;align-items:center;gap:.6rem;">
  ⚠️ MShop Plus is for active <strong>Business Club</strong> members only.
  <a href="?mode=mshop" style="color:var(--gold-d);font-weight:800;font-family:'Cinzel',serif">Shop on MShop →</a>
</div>
<?php endif; ?>

<div class="container" style="padding-top:1.5rem;background:#f8f5ef;padding-bottom:3rem;min-height:60vh">

  <?php if ($message): ?>
    <div class="alert alert-<?= e($msgType) ?>" style="margin-top:1.25rem"><?= e($message) ?></div>
  <?php endif; ?>

  <?php if (empty($allProducts)): ?>
    <div class="shop-empty">
      <span style="font-size:3.5rem;opacity:.3;margin-bottom:1rem;display:block">💊</span>
      <h3 style="font-family:'DM Serif Display',serif;color:var(--green-d)">No products found</h3>
      <p style="font-size:.8rem;color:var(--muted);margin-top:.5rem">Shop mode: <strong><?= e($shopMode) ?></strong></p>
    </div>
  <?php else: ?>

  <?php
  $catCounts = ['all'=>count($allProducts),'protein'=>0,'vitamins'=>0,'ayurvedic'=>0,'weightloss'=>0,'other'=>0];
  foreach ($allProducts as $_p) { $catCounts[getProductCategory($_p['name'])]++; }
  ?>

  <div class="filter-section" style="margin-top:1.25rem">
    <div class="filter-header">
      <span class="filter-heading">Shop by Category</span>
      <span class="item-count" id="itemCount"><strong><?= count($allProducts) ?></strong> products</span>
    </div>
    <div class="filter-cards">
      <button class="fcat active" data-cat="all" onclick="filterCat('all',this)">
        <span class="fcat-icon">🛍️</span>
        <span style="display:flex;flex-direction:column;gap:.1rem"><span class="fcat-label">All</span><span class="fcat-count"><?= $catCounts['all'] ?></span></span>
      </button>
      <?php foreach (['protein'=>'🏋️','vitamins'=>'🌿','ayurvedic'=>'🌱','weightloss'=>'🔥'] as $cat=>$icon): ?>
      <button class="fcat" data-cat="<?= $cat ?>" onclick="filterCat('<?= $cat ?>',this)">
        <span class="fcat-icon"><?= $icon ?></span>
        <span style="display:flex;flex-direction:column;gap:.1rem"><span class="fcat-label"><?= $catMeta[$cat]['label'] ?></span><span class="fcat-count"><?= $catCounts[$cat] ?></span></span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="toolbar">
    <div class="search-wrap"><input type="text" id="searchInp" placeholder="Search products…" oninput="searchProducts(this.value)"></div>
    <select class="sort-select" onchange="sortProducts(this.value)">
      <option value="default">Sort: Default</option>
      <option value="price-asc">Price: Low → High</option>
      <option value="price-desc">Price: High → Low</option>
      <option value="bv-desc">BV: High → Low</option>
      <option value="name-asc">Name: A → Z</option>
    </select>
  </div>

  <div class="shop-grid" id="shopGrid">
  <?php foreach ($allProducts as $idx => $p):
    $cat = getProductCategory($p['name']); $meta = $catMeta[$cat];
    $bv = (float)($p['bv'] ?? $p['price']);
    $discPct = (float)($p['discount_pct'] ?? 0);
    $discPrice = $discPct > 0 ? round($p['price'] * (1 - $discPct/100), 2) : null;
    $sellingPrice = $discPrice ?? $p['price'];
    $pid = (int)$p['id']; $delay = ($idx % 8) * 0.05;
  ?>
  <div class="supp-card" data-cat="<?= $cat ?>" data-pid="<?= $pid ?>" id="card-<?= $pid ?>" style="animation-delay:<?= $delay ?>s">

    <!-- Image -->
    <div class="supp-img-wrap" onclick="window.location.href='product.php?id=<?= $pid ?>&mode=<?= e($shopMode) ?>'">
      <img src="<?= e($p['image_url'] ?? '') ?>" alt="<?= e($p['name']) ?>" loading="lazy" onerror="this.style.opacity='.3'">
      <span class="supp-tag" style="background:<?= $meta['badge_bg'] ?>;color:<?= $meta['badge_fg'] ?>"><?= $meta['icon'] ?> <?= $meta['label'] ?></span>
      <button class="wish-btn" id="wish-<?= $pid ?>" onclick="event.stopPropagation();toggleWish(<?= $pid ?>)" title="Wishlist">🤍</button>
    </div>

    <!-- Body -->
    <div class="supp-body">

      <!-- Name -->
      <div class="supp-name" onclick="window.location.href='product.php?id=<?= $pid ?>&mode=<?= e($shopMode) ?>'">
        <?= e($p['name']) ?>
      </div>

      <!-- Ratings -->
      <div class="supp-rating-row">
        <div class="stars-display" id="stars-<?= $pid ?>">
          <?php for($s=1;$s<=5;$s++) echo '<span class="star-icon">★</span>'; ?>
        </div>
        <span class="rating-num" id="rnum-<?= $pid ?>"></span>
        <span class="rating-count" id="rcnt-<?= $pid ?>">No ratings</span>
        <span class="view-detail-link" onclick="window.location.href='product.php?id=<?= $pid ?>&mode=<?= e($shopMode) ?>'">Details →</span>
      </div>

      <!-- Desc -->
      <div class="supp-desc"><?= e($p['description'] ?? '') ?></div>

      <!-- BV -->
      <div class="supp-price-row">
        <span class="bv-tag">BV ₹<?= number_format($bv, 0) ?></span>
      </div>

      <!-- Actions -->
      <div class="supp-action-row">
        <div class="supp-action-top">
          <!-- Stepper (hidden on small mobile) -->
          <div class="qty-wrap">
            <button type="button" class="qty-step" onclick="stepQty(this,-1)">−</button>
            <input type="number" class="qty-input" value="1" min="1" max="10">
            <button type="button" class="qty-step" onclick="stepQty(this,1)">+</button>
          </div>
          <!-- Simple input (shown on small mobile) -->
          <input type="number" class="qty-simple" value="1" min="1" max="10">

          <?php if ($loggedIn): ?>
            <button type="button" class="add-cart-btn" id="addbtn-<?= $pid ?>" onclick="handleAddToCart(<?= $pid ?>)">
              <span>🛒</span><span class="btxt">Add to Cart</span>
            </button>
          <?php else: ?>
            <button type="button" class="add-cart-btn" id="addbtn-<?= $pid ?>" onclick="handleGuestAdd(<?= $pid ?>)">
              <span>🛒</span><span class="btxt">Add</span>
            </button>
          <?php endif; ?>
        </div>

        <?php if ($loggedIn): ?>
          <button type="button" class="buy-now-btn" onclick="handleBuyNow(<?= $pid ?>)">
            <span>⚡ Buy Now — ₹<?= number_format($sellingPrice, 0) ?></span>
          </button>
        <?php else: ?>
          <button type="button" class="buy-now-btn" onclick="window.location.href='<?= APP_URL ?>/login.php'">
            <span>🔐 Login to Buy</span>
          </button>
        <?php endif; ?>
      </div>

    </div>
  </div>
  <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ══ DETAIL MODAL ══ -->
<div class="pd-overlay" id="pdOverlay">
  <div class="pd-modal-wrapper">
    <div class="pd-modal">
      <div class="pd-topbar">
        <div class="pd-topbar-left">
          <span class="pd-topbar-chip" id="pdChip">—</span>
          <span class="pd-topbar-title" id="pdTitle">—</span>
        </div>
        <button class="pd-close" onclick="closeDetail()">✕</button>
      </div>
      <div class="pd-hero">
        <div class="pd-img-side">
          <img id="pdImg" src="" alt="">
          <span class="pd-img-cat" id="pdCat"></span>
          <div class="pd-img-bv"><span class="pd-bv-dot"></span><span id="pdBv">BV ₹0</span></div>
        </div>
        <div class="pd-info-side">
          <div class="pd-prod-name" id="pdName">—</div>
          <div class="pd-prod-desc" id="pdDesc"></div>
          <div class="pd-price-row">
            <span class="pd-price-now" id="pdPriceNow">₹0</span>
            <span class="pd-price-was" id="pdPriceWas" style="display:none"></span>
            <span class="pd-price-off" id="pdPriceOff" style="display:none"></span>
          </div>
          <div>
            <div class="pd-psb-head">Partner Sales Bonus per purchase</div>
            <div class="pd-psb-row" id="pdPsb"></div>
            <div class="pd-psb-note">*PSB calculated on BV, not MRP</div>
          </div>
          <div class="pd-bottom-row">
            <div class="pd-qty-box">
              <button class="pd-qty-btn" onclick="pdAdjQty(-1)">−</button>
              <input type="number" class="pd-qty-num" id="pdQty" value="1" min="1" max="10">
              <button class="pd-qty-btn" onclick="pdAdjQty(1)">+</button>
            </div>
            <button class="pd-act-cart" id="pdActCart">🛒 Add to Cart</button>
            <button class="pd-act-buy" id="pdActBuy">⚡ Buy Now</button>
          </div>
        </div>
      </div>
      <div class="pd-reviews">
        <div class="pd-tab-bar">
          <button class="pd-tab on" onclick="pdSwitchTab('reviews',this)">⭐ Reviews</button>
          <button class="pd-tab" onclick="pdSwitchTab('write',this)">✍️ Write Review</button>
        </div>
        <div class="pd-panel on" id="pdPanelReviews">
          <div id="pdRvSummary"></div>
          <div class="pd-rv-list" id="pdRvList"></div>
        </div>
        <div class="pd-panel" id="pdPanelWrite">
          <?php if ($loggedIn): ?>
          <div class="pd-write-wrap">
            <div class="pd-write-head">Share your experience</div>
            <div class="pd-star-pick">
              <?php for($s=1;$s<=5;$s++): ?>
              <span class="pd-star" onclick="pdPickStar(<?= $s ?>)" onmouseenter="pdHoverStar(<?= $s ?>)" onmouseleave="pdUnhover()">★</span>
              <?php endfor; ?>
            </div>
            <div class="pd-star-hint" id="pdStarHint">👆 Tap to rate</div>
            <textarea class="pd-rv-ta" id="pdRvTa" placeholder="What did you think about this product?"></textarea>
            <button class="pd-rv-sub" id="pdRvSub" onclick="pdSubmitReview()">📤 Submit Review</button>
            <div class="pd-rv-done" id="pdRvDone">✅ Thank you for your review!</div>
          </div>
          <?php else: ?>
          <div class="pd-login-note">Please <a href="<?= APP_URL ?>/login.php">Login</a> or <a href="<?= APP_URL ?>/register.php">Register</a> to write a review.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($loggedIn): ?>
<form method="POST" action="<?= APP_URL ?>/checkout.php" id="buyNowForm" style="display:none">
  <input type="hidden" name="shop_source" value="<?= e($shopMode) ?>">
  <input type="hidden" name="cart_data" id="buyNowData" value="[]">
</form>
<?php endif; ?>

<script>
var CR=<?= json_encode($commRates) ?>,APP_URL='<?= addslashes(APP_URL) ?>',LOGGED_IN=<?= $loggedIn?'true':'false' ?>;
var CART_KEY=LOGGED_IN?'mfills_cart_auth':'mfills_gc';
var PRODUCTS=<?= json_encode($jsProducts,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
function bvc(bv,l){return Math.round(bv*(CR[l]||0)/100);}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmt(n){return Number(n).toLocaleString('en-IN',{maximumFractionDigits:0});}
function $i(id){return document.getElementById(id);}
function cardQty(pid){
  var c=$i('card-'+pid);if(!c)return 1;
  // Check which qty input is visible
  var simple=c.querySelector('.qty-simple');
  var stepper=c.querySelector('.qty-input');
  var val=1;
  if(simple&&simple.offsetParent!==null){val=simple.value;}
  else if(stepper){val=stepper.value;}
  return Math.min(10,Math.max(1,parseInt(val)||1));
}

/* ── Cart ── */
function cGet(){try{return JSON.parse(localStorage.getItem(CART_KEY)||'{}')}catch(e){return{}}}
function cSet(c){try{localStorage.setItem(CART_KEY,JSON.stringify(c))}catch(e){}}
function cCount(){var c=cGet(),n=0;Object.keys(c).forEach(function(p){n+=(parseInt(c[p].qty)||1);});return n;}
function updateBadge(){
  var el=$i('cartCount'),n=cCount();
  if(el){el.textContent=n;if(n>0){el.classList.remove('bump');void el.offsetWidth;el.classList.add('bump');}}
  var nb=$i('nlCartBadge')||$i('ndCartBadge');
  if(nb){if(n>0){nb.textContent=n>99?'99+':n;nb.style.display='flex';}else nb.style.display='none';}
}
function showAtc(name){
  var ov=$i('atcOverlay'),ne=$i('atcName'),bar=$i('atcBar');
  if(ne)ne.textContent=name;
  ov.classList.add('show');
  setTimeout(function(){if(bar)bar.style.width='100%';},60);
  setTimeout(function(){window.location.href='cart.php';},780);
}
function handleAddToCart(pid){
  var d=PRODUCTS[pid];if(!d)return;
  var qty=cardQty(pid),c=cGet();
  c[pid]={product_id:d.pid,name:d.name,price:d.sellingPrice,bv:d.bv,image_url:d.img,cat:d.cat,qty:qty};
  cSet(c);
  var btn=$i('addbtn-'+pid);
  if(btn){btn.classList.add('adding');var t=btn.querySelector('.btxt');if(t)t.textContent='✓ Added!';}
  var card=$i('card-'+pid);if(card)card.classList.add('in-cart');
  updateBadge();showAtc(d.name);
}
function handleGuestAdd(pid){handleAddToCart(pid);}
function handleBuyNow(pid){
  var d=PRODUCTS[pid];if(!d)return;
  var qty=cardQty(pid);
  <?php if($loggedIn):?>
  $i('buyNowData').value=JSON.stringify([{product_id:d.pid,qty:qty,price:d.sellingPrice,bv:d.bv,name:d.name,image_url:d.img,cat:d.cat}]);
  $i('buyNowForm').submit();
  <?php else:?>
  window.location.href=APP_URL+'/login.php';
  <?php endif;?>
}

/* ── Wishlist ── */
var WK='mfills_wish';
function wGet(){try{return JSON.parse(localStorage.getItem(WK)||'[]')}catch(e){return[];}}
function wSet(a){try{localStorage.setItem(WK,JSON.stringify(a))}catch(e){}}
function toggleWish(pid){
  var w=wGet(),i=w.indexOf(String(pid));
  if(i>=0)w.splice(i,1);else w.push(String(pid));
  wSet(w);
  var b=$i('wish-'+pid);
  if(b){b.textContent=w.indexOf(String(pid))>=0?'❤️':'🤍';b.classList.toggle('wished',w.indexOf(String(pid))>=0);}
}
function restoreWish(){wGet().forEach(function(pid){var b=$i('wish-'+pid);if(b){b.textContent='❤️';b.classList.add('wished');}});}
function restoreCart(){
  var c=cGet();
  Object.keys(c).forEach(function(pid){
    var card=$i('card-'+pid);if(!card)return;
    card.classList.add('in-cart');
    var b=$i('addbtn-'+pid);
    if(b){b.classList.add('adding');var t=b.querySelector('.btxt');if(t)t.textContent='✓ In Cart';}
  });
  updateBadge();
}

/* ── Filter / Search / Sort ── */
function searchProducts(q){
  q=q.toLowerCase().trim();var v=0;
  document.querySelectorAll('#shopGrid .supp-card').forEach(function(c){
    var p=PRODUCTS[c.dataset.pid]||{};
    var m=!q||(p.name||'').toLowerCase().indexOf(q)>=0||(p.description||'').toLowerCase().indexOf(q)>=0;
    c.style.display=m?'':'none';
    if(m){v++;c.style.animation='none';void c.offsetWidth;c.style.animation='cardIn .4s ease both';}
  });
  var ic=$i('itemCount');if(ic)ic.innerHTML='<strong>'+v+'</strong> products';
}
function sortProducts(val){
  var g=$i('shopGrid'),cs=Array.from(g.querySelectorAll('.supp-card'));
  cs.sort(function(a,b){
    var pa=PRODUCTS[a.dataset.pid]||{},pb=PRODUCTS[b.dataset.pid]||{};
    if(val==='price-asc')return(pa.sellingPrice||0)-(pb.sellingPrice||0);
    if(val==='price-desc')return(pb.sellingPrice||0)-(pa.sellingPrice||0);
    if(val==='bv-desc')return(pb.bv||0)-(pa.bv||0);
    if(val==='name-asc')return(pa.name||'').localeCompare(pb.name||'');
    return 0;
  });
  cs.forEach(function(c,i){c.style.animationDelay=(i*.04)+'s';c.style.animation='none';void c.offsetWidth;c.style.animation='cardIn .4s ease both';g.appendChild(c);});
}
function filterCat(cat,el){
  document.querySelectorAll('#shopGrid .supp-card').forEach(function(c){
    var s=cat==='all'||c.dataset.cat===cat;
    c.style.display=s?'':'none';
    if(s){c.style.animation='none';void c.offsetWidth;c.style.animation='cardIn .4s ease both';}
  });
  document.querySelectorAll('.fcat').forEach(function(b){b.classList.remove('active');});
  el.classList.add('active');
  var v=document.querySelectorAll('#shopGrid .supp-card:not([style*="none"])').length;
  var ic=$i('itemCount');if(ic)ic.innerHTML='<strong>'+v+'</strong> products';
}
function stepQty(btn,dir){
  var i=btn.parentElement.querySelector('input');
  i.value=Math.min(10,Math.max(1,(parseInt(i.value)||1)+dir));
}

/* ── Tilt (desktop only) ── */
if(window.innerWidth>768){
  document.querySelectorAll('.supp-card').forEach(function(card){
    card.addEventListener('mousemove',function(e){
      var r=card.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;
      card.style.transform='perspective(600px) rotateY('+(x*7)+'deg) rotateX('+(-y*7)+'deg) translateY(-6px)';
    });
    card.addEventListener('mouseleave',function(){card.style.transform='';});
  });
}

/* ── Reviews ── */
var RV='mfills_rv3';
function rvGet(){try{return JSON.parse(localStorage.getItem(RV)||'{}')}catch(e){return{};}}
function rvSet(d){try{localStorage.setItem(RV,JSON.stringify(d))}catch(e){}}
function rvSeed(){
  var ids=Object.keys(PRODUCTS);
  var sd=[[{name:'Rahul S.',rating:5,text:'Amazing quality! Incredible results in 3 months.',date:'2025-02-10',verified:true},{name:'Priya M.',rating:4,text:'Great value for money. Genuine product.',date:'2025-01-22',verified:true}],[{name:'Amit K.',rating:5,text:'Energy levels improved significantly!',date:'2025-03-01',verified:false}],[{name:'Sunita P.',rating:4,text:'No side effects. Trusted brand.',date:'2025-02-18',verified:true}],[{name:'Meena G.',rating:5,text:'Results within 2 weeks. Highly recommend!',date:'2025-03-10',verified:true}],[{name:'Arjun B.',rating:4,text:'Consistent quality every time.',date:'2025-02-25',verified:true}]];
  var d=rvGet(),ch=false;
  sd.forEach(function(arr,i){if(i<ids.length&&!d[ids[i]]){d[ids[i]]=arr;ch=true;}});
  if(ch)rvSet(d);
}
function rvStats(pid){
  var rv=(rvGet()[String(pid)]||[]);
  if(!rv.length)return{avg:0,count:0,bars:{5:0,4:0,3:0,2:0,1:0},reviews:[]};
  var sum=0,bars={5:0,4:0,3:0,2:0,1:0};
  rv.forEach(function(r){sum+=r.rating;bars[r.rating]=(bars[r.rating]||0)+1;});
  return{avg:(sum/rv.length).toFixed(1),count:rv.length,bars:bars,reviews:rv};
}
function starsEl(avg,el){
  if(!el)return;var a=parseFloat(avg)||0,h='';
  for(var i=1;i<=5;i++){
    if(i<=Math.floor(a))h+='<span class="star-icon filled">★</span>';
    else if(a>i-1)h+='<span class="star-icon" style="position:relative;display:inline-block">★<span style="position:absolute;left:0;top:0;width:'+Math.round((a-(i-1))*100)+'%;overflow:hidden;color:#f59e0b">★</span></span>';
    else h+='<span class="star-icon">★</span>';
  }
  el.innerHTML=h;
}
function renderAllRatings(){
  Object.keys(PRODUCTS).forEach(function(pid){
    var s=rvStats(pid),st=$i('stars-'+pid),rn=$i('rnum-'+pid),rc=$i('rcnt-'+pid);
    if(!st)return;
    if(s.count>0){starsEl(s.avg,st);if(rn){rn.textContent=s.avg;rn.style.color='var(--green-d)';}if(rc)rc.textContent='('+s.count+')';}
    else{if(rn)rn.textContent='';if(rc){rc.textContent='No ratings';rc.style.color='var(--muted)';}}
  });
}

/* ══ MODAL ══ */
var curPid=null,pdStar=0;
function openDetail(pid){
  var mode=new URLSearchParams(window.location.search).get('mode')||'mshop';
  window.location.href='product.php?id='+pid+'&mode='+mode;
}
function closeDetail(){
  var ov=$i('pdOverlay');ov.style.opacity='0';ov.style.transition='opacity .22s';
  setTimeout(function(){ov.classList.remove('open');ov.style.opacity='';ov.style.transition='';},230);
  document.body.style.overflow='';curPid=null;pdStar=0;
}
$i('pdOverlay').addEventListener('click',function(e){if(e.target===this)closeDetail();});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeDetail();});
function pdAdjQty(d){var i=$i('pdQty');if(i)i.value=Math.min(10,Math.max(1,(parseInt(i.value)||1)+d));}
function pdSwitchTab(name,btn){
  document.querySelectorAll('.pd-tab').forEach(function(t){t.classList.remove('on');});
  document.querySelectorAll('.pd-panel').forEach(function(p){p.classList.remove('on');});
  if(btn)btn.classList.add('on');
  var p=name==='reviews'?$i('pdPanelReviews'):$i('pdPanelWrite');if(p)p.classList.add('on');
}
function renderModalRv(pid,s){
  var sum=$i('pdRvSummary'),list=$i('pdRvList');if(!sum||!list)return;
  if(!s.count){sum.innerHTML='<div class="pd-no-reviews">No reviews yet — be the first! 🌟</div>';list.innerHTML='';return;}
  var stH='';for(var i=1;i<=5;i++)stH+='<span class="si'+(i<=Math.round(parseFloat(s.avg))?' f':'')+'">★</span>';
  var barH=[5,4,3,2,1].map(function(st){var cnt=s.bars[st]||0,pct=Math.round(cnt/s.count*100);return'<div class="pd-rv-bar-r"><span class="pd-rv-bar-l">'+st+'★</span><div class="pd-rv-bar-t"><div class="pd-rv-bar-f" data-pct="'+pct+'"></div></div><span class="pd-rv-bar-c">'+cnt+'</span></div>';}).join('');
  sum.innerHTML='<div class="pd-rv-top"><div><div class="pd-rv-score">'+s.avg+'</div><div class="pd-rv-score-stars">'+stH+'</div><div class="pd-rv-score-label">'+s.count+' review'+(s.count>1?'s':'')+'</div></div><div class="pd-rv-bars">'+barH+'</div></div>';
  setTimeout(function(){sum.querySelectorAll('.pd-rv-bar-f').forEach(function(b){b.style.width=(b.dataset.pct||0)+'%';});},80);
  var rH='';s.reviews.slice().reverse().forEach(function(r){var stR='';for(var i=1;i<=5;i++)stR+='<span class="si'+(i<=r.rating?' f':'')+'">★</span>';rH+='<div class="pd-rv-item"><div class="pd-rv-item-top"><span class="pd-rv-author">'+esc(r.name)+(r.verified?'<span class="pd-rv-ver">✓ Verified</span>':'')+'</span><div class="pd-rv-meta"><div class="pd-rv-item-stars">'+stR+'</div><span class="pd-rv-date">'+esc(r.date)+'</span></div></div><div class="pd-rv-body">'+esc(r.text)+'</div></div>';});
  list.innerHTML=rH;
}
var sLbls=['','Poor 😕','Below Average','Average 😐','Good 😊','Excellent! 🌟'];
function pdPickStar(n){pdStar=n;document.querySelectorAll('.pd-star').forEach(function(s,i){s.classList.toggle('s',i<n);s.classList.remove('h');});var h=$i('pdStarHint');if(h)h.textContent=sLbls[n]||'';}
function pdHoverStar(n){document.querySelectorAll('.pd-star').forEach(function(s,i){s.classList.toggle('h',i<n);});}
function pdUnhover(){document.querySelectorAll('.pd-star').forEach(function(s,i){s.classList.remove('h');s.classList.toggle('s',i<pdStar);});}
function pdSubmitReview(){
  if(!curPid)return;if(!pdStar){alert('Please select a rating!');return;}
  var ta=$i('pdRvTa'),txt=(ta||{}).value||'';
  if(txt.trim().length<10){alert('Please write at least 10 characters.');return;}
  var sub=$i('pdRvSub');if(sub){sub.disabled=true;sub.textContent='Submitting…';}
  var d=rvGet(),k=String(curPid);if(!d[k])d[k]=[];
  d[k].push({name:'<?= $loggedIn?addslashes($user['full_name']??$user['username']??'User'):'Guest' ?>',rating:pdStar,text:txt.trim(),date:new Date().toISOString().split('T')[0],verified:<?= $loggedIn?'true':'false' ?>});
  rvSet(d);
  var ok=$i('pdRvDone');if(ok)ok.style.display='block';if(sub)sub.style.display='none';
  renderAllRatings();var s=rvStats(curPid);renderModalRv(curPid,s);
  setTimeout(function(){pdSwitchTab('reviews',document.querySelector('.pd-tab'));},700);
}

document.addEventListener('DOMContentLoaded',function(){
  rvSeed();renderAllRatings();restoreCart();restoreWish();
  var urlQ=new URLSearchParams(window.location.search).get('q');
  if(urlQ&&urlQ.trim()){var inp=$i('searchInp');if(inp){inp.value=urlQ.trim();searchProducts(urlQ.trim());}}
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>