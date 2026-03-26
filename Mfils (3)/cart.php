<?php
/**
 * cart.php — Mfills MShop · Full Cart Page
 * Logged-in  → full cart with checkout, promo, BV summary
 * Guest      → read-only view of localStorage cart items + login prompt
 */
$pageTitle = 'Your Cart — MShop · Mfills';
require_once __DIR__ . '/includes/functions.php';
startSession();

$loggedIn = isLoggedIn();
$userId   = $loggedIn ? currentUserId() : null;
$user     = $loggedIn ? getUser($userId) : null;

$cartItems = [];
if ($loggedIn) {
    if (!empty($_GET['data'])) {
        $decoded   = base64_decode($_GET['data']);
        $cartItems = json_decode($decoded, true) ?: [];
    } elseif (!empty($_SESSION['cart'])) {
        $cartItems = $_SESSION['cart'];
    }
}

$commRates = defined('COMMISSION_RATES') ? COMMISSION_RATES : [1=>15,2=>8,3=>6,4=>4,5=>3,6=>2,7=>2];
include __DIR__ . '/includes/header.php';
?>
<style>
:root {
  --gdd:#060f08; --gd:#0e2414; --gm:#1a3b22; --gl:#2a6336; --gll:#3a8a4a;
  --gold:#c8922a; --gold-l:#e0aa40; --gold-d:#a0721a;
  --jade:#0F7B5C; --jade-l:#14A376; --coral:#E8534A;
  --ivory:#f8f5ef; --ivory-d:#ede8de; --ivory-dd:#ddd5c4;
  --ink:#0f1a10; --muted:#5a7a60;
  --card-shadow:0 2px 18px rgba(14,36,20,.08);
}
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { font-family:'Nunito',sans-serif; background:var(--ivory); color:var(--ink); min-height:100vh; }
body::before {
  content:''; position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(ellipse 70% 45% at 5% 0%,rgba(26,59,34,.08) 0%,transparent 55%),
    radial-gradient(ellipse 50% 40% at 95% 100%,rgba(200,146,42,.06) 0%,transparent 55%),
    var(--ivory);
}

/* ══ TOP NAV ══ */
.cart-topnav {
  background:linear-gradient(135deg,var(--gdd) 0%,var(--gd) 50%,var(--gm) 100%);
  padding:.9rem 0;
  border-bottom:2.5px solid var(--gold);
  position:sticky; top:0; z-index:200;
}
.cart-topnav::after {
  content:''; position:absolute; inset:0; pointer-events:none;
  background-image:radial-gradient(circle,rgba(200,146,42,.06) 1px,transparent 1px);
  background-size:22px 22px;
}
.nav-inner {
  max-width:1120px; margin:0 auto; padding:0 1.5rem;
  display:flex; align-items:center; justify-content:space-between;
  position:relative; z-index:1; gap:1rem; flex-wrap:wrap;
}
.nav-brand {
  font-family:'Cinzel',serif; font-size:1.1rem; font-weight:900;
  color:#fff; text-decoration:none; display:flex; align-items:center; gap:.5rem;
}
.nav-brand em { color:var(--gold-l); font-style:italic; }
.nav-steps {
  display:flex; align-items:center; gap:.3rem;
  font-family:'Cinzel',serif; font-size:.68rem; font-weight:700;
}
.nav-step { color:rgba(255,255,255,.35); display:flex; align-items:center; gap:.3rem; }
.nav-step.active { color:var(--gold-l); }
.nav-step.done { color:rgba(255,255,255,.55); }
.nav-step-dot {
  width:22px; height:22px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:.62rem; font-weight:900;
  background:rgba(255,255,255,.1); border:1.5px solid rgba(255,255,255,.15);
}
.nav-step.active .nav-step-dot { background:var(--gold); border-color:var(--gold-l); color:var(--gdd); }
.nav-step.done .nav-step-dot { background:var(--jade); border-color:var(--jade-l); color:#fff; }
.nav-divider { color:rgba(255,255,255,.15); font-size:.7rem; }
.nav-secure {
  display:flex; align-items:center; gap:.4rem;
  font-size:.7rem; font-weight:700; font-family:'Cinzel',serif;
  color:rgba(200,146,42,.7);
  background:rgba(200,146,42,.08); border:1px solid rgba(200,146,42,.2);
  border-radius:20px; padding:.28rem .8rem;
}
@media(max-width:600px){ .nav-steps,.nav-secure{ display:none; } }

/* ══ PAGE ══ */
.page { max-width:1120px; margin:0 auto; padding:2rem 1.5rem 6rem; }
.page-title {
  font-family:'DM Serif Display',serif; font-size:1.9rem; font-weight:700;
  color:var(--gd); margin-bottom:.35rem;
  display:flex; align-items:center; gap:.65rem;
}
.page-title-sub { font-size:.82rem; color:var(--muted); font-family:'Nunito',sans-serif; margin-bottom:2rem; }
.back-link {
  display:inline-flex; align-items:center; gap:.35rem;
  color:var(--jade); font-size:.78rem; font-weight:800; text-decoration:none;
  font-family:'Cinzel',serif;
  background:rgba(15,123,92,.06); border:1px solid rgba(15,123,92,.15);
  border-radius:20px; padding:.25rem .7rem;
  transition:all .2s;
}
.back-link:hover { background:rgba(15,123,92,.12); transform:translateX(-2px); }

/* ══ FULL CART (logged-in) ══ */
.two-col {
  display:grid; grid-template-columns:1fr 370px; gap:2rem; align-items:start;
}
@media(max-width:920px) { .two-col { grid-template-columns:1fr; } }

/* free delivery bar */
.fdb { background:#fff; border:1.5px solid var(--ivory-dd); border-radius:14px; padding:.9rem 1.15rem; margin-bottom:1rem; box-shadow:var(--card-shadow); display:flex; flex-direction:column; gap:.5rem; }
.fdb-top { display:flex; align-items:center; justify-content:space-between; }
.fdb-label { font-size:.78rem; font-weight:800; color:var(--gd); font-family:'Cinzel',serif; }
.fdb-pct { font-size:.72rem; font-weight:800; color:var(--jade); font-family:'Cinzel',serif; }
.fdb-track { height:8px; background:var(--ivory-dd); border-radius:10px; overflow:hidden; }
.fdb-fill { height:100%; border-radius:10px; background:linear-gradient(90deg,var(--jade),var(--jade-l)); transition:width .8s cubic-bezier(.22,.68,0,1.2); }
.fdb-note { font-size:.7rem; color:var(--muted); font-family:'Nunito',sans-serif; }

/* cart items */
.cart-list { display:flex; flex-direction:column; gap:.9rem; }
.ci {
  background:#fff; border:1.5px solid var(--ivory-dd); border-radius:16px;
  padding:1.1rem 1.2rem;
  display:grid; grid-template-columns:76px 1fr auto;
  gap:1rem; align-items:center;
  box-shadow:var(--card-shadow); position:relative; overflow:hidden;
  transition:transform .28s cubic-bezier(.34,1.2,.64,1), box-shadow .28s, opacity .3s;
  animation:ciIn .4s ease both;
}
.ci::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--gm); border-radius:0 3px 3px 0; transition:background .3s; }
.ci[data-cat="vitamins"]::before { background:var(--jade); }
.ci[data-cat="ayurvedic"]::before { background:var(--gold); }
.ci[data-cat="weightloss"]::before { background:var(--coral); }
.ci:hover { transform:translateY(-3px); box-shadow:0 10px 34px rgba(14,36,20,.13); }
.ci.removing { opacity:0!important; transform:translateX(40px) scale(.96)!important; transition:opacity .3s ease,transform .3s ease!important; }
@keyframes ciIn { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

.ci-img { width:76px; height:76px; border-radius:12px; object-fit:cover; background:var(--ivory-d); border:1.5px solid var(--ivory-dd); display:block; flex-shrink:0; transition:transform .3s; }
.ci:hover .ci-img { transform:scale(1.06); }
.ci-info { min-width:0; }
.ci-badge { display:inline-flex; align-items:center; gap:.22rem; font-family:'Cinzel',serif; font-size:.58rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; padding:.16rem .5rem; border-radius:4px; margin-bottom:.38rem; }
.ci-name { font-family:'DM Serif Display',serif; font-size:1rem; font-weight:700; color:var(--ink); line-height:1.3; margin-bottom:.28rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ci-meta { font-size:.72rem; color:var(--muted); display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
.ci-bv { display:inline-flex; align-items:center; gap:.2rem; background:rgba(200,146,42,.1); color:#92400E; font-size:.62rem; font-weight:800; padding:.1rem .38rem; border-radius:20px; font-family:'Cinzel',serif; border:1px solid rgba(200,146,42,.2); }
.ci-right { display:flex; flex-direction:column; align-items:flex-end; gap:.65rem; flex-shrink:0; }
.ci-price { font-family:'DM Serif Display',serif; font-size:1.15rem; font-weight:700; color:var(--gd); white-space:nowrap; }
.ci-price small { font-size:.65rem; color:var(--muted); font-family:'Nunito',sans-serif; font-weight:600; display:block; text-align:right; }

/* qty stepper */
.qty-box { display:flex; align-items:center; border:1.5px solid var(--ivory-dd); border-radius:50px; overflow:hidden; background:var(--ivory); transition:border-color .2s; }
.qty-box:focus-within { border-color:var(--gl); }
.qb { width:30px; height:30px; border:none; background:transparent; color:var(--gm); font-size:1.1rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; user-select:none; }
.qb:hover { background:var(--ivory-d); }
.qn { width:30px; height:30px; line-height:30px; text-align:center; border:none; border-left:1px solid var(--ivory-dd); border-right:1px solid var(--ivory-dd); background:transparent; font-size:.88rem; font-weight:800; color:var(--ink); font-family:'Nunito',sans-serif; outline:none; -moz-appearance:textfield; }
.qn::-webkit-inner-spin-button { -webkit-appearance:none; }
.rm-btn { background:none; border:none; color:rgba(232,83,74,.45); font-size:.68rem; font-weight:700; cursor:pointer; font-family:'Nunito',sans-serif; display:flex; align-items:center; gap:.2rem; transition:color .18s; padding:.1rem 0; }
.rm-btn:hover { color:var(--coral); }

@media(max-width:560px) {
  .ci { grid-template-columns:60px 1fr; grid-template-rows:auto auto; gap:.75rem; }
  .ci-img { width:60px; height:60px; }
  .ci-right { flex-direction:row; align-items:center; justify-content:space-between; grid-column:1/-1; }
}

/* promo */
.promo-card { background:#fff; border:1.5px dashed rgba(200,146,42,.4); border-radius:14px; padding:.95rem 1.1rem; display:flex; align-items:center; gap:.75rem; box-shadow:var(--card-shadow); margin-top:1rem; transition:border-color .25s; }
.promo-card:focus-within { border-color:var(--gold-l); }
.promo-icon { font-size:1.35rem; flex-shrink:0; }
.promo-inp { flex:1; border:none; background:transparent; font-family:'Nunito',sans-serif; font-size:.88rem; font-weight:700; color:var(--ink); outline:none; }
.promo-inp::placeholder { color:rgba(90,122,96,.4); font-weight:600; }
.promo-ok { font-size:.78rem; color:var(--jade); font-weight:800; font-family:'Cinzel',serif; display:none; align-items:center; gap:.3rem; }
.promo-btn { background:var(--gd); color:#fff; border:none; border-radius:50px; padding:.42rem 1rem; font-family:'Cinzel',serif; font-size:.72rem; font-weight:800; cursor:pointer; transition:background .2s,transform .18s; white-space:nowrap; }
.promo-btn:hover { background:var(--gl); transform:translateY(-1px); }

/* upsell */
.upsell-section { margin-top:1.75rem; }
.section-hed { font-family:'Cinzel',serif; font-size:.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.14em; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; }
.section-hed::after { content:''; flex:1; height:1px; background:var(--ivory-dd); }
.upsell-row { display:grid; grid-template-columns:repeat(3,1fr); gap:.8rem; }
@media(max-width:560px) { .upsell-row { grid-template-columns:repeat(2,1fr); } }
.upc { background:#fff; border:1.5px solid var(--ivory-dd); border-radius:12px; overflow:hidden; transition:transform .25s,box-shadow .25s; cursor:pointer; }
.upc:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(14,36,20,.11); }
.upc img { width:100%; height:72px; object-fit:cover; background:var(--ivory-d); display:block; }
.upc-body { padding:.6rem .7rem; }
.upc-name { font-size:.76rem; font-weight:800; color:var(--ink); font-family:'Nunito',sans-serif; line-height:1.3; margin-bottom:.2rem; }
.upc-price { font-size:.82rem; font-weight:800; color:var(--gd); font-family:'DM Serif Display',serif; }
.upc-add { width:100%; margin-top:.5rem; padding:.38rem; background:var(--ivory); border:1.5px solid var(--ivory-dd); border-radius:8px; font-size:.68rem; font-weight:800; font-family:'Cinzel',serif; color:var(--gd); cursor:pointer; transition:all .2s; }
.upc-add:hover { background:var(--gd); color:#fff; border-color:var(--gd); }
.upc-add.added { background:var(--jade); color:#fff; border-color:var(--jade); cursor:default; }

/* order summary panel */
.summary { background:linear-gradient(160deg,#0c1e11 0%,#091508 60%,#040e07 100%); border-radius:20px; border:1px solid rgba(200,146,42,.14); box-shadow:0 20px 60px rgba(0,0,0,.28); position:sticky; top:80px; overflow:hidden; }
.sum-head { padding:1.3rem 1.5rem 1rem; border-bottom:1px solid rgba(200,146,42,.1); background:rgba(200,146,42,.04); }
.sum-title { font-family:'Cinzel',serif; font-size:.95rem; font-weight:900; color:#fff; display:flex; align-items:center; gap:.45rem; }
.sum-subtitle { font-size:.67rem; color:rgba(200,146,42,.5); font-family:'Nunito',sans-serif; margin-top:.18rem; }
.sum-body { padding:1.2rem 1.5rem; }
.sum-row { display:flex; justify-content:space-between; align-items:center; padding:.42rem 0; border-bottom:1px solid rgba(255,255,255,.04); }
.sum-row:last-of-type { border-bottom:none; }
.sl { font-size:.74rem; color:rgba(255,255,255,.38); font-weight:600; }
.sv { font-size:.8rem; font-weight:800; color:rgba(255,255,255,.72); font-family:'Cinzel',serif; }
.sum-row.disc .sl { color:rgba(20,163,118,.75); }
.sum-row.disc .sv { color:#14A376; }
.sum-divider { height:1px; background:linear-gradient(90deg,transparent,rgba(200,146,42,.22),transparent); margin:.75rem 0; }
.sum-total { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:1.3rem; }
.stl { font-size:.68rem; color:rgba(255,255,255,.32); text-transform:uppercase; letter-spacing:.1em; font-weight:700; }
.stv { font-family:'DM Serif Display',serif; font-size:1.75rem; font-weight:800; color:#fff; }
.stv span { color:var(--gold-l); }
.chk-btn { width:100%; padding:.98rem 1rem; background:linear-gradient(135deg,#B88018 0%,#e0aa40 50%,#B88018 100%); background-size:200% 100%; border:none; border-radius:13px; font-family:'Cinzel',serif; font-size:.9rem; font-weight:900; color:#0a1a0f; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:.5rem; transition:background-position .4s,transform .2s,box-shadow .2s; box-shadow:0 5px 22px rgba(200,146,42,.35); letter-spacing:.03em; margin-bottom:.55rem; }
.chk-btn:hover { background-position:100% 50%; transform:translateY(-2px); box-shadow:0 10px 36px rgba(200,146,42,.5); }
.cnt-btn { width:100%; padding:.7rem 1rem; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); border-radius:13px; font-family:'Cinzel',serif; font-size:.78rem; font-weight:700; color:rgba(255,255,255,.5); cursor:pointer; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:.4rem; transition:all .2s; }
.cnt-btn:hover { background:rgba(255,255,255,.09); color:rgba(255,255,255,.8); }
.trust { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.9rem; padding-top:.9rem; border-top:1px solid rgba(255,255,255,.05); }
.tb { display:flex; align-items:center; gap:.25rem; font-size:.6rem; font-weight:700; color:rgba(255,255,255,.28); font-family:'Nunito',sans-serif; flex:1; min-width:70px; }
.pmts { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.8rem; justify-content:center; }
.pmt { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08); border-radius:6px; padding:.25rem .55rem; font-size:.58rem; font-weight:800; color:rgba(255,255,255,.35); font-family:'Cinzel',serif; letter-spacing:.04em; }

/* ══ GUEST CART VIEW ══ */
.guest-cart-wrap { max-width:680px; margin:0 auto; }
.guest-cart-header {
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom:1.25rem; flex-wrap:wrap; gap:.75rem;
}
.guest-cart-title {
  font-family:'DM Serif Display',serif; font-size:1.6rem; font-weight:700;
  color:var(--gd); display:flex; align-items:center; gap:.55rem;
}
.guest-item-count {
  font-size:.72rem; color:var(--muted); background:var(--ivory-d);
  padding:.2rem .65rem; border-radius:20px; border:1px solid var(--ivory-dd);
  font-family:'Nunito',sans-serif; font-weight:700;
}
.gi {
  background:#fff; border:1.5px solid var(--ivory-dd); border-radius:14px;
  padding:.95rem 1.1rem;
  display:flex; align-items:center; gap:1rem;
  box-shadow:var(--card-shadow);
  animation:ciIn .4s ease both;
  position:relative; overflow:hidden;
}
.gi::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--gm); border-radius:0 3px 3px 0; }
.gi[data-cat="vitamins"]::before { background:var(--jade); }
.gi[data-cat="ayurvedic"]::before { background:var(--gold); }
.gi[data-cat="weightloss"]::before { background:var(--coral); }
.gi-img { width:64px; height:64px; border-radius:10px; object-fit:cover; background:var(--ivory-d); border:1.5px solid var(--ivory-dd); flex-shrink:0; }
.gi-info { flex:1; min-width:0; }
.gi-name { font-family:'DM Serif Display',serif; font-size:.95rem; font-weight:700; color:var(--ink); line-height:1.3; margin-bottom:.22rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.gi-meta { font-size:.7rem; color:var(--muted); display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.gi-qty-badge { background:var(--ivory-d); border:1px solid var(--ivory-dd); color:var(--gd); font-size:.65rem; font-weight:800; padding:.1rem .45rem; border-radius:20px; font-family:'Cinzel',serif; }
.gi-right { text-align:right; flex-shrink:0; }
.gi-price { font-family:'DM Serif Display',serif; font-size:1.1rem; font-weight:700; color:var(--gd); }
.gi-unit { font-size:.65rem; color:var(--muted); font-family:'Nunito',sans-serif; }

/* guest login nudge card */
.guest-login-card {
  background:linear-gradient(135deg,#0c1e11,#091508);
  border:1.5px solid rgba(200,146,42,.22);
  border-radius:18px; padding:2rem 1.75rem;
  text-align:center; margin-top:1.5rem;
  box-shadow:0 12px 40px rgba(0,0,0,.2);
}
.glc-icon { font-size:2.5rem; margin-bottom:.75rem; }
.glc-title { font-family:'Cinzel',serif; font-size:1.05rem; font-weight:900; color:#fff; margin-bottom:.4rem; }
.glc-sub { font-size:.8rem; color:rgba(255,255,255,.42); font-family:'Nunito',sans-serif; line-height:1.65; margin-bottom:1.35rem; }
.glc-perks { display:flex; flex-wrap:wrap; justify-content:center; gap:.5rem; margin-bottom:1.4rem; }
.glc-perk { background:rgba(200,146,42,.1); border:1px solid rgba(200,146,42,.2); color:rgba(200,146,42,.8); font-size:.65rem; font-weight:800; padding:.28rem .75rem; border-radius:20px; font-family:'Cinzel',serif; }
.glc-btns { display:flex; flex-direction:column; gap:.6rem; }
.glc-login {
  display:block; text-align:center;
  background:linear-gradient(135deg,#B88018,#e0aa40,#B88018); background-size:200% 100%;
  color:#0a1a0f; padding:.82rem; border-radius:12px;
  font-family:'Cinzel',serif; font-size:.85rem; font-weight:900;
  letter-spacing:.03em; text-decoration:none;
  transition:background-position .4s, transform .2s, box-shadow .2s;
  box-shadow:0 4px 18px rgba(200,146,42,.3);
}
.glc-login:hover { background-position:100% 50%; transform:translateY(-2px); box-shadow:0 8px 28px rgba(200,146,42,.45); color:#0a1a0f; }
.glc-register {
  display:block; text-align:center;
  background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.12);
  color:rgba(255,255,255,.6); padding:.72rem; border-radius:12px;
  font-family:'Cinzel',serif; font-size:.78rem; font-weight:700;
  text-decoration:none; transition:all .22s;
}
.glc-register:hover { background:rgba(255,255,255,.09); color:rgba(255,255,255,.9); }
.glc-divider { display:flex; align-items:center; gap:.5rem; font-size:.6rem; color:rgba(255,255,255,.2); }
.glc-divider::before,.glc-divider::after { content:''; flex:1; height:1px; background:rgba(255,255,255,.1); }

/* guest cart total strip */
.guest-total-strip {
  background:#fff; border:1.5px solid var(--ivory-dd); border-radius:14px;
  padding:1rem 1.2rem; margin-top:1rem;
  display:flex; align-items:center; justify-content:space-between;
  box-shadow:var(--card-shadow); flex-wrap:wrap; gap:.5rem;
}
.gts-label { font-size:.78rem; color:var(--muted); font-family:'Nunito',sans-serif; font-weight:600; }
.gts-val { font-family:'DM Serif Display',serif; font-size:1.4rem; font-weight:700; color:var(--gd); }
.gts-note { font-size:.65rem; color:var(--muted); font-family:'Nunito',sans-serif; width:100%; margin-top:.15rem; }

/* ══ EMPTY STATE ══ */
.empty-state { text-align:center; padding:5rem 2rem; background:#fff; border-radius:18px; border:1.5px solid var(--ivory-dd); box-shadow:var(--card-shadow); display:flex; flex-direction:column; align-items:center; gap:.9rem; animation:ciIn .5s ease both; }
.empty-icon { font-size:3.8rem; opacity:.22; animation:floatIt 3s ease-in-out infinite; }
@keyframes floatIt { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
.empty-state h2 { font-family:'DM Serif Display',serif; font-size:1.5rem; color:var(--gd); }
.empty-state p { font-size:.84rem; color:var(--muted); max-width:300px; line-height:1.65; }
.shop-btn { background:var(--gd); color:#fff; border:none; border-radius:50px; padding:.72rem 1.8rem; font-family:'Cinzel',serif; font-size:.84rem; font-weight:800; cursor:pointer; transition:background .2s,transform .18s; text-decoration:none; display:inline-block; margin-top:.4rem; }
.shop-btn:hover { background:var(--gl); transform:translateY(-2px); }

/* ══ TOAST ══ */
.toast { position:fixed; bottom:1.75rem; left:50%; transform:translateX(-50%) translateY(80px); background:var(--gd); color:#fff; border:1px solid rgba(200,146,42,.25); border-radius:50px; padding:.65rem 1.5rem; font-family:'Cinzel',serif; font-size:.76rem; font-weight:700; box-shadow:0 8px 28px rgba(0,0,0,.28); z-index:999; pointer-events:none; transition:transform .38s cubic-bezier(.22,.68,0,1.3); display:flex; align-items:center; gap:.45rem; }
.toast.show { transform:translateX(-50%) translateY(0); }
</style>

<!-- ══ TOP NAV ══ -->
<div class="cart-topnav">
  <div class="nav-inner">
    <a href="shop.php" class="nav-brand">🛒 <em>MShop</em></a>
    <div class="nav-steps">
      <div class="nav-step done"><div class="nav-step-dot">✓</div> Shop</div>
      <div class="nav-divider">›</div>
      <div class="nav-step active"><div class="nav-step-dot">2</div> Cart</div>
      <div class="nav-divider">›</div>
      <?php if ($loggedIn): ?>
      <div class="nav-step"><div class="nav-step-dot">3</div> Checkout</div>
      <div class="nav-divider">›</div>
      <div class="nav-step"><div class="nav-step-dot">4</div> Confirm</div>
      <?php else: ?>
      <div class="nav-step"><div class="nav-step-dot">3</div> Login</div>
      <div class="nav-divider">›</div>
      <div class="nav-step"><div class="nav-step-dot">4</div> Checkout</div>
      <?php endif; ?>
    </div>
    <div class="nav-secure">🔒 <?= $loggedIn ? '100% Secure' : 'Safe & Private' ?></div>
  </div>
</div>

<!-- ══ PAGE ══ -->
<div class="page">

  <a href="shop.php" class="back-link" style="margin-bottom:1rem;display:inline-flex">← Continue Shopping</a>

  <?php if ($loggedIn): ?>
  <!-- ══════════════════════════════════════
       LOGGED-IN: FULL CART
  ══════════════════════════════════════ -->
  <h1 class="page-title" style="margin-top:.75rem">
    🛒 Your Cart
    <span id="headCount" style="background:var(--gd);color:#fff;font-family:'Nunito',sans-serif;font-size:.82rem;font-weight:800;border-radius:20px;padding:.2rem .65rem;margin-top:.1rem;">0</span>
  </h1>
  <div class="page-title-sub" id="pageSub">Loading your cart…</div>

  <div class="empty-state" id="emptyState" style="display:none">
    <span class="empty-icon">🌿</span>
    <h2>Your cart is empty</h2>
    <p>Discover premium supplements, vitamins, and ayurvedic products on MShop.</p>
    <a href="shop.php" class="shop-btn">🛍️ Browse Products</a>
  </div>

  <div class="two-col" id="mainLayout">
    <div>
      <div class="fdb" id="fdbWrap">
        <div class="fdb-top">
          <span class="fdb-label" id="fdbLbl">Calculating…</span>
          <span class="fdb-pct" id="fdbPct"></span>
        </div>
        <div class="fdb-track"><div class="fdb-fill" id="fdbFill" style="width:0%"></div></div>
        <div class="fdb-note" id="fdbNote"></div>
      </div>
      <div class="cart-list" id="cartList"></div>
      <div class="promo-card" id="promoCard">
        <span class="promo-icon">🎟️</span>
        <input class="promo-inp" type="text" id="promoInp" placeholder="Enter promo / referral code…">
        <span class="promo-ok" id="promoOk">✅ Applied!</span>
        <button class="promo-btn" onclick="applyPromo()">Apply</button>
      </div>
      <div class="upsell-section" id="upsellSec">
        <div class="section-hed">✨ Frequently Bought Together</div>
        <div class="upsell-row" id="upsellRow"></div>
      </div>
    </div>

    <!-- order summary -->
    <div class="summary" id="summaryPanel">
      <div class="sum-head">
        <div class="sum-title">📋 Order Summary</div>
        <div class="sum-subtitle" id="sumSub">—</div>
      </div>
      <div class="sum-body">
        <div class="sum-row">
          <span class="sl">Subtotal (<span id="sumQtyLbl">0</span> items)</span>
          <span class="sv" id="sumSubtotal">₹0</span>
        </div>
        <div class="sum-row">
          <span class="sl">Delivery</span>
          <span class="sv" id="sumDel">₹99</span>
        </div>
        <div class="sum-row disc" id="discRow" style="display:none">
          <span class="sl">🎟️ Promo Discount</span>
          <span class="sv" id="sumDisc">-₹0</span>
        </div>
        <div class="sum-divider"></div>
        <div class="sum-total">
          <span class="stl">Total Payable</span>
          <div class="stv"><span>₹</span><span id="sumTotal">0</span></div>
        </div>

        <!-- PSB/BV Estimate box: HIDDEN -->

        <form method="POST" action="<?= APP_URL ?>/checkout.php" id="chkForm">
          <input type="hidden" name="shop_source" value="mshop">
          <input type="hidden" name="cart_data" id="chkData" value="[]">
          <button type="button" class="chk-btn" onclick="checkout()">
            🛍️ Proceed to Checkout
          </button>
        </form>
        <a href="shop.php" class="cnt-btn">← Continue Shopping</a>
        <div class="trust">
          <div class="tb">🔒 SSL Secure</div>
          <div class="tb">🚚 Fast Delivery</div>
          <div class="tb">↩️ Easy Returns</div>
          <div class="tb">✅ Genuine</div>
        </div>
        <div class="pmts">
          <span class="pmt">UPI</span><span class="pmt">VISA</span>
          <span class="pmt">MASTER</span><span class="pmt">NETBANK</span>
          <span class="pmt">COD</span><span class="pmt">EMI</span>
        </div>
      </div>
    </div>
  </div>

  <?php else: ?>
  <!-- ══════════════════════════════════════
       GUEST: READ-ONLY CART VIEW
  ══════════════════════════════════════ -->
  <div class="guest-cart-wrap" style="margin-top:.75rem">

    <div class="guest-cart-header">
      <div class="guest-cart-title">
        🛒 Your Cart
        <span class="guest-item-count" id="guestItemCount">0 items</span>
      </div>
    </div>

    <div class="empty-state" id="guestEmpty" style="display:none">
      <span class="empty-icon">🌿</span>
      <h2>Your cart is empty</h2>
      <p>Add products from MShop — your cart saves automatically even without login.</p>
      <a href="shop.php" class="shop-btn">🛍️ Browse Products</a>
    </div>

    <div id="guestCartList" style="display:flex;flex-direction:column;gap:.75rem"></div>

    <div class="guest-total-strip" id="guestTotalStrip" style="display:none">
      <div>
        <div class="gts-label">Estimated Subtotal</div>
        <div class="gts-val" id="guestSubtotal">₹0</div>
        <div class="gts-note">* Delivery & final price calculated at checkout after login</div>
      </div>
    </div>

    <div class="guest-login-card" id="guestLoginCard" style="display:none">
      <div class="glc-icon">🔐</div>
      <div class="glc-title">Login to Checkout</div>
      <div class="glc-sub">
        Your cart is saved. Login or register free to complete your order,<br>
        earn PSB Business Volume, and get exclusive member benefits.
      </div>
      <div class="glc-perks">
        <span class="glc-perk">📊 Earn PSB on every order</span>
        <span class="glc-perk">🚚 Fast tracked delivery</span>
        <span class="glc-perk">💰 Wallet rewards</span>
        <span class="glc-perk">↩️ Easy returns</span>
      </div>
      <div class="glc-btns">
        <a href="<?= APP_URL ?>/login.php?redirect=cart" class="glc-login">🔑 Login to Checkout</a>
        <div class="glc-divider">or</div>
        <a href="<?= APP_URL ?>/register.php" class="glc-register">📝 Register Free — Start Earning PSB</a>
      </div>
    </div>

  </div>
  <?php endif; ?>

</div><!-- /page -->

<div class="toast" id="toast">✅ Done</div>

<script>
var CR = <?= json_encode($commRates) ?>;
var FREE_DEL = 1299;
var DEL_CHARGE = 99;
var promoApplied = false;
var LOGGED_IN = <?= $loggedIn ? 'true' : 'false' ?>;
var STORE_KEY = LOGGED_IN ? 'mfills_cart_auth' : 'mfills_gc';

var UPSELLS = [
  { pid:101, name:'Vitamin D3 + K2',       price:499, bv:420, img:'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=200&h=72&fit=crop', cat:'vitamins' },
  { pid:102, name:'Mass Gainer 3kg',        price:799, bv:680, img:'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=200&h=72&fit=crop', cat:'protein'  },
  { pid:103, name:'KSM-66 Ashwagandha',     price:349, bv:290, img:'https://images.unsplash.com/photo-1611071536226-785bc5f12d76?w=200&h=72&fit=crop', cat:'ayurvedic'},
  { pid:104, name:'Omega-3 Fish Oil 60cap', price:649, bv:550, img:'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=200&h=72&fit=crop', cat:'vitamins' },
];

var CAT_BADGE = {
  protein:   { bg:'rgba(26,59,34,.1)',   fg:'#1a3b22', icon:'🏋️' },
  vitamins:  { bg:'rgba(15,123,92,.1)',  fg:'#0F7B5C', icon:'🌿' },
  ayurvedic: { bg:'rgba(160,114,26,.1)', fg:'#a0721a', icon:'🌱' },
  weightloss:{ bg:'rgba(192,50,26,.1)',  fg:'#C0321A', icon:'🔥' },
  other:     { bg:'rgba(55,65,81,.1)',   fg:'#374151', icon:'💊' },
};

function fmt(n){ return Number(n).toLocaleString('en-IN',{maximumFractionDigits:0}); }
function $(id){ return document.getElementById(id); }
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function toast(msg){ var el=$('toast'); el.textContent=msg; el.classList.add('show'); setTimeout(function(){ el.classList.remove('show'); },2600); }

/* ── Cart state ── */
var cart = {};
function loadCart(){
  var urlParam = new URLSearchParams(window.location.search).get('data');
  if (urlParam) {
    try{
      var arr = JSON.parse(atob(urlParam));
      arr.forEach(function(it){ cart[it.product_id||it.pid]=it; });
      saveCart();
      window.history.replaceState({},'',(window.location.pathname));
      return;
    }catch(e){}
  }
  try{ cart = JSON.parse(localStorage.getItem(STORE_KEY)||'{}'); }catch(e){ cart={}; }
}
function saveCart(){
  try{ localStorage.setItem(STORE_KEY,JSON.stringify(cart)); }catch(e){}
}

/* ══════════════════════════
   LOGGED-IN CART RENDER
══════════════════════════ */
function renderCart(){
  if (!LOGGED_IN) return;
  var pids = Object.keys(cart);
  var listEl = $('cartList');
  listEl.innerHTML='';
  if (!pids.length){
    $('emptyState').style.display='flex';
    $('mainLayout').style.display='none';
    $('headCount').textContent='0';
    $('pageSub').textContent='Your cart is empty';
    return;
  }
  $('emptyState').style.display='none';
  $('mainLayout').style.display='';
  pids.forEach(function(pid,idx){
    var it=cart[pid];
    var badge=CAT_BADGE[it.cat||'other']||CAT_BADGE.other;
    var linePrice=(it.price||0)*(it.qty||1);
    var div=document.createElement('div');
    div.className='ci'; div.dataset.pid=pid; div.dataset.cat=it.cat||'other';
    div.style.animationDelay=(idx*.06)+'s';
    div.innerHTML=
      '<img class="ci-img" src="'+esc(it.image_url||'')+'" alt="'+esc(it.name)+'" onerror="this.style.opacity=\'.3\'">'+
      '<div class="ci-info">'+
        '<span class="ci-badge" style="background:'+badge.bg+';color:'+badge.fg+'">'+badge.icon+' '+(it.cat||'other')+'</span>'+
        '<div class="ci-name">'+esc(it.name)+'</div>'+
        '<div class="ci-meta">₹'+fmt(it.price||0)+' each <span class="ci-bv">📊 BV ₹'+fmt(it.bv||0)+'</span></div>'+
      '</div>'+
      '<div class="ci-right">'+
        '<div>'+
          '<div class="ci-price">₹<span id="lp-'+pid+'">'+fmt(linePrice)+'</span><small>line total</small></div>'+
          '<div class="qty-box" style="margin-top:.5rem;margin-left:auto;width:fit-content">'+
            '<button class="qb" onclick="stepQ('+pid+',-1)">−</button>'+
            '<input class="qn" type="number" id="qn-'+pid+'" value="'+(it.qty||1)+'" min="1" max="10" onchange="qChanged('+pid+')">'+
            '<button class="qb" onclick="stepQ('+pid+',1)">+</button>'+
          '</div>'+
        '</div>'+
        '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:.2rem">'+
          '<button class="rm-btn" onclick="removeItem('+pid+')">✕ Remove</button>'+
        '</div>'+
      '</div>';
    listEl.appendChild(div);
  });
  updateTotals();
  renderUpsells();
}

function stepQ(pid,dir){
  if(!cart[pid]) return;
  var v=Math.min(10,Math.max(1,(parseInt($('qn-'+pid).value)||1)+dir));
  $('qn-'+pid).value=v; cart[pid].qty=v;
  var lp=$('lp-'+pid); if(lp) lp.textContent=fmt(cart[pid].price*v);
  saveCart(); updateTotals();
}
function qChanged(pid){
  if(!cart[pid]) return;
  var v=Math.min(10,Math.max(1,parseInt($('qn-'+pid).value)||1));
  $('qn-'+pid).value=v; cart[pid].qty=v;
  var lp=$('lp-'+pid); if(lp) lp.textContent=fmt(cart[pid].price*v);
  saveCart(); updateTotals();
}
function removeItem(pid){
  var el=document.querySelector('.ci[data-pid="'+pid+'"]');
  if(el){ el.classList.add('removing'); setTimeout(function(){ el.remove(); },300); }
  delete cart[pid]; saveCart();
  toast('🗑️ Removed from cart');
  setTimeout(function(){ updateTotals(); renderUpsells(); checkEmpty(); },320);
}
function checkEmpty(){
  if(!Object.keys(cart).length){
    $('emptyState').style.display='flex'; $('mainLayout').style.display='none';
    $('headCount').textContent='0'; $('pageSub').textContent='Your cart is empty';
  }
}

function updateTotals(){
  if(!LOGGED_IN) return;
  var pids=Object.keys(cart),subtotal=0,totalBv=0,totalQty=0;
  pids.forEach(function(p){ subtotal+=(cart[p].price||0)*(cart[p].qty||1); totalBv+=(cart[p].bv||0)*(cart[p].qty||1); totalQty+=(cart[p].qty||1); });
  var del=subtotal>=FREE_DEL?0:DEL_CHARGE;
  var discount=promoApplied?Math.round(subtotal*.10):0;
  var grand=subtotal+del-discount;
  $('headCount').textContent=pids.length+' item'+(pids.length!==1?'s':'');
  $('pageSub').textContent=totalQty+' unit'+(totalQty!==1?'s':'')+' · '+pids.length+' product'+(pids.length!==1?'s':'');
  $('sumSub').textContent=pids.length+' product'+(pids.length!==1?'s':'')+' in cart';
  $('sumQtyLbl').textContent=totalQty;
  $('sumSubtotal').textContent='₹'+fmt(subtotal);
  $('sumDel').textContent=del===0?'🎉 FREE':'₹'+fmt(del);
  $('sumTotal').textContent=fmt(grand);
  if(discount>0){ $('discRow').style.display=''; $('sumDisc').textContent='-₹'+fmt(discount); }
  else { $('discRow').style.display='none'; }
  /* BV variables still calculated internally for cart logic, just not displayed */
  var pct=Math.min(100,Math.round(subtotal/FREE_DEL*100));
  $('fdbFill').style.width=pct+'%'; $('fdbPct').textContent=pct+'%';
  if(subtotal>=FREE_DEL){ $('fdbLbl').innerHTML='🎉 <strong>Free delivery unlocked!</strong>'; $('fdbNote').textContent='Your order qualifies for FREE delivery.'; }
  else{ var rem=FREE_DEL-subtotal; $('fdbLbl').innerHTML='Add <strong>₹'+fmt(rem)+'</strong> more for FREE delivery 🚚'; $('fdbNote').textContent='Free delivery on orders above ₹'+fmt(FREE_DEL); }
}

function renderUpsells(){
  if(!LOGGED_IN) return;
  var row=$('upsellRow'); row.innerHTML=''; var shown=0;
  UPSELLS.forEach(function(u){
    if(cart[u.pid]||shown>=3) return; shown++;
    var div=document.createElement('div'); div.className='upc'; div.id='upc-'+u.pid;
    div.innerHTML='<img src="'+esc(u.img)+'" alt="'+esc(u.name)+'" onerror="this.style.opacity=\'.3\'"><div class="upc-body"><div class="upc-name">'+esc(u.name)+'</div><div class="upc-price">₹'+fmt(u.price)+'</div><button class="upc-add" id="ua-'+u.pid+'" onclick="upsellAdd('+u.pid+')">+ Add</button></div>';
    row.appendChild(div);
  });
  $('upsellSec').style.display=shown>0?'':'none';
}

function upsellAdd(pid){
  var u=UPSELLS.find(function(x){ return x.pid===pid; }); if(!u) return;
  cart[pid]={product_id:pid,name:u.name,price:u.price,bv:u.bv,image_url:u.img,cat:u.cat,qty:1};
  saveCart();
  var listEl=$('cartList');
  var badge=CAT_BADGE[u.cat]||CAT_BADGE.other;
  var div=document.createElement('div'); div.className='ci'; div.dataset.pid=pid; div.dataset.cat=u.cat;
  div.innerHTML='<img class="ci-img" src="'+esc(u.img)+'" alt="'+esc(u.name)+'" onerror="this.style.opacity=\'.3\'"><div class="ci-info"><span class="ci-badge" style="background:'+badge.bg+';color:'+badge.fg+'">'+badge.icon+' '+u.cat+'</span><div class="ci-name">'+esc(u.name)+'</div><div class="ci-meta">₹'+fmt(u.price)+' each <span class="ci-bv">📊 BV ₹'+fmt(u.bv)+'</span></div></div><div class="ci-right"><div><div class="ci-price">₹<span id="lp-'+pid+'">'+fmt(u.price)+'</span><small>line total</small></div><div class="qty-box" style="margin-top:.5rem;margin-left:auto;width:fit-content"><button class="qb" onclick="stepQ('+pid+',-1)">−</button><input class="qn" type="number" id="qn-'+pid+'" value="1" min="1" max="10" onchange="qChanged('+pid+')"><button class="qb" onclick="stepQ('+pid+',1)">+</button></div></div><button class="rm-btn" onclick="removeItem('+pid+')">✕ Remove</button></div>';
  listEl.appendChild(div);
  toast('🛒 '+u.name+' added!');
  updateTotals(); renderUpsells();
}

/* ── Promo ── */
function applyPromo(){
  var code=$('promoInp').value.trim().toUpperCase();
  var valid=['MFILLS10','SAVE10','MBPIN10','WELCOME10'];
  if(valid.indexOf(code)>=0){
    promoApplied=true;
    $('promoOk').style.display='flex'; $('promoOk').textContent='✅ '+code+' — 10% off!';
    $('promoInp').style.display='none'; document.querySelector('.promo-btn').style.display='none';
    toast('🎉 10% discount applied!'); updateTotals();
  } else if(!code){ toast('⚠️ Enter a promo code first'); }
  else { toast('❌ Invalid code. Try: MFILLS10'); }
}

/* ── Checkout ── */
function checkout(){
  var pids=Object.keys(cart); if(!pids.length){ toast('⚠️ Cart is empty!'); return; }
  var arr=pids.map(function(p){ return {product_id:parseInt(p),qty:cart[p].qty||1,price:cart[p].price,bv:cart[p].bv,name:cart[p].name,image_url:cart[p].image_url,cat:cart[p].cat}; });
  $('chkData').value=JSON.stringify(arr);
  $('chkForm').submit();
}

/* ══════════════════════════
   GUEST CART RENDER
══════════════════════════ */
function renderGuestCart(){
  if(LOGGED_IN) return;
  var pids=Object.keys(cart);
  var listEl=$('guestCartList');
  var emptyEl=$('guestEmpty');
  var stripEl=$('guestTotalStrip');
  var loginCard=$('guestLoginCard');
  var countEl=$('guestItemCount');

  if(!pids.length){
    emptyEl.style.display='flex';
    listEl.style.display='none';
    if(stripEl) stripEl.style.display='none';
    if(loginCard) loginCard.style.display='none';
    if(countEl) countEl.textContent='0 items';
    return;
  }

  emptyEl.style.display='none';
  listEl.style.display='flex';

  var totalQty=0, subtotal=0;
  pids.forEach(function(p){ totalQty+=(parseInt(cart[p].qty)||1); subtotal+=(cart[p].price||0)*(parseInt(cart[p].qty)||1); });
  if(countEl) countEl.textContent=pids.length+' item'+(pids.length!==1?'s':'')+' · '+totalQty+' unit'+(totalQty!==1?'s':'');

  listEl.innerHTML='';
  pids.forEach(function(pid,idx){
    var it=cart[pid];
    var badge=CAT_BADGE[it.cat||'other']||CAT_BADGE.other;
    var qty=parseInt(it.qty)||1;
    var linePrice=(it.price||0)*qty;
    var div=document.createElement('div');
    div.className='gi'; div.dataset.cat=it.cat||'other';
    div.style.animationDelay=(idx*.07)+'s';
    div.innerHTML=
      '<img class="gi-img" src="'+esc(it.image_url||'')+'" alt="'+esc(it.name)+'" onerror="this.style.opacity=\'.3\'">'+
      '<div class="gi-info">'+
        '<div class="gi-name">'+esc(it.name)+'</div>'+
        '<div class="gi-meta">'+
          '<span class="gi-qty-badge">Qty: '+qty+'</span>'+
          '<span style="background:'+badge.bg+';color:'+badge.fg+';font-size:.6rem;font-weight:800;padding:.1rem .4rem;border-radius:4px;font-family:\'Cinzel\',sans-serif">'+badge.icon+' '+(it.cat||'other')+'</span>'+
          '<span>₹'+fmt(it.price||0)+' each</span>'+
        '</div>'+
      '</div>'+
      '<div class="gi-right">'+
        '<div class="gi-price">₹'+fmt(linePrice)+'</div>'+
        '<div class="gi-unit">line total</div>'+
      '</div>';
    listEl.appendChild(div);
  });

  if(stripEl){
    stripEl.style.display='flex';
    var sub=$('guestSubtotal'); if(sub) sub.textContent='₹'+fmt(subtotal);
  }
  if(loginCard) loginCard.style.display='block';
}

/* ══ INIT ══ */
loadCart();
if(LOGGED_IN){
  renderCart();
} else {
  renderGuestCart();
}

var ss=document.createElement('style');
ss.textContent='@keyframes shake{0%{transform:translateX(0)}20%{transform:translateX(-6px)}40%{transform:translateX(6px)}60%{transform:translateX(-4px)}80%{transform:translateX(4px)}100%{transform:translateX(0)}}';
document.head.appendChild(ss);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>