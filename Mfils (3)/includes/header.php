<?php
// includes/header.php
require_once __DIR__ . '/functions.php';
startSession();
$flash     = getFlash();
$loggedIn  = isLoggedIn();
$currentId = currentUserId();
$user      = $loggedIn ? getUser($currentId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Cinzel:wght@700;900&family=Outfit:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/includes/animations.css">
<script src="<?= APP_URL ?>/includes/animations.js" defer></script>
<style>
/* ══════════════════════════════════════
   CSS VARIABLES
══════════════════════════════════════ */
:root {
  --green-dd:#122a18; --green-d:#1a3b22; --green:#1C3D24; --green-m:#2E6244; --green-l:#4E9A60; --green-ll:#7ec490;
  --gold:#B88018; --gold-l:#D4A030; --gold-ll:#EFBF50; --gold-d:#8a5c10;
  --w:#ffffff; --g1:#F8F5F0; --g2:#F2EEE8; --b:#E8E4DC; --b2:#D0CCBF;
  --t:#1A1A14; --t2:#4A4A3E; --t3:#8A8A78; --t4:#C0BFAF;
  --jade:#0F7B5C; --jade-l:#13a077; --jade-d:#0a5a43;
  --coral:#E8534A; --red:#C44030; --muted:#6e9a7a; --success:#0F7B5C; --danger:#C0392B;
  --ivory:var(--g1); --ivory-d:var(--g2); --ivory-dd:var(--b); --ink:var(--t);
  --indigo:var(--green-d); --indigo-d:var(--green-dd); --indigo-l:var(--green-m); --indigo-ll:var(--green-l);
  --teal:var(--jade); --teal-d:var(--jade-d); --teal-l:var(--jade-l); --teal-ll:#1bc896;
  --turmeric:var(--gold); --turmeric-l:var(--gold-l); --turmeric-d:var(--gold-d);
  --cream:var(--g1); --cream-d:var(--g2); --cream-dd:var(--b);
  --maroon:var(--green-d); --maroon-d:var(--green-dd); --maroon-l:var(--green-m);
  --shadow:0 4px 24px rgba(26,59,34,.12); --radius:10px;
  --navbar-h:78px;
}

/* ══════════════════════════════════════
   RESET & BASE
══════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;font-size:15px}
body{
  font-family:'Outfit','Nunito',sans-serif;
  background:var(--w);color:var(--t);
  min-height:100vh;line-height:1.6;
  -webkit-font-smoothing:antialiased;
  overflow-x:hidden;
}
h1,h2,h3,h4,h5{line-height:1.2}
a{color:var(--green);text-decoration:none}
a:hover{color:var(--green-m)}
img{max-width:100%;display:block}
button{cursor:pointer;font-family:'Outfit','Nunito',sans-serif}
::-webkit-scrollbar{width:3px}
::-webkit-scrollbar-thumb{background:var(--green-l)}

/* ══════════════════════════════════════
   GUEST / LANDING NAVBAR
══════════════════════════════════════ */
.nav-landing{
  position:sticky;
  top:0;
  z-index:900;
  background:rgba(255,255,255,.97);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  height:var(--navbar-h);
  will-change:transform;
  box-shadow:0 1px 0 rgba(26,59,34,.07), 0 6px 20px rgba(0,0,0,.05);
  transition:box-shadow .3s ease;
}
.nav-landing.scrolled{
  box-shadow:0 1px 0 rgba(26,59,34,.1), 0 8px 28px rgba(0,0,0,.09);
}
.nav-landing::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:2.5px;
  background:linear-gradient(90deg, transparent, var(--green-l) 25%, var(--gold) 55%, var(--green-l) 80%, transparent);
  opacity:.45;
  pointer-events:none;
}

.nav-landing-inner{
  max-width:1440px;margin:0 auto;padding:0 1.75rem;
  display:flex;align-items:center;justify-content:space-between;
  height:100%;gap:1rem;
}

/* Logo / Brand */
.nav-landing .nl-brand{
  display:flex;align-items:center;gap:10px;
  flex-shrink:0;text-decoration:none;
}
.nav-landing .nl-brand-logo{
  height:68px;
  width:auto;
  max-width:190px;
  object-fit:contain;
  object-position:left center;
  transition:opacity .2s, transform .2s;
}
.nav-landing .nl-brand-logo:hover{opacity:.85;transform:scale(1.03)}
.nav-landing .nl-brand-name{
  font-family:'DM Serif Display',serif;
  font-size:1.1rem;color:var(--green);
  letter-spacing:.03em;line-height:1;display:none;
}

/* Desktop Nav Links */
.nav-landing .nl-links{
  display:flex;align-items:center;gap:.05rem;
  flex:1;margin-left:1.75rem;
}
.nav-landing .nl-links a{
  font-size:.78rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;
  color:var(--t3);padding:.5rem .88rem;position:relative;
  transition:color .2s, background .2s;white-space:nowrap;border-radius:6px;
}
.nav-landing .nl-links a::after{
  content:'';position:absolute;bottom:3px;left:50%;right:50%;height:2px;
  background:var(--green);border-radius:2px;
  transition:left .22s cubic-bezier(.4,0,.2,1), right .22s cubic-bezier(.4,0,.2,1);
}
.nav-landing .nl-links a:hover{color:var(--green);background:rgba(26,59,34,.04);}
.nav-landing .nl-links a:hover::after{left:.88rem;right:.88rem;}

/* Desktop Right Actions */
.nav-landing .nl-right{
  display:flex;align-items:center;gap:.3rem;flex-shrink:0;
}
.nav-landing .nl-right a{
  font-size:.68rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;
  color:var(--t3);padding:.5rem .75rem;transition:color .2s;white-space:nowrap;
}
.nav-landing .nl-right a:hover{color:var(--green-d)}
.nav-landing .nl-cta{
  background:var(--green)!important;color:#fff!important;
  padding:.46rem 1.25rem!important;font-size:.66rem!important;
  font-weight:700!important;letter-spacing:.1em!important;
  margin-left:.4rem;transition:background .2s, box-shadow .2s, transform .15s!important;
  border:none;border-radius:6px!important;
  box-shadow:0 2px 10px rgba(26,59,34,.28)!important;
  position:relative;overflow:hidden;
}
.nav-landing .nl-cta::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.14) 0%,transparent 60%);
  pointer-events:none;
}
.nav-landing .nl-cta:hover{
  background:var(--green-m)!important;
  box-shadow:0 4px 18px rgba(26,59,34,.38)!important;
  transform:translateY(-1px)!important;
}

/* Mobile Auth Buttons */
.nl-mobile-auth{
  display:none;
  align-items:center;
  gap:.35rem;
  flex-shrink:0;
}
.nl-mobile-auth a{
  font-size:.62rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;
  padding:.4rem .78rem;border-radius:20px;text-decoration:none;white-space:nowrap;
  transition:all .18s;line-height:1;
}
.nl-mobile-auth .mob-login{
  color:var(--green);border:1.5px solid rgba(26,59,34,.25);background:transparent;
}
.nl-mobile-auth .mob-login:hover{background:rgba(26,59,34,.06);border-color:var(--green);color:var(--green-d);}
.nl-mobile-auth .mob-reg{
  background:var(--green);color:#fff;
  border:1.5px solid var(--green);
  box-shadow:0 2px 8px rgba(26,59,34,.2);
}
.nl-mobile-auth .mob-reg:hover{background:var(--green-m);border-color:var(--green-m);}

/* Guest Burger */
.nav-landing .nl-burger{
  display:none;
  flex-direction:column;justify-content:center;align-items:center;
  gap:0;width:44px;height:44px;
  background:rgba(26,59,34,.07);
  border:1.5px solid var(--b2);border-radius:11px;
  cursor:pointer;padding:0;flex-shrink:0;
  transition:background .2s, border-color .2s, box-shadow .2s, transform .18s;
}
.nav-landing .nl-burger:hover{
  background:rgba(26,59,34,.13);border-color:var(--green-l);
  box-shadow:0 0 0 3px rgba(26,59,34,.1);transform:scale(1.05);
}
.nav-landing .nl-burger:active{transform:scale(.96);}
.nav-landing .nl-burger span{
  display:block;border-radius:4px;
  transition:transform .32s cubic-bezier(.68,-.55,.27,1.55), opacity .22s ease, width .25s ease;
  transform-origin:center;
}
.nav-landing .nl-burger span:nth-child(1){
  width:22px;height:2.5px;
  background:linear-gradient(90deg,var(--green),var(--green-m));
  margin-bottom:4px;
}
.nav-landing .nl-burger span:nth-child(2){
  width:16px;height:3.5px;
  background:linear-gradient(90deg,var(--green-m),var(--green-l));
  margin-bottom:4px;
}
.nav-landing .nl-burger span:nth-child(3){
  width:11px;height:2.5px;
  background:linear-gradient(90deg,var(--green-l),var(--green-ll));
}

/* Guest Mobile Drawer */
.nl-drawer{
  position:fixed;top:0;left:0;bottom:0;width:300px;
  background:var(--w);z-index:1000;
  transform:translateX(-100%);
  transition:transform .3s cubic-bezier(.4,0,.2,1);
  box-shadow:4px 0 32px rgba(0,0,0,.15);
  overflow-y:auto;display:flex;flex-direction:column;
}
.nl-drawer.open{transform:translateX(0)}
.nl-overlay{
  position:fixed;inset:0;
  background:rgba(0,0,0,.45);z-index:999;
  opacity:0;pointer-events:none;
  transition:opacity .3s;
  backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);
}
.nl-overlay.open{opacity:1;pointer-events:all}
.nl-drawer-head{
  display:flex;justify-content:space-between;align-items:center;
  padding:1.1rem 1.25rem 1rem;
  border-bottom:1px solid var(--b);background:var(--g1);flex-shrink:0;
}
.nl-drawer-head img{height:54px;width:auto;max-width:155px;object-fit:contain;object-position:left center;}
.nl-drawer-close{
  background:rgba(26,59,34,.07);border:1.5px solid var(--b2);border-radius:8px;
  width:36px;height:36px;font-size:1rem;color:var(--t3);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:all .15s;flex-shrink:0;
}
.nl-drawer-close:hover{background:rgba(232,83,74,.1);border-color:var(--coral);color:var(--coral);}
.nl-drawer-links{padding:.5rem 0;flex:1;}
.nl-drawer-links a{
  display:flex;align-items:center;gap:.65rem;
  font-size:.92rem;font-weight:600;color:var(--t2);
  padding:.82rem 1.35rem;border-bottom:1px solid var(--g2);
  letter-spacing:.02em;transition:color .15s, background .15s;
}
.nl-drawer-links a:hover{color:var(--green);background:rgba(26,59,34,.04);}
.nl-drawer-links a:last-child{border-bottom:none;}
.nl-drawer-btns{
  padding:1rem 1.25rem 1.5rem;display:flex;flex-direction:column;gap:.6rem;
  border-top:1px solid var(--b);background:var(--g1);flex-shrink:0;
}
.nl-drawer-btns a{
  text-align:center;padding:.78rem;font-size:.8rem;font-weight:700;
  letter-spacing:.07em;text-transform:uppercase;border-radius:10px;transition:all .15s;
}
.nl-drawer-btns .d-login{border:1.5px solid var(--b2);color:var(--t2);background:var(--w);}
.nl-drawer-btns .d-login:hover{border-color:var(--green);color:var(--green);}
.nl-drawer-btns .d-join{background:var(--green);color:#fff;box-shadow:0 2px 8px rgba(26,59,34,.25);}
.nl-drawer-btns .d-join:hover{background:var(--green-m);}

/* DASHBOARD NAVBAR */
.nav-dashboard{
  background:linear-gradient(135deg,#0b1d0f 0%,var(--green-dd) 55%,#152a1a 100%);
  border-bottom:none;padding:0 2rem;
  display:flex;align-items:center;justify-content:space-between;
  height:var(--navbar-h);
  position:sticky;top:0;z-index:200;
  will-change:transform;
  box-shadow:0 1px 0 rgba(200,146,42,.2), 0 6px 24px rgba(0,0,0,.5);
}
.nav-dashboard::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg, transparent 0%, var(--gold-d) 15%, var(--gold-ll) 50%, var(--gold-d) 85%, transparent 100%);
  pointer-events:none;
}

/* Dashboard Brand */
.nd-brand{display:flex;align-items:center;gap:14px;text-decoration:none;flex-shrink:0}
.nd-brand-logo{
  height:62px;
  width:auto;
  max-width:178px;
  display:block;
  object-fit:contain;
  object-position:left center;
  background:rgba(255,255,255,.97);
  border-radius:10px;
  padding:5px 14px;
  box-shadow:0 2px 10px rgba(0,0,0,.35), 0 0 0 1px rgba(200,146,42,.22);
  transition:opacity .2s, transform .2s;
}
.nd-brand-logo:hover{opacity:.9;transform:scale(1.03)}
.nd-brand-divider{width:1px;height:32px;background:rgba(200,146,42,.35);flex-shrink:0}
.nd-brand-text{display:flex;flex-direction:column;gap:0;line-height:1}
.nd-brand-name{font-family:'Cinzel','DM Serif Display',serif;font-size:1.18rem;font-weight:900;color:var(--g1);letter-spacing:.04em}
.nd-brand-sub{font-family:'Outfit',sans-serif;font-size:.6rem;font-weight:600;color:var(--muted);letter-spacing:.12em;text-transform:uppercase;margin-top:3px}

/* Dashboard Nav Links */
.nd-nav{display:flex;align-items:center;gap:.12rem}
.nd-nav .nd-link{
  color:rgba(255,255,255,.55);padding:.48rem .95rem;border-radius:5px;
  font-size:.72rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;
  transition:all .22s;white-space:nowrap;position:relative;text-decoration:none;
}
.nd-nav .nd-link::after{
  content:'';position:absolute;bottom:3px;left:.95rem;right:.95rem;height:1.5px;
  background:var(--gold-l);border-radius:2px;
  transform:scaleX(0);transform-origin:center;
  transition:transform .22s cubic-bezier(.4,0,.2,1);
}
.nd-nav .nd-link:hover{background:rgba(200,146,42,.1);color:rgba(255,255,255,.9);}
.nd-nav .nd-link:hover::after{transform:scaleX(1);}
.nd-nav .nd-link.active{color:var(--gold-ll);background:rgba(200,146,42,.15);}
.nd-nav .nd-link.active::after{transform:scaleX(1);}

/* Dashboard Cart */
.nd-cart-btn{
  position:relative;display:flex;align-items:center;justify-content:center;
  width:36px;height:36px;border-radius:50%;
  color:rgba(255,255,255,.6);text-decoration:none;
  background:rgba(200,146,42,.1);border:1px solid rgba(200,146,42,.2);
  transition:all .22s;margin-left:.25rem;flex-shrink:0;
}
.nd-cart-btn:hover{background:rgba(200,146,42,.22);color:#fff;transform:scale(1.08);}
.nd-cart-badge{
  position:absolute;top:-3px;right:-3px;
  min-width:17px;height:17px;border-radius:50%;
  background:var(--coral);color:#fff;font-size:.5rem;font-weight:800;
  display:none;align-items:center;justify-content:center;
  border:2px solid #0b1d0f;line-height:1;padding:0 2px;font-family:'Nunito',sans-serif;
}

/* Wallet & Logout */
.nd-wallet{
  background:linear-gradient(135deg,var(--gold-d) 0%,var(--gold) 45%,var(--gold-l) 100%);
  color:#fff;padding:.4rem 1.1rem;border-radius:20px;font-weight:700;font-size:.76rem;
  letter-spacing:.04em;white-space:nowrap;text-decoration:none;
  transition:all .25s;margin-left:.5rem;
  border:1px solid rgba(255,255,255,.12);
  box-shadow:0 2px 10px rgba(200,146,42,.35), inset 0 1px 0 rgba(255,255,255,.15);
  position:relative;overflow:hidden;
}
.nd-wallet::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.18) 0%,transparent 55%);pointer-events:none;}
.nd-wallet:hover{color:#fff;box-shadow:0 4px 18px rgba(200,146,42,.5);transform:translateY(-1px);}
.nd-logout{
  background:transparent;border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.45);
  padding:.4rem .85rem;border-radius:5px;font-size:.7rem;font-weight:600;letter-spacing:.04em;
  cursor:pointer;text-decoration:none;transition:all .22s;margin-left:.35rem;
}
.nd-logout:hover{background:rgba(232,83,74,.18);border-color:rgba(232,83,74,.4);color:rgba(255,255,255,.9);}

/* Dashboard Hamburger */
.nd-hamburger{
  display:none;
  flex-direction:column;justify-content:center;align-items:center;
  gap:0;width:46px;height:46px;
  background:linear-gradient(135deg,rgba(200,146,42,.15),rgba(200,146,42,.07));
  border:1.5px solid rgba(200,146,42,.4);border-radius:12px;
  cursor:pointer;padding:0;flex-shrink:0;position:relative;overflow:hidden;
  transition:background .22s, border-color .22s, box-shadow .22s, transform .18s;
}
.nd-hamburger::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.08) 0%,transparent 55%);
  pointer-events:none;border-radius:inherit;
}
.nd-hamburger:hover{
  background:linear-gradient(135deg,rgba(200,146,42,.3),rgba(200,146,42,.14));
  border-color:var(--gold-l);
  box-shadow:0 0 0 3px rgba(200,146,42,.2), 0 4px 14px rgba(200,146,42,.25);
  transform:scale(1.05);
}
.nd-hamburger:active{transform:scale(.96);}
.nd-hamburger span{
  display:block;border-radius:4px;
  transition:transform .32s cubic-bezier(.68,-.55,.27,1.55), opacity .22s ease, width .25s ease, background .2s;
  transform-origin:center;
}
.nd-hamburger span:nth-child(1){width:22px;height:2.5px;background:linear-gradient(90deg,var(--gold-ll),var(--gold-l));margin-bottom:5px;box-shadow:0 1px 4px rgba(200,146,42,.45);}
.nd-hamburger span:nth-child(2){width:17px;height:3.5px;background:linear-gradient(90deg,var(--gold),var(--gold-l));margin-bottom:5px;box-shadow:0 1px 3px rgba(200,146,42,.3);}
.nd-hamburger span:nth-child(3){width:12px;height:2.5px;background:linear-gradient(90deg,var(--gold-d),var(--gold));box-shadow:0 1px 3px rgba(200,146,42,.2);}
.nd-hamburger.open{background:linear-gradient(135deg,rgba(232,83,74,.2),rgba(232,83,74,.08));border-color:rgba(232,83,74,.5);}
.nd-hamburger.open span:nth-child(1){transform:translateY(9px) rotate(45deg);width:20px;background:var(--coral);box-shadow:0 0 8px rgba(232,83,74,.45);}
.nd-hamburger.open span:nth-child(2){opacity:0;transform:scaleX(0);}
.nd-hamburger.open span:nth-child(3){transform:translateY(-9px) rotate(-45deg);width:20px;background:var(--coral);box-shadow:0 0 8px rgba(232,83,74,.45);}

/* Dashboard Mobile Drawer */
.nd-drawer{
  display:none;
  position:fixed;top:var(--navbar-h);left:0;right:0;bottom:0;
  background:var(--green-dd);
  border-top:2px solid var(--gold);z-index:190;
  overflow-y:auto;-webkit-overflow-scrolling:touch;
  transform:translateY(-10px);opacity:0;pointer-events:none;
  transition:transform .3s cubic-bezier(.4,0,.2,1), opacity .3s ease;
  box-shadow:0 8px 32px rgba(0,0,0,.45);
}
.nd-drawer.open{transform:translateY(0);opacity:1;pointer-events:all;}
.nd-drawer-inner{padding:1rem 1.25rem 2rem;display:flex;flex-direction:column;gap:.35rem}
.nd-drawer-brand{
  display:flex;align-items:center;gap:12px;
  padding:.5rem 1rem .85rem;
  border-bottom:1px solid rgba(200,146,42,.18);margin-bottom:.25rem;
}
.nd-drawer-brand img{
  height:52px;width:auto;max-width:148px;
  background:rgba(255,255,255,.96);border-radius:8px;
  padding:4px 11px;
  box-shadow:0 1px 6px rgba(0,0,0,.25), 0 0 0 1px rgba(200,146,42,.2);
}
.nd-drawer-brand span{font-family:'Cinzel',serif;font-size:1.05rem;font-weight:900;color:var(--g1);letter-spacing:.04em}
.nd-drawer .nd-wallet{
  display:flex;align-items:center;justify-content:space-between;
  padding:.78rem 1rem;border-radius:10px;font-size:.92rem;margin-bottom:.15rem;
  background:linear-gradient(135deg,var(--gold-d) 0%,var(--gold) 45%,var(--gold-l) 100%);
}
.nd-drawer .nd-link{
  font-size:.92rem;padding:.8rem 1rem;border-radius:10px;
  display:flex;align-items:center;gap:.7rem;
  color:rgba(255,255,255,.75);font-weight:600;transition:all .2s;border:1px solid transparent;
  text-decoration:none;
}
.nd-drawer .nd-link:hover,
.nd-drawer .nd-link.active{background:rgba(200,146,42,.18);color:var(--gold-l);border-color:rgba(200,146,42,.2);}
.nd-drawer-divider{height:1px;background:rgba(200,146,42,.15);margin:.5rem 0}
.nd-drawer .nd-logout-mobile{
  display:flex;align-items:center;justify-content:center;gap:.5rem;
  background:transparent;border:1.5px solid rgba(200,146,42,.3);
  color:rgba(255,255,255,.7);padding:.8rem 1rem;border-radius:10px;
  font-size:.92rem;font-weight:600;margin-top:.35rem;cursor:pointer;
  text-decoration:none;transition:all .2s;
}
.nd-drawer .nd-logout-mobile:hover{background:var(--coral);border-color:var(--coral);color:#fff}
.nd-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:180;
  backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);display:none;
}
.nd-overlay.open{display:block}

/* MSHOP MEGA DROPDOWN */
.nl-mshop-wrap{position:relative;display:inline-flex;align-items:center;}
.nl-mshop-trigger{
  font-size:.78rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;
  color:var(--t3);padding:.5rem .88rem;border-radius:6px;
  transition:color .15s, background .15s;white-space:nowrap;
  display:flex;align-items:center;gap:.3rem;cursor:pointer;position:relative;
}
.nl-mshop-trigger::after{
  content:'';position:absolute;bottom:3px;left:50%;right:50%;height:2px;
  background:var(--green);border-radius:2px;
  transition:left .22s cubic-bezier(.4,0,.2,1), right .22s cubic-bezier(.4,0,.2,1);
}
.nl-mshop-trigger:hover,
.nl-mshop-wrap.open .nl-mshop-trigger{color:var(--green);background:rgba(26,59,34,.04);}
.nl-mshop-trigger:hover::after,
.nl-mshop-wrap.open .nl-mshop-trigger::after{left:.88rem;right:.88rem;}

.nl-mega{
  position:fixed;top:var(--navbar-h);left:0;right:0;
  background:#fff;border-top:1px solid var(--b);border-bottom:2px solid var(--green);
  box-shadow:0 16px 48px rgba(26,59,34,.12);z-index:890;
  opacity:0;pointer-events:none;transform:translateY(-8px);
  transition:opacity .22s ease, transform .22s ease;
}
.nl-mshop-wrap.open .nl-mega{opacity:1;pointer-events:all;transform:translateY(0);}
.nl-mega-inner{max-width:1440px;margin:0 auto;padding:2rem 1.75rem 1.5rem;}
.nl-mega-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:1rem;margin-bottom:1.25rem;}
.nl-mega-card{display:block;text-decoration:none;color:inherit;transition:transform .2s;cursor:pointer;}
.nl-mega-card:hover{transform:translateY(-3px);}
.nl-mega-img{aspect-ratio:1/1;background:var(--g1);overflow:hidden;margin-bottom:.5rem;display:flex;align-items:center;justify-content:center;}
.nl-mega-img img{width:100%;height:100%;object-fit:cover;object-position:center;display:block;transition:transform .4s ease;}
.nl-mega-card:hover .nl-mega-img img{transform:scale(1.05);}
.nl-mega-placeholder{font-size:2.5rem;opacity:.3;}
.nl-mega-cat{font-size:.54rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--green-m);margin-bottom:.18rem;}
.nl-mega-name{font-size:.72rem;font-weight:500;color:var(--t);line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.2rem;}
.nl-mega-price{font-size:.78rem;font-weight:700;color:var(--green);}
.nl-mega-footer{display:flex;align-items:center;justify-content:space-between;padding-top:1rem;border-top:1px solid var(--b);}
.nl-mega-viewall{font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--t);border-bottom:1.5px solid var(--t);padding-bottom:1px;transition:color .15s, border-color .15s;}
.nl-mega-viewall:hover{color:var(--green);border-color:var(--green);}
.nl-mega-login{font-size:.68rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;background:var(--green);color:#fff;padding:.42rem 1.1rem;transition:background .15s;display:inline-flex;align-items:center;gap:.4rem;border:none;cursor:pointer;}
.nl-mega-login:hover{background:var(--green-m);color:#fff;}
@media(max-width:1200px){.nl-mega-grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:768px){.nl-mega-grid{grid-template-columns:repeat(2,1fr)}}

/* NAVBAR SEARCH */
.nl-search-wrap{position:relative;display:flex;align-items:center;flex-shrink:0;margin-left:.5rem;}
.nl-search-btn{background:transparent;border:none;cursor:pointer;color:var(--t3);width:36px;height:36px;display:flex;align-items:center;justify-content:center;transition:color .18s, transform .2s;border-radius:50%;flex-shrink:0;}
.nl-search-btn:hover{color:var(--green);transform:scale(1.1);}
.nl-search-box{
  position:absolute;right:0;top:50%;
  transform:translateY(-50%) scaleX(0.85) translateX(8px);
  transform-origin:right center;width:300px;background:#fff;
  border:1.5px solid var(--green-l);border-radius:24px;
  box-shadow:0 8px 28px rgba(26,59,34,.14), 0 2px 8px rgba(0,0,0,.06);
  opacity:0;pointer-events:none;
  transition:transform .25s cubic-bezier(.34,1.2,.64,1), opacity .2s ease;
  z-index:910;overflow:hidden;
}
.nl-search-wrap.open .nl-search-box{opacity:1;pointer-events:all;transform:translateY(-50%) scaleX(1) translateX(0);}
.nl-search-form{display:flex;align-items:center;gap:.5rem;padding:.55rem .65rem .55rem .9rem;}
.nl-search-form svg{color:var(--green-l);flex-shrink:0;}
.nl-search-input{flex:1;border:none;outline:none;font-family:'Outfit',sans-serif;font-size:.85rem;color:var(--t);background:transparent;letter-spacing:.01em;}
.nl-search-input::placeholder{color:var(--t4);}
.nl-search-clear{background:var(--g1);border:1px solid var(--b);cursor:pointer;color:var(--t3);font-size:.7rem;padding:0;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;line-height:1;transition:all .15s;flex-shrink:0;}
.nl-search-clear:hover{background:var(--coral);border-color:var(--coral);color:#fff;}

/* NAVBAR RIGHT ICON ACTIONS */
.nl-actions{display:flex;align-items:center;gap:.2rem;flex-shrink:0;}
.nl-icon-btn{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:transparent;border:none;cursor:pointer;color:var(--t3);text-decoration:none;transition:color .18s, background .18s, transform .2s;position:relative;flex-shrink:0;}
.nl-icon-btn:hover{color:var(--green);background:rgba(26,59,34,.06);transform:scale(1.1);}
.nl-icon-btn svg{display:block;}
.nl-cart-badge{position:absolute;top:3px;right:2px;min-width:16px;height:16px;border-radius:50%;background:var(--coral);color:#fff;font-size:.5rem;font-weight:800;display:none;align-items:center;justify-content:center;border:2px solid #fff;line-height:1;padding:0 2px;font-family:'Nunito',sans-serif;}
.nl-actions-sep{width:1px;height:20px;background:var(--b);margin:0 .35rem;flex-shrink:0;}
.nl-register-btn{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--t2);padding:.42rem .9rem;border-radius:20px;border:1.5px solid var(--b2);background:transparent;text-decoration:none;transition:all .2s;white-space:nowrap;flex-shrink:0;}
.nl-register-btn:hover{border-color:var(--green-l);color:var(--green);background:rgba(26,59,34,.04);}
.nl-login-btn{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--t3);padding:.42rem .75rem;text-decoration:none;transition:color .18s;white-space:nowrap;flex-shrink:0;}
.nl-login-btn:hover{color:var(--green-d);}
.nl-wa-btn{display:inline-flex;align-items:center;gap:.38rem;background:#25D366;color:#fff;padding:.42rem 1rem;border-radius:20px;font-size:.63rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;text-decoration:none;transition:background .18s, transform .2s, box-shadow .2s;white-space:nowrap;flex-shrink:0;box-shadow:0 2px 8px rgba(37,211,102,.3);}
.nl-wa-btn:hover{background:#1ebe5d;color:#fff;transform:translateY(-1px);box-shadow:0 4px 14px rgba(37,211,102,.4);}
.nl-wa-btn svg{flex-shrink:0;}

/* Mobile WhatsApp icon */
.nl-wa-mobile{
  display:none;
  align-items:center;justify-content:center;
  width:38px;height:38px;border-radius:50%;
  background:#25D366;color:#fff;text-decoration:none;flex-shrink:0;
  transition:background .18s, transform .2s;
  box-shadow:0 2px 8px rgba(37,211,102,.35);
}
.nl-wa-mobile:hover{background:#1ebe5d;transform:scale(1.08);}

/* PROFILE AVATAR DROPDOWN */
.nl-profile-wrap{position:relative;display:flex;align-items:center;flex-shrink:0;}
.nl-avatar-btn{background:var(--green);border:2px solid rgba(255,255,255,.2);border-radius:50%;width:36px;height:36px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s, box-shadow .2s, border-color .2s;box-shadow:0 2px 8px rgba(26,59,34,.2);flex-shrink:0;padding:0;}
.nl-avatar-btn:hover{transform:scale(1.06);border-color:var(--green-m);box-shadow:0 4px 14px rgba(26,59,34,.25);}
.nl-avatar-initials{font-family:'Cinzel',serif;font-size:.8rem;font-weight:700;color:#fff;line-height:1;letter-spacing:.05em;}
.nl-profile-drop{position:absolute;top:calc(100% + 10px);right:0;width:230px;background:#fff;border:1.5px solid var(--b);border-radius:12px;box-shadow:0 8px 32px rgba(26,59,34,.14), 0 2px 8px rgba(0,0,0,.06);z-index:920;opacity:0;pointer-events:none;transform:translateY(-6px) scale(.97);transform-origin:top right;transition:opacity .18s ease, transform .18s ease;overflow:hidden;}
.nl-profile-wrap.open .nl-profile-drop{opacity:1;pointer-events:all;transform:translateY(0) scale(1);}
.nl-profile-drop::before{content:'';position:absolute;top:-6px;right:12px;width:12px;height:12px;background:#fff;border-left:1.5px solid var(--b);border-top:1.5px solid var(--b);transform:rotate(45deg);}
.nl-profile-drop-head{display:flex;align-items:center;gap:.75rem;padding:1rem 1rem .75rem;border-bottom:1px solid var(--g2);background:var(--g1);}
.nl-profile-drop-avatar{width:36px;height:36px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:.82rem;font-weight:700;color:#fff;flex-shrink:0;}
.nl-profile-drop-name{font-size:.82rem;font-weight:700;color:var(--t);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.nl-profile-drop-id{font-size:.65rem;color:var(--t3);margin-top:2px;font-family:'Outfit',monospace;letter-spacing:.04em;}
.nl-profile-drop-links{padding:.4rem 0;}
.nl-profile-drop-item{display:flex;align-items:center;gap:.6rem;padding:.62rem 1rem;font-size:.8rem;font-weight:500;color:var(--t2);text-decoration:none;transition:background .15s, color .15s;white-space:nowrap;}
.nl-profile-drop-item svg{color:var(--t3);flex-shrink:0;transition:color .15s;}
.nl-profile-drop-item:hover{background:var(--g1);color:var(--green);}
.nl-profile-drop-item:hover svg{color:var(--green);}
.nl-profile-drop-divider{height:1px;background:var(--g2);margin:.3rem 0;}
.nl-profile-drop-logout{color:var(--coral)!important;}
.nl-profile-drop-logout svg{color:var(--coral)!important;}
.nl-profile-drop-logout:hover{background:rgba(232,83,74,.06)!important;}

/* GLOBAL LOGIN MODAL */
.glm-backdrop{position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.52);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;pointer-events:none;transition:opacity .22s;}
.glm-backdrop.open{opacity:1;pointer-events:all;}
.glm-box{background:#fff;border-radius:18px;width:100%;max-width:400px;padding:2rem 1.75rem 1.75rem;box-shadow:0 24px 64px rgba(0,0,0,.28);transform:translateY(14px) scale(.96);transition:transform .26s cubic-bezier(.34,1.2,.64,1);position:relative;}
.glm-backdrop.open .glm-box{transform:translateY(0) scale(1);}
.glm-close{position:absolute;top:.85rem;right:.85rem;background:none;border:none;font-size:1.1rem;color:var(--t3);cursor:pointer;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:background .15s;}
.glm-close:hover{background:var(--g1);}
.glm-icon{font-size:2.4rem;text-align:center;margin-bottom:.65rem;}
.glm-title{font-family:'Cinzel',serif;font-size:1.15rem;color:var(--green);text-align:center;margin-bottom:.35rem;}
.glm-sub{font-size:.78rem;color:var(--t3);text-align:center;line-height:1.65;margin-bottom:1.2rem;}
.glm-prod{display:flex;align-items:center;gap:.75rem;background:var(--g1);border-radius:10px;padding:.6rem .85rem;margin-bottom:1.1rem;border:1px solid var(--b);}
.glm-prod-img{width:42px;height:42px;border-radius:8px;object-fit:cover;background:var(--g2);flex-shrink:0;}
.glm-prod-name{font-size:.8rem;font-weight:600;color:var(--t);line-height:1.3;}
.glm-prod-price{font-size:.74rem;color:var(--green);font-weight:700;margin-top:2px;}
.glm-btns{display:flex;flex-direction:column;gap:.5rem;}
.glm-login{display:block;text-align:center;background:var(--green);color:#fff;padding:.72rem;border-radius:9px;font-size:.82rem;font-weight:700;letter-spacing:.04em;text-decoration:none;transition:background .18s;}
.glm-login:hover{background:var(--green-m);color:#fff;}
.glm-register{display:block;text-align:center;background:transparent;border:1.5px solid var(--b2);color:var(--t2);padding:.68rem;border-radius:9px;font-size:.78rem;font-weight:600;text-decoration:none;transition:all .18s;}
.glm-register:hover{border-color:var(--green);color:var(--green);}
.glm-divider{display:flex;align-items:center;gap:.5rem;font-size:.62rem;color:var(--t4);margin:.1rem 0;}
.glm-divider::before,.glm-divider::after{content:'';flex:1;height:1px;background:var(--b);}

/* SHARED COMPONENTS */
.container{max-width:1200px;margin:0 auto;padding:0 1.5rem}
.page-wrap{padding:2rem 0 4rem}
.card{background:#fff;border-radius:16px;border:1.5px solid var(--b);box-shadow:0 2px 12px rgba(26,59,34,.06);overflow:hidden}
.card-header{background:var(--g2);color:var(--green-d);padding:1rem 1.5rem;font-family:'Cinzel','DM Serif Display',serif;font-size:1rem;font-weight:700;display:flex;align-items:center;justify-content:space-between;border-bottom:1.5px solid var(--b)}
.card-body{padding:1.5rem}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem;margin-bottom:2rem}
.stat-tile{background:#fff;border-radius:16px;border:1.5px solid var(--b);box-shadow:0 2px 12px rgba(26,59,34,.06);padding:1.25rem 1.5rem;border-top:3px solid var(--green);display:flex;flex-direction:column;gap:.25rem}
.stat-tile .label{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--muted)}
.stat-tile .value{font-family:'Cinzel',serif;font-size:1.8rem;color:var(--green);font-weight:700}
.stat-tile .sub{font-size:.78rem;color:var(--muted)}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.25rem;border-radius:8px;border:none;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .2s;text-decoration:none}
.btn-primary{background:var(--green);color:#fff}.btn-primary:hover{background:var(--green-m);color:#fff}
.btn-gold{background:var(--gold);color:#fff}.btn-gold:hover{background:var(--gold-l);color:#fff}
.btn-outline{background:transparent;border:2px solid var(--green);color:var(--green)}.btn-outline:hover{background:var(--green);color:#fff}
.btn-sm{padding:.35rem .85rem;font-size:.8rem}
.btn-danger{background:var(--danger);color:#fff}
.btn-jade{background:var(--jade);color:#fff}.btn-jade:hover{background:var(--jade-l);color:#fff}
.btn-dark{display:inline-flex;align-items:center;gap:.4rem;background:var(--t);color:#fff;font-size:.68rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:.82rem 2rem;transition:background .2s}
.btn-dark:hover{background:#2a2a22}
.btn-bare{font-size:.68rem;font-weight:500;letter-spacing:.07em;text-transform:uppercase;color:var(--t2);border-bottom:1px solid var(--b2);padding-bottom:2px;transition:color .15s, border-color .15s}
.btn-bare:hover{color:var(--green);border-color:var(--green)}
.btn-gld{background:var(--gold);color:var(--t);font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:.82rem 2.25rem;border:1px solid var(--gold);transition:background .15s;display:inline-flex;align-items:center;gap:.35rem}
.btn-gld:hover{background:var(--gold-l)}
.btn-ghost{color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.2);font-size:.68rem;font-weight:500;letter-spacing:.08em;text-transform:uppercase;padding:.82rem 1.75rem;transition:all .15s;display:inline-flex;align-items:center}
.btn-ghost:hover{border-color:rgba(255,255,255,.5);color:#fff}
.form-group{margin-bottom:1.1rem}
.form-label{display:block;font-weight:700;font-size:.8rem;margin-bottom:.4rem;color:var(--t);text-transform:uppercase;letter-spacing:.05em}
.form-control{width:100%;padding:.65rem .9rem;border:1.5px solid var(--b);border-radius:8px;font-size:.95rem;background:var(--g1);color:var(--t);transition:border-color .2s;outline:none}
.form-control:focus{border-color:var(--green-l);box-shadow:0 0 0 3px rgba(26,59,34,.09);background:#fff}
.form-hint{font-size:.78rem;color:var(--muted);margin-top:.3rem}
.alert{padding:.85rem 1.1rem;border-radius:10px;font-size:.9rem;margin-bottom:1.25rem;font-weight:600}
.alert-success{background:rgba(15,123,92,.1);color:var(--jade-d);border-left:4px solid var(--jade)}
.alert-danger{background:rgba(232,83,74,.1);color:#9A3412;border-left:4px solid var(--coral)}
.alert-warning{background:rgba(184,128,24,.1);color:#92400E;border-left:4px solid var(--gold)}
.alert-info{background:rgba(26,59,34,.07);color:var(--green-d);border-left:4px solid var(--green-l)}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{background:var(--g2);padding:.75rem 1rem;text-align:left;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);border-bottom:1.5px solid var(--b)}
tbody td{padding:.8rem 1rem;border-bottom:1px solid var(--g2);font-size:.9rem;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:var(--g2)}
.badge{display:inline-flex;align-items:center;padding:.22rem .65rem;border-radius:20px;font-size:.72rem;font-weight:800;letter-spacing:.03em}
.badge-gold{background:rgba(184,128,24,.15);color:var(--gold-d)}
.badge-green{background:rgba(26,59,34,.1);color:var(--green-m)}
.badge-jade{background:rgba(15,123,92,.12);color:var(--jade-d)}
.badge-blue{background:rgba(26,59,34,.08);color:var(--green-l)}
.badge-indigo,.badge-maroon{background:rgba(26,59,34,.1);color:var(--green-m)}
.badge-teal{background:rgba(15,123,92,.12);color:var(--jade-d)}
.tree ul{list-style:none;padding-left:2rem;border-left:2px solid var(--green-l);margin-left:.75rem}
.tree>ul{border:none;padding:0;margin:0}
.tree li{position:relative;padding:.4rem 0}
.tree li::before{content:'';position:absolute;top:1rem;left:-2rem;width:1.75rem;height:2px;background:var(--green-l)}
.tree>ul>li::before{display:none}
.tree-node{display:inline-flex;align-items:center;gap:.6rem;background:#fff;border:1.5px solid var(--b);border-radius:8px;padding:.5rem .9rem;box-shadow:0 2px 8px rgba(26,59,34,.05);transition:border-color .2s}
.tree-node:hover{border-color:var(--gold)}
.tree-node .avatar{width:32px;height:32px;border-radius:50%;background:var(--green);color:rgba(255,255,255,.9);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}
.tree-node .uname{font-weight:600;font-size:.9rem;color:var(--t)}
.tree-node .ulevel{font-size:.75rem;color:var(--muted)}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:1.5rem}
.product-card{background:#fff;border-radius:16px;border:1.5px solid var(--b);box-shadow:0 2px 12px rgba(26,59,34,.06);overflow:hidden;transition:transform .2s, box-shadow .2s}
.product-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(26,59,34,.14)}
.product-card img{width:100%;height:180px;object-fit:cover}
.product-card .p-body{padding:1.1rem}
.product-card .p-name{font-family:'Cinzel',serif;font-size:.95rem;margin-bottom:.35rem;color:var(--t)}
.product-card .p-price{font-size:1.2rem;font-weight:700;color:var(--green)}
.product-card .p-desc{font-size:.8rem;color:var(--muted);margin-bottom:.85rem}
.page-header{background:linear-gradient(135deg,var(--green-dd) 0%,var(--green) 55%,var(--green-m) 100%);color:#fff;padding:2.5rem 0 2rem;margin-bottom:2.5rem;border-bottom:3px solid var(--gold);position:relative;overflow:hidden}
.page-header::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(200,146,42,.1) 1.5px,transparent 1.5px);background-size:24px 24px;pointer-events:none}
.page-header h1{font-size:2rem;font-weight:900;font-family:'Cinzel',serif}
.page-header p{opacity:.65;margin-top:.3rem}
.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--green-dd) 0%,var(--green) 55%,var(--gold-d) 100%);padding:2rem}
.auth-box{background:#fff;border-radius:16px;width:100%;max-width:440px;padding:2.5rem;box-shadow:0 20px 60px rgba(0,0,0,.28)}
.auth-logo{text-align:center;margin-bottom:1.75rem}
.auth-logo img{height:64px;width:auto;margin:0 auto .75rem}
.auth-logo h1{font-family:'Cinzel',serif;font-size:2rem;color:var(--green)}
.auth-logo p{color:var(--muted);font-size:.875rem}
.ref-box{display:flex;align-items:center;gap:.5rem;background:var(--g2);border-radius:8px;padding:.6rem .9rem;border:1.5px dashed var(--gold)}
.ref-box input{flex:1;border:none;background:none;font-size:.875rem;color:var(--t);outline:none}
.copy-btn{background:var(--green);border:none;border-radius:6px;padding:.4rem .8rem;font-size:.8rem;font-weight:700;cursor:pointer;color:#fff;transition:background .2s}
.copy-btn:hover{background:var(--green-m)}
.section-title{font-size:1.2rem;margin-bottom:1.25rem;color:var(--green);display:flex;align-items:center;gap:.5rem;font-family:'Cinzel',serif;font-weight:700}
.section-title::after{content:'';flex:1;height:1.5px;background:var(--b)}
.slabel{font-size:.58rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);display:block;margin-bottom:.5rem}
.sh{font-family:'DM Serif Display',serif;font-size:clamp(1.55rem,2.5vw,2.2rem);font-weight:400;color:var(--t);line-height:1.12;letter-spacing:-.015em}
.sh em{font-style:italic;color:var(--green)}
.text-muted{color:var(--muted)}.text-gold{color:var(--gold-d)}.text-green{color:var(--green)}.text-jade{color:var(--jade)}.text-indigo{color:var(--green)}.text-maroon{color:var(--green)}.text-center{text-align:center}
.mt-1{margin-top:.5rem}.mt-2{margin-top:1rem}.mt-3{margin-top:1.5rem}
.mb-1{margin-bottom:.5rem}.mb-2{margin-bottom:1rem}.mb-3{margin-bottom:1.5rem}
.d-flex{display:flex}.align-center{align-items:center}.gap-1{gap:.5rem}.gap-2{gap:1rem}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
.rv{opacity:0;transform:translateY(16px);transition:opacity .6s ease, transform .6s ease}
.rv.on{opacity:1;transform:none}
.stt{position:fixed;bottom:1.25rem;right:1.25rem;z-index:600;width:36px;height:36px;background:var(--t);color:#fff;border:none;font-size:.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;opacity:0;transform:translateY(8px);transition:opacity .3s, transform .3s;pointer-events:none}
.stt.v{opacity:1;transform:none;pointer-events:all}
.stt:hover{background:#2a2a22}

/* ══════════════════════════════════════
   MOBILE LOGO SIZE FIX
══════════════════════════════════════ */
/* ══════════════════════════════════════
   MOBILE LOGO SIZE FIX - EXTRA EXTRA LARGE
══════════════════════════════════════ */
@media(max-width:768px){
  :root { --navbar-h: 92px; }
  
  /* Guest/Landing navbar logo - LARGER */
  .nav-landing .nl-brand-logo {
    height: 100px !important;
    max-width: 260px !important;
  }
  
  /* Dashboard navbar logo - LARGER */
  .nd-brand-logo {
    height: 92px !important;
    padding: 10px 20px !important;
    max-width: 240px !important;
  }
  
  /* Mobile auth buttons adjustment */
  .nl-mobile-auth a { 
    font-size: .72rem; 
    padding: .48rem .88rem; 
  }
  
  /* Hide desktop elements */
  .nav-landing .nl-links   { display:none !important }
  .nav-landing .nl-right   { display:none !important }
  .nl-actions              { display:none !important }
  .nl-search-wrap          { display:none !important }
  .nl-profile-wrap         { display:none !important }
  .nl-mega                 { display:none !important }
  .nl-mshop-trigger        { border-bottom:none }

  /* Guest: show mobile elements */
  .nl-mobile-auth          { display:flex !important }
  .nav-landing .nl-burger  { display:flex !important }
  .nl-wa-mobile            { display:flex !important }

  /* Dashboard: hide desktop nav */
  .nd-nav      { display:none !important }
  .nd-wallet   { display:none !important }
  .nd-logout   { display:none !important }

  /* Dashboard: show mobile elements */
  .nd-hamburger { display:flex !important }
  .nd-drawer    { display:block !important }
  
  /* Dashboard mobile nav adjustments */
  .nav-dashboard{padding:0 1rem}
  .nd-brand-divider { display:none }
  .nd-brand-text    { display:none }
}

@media(max-width:640px){
  .nav-landing-inner { padding:0 1rem }
  .nav-landing .nl-brand-logo {
    height: 95px !important;
    max-width: 250px !important;
  }
  
  .nd-brand-logo {
    height: 88px !important;
    max-width: 230px !important;
    padding: 8px 18px !important;
  }
  
  .nl-mobile-auth a { 
    font-size: .68rem; 
    padding: .44rem .82rem; 
  }
  
  .nl-wa-mobile { 
    width: 48px; 
    height: 48px; 
  }
  
  .nav-landing .nl-burger { 
    width: 52px; 
    height: 52px; 
  }
}

@media(max-width:480px){
  :root { --navbar-h: 88px; }
  
  .nav-landing .nl-brand-logo {
    height: 90px !important;
    max-width: 235px !important;
  }
  
  .nd-brand-logo {
    height: 84px !important;
    max-width: 220px !important;
    padding: 7px 16px !important;
  }
  
  .nl-mobile-auth a { 
    font-size: .65rem !important; 
    padding: .40rem .78rem !important; 
  }
  
  .nl-wa-mobile { 
    width: 46px; 
    height: 46px; 
  }
  
  .nav-landing .nl-burger { 
    width: 50px; 
    height: 50px; 
  }
  
  .stats-grid{grid-template-columns:1fr 1fr;gap:.75rem}
  .stat-tile{padding:1rem}
  .stat-tile .value{font-size:1.5rem}
  .product-grid{grid-template-columns:1fr 1fr;gap:.75rem}
  .product-card img{height:130px}
  .page-header h1{font-size:1.35rem}
  .btn{padding:.5rem 1rem;font-size:.85rem}
  .container{padding:0 1rem}
  .page-wrap{padding:1.25rem 0 3rem}
  .grid-2{grid-template-columns:1fr;gap:1rem}
  .card-header{padding:.85rem 1.1rem;font-size:.95rem}
  .card-body{padding:1.1rem}
  .auth-box{padding:1.75rem 1.25rem}
  thead th, tbody td{padding:.65rem .75rem}
}

@media(max-width:360px){
  :root { --navbar-h: 84px; }
  
  .nav-landing .nl-brand-logo {
    height: 85px !important;
    max-width: 220px !important;
  }
  
  .nd-brand-logo {
    height: 80px !important;
    max-width: 210px !important;
    padding: 6px 14px !important;
  }
  
  .nl-mobile-auth a { 
    font-size: .62rem !important; 
    padding: .36rem .72rem !important; 
  }
  
  .nl-mobile-auth { gap:.3rem }
  
  .nl-wa-mobile { 
    width: 44px; 
    height: 44px; 
  }
  
  .nav-landing .nl-burger { 
    width: 48px; 
    height: 48px; 
  }
  
  .stats-grid{grid-template-columns:1fr}
  .product-grid{grid-template-columns:1fr}
}

/* ══════════════════════════════════════
   MOBILE LOGO SIZE - OPTIMIZED
══════════════════════════════════════ */
@media(max-width:768px){
  :root { --navbar-h: 78px; }
  
  /* Guest/Landing navbar logo - OPTIMIZED */
  .nav-landing .nl-brand-logo {
    height: 58px !important;
    max-width: 170px !important;
  }
  
  /* Dashboard navbar logo - OPTIMIZED */
  .nd-brand-logo {
    height: 52px !important;
    padding: 4px 12px !important;
    max-width: 150px !important;
  }
  
  /* Mobile auth buttons - balanced */
  .nl-mobile-auth a { 
    font-size: .62rem; 
    padding: .38rem .72rem; 
  }
  
  /* Hide desktop elements */
  .nav-landing .nl-links   { display:none !important }
  .nav-landing .nl-right   { display:none !important }
  .nl-actions              { display:none !important }
  .nl-search-wrap          { display:none !important }
  .nl-profile-wrap         { display:none !important }
  .nl-mega                 { display:none !important }
  .nl-mshop-trigger        { border-bottom:none }

  /* Guest: show mobile elements */
  .nl-mobile-auth          { display:flex !important }
  .nav-landing .nl-burger  { display:flex !important }
  .nl-wa-mobile            { display:flex !important }

  /* Dashboard: hide desktop nav */
  .nd-nav      { display:none !important }
  .nd-wallet   { display:none !important }
  .nd-logout   { display:none !important }

  /* Dashboard: show mobile elements */
  .nd-hamburger { display:flex !important }
  .nd-drawer    { display:block !important }
  
  /* Dashboard mobile nav adjustments */
  .nav-dashboard{padding:0 1rem}
  .nd-brand-divider { display:none }
  .nd-brand-text    { display:none }
}

@media(max-width:640px){
  .nav-landing-inner { padding:0 1rem }
  .nav-landing .nl-brand-logo {
    height: 54px !important;
    max-width: 160px !important;
  }
  
  .nd-brand-logo {
    height: 48px !important;
    max-width: 140px !important;
    padding: 3px 10px !important;
  }
  
  .nl-mobile-auth a { 
    font-size: .58rem; 
    padding: .34rem .65rem; 
  }
  
  .nl-wa-mobile { 
    width: 38px; 
    height: 38px; 
  }
  
  .nav-landing .nl-burger { 
    width: 42px; 
    height: 42px; 
  }
}

@media(max-width:480px){
  :root { --navbar-h: 72px; }
  
  .nav-landing .nl-brand-logo {
    height: 50px !important;
    max-width: 150px !important;
  }
  
  .nd-brand-logo {
    height: 44px !important;
    max-width: 130px !important;
    padding: 2px 8px !important;
  }
  
  .nl-mobile-auth a { 
    font-size: .54rem !important; 
    padding: .28rem .58rem !important; 
  }
  
  .nl-wa-mobile { 
    width: 36px; 
    height: 36px; 
  }
  
  .nav-landing .nl-burger { 
    width: 40px; 
    height: 40px; 
  }
  
  .stats-grid{grid-template-columns:1fr 1fr;gap:.75rem}
  .stat-tile{padding:1rem}
  .stat-tile .value{font-size:1.5rem}
  .product-grid{grid-template-columns:1fr 1fr;gap:.75rem}
  .product-card img{height:130px}
  .page-header h1{font-size:1.35rem}
  .btn{padding:.5rem 1rem;font-size:.85rem}
  .container{padding:0 1rem}
  .page-wrap{padding:1.25rem 0 3rem}
  .grid-2{grid-template-columns:1fr;gap:1rem}
  .card-header{padding:.85rem 1.1rem;font-size:.95rem}
  .card-body{padding:1.1rem}
  .auth-box{padding:1.75rem 1.25rem}
  thead th, tbody td{padding:.65rem .75rem}
}

@media(max-width:360px){
  :root { --navbar-h: 68px; }
  
  .nav-landing .nl-brand-logo {
    height: 46px !important;
    max-width: 138px !important;
  }
  
  .nd-brand-logo {
    height: 42px !important;
    max-width: 125px !important;
    padding: 2px 6px !important;
  }
  
  .nl-mobile-auth a { 
    font-size: .5rem !important; 
    padding: .24rem .48rem !important; 
  }
  
  .nl-mobile-auth { gap:.2rem }
  
  .nl-wa-mobile { 
    width: 34px; 
    height: 34px; 
  }
  
  .nav-landing .nl-burger { 
    width: 38px; 
    height: 38px; 
  }
  
  .stats-grid{grid-template-columns:1fr}
  .product-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<?php if ($loggedIn): ?>
<!-- DASHBOARD NAVBAR (Logged In) -->
<div class="nd-overlay" id="ndOverlay" onclick="ndCloseDrawer()"></div>

<nav class="nav-dashboard">
  <a href="<?= APP_URL ?>/dashboard.php" class="nd-brand">
    <img src="<?= APP_URL ?>/includes/images/logo2.png" alt="Mfills" class="nd-brand-logo" onerror="this.style.display='none'">
    <div class="nd-brand-divider"></div>
    <div class="nd-brand-text">
      <span class="nd-brand-name">MFILLS</span>
      <span class="nd-brand-sub">Business Network</span>
    </div>
  </a>

  <div class="nd-nav">
    <a href="<?= APP_URL ?>/dashboard"  class="nd-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'  ? 'active':'' ?>">Dashboard</a>
    <a href="<?= APP_URL ?>/shop"        class="nd-link <?= basename($_SERVER['PHP_SELF'])=='shop.php'        ? 'active':'' ?>">MShop</a>
    <a href="<?= APP_URL ?>/mshop_plus"  class="nd-link <?= basename($_SERVER['PHP_SELF'])=='mshop_plus.php'  ? 'active':'' ?>">MShop Plus</a>
    <a href="<?= APP_URL ?>/network"     class="nd-link <?= basename($_SERVER['PHP_SELF'])=='network.php'     ? 'active':'' ?>">Network</a>
    <a href="<?= APP_URL ?>/commissions" class="nd-link <?= basename($_SERVER['PHP_SELF'])=='commissions.php' ? 'active':'' ?>">Commissions</a>
    <a href="<?= APP_URL ?>/cart.php" class="nd-cart-btn" id="ndCartBtn" title="Your Cart">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      <span class="nd-cart-badge" id="ndCartBadge">0</span>
    </a>
    <a class="nd-wallet" href="<?= APP_URL ?>/wallet.php">₹ <?= number_format($user['wallet'], 2) ?></a>
    <a href="<?= APP_URL ?>/logout" class="nd-logout">Logout</a>
  </div>

  <button class="nd-hamburger" id="ndHamburger" onclick="ndToggleDrawer()" aria-label="Menu" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
</nav>

<div class="nd-drawer" id="ndDrawer" aria-hidden="true">
  <div class="nd-drawer-inner">
    <div class="nd-drawer-brand">
      <img src="<?= APP_URL ?>/includes/images/logo2.png" alt="Mfills" onerror="this.style.display='none'">
      <span>MFILLS</span>
    </div>
    <a class="nd-wallet" href="<?= APP_URL ?>/wallet.php" onclick="ndCloseDrawer()">
      <span>💰 Wallet Balance</span>
      <span>₹ <?= number_format($user['wallet'], 2) ?></span>
    </a>
    <div class="nd-drawer-divider"></div>
    <a href="<?= APP_URL ?>/dashboard"  class="nd-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'  ? 'active':'' ?>" onclick="ndCloseDrawer()">📊 Dashboard</a>
    <a href="<?= APP_URL ?>/shop"        class="nd-link <?= basename($_SERVER['PHP_SELF'])=='shop.php'        ? 'active':'' ?>" onclick="ndCloseDrawer()">🛍️ MShop</a>
    <a href="<?= APP_URL ?>/mshop_plus"  class="nd-link <?= basename($_SERVER['PHP_SELF'])=='mshop_plus.php'  ? 'active':'' ?>" onclick="ndCloseDrawer()">⭐ MShop Plus</a>
    <a href="<?= APP_URL ?>/network"     class="nd-link <?= basename($_SERVER['PHP_SELF'])=='network.php'     ? 'active':'' ?>" onclick="ndCloseDrawer()">🌐 Network</a>
    <a href="<?= APP_URL ?>/commissions" class="nd-link <?= basename($_SERVER['PHP_SELF'])=='commissions.php' ? 'active':'' ?>" onclick="ndCloseDrawer()">💸 Commissions</a>
    <a href="<?= APP_URL ?>/cart.php"    class="nd-link" onclick="ndCloseDrawer()">🛒 Cart</a>
    <div class="nd-drawer-divider"></div>
    <a href="<?= APP_URL ?>/logout" class="nd-logout-mobile">🚪 Logout</a>
  </div>
</div>

<script>
function ndToggleDrawer() {
  var d = document.getElementById('ndDrawer'), o = document.getElementById('ndOverlay'), h = document.getElementById('ndHamburger');
  if (d.classList.contains('open')) { ndCloseDrawer(); }
  else {
    d.classList.add('open');
    o.classList.add('open');
    h.classList.add('open');
    h.setAttribute('aria-expanded', 'true');
    d.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
}
function ndCloseDrawer() {
  var d = document.getElementById('ndDrawer'), o = document.getElementById('ndOverlay'), h = document.getElementById('ndHamburger');
  d.classList.remove('open');
  o.classList.remove('open');
  h.classList.remove('open');
  h.setAttribute('aria-expanded', 'false');
  d.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') ndCloseDrawer(); });
window.addEventListener('resize', function() { if (window.innerWidth > 900) ndCloseDrawer(); }, { passive: true });
function toggleDrawer() { ndToggleDrawer(); }
function closeDrawer()  { ndCloseDrawer(); }

(function() {
  var nav = document.querySelector('.nav-dashboard');
  if (nav) {
    window.addEventListener('scroll', function() { nav.classList.toggle('scrolled', window.scrollY > 10); }, { passive: true });
  }
})();

(function() {
  var KEYS = ['mfills_cart_auth', 'mfills_gc'];
  function getCount() {
    var n = 0;
    KEYS.forEach(function(k) {
      try {
        var c = JSON.parse(localStorage.getItem(k) || '{}');
        Object.keys(c).forEach(function(p) { n += (parseInt(c[p].qty) || 1); });
      } catch(e) {}
    });
    return n;
  }
  function update() {
    var badge = document.getElementById('ndCartBadge');
    if (badge) {
      var n = getCount();
      if (n > 0) { badge.textContent = n > 99 ? '99+' : n; badge.style.display = 'flex'; }
      else { badge.style.display = 'none'; }
    }
  }
  update();
  window.addEventListener('storage', update);
  setInterval(update, 2000);
})();
</script>

<?php else: ?>
<!-- GUEST / LANDING NAVBAR -->
<div class="nl-overlay" id="nlOverlay" onclick="nlCloseDrawer()"></div>

<nav class="nav-landing" id="nav">
  <div class="nav-landing-inner">
    <a href="<?= APP_URL ?>/" class="nl-brand">
      <img src="<?= APP_URL ?>/includes/images/logo2.png" alt="Mfills" class="nl-brand-logo" onerror="this.style.display='none'">
      <span class="nl-brand-name">Mfills®</span>
    </a>

    <div class="nl-links">
      <a href="<?= APP_URL ?>/">Home</a>
      <a href="<?= APP_URL ?>/about.php">About</a>
      <div class="nl-mshop-wrap" id="nlMshopWrap">
        <a href="<?= APP_URL ?>/shop.php" class="nl-mshop-trigger" id="nlMshopTrigger">MShop <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></a>
        <div class="nl-mega" id="nlMega">
          <div class="nl-mega-inner">
            <div class="nl-mega-grid" id="nlMegaGrid">
              <?php
              if (!function_exists('getProducts')) require_once __DIR__ . '/../includes/functions.php';
              $megaProds = function_exists('getProducts') ? array_slice(getProducts('mshop'), 0, 8) : [];
              if (!empty($megaProds)):
                foreach ($megaProds as $mp):
                  $mcat = strtolower($mp['name']);
                  if      (str_contains($mcat,'protein')||str_contains($mcat,'whey')||str_contains($mcat,'mass'))   $mc='Protein';
                  elseif  (str_contains($mcat,'vitamin')||str_contains($mcat,'omega')||str_contains($mcat,'fish')||str_contains($mcat,'multi')) $mc='Vitamins';
                  elseif  (str_contains($mcat,'ashwa')||str_contains($mcat,'triphala')||str_contains($mcat,'shatavari')||str_contains($mcat,'herbal')) $mc='Ayurvedic';
                  elseif  (str_contains($mcat,'cla')||str_contains($mcat,'garcinia')||str_contains($mcat,'slim')||str_contains($mcat,'weight')||str_contains($mcat,'detox')) $mc='Weight';
                  else $mc='Wellness';
              ?>
              <div class="nl-mega-card" onclick="glmOpen('<?= addslashes($mp['name']) ?>','<?= number_format($mp['price'],0) ?>','<?= htmlspecialchars($mp['image_url']??'') ?>')">
                <div class="nl-mega-img">
                  <?php if (!empty($mp['image_url'])): ?>
                    <img src="<?= htmlspecialchars($mp['image_url']) ?>" alt="<?= htmlspecialchars($mp['name']) ?>" loading="lazy">
                  <?php else: ?>
                    <div class="nl-mega-placeholder">💊</div>
                  <?php endif; ?>
                </div>
                <div class="nl-mega-cat"><?= $mc ?></div>
                <div class="nl-mega-name"><?= htmlspecialchars($mp['name']) ?></div>
                <div class="nl-mega-price">₹<?= number_format($mp['price'],0) ?></div>
              </div>
              <?php endforeach; else: ?>
              <?php foreach ([
                ['Protein',   'Whey Protein Concentrate','1,299','https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=300&q=60'],
                ['Vitamins',  'Vitamin D3 + K2',          '599', 'https://images.unsplash.com/photo-1550572017-edd951b55104?w=300&q=60'],
                ['Ayurvedic', 'Ashwagandha KSM-66',       '549', 'https://images.unsplash.com/photo-1611073615830-9b2be67a4e72?w=300&q=60'],
                ['Weight',    'CLA 1000mg',                '999', 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=300&q=60'],
              ] as $sf): ?>
              <div class="nl-mega-card" onclick="glmOpen('<?= $sf[1] ?>','<?= $sf[2] ?>','<?= $sf[3] ?>')">
                <div class="nl-mega-img"><img src="<?= $sf[3] ?>" alt="<?= $sf[1] ?>" loading="lazy"></div>
                <div class="nl-mega-cat"><?= $sf[0] ?></div>
                <div class="nl-mega-name"><?= $sf[1] ?></div>
                <div class="nl-mega-price">₹<?= $sf[2] ?></div>
              </div>
              <?php endforeach; endif; ?>
            </div>
            <div class="nl-mega-footer">
              <a href="<?= APP_URL ?>/shop.php" class="nl-mega-viewall">View All Products →</a>
              <button onclick="glmOpen('','','')" class="nl-mega-login">🛒 Buy Now — Login Required</button>
            </div>
          </div>
        </div>
      </div>
      <a href="<?= APP_URL ?>/blog.php">Blog</a>
      <a href="<?= APP_URL ?>/grievance.php">Grievance</a>
      <a href="<?= APP_URL ?>/contact.php">Contact</a>
    </div>

    <div class="nl-search-wrap" id="nlSearchWrap">
      <button class="nl-icon-btn nl-search-btn" id="nlSearchBtn" onclick="nlToggleSearch()" aria-label="Search products">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
      <div class="nl-search-box" id="nlSearchBox">
        <form action="<?= APP_URL ?>/shop.php" method="GET" class="nl-search-form">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" name="q" id="nlSearchInput" class="nl-search-input" placeholder="Search products…" autocomplete="off">
          <button type="button" class="nl-search-clear" id="nlSearchClear" onclick="nlCloseSearch()">✕</button>
        </form>
      </div>
    </div>

    <div class="nl-actions">
      <a href="#" class="nl-icon-btn" id="nlCartBtn" title="Cart — Login to shop" onclick="glmOpen('','','');return false;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <span class="nl-cart-badge" id="nlCartBadge">0</span>
      </a>
      <div class="nl-actions-sep"></div>
      <a href="<?= APP_URL ?>/register.php" class="nl-register-btn">📝 Register</a>
      <a href="<?= APP_URL ?>/login.php"    class="nl-login-btn">🔑 Login</a>
      <div class="nl-actions-sep"></div>
      <a href="https://wa.me/918877966666" target="_blank" rel="noopener" class="nl-wa-btn" title="WhatsApp Support">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.52 3.48A11.93 11.93 0 0 0 12 0C5.37 0 0 5.37 0 12c0 2.11.55 4.16 1.6 5.97L0 24l6.18-1.58A11.94 11.94 0 0 0 12 24c6.63 0 12-5.37 12-12 0-3.2-1.25-6.22-3.48-8.52zM12 22c-1.85 0-3.66-.5-5.24-1.44l-.37-.22-3.87.99 1.02-3.77-.24-.38A9.96 9.96 0 0 1 2 12C2 6.48 6.48 2 12 2c2.66 0 5.16 1.04 7.04 2.92A9.94 9.94 0 0 1 22 12c0 5.52-4.48 10-10 10zm5.47-7.41c-.3-.15-1.77-.87-2.04-.97-.28-.1-.47-.15-.68.15s-.78.97-.96 1.17c-.17.2-.35.22-.65.07a8.15 8.15 0 0 1-2.4-1.48 9.03 9.03 0 0 1-1.66-2.07c-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.68-1.63-.93-2.23-.24-.58-.49-.5-.68-.51h-.58c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.07 2.86 1.22 3.06c.15.2 2.1 3.2 5.09 4.49.71.3 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35z"/></svg>
        WhatsApp
      </a>
    </div>

    <div class="nl-mobile-auth" id="nlMobileAuth">
      <a href="<?= APP_URL ?>/login.php" class="mob-login">🔑 Login</a>
      <a href="<?= APP_URL ?>/register.php" class="mob-reg">📝 Join</a>
    </div>

    <a href="https://wa.me/918877777889" target="_blank" rel="noopener" class="nl-wa-mobile" title="WhatsApp Support" aria-label="WhatsApp Support">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.52 3.48A11.93 11.93 0 0 0 12 0C5.37 0 0 5.37 0 12c0 2.11.55 4.16 1.6 5.97L0 24l6.18-1.58A11.94 11.94 0 0 0 12 24c6.63 0 12-5.37 12-12 0-3.2-1.25-6.22-3.48-8.52zM12 22c-1.85 0-3.66-.5-5.24-1.44l-.37-.22-3.87.99 1.02-3.77-.24-.38A9.96 9.96 0 0 1 2 12C2 6.48 6.48 2 12 2c2.66 0 5.16 1.04 7.04 2.92A9.94 9.94 0 0 1 22 12c0 5.52-4.48 10-10 10zm5.47-7.41c-.3-.15-1.77-.87-2.04-.97-.28-.1-.47-.15-.68.15s-.78.97-.96 1.17c-.17.2-.35.22-.65.07a8.15 8.15 0 0 1-2.4-1.48 9.03 9.03 0 0 1-1.66-2.07c-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.68-1.63-.93-2.23-.24-.58-.49-.5-.68-.51h-.58c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.07 2.86 1.22 3.06c.15.2 2.1 3.2 5.09 4.49.71.3 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35z"/></svg>
    </a>

    <button class="nl-burger" id="nlBurger" onclick="nlOpenDrawer()" aria-label="Open menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<div class="nl-drawer" id="nlDrawer" role="dialog" aria-modal="true" aria-label="Navigation menu">
  <div class="nl-drawer-head">
    <img src="<?= APP_URL ?>/includes/images/logo2.png" alt="Mfills" onerror="this.style.display='none'">
    <button class="nl-drawer-close" onclick="nlCloseDrawer()" aria-label="Close menu">✕</button>
  </div>

  <div class="nl-drawer-links">
    <a href="<?= APP_URL ?>/"              onclick="nlCloseDrawer()">🏠 Home</a>
    <a href="<?= APP_URL ?>/about.php"     onclick="nlCloseDrawer()">ℹ️ About</a>
    <a href="<?= APP_URL ?>/shop.php"      onclick="nlCloseDrawer()">🛍️ MShop</a>
    <a href="<?= APP_URL ?>/blog.php"      onclick="nlCloseDrawer()">📝 Blog</a>
    <a href="<?= APP_URL ?>/grievance.php" onclick="nlCloseDrawer()">📋 Grievance</a>
    <a href="<?= APP_URL ?>/contact.php"   onclick="nlCloseDrawer()">📞 Contact</a>
  </div>

  <div style="padding:.75rem 1.25rem;border-top:1px solid var(--b);border-bottom:1px solid var(--b);">
    <form action="<?= APP_URL ?>/shop.php" method="GET" style="display:flex;align-items:center;gap:.5rem;background:var(--g1);border:1.5px solid var(--b);border-radius:24px;padding:.5rem .75rem;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--t3)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" name="q" placeholder="Search products…" style="flex:1;border:none;background:none;outline:none;font-size:.88rem;color:var(--t);font-family:'Outfit',sans-serif;">
    </form>
  </div>

  <div class="nl-drawer-btns">
    <a href="<?= APP_URL ?>/register.php" class="d-login" onclick="nlCloseDrawer()">📝 Register Free</a>
    <a href="<?= APP_URL ?>/login.php"    class="d-join"  onclick="nlCloseDrawer()">🔑 Login</a>
    <a href="https://wa.me/918877777889" target="_blank" rel="noopener" onclick="nlCloseDrawer()" style="text-align:center;padding:.78rem;font-size:.8rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border-radius:10px;background:#25D366;color:#fff;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:.45rem;box-shadow:0 2px 8px rgba(37,211,102,.3);">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20.52 3.48A11.93 11.93 0 0 0 12 0C5.37 0 0 5.37 0 12c0 2.11.55 4.16 1.6 5.97L0 24l6.18-1.58A11.94 11.94 0 0 0 12 24c6.63 0 12-5.37 12-12 0-3.2-1.25-6.22-3.48-8.52zM12 22c-1.85 0-3.66-.5-5.24-1.44l-.37-.22-3.87.99 1.02-3.77-.24-.38A9.96 9.96 0 0 1 2 12C2 6.48 6.48 2 12 2c2.66 0 5.16 1.04 7.04 2.92A9.94 9.94 0 0 1 22 12c0 5.52-4.48 10-10 10zm5.47-7.41c-.3-.15-1.77-.87-2.04-.97-.28-.1-.47-.15-.68.15s-.78.97-.96 1.17c-.17.2-.35.22-.65.07a8.15 8.15 0 0 1-2.4-1.48 9.03 9.03 0 0 1-1.66-2.07c-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.68-1.63-.93-2.23-.24-.58-.49-.5-.68-.51h-.58c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.07 2.86 1.22 3.06c.15.2 2.1 3.2 5.09 4.49.71.3 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35z"/></svg>
      WhatsApp Support
    </a>
  </div>
</div>

<div class="glm-backdrop" id="glmBackdrop" onclick="if(event.target===this)glmClose()">
  <div class="glm-box">
    <button class="glm-close" onclick="glmClose()">✕</button>
    <div class="glm-icon">🔐</div>
    <div class="glm-title">Login to Purchase</div>
    <div class="glm-sub">Browse freely — login or register free to buy and earn PSB on every order.</div>
    <div class="glm-prod" id="glmProd" style="display:none">
      <img class="glm-prod-img" id="glmImg" src="" alt="">
      <div>
        <div class="glm-prod-name"  id="glmName"></div>
        <div class="glm-prod-price" id="glmPrice"></div>
      </div>
    </div>
    <div class="glm-btns">
      <a href="<?= APP_URL ?>/login.php" class="glm-login" id="glmLoginBtn">Login to Buy</a>
      <div class="glm-divider">or</div>
      <a href="<?= APP_URL ?>/register.php" class="glm-register">Register Free — Earn PSB</a>
    </div>
  </div>
</div>

<script>
function nlOpenDrawer() {
  var d = document.getElementById('nlDrawer'), o = document.getElementById('nlOverlay'), b = document.getElementById('nlBurger');
  d.classList.add('open');
  o.classList.add('open');
  b.setAttribute('aria-expanded', 'true');
  document.body.style.overflow = 'hidden';
}
function nlCloseDrawer() {
  var d = document.getElementById('nlDrawer'), o = document.getElementById('nlOverlay'), b = document.getElementById('nlBurger');
  d.classList.remove('open');
  o.classList.remove('open');
  b.setAttribute('aria-expanded', 'false');
  document.body.style.overflow = '';
}
function openDrawer()  { nlOpenDrawer(); }
function closeDrawer() { nlCloseDrawer(); }

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { nlCloseDrawer(); glmClose(); nlCloseSearch(); } });
window.addEventListener('resize', function() { if (window.innerWidth > 900) nlCloseDrawer(); }, { passive: true });

(function() {
  var nav = document.getElementById('nav');
  if (nav) {
    window.addEventListener('scroll', function() { nav.classList.toggle('scrolled', window.scrollY > 10); }, { passive: true });
  }
})();

(function() {
  var wrap = document.getElementById('nlMshopWrap'), mega = document.getElementById('nlMega');
  if (!wrap || !mega) return;
  var t;
  function openMega()  { clearTimeout(t); wrap.classList.add('open'); }
  function closeMega() { t = setTimeout(function() { wrap.classList.remove('open'); }, 120); }
  wrap.addEventListener('mouseenter', openMega);
  wrap.addEventListener('mouseleave', closeMega);
  mega.addEventListener('mouseenter', function() { clearTimeout(t); });
  mega.addEventListener('mouseleave', closeMega);
  var trigger = wrap.querySelector('.nl-mshop-trigger');
  if (trigger) {
    trigger.addEventListener('click', function(e) { if (window.innerWidth <= 900) return; if (!wrap.classList.contains('open')) { e.preventDefault(); openMega(); } });
  }
  document.addEventListener('click', function(e) { if (!wrap.contains(e.target)) wrap.classList.remove('open'); });
})();

function nlToggleSearch() {
  var w = document.getElementById('nlSearchWrap'), i = document.getElementById('nlSearchInput');
  if (w.classList.toggle('open')) setTimeout(function() { if (i) i.focus(); }, 220);
}
function nlCloseSearch() {
  var w = document.getElementById('nlSearchWrap'), i = document.getElementById('nlSearchInput');
  if (w) w.classList.remove('open');
  if (i) i.value = '';
}
document.addEventListener('click', function(e) {
  var w = document.getElementById('nlSearchWrap');
  if (w && !w.contains(e.target)) w.classList.remove('open');
});
var si = document.getElementById('nlSearchInput');
if (si) si.addEventListener('keydown', function(e) { if (e.key === 'Enter') this.closest('form').submit(); });

(function() {
  var KEYS = ['mfills_cart_auth', 'mfills_gc'];
  function getCount() {
    var n = 0;
    KEYS.forEach(function(k) {
      try {
        var c = JSON.parse(localStorage.getItem(k) || '{}');
        Object.keys(c).forEach(function(p) { n += (parseInt(c[p].qty) || 1); });
      } catch(e) {}
    });
    return n;
  }
  function update() {
    var badge = document.getElementById('nlCartBadge');
    if (badge) {
      var n = getCount();
      if (n > 0) { badge.textContent = n > 99 ? '99+' : n; badge.style.display = 'flex'; }
      else { badge.style.display = 'none'; }
    }
  }
  update();
  window.addEventListener('storage', update);
  setInterval(update, 2000);
})();

function glmOpen(name, price, img) {
  var bd = document.getElementById('glmBackdrop'), pr = document.getElementById('glmProd'), nm = document.getElementById('glmName'), px = document.getElementById('glmPrice'), im = document.getElementById('glmImg'), lb = document.getElementById('glmLoginBtn');
  if (name) {
    nm.textContent = name;
    px.textContent = price ? '₹' + price : '';
    if (img) { im.src = img; im.style.display = 'block'; }
    else { im.style.display = 'none'; }
    pr.style.display = 'flex';
  } else { pr.style.display = 'none'; }
  lb.href = '<?= APP_URL ?>/login.php' + (name ? '?redirect=shop&product=' + encodeURIComponent(name) : '');
  bd.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function glmClose() {
  document.getElementById('glmBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}

function nlToggleProfile() {
  var w = document.getElementById('nlProfileWrap');
  if (w) w.classList.toggle('open');
}
document.addEventListener('click', function(e) {
  var w = document.getElementById('nlProfileWrap');
  if (w && !w.contains(e.target)) w.classList.remove('open');
});
</script>

<?php endif; ?>

<?php if ($flash): ?>
<div class="container" style="padding-top:1rem">
  <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
</div>
<?php endif; ?>