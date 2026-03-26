<?php
// product.php — Mfills Product Detail Page
require_once __DIR__ . '/includes/functions.php';
startSession();

$pid = (int)($_GET['id'] ?? 0);
$shopMode = $_GET['mode'] ?? 'mshop';
if ($shopMode !== 'mshop_plus') $shopMode = 'mshop';
$isMshopPlus = ($shopMode === 'mshop_plus');

if (!$pid) { header('Location: shop.php'); exit; }

$loggedIn = isLoggedIn();
$userId   = $loggedIn ? currentUserId() : null;
$user     = $loggedIn ? getUser($userId) : null;
$isClub   = $loggedIn ? isActiveClubMember($userId) : false;

$pdo  = db();
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=? AND is_active=1");
$stmt->execute([$pid]);
$p = $stmt->fetch();
if (!$p) { header('Location: shop.php'); exit; }

$commRates = defined('COMMISSION_RATES') ? COMMISSION_RATES : [1=>15,2=>8,3=>6,4=>4,5=>3,6=>2,7=>2];
$bv        = (float)($p['bv'] ?? $p['price']);
$discPct   = (float)($p['discount_pct'] ?? 0);
$discPrice = ($discPct > 0 && $loggedIn && $isClub) ? round($p['price'] * (1 - $discPct/100), 2) : null;
$selling   = $discPrice ?? $p['price'];

function getProductCategory(string $name): string {
    $n = strtolower($name);
    if (strpos($n,'protein')!==false||strpos($n,'whey')!==false||strpos($n,'mass')!==false) return 'protein';
    if (strpos($n,'vitamin')!==false||strpos($n,'omega')!==false||strpos($n,'fish oil')!==false||strpos($n,'multivit')!==false||strpos($n,'b-complex')!==false) return 'vitamins';
    if (strpos($n,'ashwagandha')!==false||strpos($n,'triphala')!==false||strpos($n,'ayurved')!==false||strpos($n,'herbal')!==false||strpos($n,'shatavari')!==false) return 'ayurvedic';
    if (strpos($n,'detox')!==false||strpos($n,'slim')!==false||strpos($n,'cla')!==false||strpos($n,'weight')!==false||strpos($n,'lean')!==false||strpos($n,'garcinia')!==false) return 'weightloss';
    return 'other';
}
$catMeta = [
    'protein'    => ['label'=>'Protein',     'badge_bg'=>'#1a3b22','badge_fg'=>'#c8e8d0','icon'=>'🏋️','accent'=>'#2a6336','light'=>'#e8f5ec'],
    'vitamins'   => ['label'=>'Vitamins',    'badge_bg'=>'#0F7B5C','badge_fg'=>'#CCFCE8','icon'=>'🌿','accent'=>'#0F7B5C','light'=>'#e6f7f3'],
    'ayurvedic'  => ['label'=>'Ayurvedic',   'badge_bg'=>'#a0721a','badge_fg'=>'#FDE68A','icon'=>'🌱','accent'=>'#a0721a','light'=>'#fdf3e3'],
    'weightloss' => ['label'=>'Weight Loss', 'badge_bg'=>'#C0321A','badge_fg'=>'#FFD0C7','icon'=>'🔥','accent'=>'#C0321A','light'=>'#fdecea'],
    'other'      => ['label'=>'Other',       'badge_bg'=>'#374151','badge_fg'=>'#E5E7EB','icon'=>'💊','accent'=>'#374151','light'=>'#f1f3f5'],
];
$cat  = getProductCategory($p['name']);
$meta = $catMeta[$cat];

$related = $pdo->prepare("SELECT * FROM products WHERE is_active=1 AND id!=? ORDER BY RAND() LIMIT 4");
$related->execute([$pid]);
$relatedProducts = $related->fetchAll();

$pageTitle  = e($p['name']) . ' — MShop';
$flashMsg   = getFlash('success') ?? '';
$errorMsg   = '';

include __DIR__ . '/includes/header.php';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=DM+Serif+Display:ital@0;1&family=Outfit:wght@300;400;500;600;700;800&display=swap');

:root{
  --gdd:#0e2414;--gd:#1a3b22;--gm:#2a6336;--gl:#3a8a4a;
  --gold:#c8922a;--gold-l:#e0aa40;--gold-d:#a0721a;
  --jade:#0F7B5C;--jade-l:#14A376;--coral:#E8534A;
  --ivory:#f8f5ef;--ivory-d:#ede8de;--ivory-dd:#ddd5c4;
  --white:#ffffff;--ink:#152018;--muted:#5a7a60;--border:#e2ddd5;
  --cat-accent:<?= $meta['accent'] ?>;--cat-light:<?= $meta['light'] ?>;
}
*,*::before,*::after{box-sizing:border-box;}
html,body{background:var(--ivory)!important;font-family:'Outfit',sans-serif;color:var(--ink);}

/* BREADCRUMB */
.bc{background:var(--white);border-bottom:1px solid var(--border);padding:.6rem 0;}
.bc-inner{display:flex;align-items:center;gap:.45rem;font-size:.75rem;color:var(--muted);}
.bc a{color:var(--muted);text-decoration:none;transition:color .18s;}
.bc a:hover{color:var(--gd);}
.bc-sep{opacity:.4;}
.bc-cur{color:var(--ink);font-weight:600;}

/* HERO */
.prod-hero{background:var(--white);border-bottom:1px solid var(--border);padding:2.25rem 0 2.5rem;}
.hero-grid{display:grid;grid-template-columns:440px 1fr;gap:3rem;align-items:start;}

/* IMAGE */
.prod-img-block{position:relative;}
.prod-img-main{border-radius:18px;overflow:hidden;aspect-ratio:1;background:var(--ivory-d);border:2px solid var(--border);box-shadow:0 4px 28px rgba(14,36,20,.07);position:relative;}
.prod-img-main img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .5s;}
.prod-img-main:hover img{transform:scale(1.03);}
.prod-img-cat{position:absolute;top:.9rem;left:.9rem;z-index:2;display:inline-flex;align-items:center;gap:.35rem;font-family:'Cinzel',serif;font-size:.6rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;padding:.28rem .75rem;border-radius:20px;background:<?= $meta['badge_bg'] ?>;color:<?= $meta['badge_fg'] ?>;box-shadow:0 2px 8px rgba(0,0,0,.18);}
.prod-img-bv{position:absolute;bottom:.9rem;left:.9rem;z-index:2;display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.92);border:1px solid var(--border);color:var(--gold-d);font-family:'Cinzel',serif;font-size:.7rem;font-weight:800;padding:.3rem .8rem;border-radius:20px;backdrop-filter:blur(8px);box-shadow:0 2px 8px rgba(0,0,0,.08);}
.bv-dot{width:6px;height:6px;border-radius:50%;background:var(--gold);animation:bvp 2s ease infinite;flex-shrink:0;}
@keyframes bvp{0%,100%{opacity:1}50%{opacity:.35}}
.prod-thumbs{display:flex;gap:.55rem;margin-top:.75rem;overflow-x:auto;scrollbar-width:none;}
.prod-thumbs::-webkit-scrollbar{display:none;}
.prod-thumb{width:62px;height:62px;border-radius:10px;overflow:hidden;border:2px solid var(--border);cursor:pointer;flex-shrink:0;transition:border-color .2s,transform .2s;}
.prod-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
.prod-thumb.active,.prod-thumb:hover{border-color:var(--gold);transform:scale(1.06);}

/* INFO */
.prod-info-cat{display:inline-flex;align-items:center;gap:.4rem;font-family:'Cinzel',serif;font-size:.6rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;padding:.22rem .75rem;border-radius:20px;background:var(--cat-light);color:var(--cat-accent);border:1px solid rgba(0,0,0,.07);margin-bottom:.75rem;}
.prod-name{font-family:'DM Serif Display',serif;font-size:1.95rem;font-weight:700;color:var(--ink);line-height:1.25;margin-bottom:.55rem;}

/* Rating */
.prod-rating-row{display:flex;align-items:center;gap:.55rem;margin-bottom:1rem;flex-wrap:wrap;}
.prod-stars{display:flex;gap:1px;}
.prod-star{font-size:.95rem;color:#d1d5db;}
.prod-star.f{color:#f59e0b;}
.prod-rating-num{font-size:.85rem;font-weight:800;color:var(--gold-d);}
.prod-rating-cnt{font-size:.75rem;color:var(--muted);}
.prod-rv-link{font-size:.7rem;font-weight:700;color:var(--jade);font-family:'Cinzel',serif;letter-spacing:.04em;background:rgba(15,123,92,.07);padding:.18rem .55rem;border-radius:20px;border:1px solid rgba(15,123,92,.18);cursor:pointer;transition:background .2s;text-decoration:none;}
.prod-rv-link:hover{background:rgba(15,123,92,.14);}
.prod-desc{font-size:.9rem;color:var(--muted);line-height:1.75;margin-bottom:1.1rem;}

/* Price */
.prod-price-block{background:var(--ivory);border:1.5px solid var(--border);border-radius:16px;padding:1rem 1.25rem;margin-bottom:1.1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;}
.prod-price-now{font-family:'Cinzel',serif;font-size:2.2rem;font-weight:900;color:var(--gd);line-height:1;letter-spacing:-.02em;}
.prod-price-mrp{font-size:.9rem;color:var(--muted);text-decoration:line-through;}
.prod-price-off{font-family:'Cinzel',serif;font-size:.68rem;font-weight:800;padding:.22rem .65rem;border-radius:20px;letter-spacing:.05em;background:rgba(232,83,74,.1);color:var(--coral);border:1px solid rgba(232,83,74,.2);}
.prod-bv-box{margin-left:auto;text-align:right;background:rgba(200,146,42,.08);border:1px solid rgba(200,146,42,.2);border-radius:12px;padding:.5rem .85rem;}
.prod-bv-num{font-family:'Cinzel',serif;font-size:1rem;font-weight:800;color:var(--gold-d);}
.prod-bv-sub{font-size:.62rem;color:var(--muted);}

/* PSB */
.prod-psb-wrap{margin-bottom:1.1rem;}
.prod-psb-label{font-family:'Cinzel',serif;font-size:.58rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.14em;margin-bottom:.45rem;display:flex;align-items:center;gap:.5rem;}
.prod-psb-label::after{content:'';flex:1;height:1px;background:var(--border);}
.psb-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:.28rem;}
.psb-c{background:var(--white);border:1.5px solid var(--border);border-radius:10px;padding:.48rem .25rem;text-align:center;transition:all .2s;cursor:default;}
.psb-c:hover{border-color:var(--gold);background:rgba(200,146,42,.05);transform:translateY(-2px);box-shadow:0 3px 12px rgba(200,146,42,.12);}
.psb-c.l1{border-color:var(--gold);background:rgba(200,146,42,.06);}
.psb-c-lv{font-size:.5rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.15rem;}
.psb-c.l1 .psb-c-lv{color:var(--gold-d);}
.psb-c-pct{font-size:.58rem;font-weight:800;color:var(--muted);font-family:'Cinzel',serif;margin-bottom:.12rem;}
.psb-c.l1 .psb-c-pct{color:var(--gold-d);}
.psb-c-amt{font-size:.76rem;font-weight:900;color:var(--ink);font-family:'Cinzel',serif;}
.psb-c.l1 .psb-c-amt{color:var(--gold-d);}
.psb-note{font-size:.6rem;color:var(--muted);font-style:italic;margin-top:.3rem;}

/* PURCHASE */
.prod-purchase{background:var(--ivory);border:1.5px solid var(--border);border-radius:16px;padding:1.1rem 1.25rem;}
.qty-row{display:flex;align-items:center;gap:.9rem;margin-bottom:.9rem;}
.qty-label{font-family:'Cinzel',serif;font-size:.62rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;flex-shrink:0;}
.qty-ctrl{display:flex;align-items:center;background:var(--white);border:1.5px solid var(--border);border-radius:50px;overflow:hidden;}
.qty-btn{width:38px;height:40px;border:none;background:transparent;color:var(--gm);font-size:1.1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;}
.qty-btn:hover{background:var(--ivory-d);}
.qty-inp{width:40px;text-align:center;border:none;background:transparent;color:var(--ink);font-size:.9rem;font-weight:700;outline:none;border-left:1px solid var(--border);border-right:1px solid var(--border);-moz-appearance:textfield;}
.qty-inp::-webkit-inner-spin-button{-webkit-appearance:none;}

/* ── BUTTONS ── */
.btn-row{display:flex;gap:.65rem;}

/* Add to Cart — green outline style */
.btn-atc{
  flex:1;display:flex;align-items:center;justify-content:center;gap:.5rem;
  background:var(--white);color:var(--gd);
  border:2px solid var(--gd);
  border-radius:13px;padding:.85rem 1rem;
  font-family:'Cinzel',serif;font-size:.78rem;font-weight:800;letter-spacing:.05em;
  cursor:pointer;transition:all .22s;white-space:nowrap;
}
.btn-atc:hover{background:var(--gd);color:#fff;box-shadow:0 6px 20px rgba(26,59,34,.28);transform:translateY(-1px);}

/* Buy Now — solid gold, goes to checkout */
.btn-buy{
  flex:1;display:flex;align-items:center;justify-content:center;gap:.5rem;
  background:linear-gradient(135deg,var(--gold-d),var(--gold));
  color:#fff;border:none;border-radius:13px;
  padding:.85rem 1rem;font-family:'Cinzel',serif;
  font-size:.78rem;font-weight:800;letter-spacing:.05em;
  cursor:pointer;transition:all .22s;
  box-shadow:0 4px 18px rgba(200,146,42,.35);white-space:nowrap;
}
.btn-buy:hover{background:linear-gradient(135deg,var(--gold),var(--gold-l));transform:translateY(-1px);box-shadow:0 7px 24px rgba(200,146,42,.45);}

.btn-login{width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:var(--gd);color:#fff;border:none;border-radius:13px;padding:.85rem;font-family:'Cinzel',serif;font-size:.8rem;font-weight:800;letter-spacing:.06em;cursor:pointer;transition:all .22s;text-decoration:none;box-shadow:0 4px 18px rgba(26,59,34,.22);}
.btn-login:hover{background:var(--gm);transform:translateY(-1px);}

.trust-row{display:flex;gap:.65rem;margin-top:.85rem;flex-wrap:wrap;}
.trust-b{display:inline-flex;align-items:center;gap:.3rem;font-size:.68rem;font-weight:600;color:var(--muted);}

/* CONTENT AREA */
.prod-content{background:var(--ivory);padding:2.5rem 0;}
.content-grid{display:grid;grid-template-columns:1fr 320px;gap:2.25rem;align-items:start;}

/* Tabs */
.prod-tabs{display:flex;border-bottom:2px solid var(--border);margin-bottom:1.75rem;overflow-x:auto;scrollbar-width:none;}
.prod-tabs::-webkit-scrollbar{display:none;}
.prod-tab{padding:.7rem 1.15rem;font-family:'Cinzel',serif;font-size:.7rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);background:transparent;border:none;cursor:pointer;white-space:nowrap;border-bottom:2.5px solid transparent;margin-bottom:-2px;transition:color .18s,border-color .18s;}
.prod-tab.active{color:var(--gd);border-bottom-color:var(--gold);}
.prod-tab:hover:not(.active){color:var(--gd);}
.prod-tab-panel{display:none;}
.prod-tab-panel.active{display:block;}

/* Description tab */
.desc-body{font-size:.9rem;color:var(--ink);line-height:1.85;}
.desc-body p{margin-bottom:1rem;}
.hl-grid{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-top:1.1rem;}
.hl-item{display:flex;align-items:flex-start;gap:.55rem;background:var(--white);border:1.5px solid var(--border);border-radius:12px;padding:.75rem .9rem;}
.hl-icon{font-size:1.15rem;flex-shrink:0;margin-top:.05rem;}
.hl-text{font-size:.8rem;color:var(--ink);font-weight:600;line-height:1.35;}
.hl-sub{font-size:.7rem;color:var(--muted);margin-top:.1rem;}

/* PSB table */
.psb-table{width:100%;border-collapse:collapse;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(14,36,20,.06);}
.psb-table thead tr{background:linear-gradient(135deg,var(--gdd),var(--gd));border-bottom:2px solid var(--gold);}
.psb-table th{color:rgba(255,255,255,.82);font-size:.63rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:.7rem 1rem;text-align:left;}
.psb-table td{padding:.78rem 1rem;border-bottom:1px solid var(--border);font-size:.87rem;color:var(--ink);vertical-align:middle;background:var(--white);}
.psb-table tr:last-child td{border-bottom:none;}
.psb-table tbody tr:hover td{background:var(--ivory);}
.psb-table tbody tr:first-child td{background:rgba(200,146,42,.04);}
.lv-badge{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:var(--gd);color:#fff;font-family:'Cinzel',serif;font-size:.7rem;font-weight:800;}
.lv-badge.l1{background:linear-gradient(135deg,var(--gold-d),var(--gold));color:var(--gdd);}
.pct-c{font-family:'Cinzel',serif;font-size:1rem;font-weight:800;color:var(--gd);}
.pct-c.l1{color:var(--gold-d);}
.amt-c{font-family:'Cinzel',serif;font-size:1.05rem;font-weight:800;color:var(--gd);}
.amt-c.l1{color:var(--gold-d);}
.psb-info{background:rgba(200,146,42,.07);border:1.5px solid rgba(200,146,42,.2);border-radius:12px;padding:.85rem 1rem;margin-top:.9rem;font-size:.8rem;color:var(--gd);line-height:1.6;}
.psb-info strong{color:var(--gold-d);}

/* Reviews */
.rv-summary-wrap{display:grid;grid-template-columns:auto 1fr;gap:1.5rem;align-items:center;margin-bottom:1.25rem;}
.rv-score-box{background:var(--gdd);border-radius:16px;padding:1.25rem 1.5rem;text-align:center;min-width:130px;}
.rv-score-big{font-family:'DM Serif Display',serif;font-size:4rem;font-weight:800;color:#fff;line-height:1;}
.rv-stars-big{display:flex;gap:2px;justify-content:center;margin:.25rem 0;}
.rv-sb{font-size:1.05rem;color:#2a3b2c;}
.rv-sb.f{color:#f59e0b;}
.rv-score-lbl{font-size:.7rem;color:rgba(255,255,255,.3);}
.rv-bars{display:flex;flex-direction:column;gap:.4rem;}
.rv-bar-r{display:flex;align-items:center;gap:.6rem;}
.rv-bar-l{font-size:.7rem;font-weight:700;color:var(--muted);min-width:22px;}
.rv-bar-t{flex:1;height:6px;background:var(--border);border-radius:10px;overflow:hidden;}
.rv-bar-f{height:100%;border-radius:10px;background:linear-gradient(90deg,#f59e0b,#fbbf24);transition:width 1s cubic-bezier(.22,.68,0,1.2);width:0%;}
.rv-bar-c{font-size:.65rem;font-weight:700;color:var(--muted);min-width:16px;text-align:right;}
.rv-list{display:flex;flex-direction:column;gap:.75rem;margin:1.25rem 0;}
.rv-card{background:var(--white);border:1.5px solid var(--border);border-radius:14px;padding:1rem 1.15rem;transition:box-shadow .2s;}
.rv-card:hover{box-shadow:0 4px 18px rgba(14,36,20,.07);}
.rv-card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:.4rem;flex-wrap:wrap;gap:.3rem;}
.rv-author{font-weight:700;font-size:.85rem;color:var(--ink);display:flex;align-items:center;gap:.4rem;}
.rv-ver{font-size:.58rem;font-weight:800;padding:.1rem .38rem;border-radius:4px;background:rgba(15,123,92,.1);color:var(--jade);font-family:'Cinzel',serif;letter-spacing:.06em;}
.rv-meta{display:flex;align-items:center;gap:.45rem;}
.rv-item-stars{display:flex;gap:1px;}
.rv-is{font-size:.78rem;color:#d1d5db;}
.rv-is.f{color:#f59e0b;}
.rv-date{font-size:.63rem;color:var(--muted);}
.rv-text{font-size:.83rem;color:var(--muted);line-height:1.7;}
.write-box{background:var(--white);border:1.5px solid var(--border);border-radius:14px;padding:1.15rem 1.3rem;margin-top:1.25rem;}
.write-head{font-family:'Cinzel',serif;font-size:.68rem;font-weight:800;color:var(--gd);text-transform:uppercase;letter-spacing:.12em;margin-bottom:.8rem;}
.star-pick-row{display:flex;gap:.4rem;margin-bottom:.35rem;}
.sp{font-size:1.75rem;color:var(--border);cursor:pointer;transition:color .12s,transform .14s;user-select:none;}
.sp.h,.sp.s{color:#f59e0b;}
.sp:hover{transform:scale(1.2);}
.sp-hint{font-size:.73rem;color:var(--muted);margin-bottom:.7rem;min-height:.88rem;}
.rv-ta{width:100%;border:1.5px solid var(--border);border-radius:11px;padding:.7rem .9rem;font-size:.87rem;color:var(--ink);background:var(--ivory);outline:none;resize:vertical;min-height:85px;box-sizing:border-box;transition:border-color .2s;font-family:'Outfit',sans-serif;}
.rv-ta:focus{border-color:var(--gl);}
.rv-sub-btn{margin-top:.7rem;background:linear-gradient(135deg,var(--gd),var(--gm));color:#fff;border:none;border-radius:50px;padding:.58rem 1.35rem;font-family:'Cinzel',serif;font-size:.7rem;font-weight:800;letter-spacing:.07em;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem;}
.rv-sub-btn:hover{background:linear-gradient(135deg,var(--gm),var(--gl));transform:translateY(-1px);}
.rv-sub-btn:disabled{opacity:.4;cursor:not-allowed;transform:none;}
.rv-ok{background:rgba(15,123,92,.08);color:var(--jade);border:1.5px solid rgba(15,123,92,.2);border-radius:8px;padding:.55rem .85rem;font-size:.8rem;font-weight:700;margin-top:.7rem;display:none;}
.login-rv{background:var(--ivory-d);border:1.5px solid var(--border);border-radius:12px;padding:1rem;text-align:center;font-size:.83rem;color:var(--muted);line-height:1.7;}
.login-rv a{color:var(--gd);font-weight:700;text-decoration:none;}

/* SIDEBAR */
.prod-sidebar{display:flex;flex-direction:column;gap:1.1rem;}
.sb-cart{background:var(--white);border:1.5px solid var(--border);border-radius:18px;padding:1.3rem;box-shadow:0 4px 22px rgba(14,36,20,.07);position:sticky;top:1.25rem;}
.sbc-price{font-family:'Cinzel',serif;font-size:1.9rem;font-weight:900;color:var(--gd);line-height:1;margin-bottom:.15rem;}
.sbc-mrp{font-size:.82rem;color:var(--muted);text-decoration:line-through;margin-bottom:.6rem;}
.sbc-bv{display:inline-flex;align-items:center;gap:.38rem;background:rgba(200,146,42,.08);border:1px solid rgba(200,146,42,.2);color:var(--gold-d);font-family:'Cinzel',serif;font-size:.68rem;font-weight:800;padding:.25rem .7rem;border-radius:20px;margin-bottom:.9rem;}
.sbc-sep{border:none;border-top:1px solid var(--border);margin:.85rem 0;}
.sbc-qty-row{display:flex;align-items:center;gap:.7rem;margin-bottom:.85rem;}
.sbc-qty-label{font-size:.62rem;font-weight:700;color:var(--muted);font-family:'Cinzel',serif;text-transform:uppercase;letter-spacing:.1em;flex-shrink:0;}
.sbc-qty-ctrl{display:flex;align-items:center;background:var(--ivory);border:1.5px solid var(--border);border-radius:50px;overflow:hidden;}
.sbc-qb{width:34px;height:36px;border:none;background:transparent;color:var(--gm);font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;}
.sbc-qb:hover{background:var(--ivory-d);}
.sbc-qi{width:34px;text-align:center;border:none;background:transparent;color:var(--ink);font-size:.88rem;font-weight:700;outline:none;border-left:1px solid var(--border);border-right:1px solid var(--border);-moz-appearance:textfield;}
.sbc-qi::-webkit-inner-spin-button{-webkit-appearance:none;}

/* Sidebar buttons match hero */
.sbc-atc{width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:var(--white);color:var(--gd);border:2px solid var(--gd);border-radius:12px;padding:.78rem;font-family:'Cinzel',serif;font-size:.76rem;font-weight:800;letter-spacing:.05em;cursor:pointer;transition:all .22s;margin-bottom:.5rem;}
.sbc-atc:hover{background:var(--gd);color:#fff;box-shadow:0 5px 18px rgba(26,59,34,.25);transform:translateY(-1px);}
.sbc-buy{width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;background:linear-gradient(135deg,var(--gold-d),var(--gold));color:#fff;border:none;border-radius:12px;padding:.78rem;font-family:'Cinzel',serif;font-size:.76rem;font-weight:800;letter-spacing:.05em;cursor:pointer;transition:all .22s;box-shadow:0 4px 16px rgba(200,146,42,.3);}
.sbc-buy:hover{background:linear-gradient(135deg,var(--gold),var(--gold-l));transform:translateY(-1px);box-shadow:0 7px 22px rgba(200,146,42,.42);}

.sbc-trust{display:flex;flex-direction:column;gap:.4rem;margin-top:.85rem;}
.sbc-ti{display:flex;align-items:center;gap:.45rem;font-size:.7rem;color:var(--muted);}
.sb-info{background:var(--white);border:1.5px solid var(--border);border-radius:16px;padding:1rem 1.15rem;}
.sbi-title{font-family:'Cinzel',serif;font-size:.66rem;font-weight:800;color:var(--gd);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.7rem;}
.sbi-row{display:flex;align-items:flex-start;gap:.55rem;padding:.48rem 0;border-bottom:1px solid var(--ivory-d);}
.sbi-row:last-child{border-bottom:none;padding-bottom:0;}
.sbi-icon{font-size:1rem;flex-shrink:0;margin-top:.05rem;}
.sbi-text{font-size:.76rem;color:var(--ink);font-weight:600;line-height:1.3;}
.sbi-sub{font-size:.68rem;color:var(--muted);margin-top:.08rem;}

/* RELATED */
.related-sec{background:var(--ivory-d);border-top:1.5px solid var(--border);padding:2.25rem 0;}
.related-head{font-family:'Cinzel',serif;font-size:1rem;font-weight:700;color:var(--gd);margin-bottom:1.25rem;display:flex;align-items:center;gap:.65rem;}
.related-head::before{content:'';width:3px;height:20px;background:linear-gradient(to bottom,var(--gold),var(--gl));border-radius:3px;flex-shrink:0;}
.related-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;}
.rel-card{background:var(--white);border-radius:12px;overflow:hidden;border:1.5px solid var(--border);transition:transform .22s,box-shadow .22s;text-decoration:none;display:block;}
.rel-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(14,36,20,.09);}
.rel-img{height:130px;overflow:hidden;background:var(--ivory-d);}
.rel-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s;}
.rel-card:hover .rel-img img{transform:scale(1.05);}
.rel-body{padding:.7rem .85rem;}
.rel-name{font-weight:700;font-size:.8rem;color:var(--ink);margin-bottom:.22rem;line-height:1.3;}
.rel-price{font-family:'Cinzel',serif;font-size:.9rem;font-weight:800;color:var(--gd);}
.rel-bv{font-size:.62rem;color:var(--muted);margin-top:.08rem;}

/* ALERTS */
.alert{padding:.82rem 1.05rem;border-radius:10px;font-size:.87rem;font-weight:600;margin-bottom:1.1rem;}
.alert-success{background:rgba(15,123,92,.09);color:var(--jade);border-left:3px solid var(--jade-l);}
.alert-danger{background:rgba(232,83,74,.09);color:#9A1A09;border-left:3px solid var(--coral);}

/* ATC TOAST — slides in from top-right */
.atc-toast{
  position:fixed;top:1.25rem;right:1.25rem;z-index:9999;
  background:var(--gd);color:#fff;
  border-radius:14px;padding:.85rem 1.15rem;
  display:flex;align-items:center;gap:.75rem;
  box-shadow:0 8px 28px rgba(14,36,20,.28);
  font-family:'Outfit',sans-serif;font-size:.85rem;font-weight:600;
  max-width:320px;
  transform:translateX(150%);
  transition:transform .38s cubic-bezier(.34,1.3,.64,1);
  border:1px solid rgba(255,255,255,.1);
}
.atc-toast.show{transform:translateX(0);}
.atc-toast-icon{font-size:1.25rem;flex-shrink:0;}
.atc-toast-body{flex:1;}
.atc-toast-name{font-weight:700;font-size:.82rem;margin-bottom:.08rem;}
.atc-toast-sub{font-size:.7rem;color:rgba(255,255,255,.6);}
.atc-toast-btn{
  background:var(--gold);color:var(--gdd);border:none;border-radius:8px;
  padding:.35rem .85rem;font-family:'Cinzel',serif;font-size:.68rem;font-weight:800;
  letter-spacing:.05em;cursor:pointer;text-decoration:none;white-space:nowrap;
  flex-shrink:0;transition:background .2s;
}
.atc-toast-btn:hover{background:var(--gold-l);}

@media(max-width:1024px){
  .hero-grid{grid-template-columns:360px 1fr;gap:2rem;}
  .content-grid{grid-template-columns:1fr 290px;}
  .related-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:780px){
  .hero-grid{grid-template-columns:1fr;gap:1.5rem;}
  .prod-img-main{aspect-ratio:4/3;max-height:300px;}
  .prod-name{font-size:1.5rem;}
  .content-grid{grid-template-columns:1fr;}
  .sb-cart{position:static;}
  .rv-summary-wrap{grid-template-columns:1fr;}
  .hl-grid{grid-template-columns:1fr;}
  .psb-grid{grid-template-columns:repeat(4,1fr);}
  .related-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:480px){
  .btn-row{flex-direction:column;}
  .psb-grid{grid-template-columns:repeat(4,1fr);}
  .related-grid{grid-template-columns:1fr 1fr;gap:.65rem;}
  .atc-toast{right:.75rem;left:.75rem;max-width:none;}
}
</style>

<!-- ATC Toast -->
<div class="atc-toast" id="atcToast">
  <div class="atc-toast-icon">🛒</div>
  <div class="atc-toast-body">
    <div class="atc-toast-name"><?= e($p['name']) ?></div>
    <div class="atc-toast-sub">Added to cart successfully</div>
  </div>
  <a href="cart.php" class="atc-toast-btn">View Cart →</a>
</div>

<!-- Breadcrumb -->
<div class="bc">
  <div class="container bc-inner">
    <a href="<?= APP_URL ?>">Home</a>
    <span class="bc-sep">›</span>
    <a href="shop.php<?= $isMshopPlus ? '?mode=mshop_plus' : '' ?>">MShop<?= $isMshopPlus ? ' Plus' : '' ?></a>
    <span class="bc-sep">›</span>
    <span class="bc-cur"><?= e($p['name']) ?></span>
  </div>
</div>

<!-- Alerts -->
<?php if ($flashMsg || $errorMsg): ?>
<div class="container" style="padding-top:1rem">
  <?php if ($flashMsg): ?><div class="alert alert-success"><?= e($flashMsg) ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="alert alert-danger"><?= e($errorMsg) ?></div><?php endif; ?>
</div>
<?php endif; ?>

<!-- Hero -->
<div class="prod-hero">
  <div class="container">
    <div class="hero-grid">

      <!-- Image -->
      <div class="prod-img-block">
        <div class="prod-img-main">
          <img id="mainImg" src="<?= e($p['image_url'] ?? '') ?>" alt="<?= e($p['name']) ?>" onerror="this.style.opacity='.2'">
          <span class="prod-img-cat"><?= $meta['icon'] ?> <?= $meta['label'] ?></span>
          <div class="prod-img-bv"><span class="bv-dot"></span>BV ₹<?= number_format($bv, 0) ?></div>
        </div>
        <?php if ($p['image_url']): ?>
        <div class="prod-thumbs">
          <div class="prod-thumb active" onclick="setImg('<?= e($p['image_url']) ?>',this)">
            <img src="<?= e($p['image_url']) ?>" alt="">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Info -->
      <div>
        <div class="prod-info-cat"><?= $meta['icon'] ?> <?= $meta['label'] ?> · Mfills Genuine</div>
        <h1 class="prod-name"><?= e($p['name']) ?></h1>

        <div class="prod-rating-row">
          <div class="prod-stars" id="heroStars">
            <?php for($s=1;$s<=5;$s++) echo '<span class="prod-star">★</span>'; ?>
          </div>
          <span class="prod-rating-num" id="heroRatingNum" style="display:none"></span>
          <span class="prod-rating-cnt" id="heroRatingCnt">Loading…</span>
          <a class="prod-rv-link" onclick="switchTab('reviews'); document.getElementById('rv-anchor').scrollIntoView({behavior:'smooth'});">See Reviews ↓</a>
        </div>

        <p class="prod-desc"><?= e($p['description'] ?? '') ?></p>

        <!-- Price -->
        <div class="prod-price-block">
          <?php if ($discPrice): ?>
            <div>
              <div class="prod-price-now">₹<?= number_format($discPrice, 0) ?></div>
              <div class="prod-price-mrp">MRP ₹<?= number_format($p['price'], 0) ?></div>
            </div>
            <span class="prod-price-off"><?= $discPct ?>% OFF</span>
          <?php else: ?>
            <div class="prod-price-now">₹<?= number_format($p['price'], 0) ?></div>
          <?php endif; ?>
          <div class="prod-bv-box">
            <div class="prod-bv-num">₹<?= number_format($bv * 0.15, 0) ?></div>
            <div class="prod-bv-sub">L1 PSB earn</div>
          </div>
        </div>

        <!-- PSB grid -->
        <div class="prod-psb-wrap">
          <div class="prod-psb-label">Partner Sales Bonus</div>
          <div class="psb-grid">
            <?php foreach ($commRates as $l => $r): $amt = round($bv * $r / 100); ?>
            <div class="psb-c <?= $l===1?'l1':'' ?>">
              <div class="psb-c-lv">L<?= $l ?></div>
              <div class="psb-c-pct"><?= $r ?>%</div>
              <div class="psb-c-amt">₹<?= number_format($amt,0) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="psb-note">*On BV ₹<?= number_format($bv,0) ?>, not selling price</div>
        </div>

        <!-- Purchase -->
        <div class="prod-purchase">
          <?php if ($loggedIn): ?>
          <div class="qty-row">
            <span class="qty-label">Qty</span>
            <div class="qty-ctrl">
              <button type="button" class="qty-btn" onclick="adj('heroQty',-1)">−</button>
              <input type="number" class="qty-inp" id="heroQty" value="1" min="1" max="10">
              <button type="button" class="qty-btn" onclick="adj('heroQty',1)">+</button>
            </div>
          </div>
          <div class="btn-row">
            <button type="button" class="btn-atc" onclick="doAddCart('heroQty')">🛒 Add to Cart</button>
            <button type="button" class="btn-buy" onclick="doBuyNow('heroQty')">⚡ Buy Now</button>
          </div>
          <?php else: ?>
          <a href="<?= APP_URL ?>/login.php?redirect=<?= urlencode('product.php?id='.$pid) ?>" class="btn-login">🔐 Login to Purchase</a>
          <?php endif; ?>
          <div class="trust-row">
            <span class="trust-b">✅ Genuine</span>
            <span class="trust-b">🔒 Secure</span>
            <span class="trust-b">📦 Fast Delivery</span>
            <span class="trust-b">↩️ Easy Returns</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Content + Sidebar -->
<div class="prod-content" id="rv-anchor">
  <div class="container">
    <div class="content-grid">

      <!-- LEFT: Tabs -->
      <div>
        <div class="prod-tabs">
          <button class="prod-tab active" onclick="switchTab('description',this)">Description</button>
          <button class="prod-tab" onclick="switchTab('psb',this)">PSB Breakdown</button>
          <button class="prod-tab" onclick="switchTab('reviews',this)">Reviews <span id="tabRvCnt"></span></button>
        </div>

        <!-- Description -->
        <div class="prod-tab-panel active" id="tab-description">
          <div class="desc-body">
            <p><?= nl2br(e($p['description'] ?? 'Premium Mfills product. Each purchase generates Business Volume (BV) contributing to your partner earnings.')) ?></p>
            <p>Every purchase through MShop contributes toward your BV accumulation — essential for business growth and qualifying for income opportunities within the Mfills system.</p>
          </div>
          <div class="hl-grid">
            <div class="hl-item"><div class="hl-icon">✅</div><div><div class="hl-text">Genuine Mfills Product</div><div class="hl-sub">100% authentic, quality assured</div></div></div>
            <div class="hl-item"><div class="hl-icon">📊</div><div><div class="hl-text">BV: ₹<?= number_format($bv,0) ?></div><div class="hl-sub">Business Volume for PSB</div></div></div>
            <div class="hl-item"><div class="hl-icon"><?= $meta['icon'] ?></div><div><div class="hl-text"><?= $meta['label'] ?> Category</div><div class="hl-sub">Premium quality range</div></div></div>
            <div class="hl-item"><div class="hl-icon">💰</div><div><div class="hl-text">Earn ₹<?= number_format($bv*0.15,0) ?> PSB (L1)</div><div class="hl-sub">Partner Sales Bonus per sale</div></div></div>
          </div>
        </div>

        <!-- PSB Breakdown -->
        <div class="prod-tab-panel" id="tab-psb">
          <table class="psb-table">
            <thead><tr><th>Level</th><th>Bonus %</th><th>BV Base</th><th>PSB Earned</th><th>Who Earns</th></tr></thead>
            <tbody>
              <?php
              $descs=[1=>'Your direct sponsor',2=>'Level 2 upline',3=>'Level 3 upline',4=>'Level 4 upline',5=>'Level 5 upline',6=>'Level 6 upline',7=>'Level 7 upline'];
              foreach ($commRates as $l => $r): $amt=round($bv*$r/100);
              ?>
              <tr>
                <td><span class="lv-badge <?= $l===1?'l1':'' ?>">L<?= $l ?></span></td>
                <td><span class="pct-c <?= $l===1?'l1':'' ?>"><?= $r ?>%</span></td>
                <td style="color:var(--muted)">₹<?= number_format($bv,0) ?></td>
                <td><span class="amt-c <?= $l===1?'l1':'' ?>">₹<?= number_format($amt,0) ?></span></td>
                <td style="font-size:.78rem;color:var(--muted)"><?= $descs[$l] ?></td>
              </tr>
              <?php endforeach; ?>
              <tr style="border-top:2px solid var(--border)">
                <td colspan="3" style="font-weight:700">Total PSB</td>
                <td colspan="2">
                  <span style="font-family:'Cinzel',serif;font-size:1.05rem;font-weight:800;color:var(--gd)">₹<?= number_format(array_sum(array_map(fn($r)=>round($bv*$r/100),$commRates)),0) ?></span>
                  <span style="font-size:.7rem;color:var(--muted)"> (up to 40% of BV)</span>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="psb-info">💡 <strong>How PSB works:</strong> Every purchase distributes BV ₹<?= number_format($bv,0) ?> as commissions across 7 upline levels automatically.</div>
        </div>

        <!-- Reviews -->
        <div class="prod-tab-panel" id="tab-reviews">
          <div class="rv-summary-wrap" id="rvSummary"></div>
          <div class="rv-list" id="rvList"><div style="text-align:center;padding:2rem;color:var(--muted)">Loading…</div></div>
          <?php if ($loggedIn): ?>
          <div class="write-box">
            <div class="write-head">✍️ Write a Review</div>
            <div class="star-pick-row" id="starPick">
              <?php for($s=1;$s<=5;$s++): ?>
              <span class="sp" onclick="pickStar(<?=$s?>)" onmouseenter="hoverStar(<?=$s?>)" onmouseleave="unhoverStar()">★</span>
              <?php endfor; ?>
            </div>
            <div class="sp-hint" id="spHint">👆 Click to rate</div>
            <textarea class="rv-ta" id="rvText" placeholder="Share your experience…"></textarea>
            <button class="rv-sub-btn" id="rvSubmit" onclick="submitReview()">📤 Submit Review</button>
            <div class="rv-ok" id="rvOk">✅ Thank you!</div>
          </div>
          <?php else: ?>
          <div class="login-rv">Please <a href="<?= APP_URL ?>/login.php">Login</a> or <a href="<?= APP_URL ?>/register.php">Register</a> to write a review.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT: Sidebar -->
      <div class="prod-sidebar">
        <div class="sb-cart">
          <div class="sbc-price">₹<?= number_format($selling,0) ?></div>
          <?php if ($discPrice): ?><div class="sbc-mrp">MRP ₹<?= number_format($p['price'],0) ?></div><?php endif; ?>
          <div class="sbc-bv">📊 BV ₹<?= number_format($bv,0) ?></div>

          <?php if ($loggedIn): ?>
          <div class="sbc-qty-row">
            <span class="sbc-qty-label">Qty</span>
            <div class="sbc-qty-ctrl">
              <button class="sbc-qb" onclick="adj('sbcQty',-1)">−</button>
              <input type="number" class="sbc-qi" id="sbcQty" value="1" min="1" max="10">
              <button class="sbc-qb" onclick="adj('sbcQty',1)">+</button>
            </div>
          </div>
          <button class="sbc-atc" onclick="doAddCart('sbcQty')">🛒 Add to Cart</button>
          <button class="sbc-buy" onclick="doBuyNow('sbcQty')">⚡ Buy Now</button>
          <?php else: ?>
          <a href="<?= APP_URL ?>/login.php?redirect=<?= urlencode('product.php?id='.$pid) ?>" class="btn-login" style="margin-top:.5rem">🔐 Login to Purchase</a>
          <?php endif; ?>

          <div class="sbc-sep"></div>
          <div class="sbc-trust">
            <div class="sbc-ti">✅ Genuine Mfills Product</div>
            <div class="sbc-ti">📊 Earns BV for PSB</div>
            <div class="sbc-ti">🔒 Secure Checkout</div>
            <div class="sbc-ti">📦 Fast Delivery</div>
          </div>
        </div>

        <div class="sb-info">
          <div class="sbi-title">Product Details</div>
          <div class="sbi-row"><div class="sbi-icon">📦</div><div><div class="sbi-text">Category</div><div class="sbi-sub"><?= $meta['icon'] ?> <?= $meta['label'] ?></div></div></div>
          <div class="sbi-row"><div class="sbi-icon">📊</div><div><div class="sbi-text">Business Volume</div><div class="sbi-sub">₹<?= number_format($bv,0) ?> BV per unit</div></div></div>
          <div class="sbi-row"><div class="sbi-icon">💰</div><div><div class="sbi-text">L1 PSB Earning</div><div class="sbi-sub">₹<?= number_format(round($bv*$commRates[1]/100),0) ?> per sale</div></div></div>
          <?php if ($discPct > 0): ?>
          <div class="sbi-row"><div class="sbi-icon">⭐</div><div><div class="sbi-text">MShop Plus Discount</div><div class="sbi-sub"><?= $discPct ?>% off for Club Members</div></div></div>
          <?php endif; ?>
          <div class="sbi-row"><div class="sbi-icon">🛒</div><div><div class="sbi-text">Available On</div><div class="sbi-sub">MShop<?= $discPct > 0 ? ' & MShop Plus' : '' ?></div></div></div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Related -->
<?php if (!empty($relatedProducts)): ?>
<div class="related-sec">
  <div class="container">
    <div class="related-head">More Products</div>
    <div class="related-grid">
      <?php foreach ($relatedProducts as $rp):
        $rbv = (float)($rp['bv'] ?? $rp['price']); $rpid = (int)$rp['id'];
      ?>
      <a href="product.php?id=<?= $rpid ?>&mode=<?= e($shopMode) ?>" class="rel-card">
        <div class="rel-img"><img src="<?= e($rp['image_url'] ?? '') ?>" alt="<?= e($rp['name']) ?>" onerror="this.style.opacity='.2'"></div>
        <div class="rel-body">
          <div class="rel-name"><?= e(substr($rp['name'],0,40)) ?><?= strlen($rp['name'])>40?'…':'' ?></div>
          <div class="rel-price">₹<?= number_format($rp['price'],0) ?></div>
          <div class="rel-bv">BV ₹<?= number_format($rbv,0) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Buy Now hidden form → checkout.php -->
<?php if ($loggedIn): ?>
<form method="POST" action="<?= APP_URL ?>/checkout.php" id="buyNowForm" style="display:none">
  <input type="hidden" name="shop_source" value="<?= e($shopMode) ?>">
  <input type="hidden" name="cart_data"   id="buyNowData" value="[]">
</form>
<?php endif; ?>

<script>
var CART_KEY = '<?= $loggedIn ? "mfills_cart_auth" : "mfills_gc" ?>';
var PID      = <?= $pid ?>;
var PROD     = { pid:<?= $pid ?>, name:<?= json_encode($p['name']) ?>, price:<?= $selling ?>, bv:<?= $bv ?>, img:<?= json_encode($p['image_url']??'') ?>, cat:<?= json_encode($cat) ?> };
var APP_URL  = '<?= addslashes(APP_URL) ?>';
var LOGGED_IN = <?= $loggedIn?'true':'false' ?>;

function fmt(n){ return Number(n).toLocaleString('en-IN',{maximumFractionDigits:0}); }
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function $id(id){ return document.getElementById(id); }
function adj(id,d){ var i=$id(id); if(i) i.value=Math.min(10,Math.max(1,(parseInt(i.value)||1)+d)); }

/* ── Cart ── */
function cGet(){ try{ return JSON.parse(localStorage.getItem(CART_KEY)||'{}'); }catch(e){ return {}; } }
function cSet(c){ try{ localStorage.setItem(CART_KEY,JSON.stringify(c)); }catch(e){} }
function cCount(){ var c=cGet(),n=0; Object.keys(c).forEach(function(p){ n+=(parseInt(c[p].qty)||1); }); return n; }
function updateBadge(){
  var n=cCount();
  ['cartCount','nlCartBadge','ndCartBadge'].forEach(function(id){
    var el=$id(id); if(!el) return;
    if(id==='cartCount') el.textContent=n;
    else if(n>0){ el.textContent=n>99?'99+':n; el.style.display='flex'; }
    else el.style.display='none';
  });
}

/* ADD TO CART → shows toast, stays on page, goes cart.php on button click */
function doAddCart(qtyId){
  var qty=Math.min(10,Math.max(1,parseInt(($id(qtyId)||{}).value||1)||1));
  var c=cGet();
  c[PID]={ product_id:PROD.pid, name:PROD.name, price:PROD.price, bv:PROD.bv, image_url:PROD.img, cat:PROD.cat, qty:qty };
  cSet(c);
  updateBadge();
  /* Show toast */
  var toast=$id('atcToast');
  if(toast){
    toast.classList.add('show');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(function(){ toast.classList.remove('show'); }, 4000);
  }
}

/* BUY NOW → goes directly to checkout.php */
function doBuyNow(qtyId){
  if(!LOGGED_IN){ window.location.href=APP_URL+'/login.php'; return; }
  var qty=Math.min(10,Math.max(1,parseInt(($id(qtyId)||{}).value||1)||1));
  $id('buyNowData').value=JSON.stringify([{
    product_id:PROD.pid, qty:qty, price:PROD.price,
    bv:PROD.bv, name:PROD.name, image_url:PROD.img, cat:PROD.cat
  }]);
  $id('buyNowForm').submit();
}

/* Image switch */
function setImg(src,thumb){
  $id('mainImg').src=src;
  document.querySelectorAll('.prod-thumb').forEach(function(t){ t.classList.remove('active'); });
  if(thumb) thumb.classList.add('active');
}

/* Tabs */
function switchTab(name,btn){
  document.querySelectorAll('.prod-tab').forEach(function(t){ t.classList.remove('active'); });
  document.querySelectorAll('.prod-tab-panel').forEach(function(p){ p.classList.remove('active'); });
  var panel=$id('tab-'+name); if(panel) panel.classList.add('active');
  if(btn) btn.classList.add('active');
  else document.querySelectorAll('.prod-tab').forEach(function(t){
    if(t.getAttribute('onclick')&&t.getAttribute('onclick').indexOf("'"+name+"'")>=0) t.classList.add('active');
  });
}

/* Reviews */
var RV='mfills_rv3', selStar=0;
function rvGet(){ try{ return JSON.parse(localStorage.getItem(RV)||'{}'); }catch(e){ return {}; } }
function rvSet(d){ try{ localStorage.setItem(RV,JSON.stringify(d)); }catch(e){} }
function rvStats(){
  var rv=(rvGet()[String(PID)]||[]);
  if(!rv.length) return {avg:0,count:0,bars:{5:0,4:0,3:0,2:0,1:0},reviews:[]};
  var sum=0,bars={5:0,4:0,3:0,2:0,1:0};
  rv.forEach(function(r){ sum+=r.rating; bars[r.rating]=(bars[r.rating]||0)+1; });
  return {avg:(sum/rv.length).toFixed(1),count:rv.length,bars:bars,reviews:rv};
}
function rvSeed(){
  var d=rvGet(),k=String(PID);
  if(!d[k]) d[k]=[
    {name:'Rahul S.',rating:5,text:'Amazing quality! Incredible results in 3 months. Highly recommend.',date:'2025-02-10',verified:true},
    {name:'Priya M.',rating:4,text:'Great value for money. Genuine Mfills product, fast delivery too.',date:'2025-01-22',verified:true},
    {name:'Amit K.',rating:5,text:'Energy levels improved significantly. Will definitely order again.',date:'2025-03-01',verified:false}
  ];
  rvSet(d);
}
function renderReviews(){
  var s=rvStats();
  var tc=$id('tabRvCnt'); if(tc) tc.textContent=s.count>0?'('+s.count+')':'';
  var hs=$id('heroStars'),hn=$id('heroRatingNum'),hc=$id('heroRatingCnt');
  if(hs&&s.count>0){
    var avg=parseFloat(s.avg),h='';
    for(var i=1;i<=5;i++){
      if(i<=Math.floor(avg)) h+='<span class="prod-star f">★</span>';
      else if(avg>i-1) h+='<span class="prod-star" style="position:relative;display:inline-block">★<span style="position:absolute;left:0;top:0;width:'+Math.round((avg-(i-1))*100)+'%;overflow:hidden;color:#f59e0b">★</span></span>';
      else h+='<span class="prod-star">★</span>';
    }
    hs.innerHTML=h; if(hn){hn.textContent=s.avg;hn.style.display='inline';} if(hc) hc.textContent='('+s.count+' reviews)';
  } else { if(hc) hc.textContent='No reviews yet'; if(hn) hn.style.display='none'; }

  var sw=$id('rvSummary');
  if(sw){
    if(!s.count){ sw.innerHTML='<div style="color:var(--muted);font-size:.88rem">No reviews yet — be the first! ⭐</div>'; }
    else{
      var stH=''; for(var i=1;i<=5;i++) stH+='<span class="rv-sb'+(i<=Math.round(parseFloat(s.avg))?' f':'')+'">★</span>';
      var barH=[5,4,3,2,1].map(function(st){ var cnt=s.bars[st]||0,pct=Math.round(cnt/s.count*100); return'<div class="rv-bar-r"><span class="rv-bar-l">'+st+'★</span><div class="rv-bar-t"><div class="rv-bar-f" data-pct="'+pct+'"></div></div><span class="rv-bar-c">'+cnt+'</span></div>'; }).join('');
      sw.innerHTML='<div class="rv-score-box"><div class="rv-score-big">'+s.avg+'</div><div class="rv-stars-big">'+stH+'</div><div class="rv-score-lbl">'+s.count+' review'+(s.count>1?'s':'')+'</div></div><div class="rv-bars">'+barH+'</div>';
      setTimeout(function(){ sw.querySelectorAll('.rv-bar-f').forEach(function(b){ b.style.width=(b.dataset.pct||0)+'%'; }); },80);
    }
  }
  var list=$id('rvList'); if(!list) return;
  if(!s.reviews.length){ list.innerHTML='<div style="text-align:center;padding:1.5rem;color:var(--muted)">No reviews yet.</div>'; return; }
  var h='';
  s.reviews.slice().reverse().forEach(function(r){
    var stR=''; for(var i=1;i<=5;i++) stR+='<span class="rv-is'+(i<=r.rating?' f':'')+'">★</span>';
    h+='<div class="rv-card"><div class="rv-card-top"><span class="rv-author">'+esc(r.name)+(r.verified?'<span class="rv-ver">✓ Verified</span>':'')+'</span><div class="rv-meta"><div class="rv-item-stars">'+stR+'</div><span class="rv-date">'+esc(r.date)+'</span></div></div><div class="rv-text">'+esc(r.text)+'</div></div>';
  });
  list.innerHTML=h;
}
var sLbls=['','Poor 😕','Below Average','Average 😐','Good 😊','Excellent! 🌟'];
function pickStar(n){ selStar=n; document.querySelectorAll('.sp').forEach(function(s,i){s.classList.toggle('s',i<n);s.classList.remove('h');}); var h=$id('spHint');if(h)h.textContent=sLbls[n]||''; }
function hoverStar(n){ document.querySelectorAll('.sp').forEach(function(s,i){s.classList.toggle('h',i<n);}); }
function unhoverStar(){ document.querySelectorAll('.sp').forEach(function(s,i){s.classList.remove('h');s.classList.toggle('s',i<selStar);}); }
function submitReview(){
  if(!selStar){alert('Please select a rating!');return;}
  var ta=$id('rvText'),txt=(ta||{}).value||'';
  if(txt.trim().length<10){alert('Please write at least 10 characters.');return;}
  var sub=$id('rvSubmit'); if(sub){sub.disabled=true;sub.textContent='Submitting…';}
  var d=rvGet(),k=String(PID); if(!d[k]) d[k]=[];
  d[k].push({ name:<?= json_encode($user?($user['full_name']??$user['username']??'User'):'Guest') ?>, rating:selStar, text:txt.trim(), date:new Date().toISOString().split('T')[0], verified:<?= $loggedIn?'true':'false' ?> });
  rvSet(d);
  var ok=$id('rvOk'); if(ok) ok.style.display='block';
  if(sub) sub.style.display='none';
  renderReviews();
}

document.addEventListener('DOMContentLoaded',function(){ rvSeed(); renderReviews(); updateBadge(); });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>