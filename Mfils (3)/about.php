<?php
// about.php — Mfills About Us Page
require_once __DIR__ . '/includes/functions.php';
startSession();
$pageTitle = 'About Us — Mfills';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ══════════════════════════════════════
   ABOUT US PAGE — FULL REWRITE
══════════════════════════════════════ */

/* ══════════════════════════════════════
   ABOUT PAGE — ANIMATION ADDITIONS
══════════════════════════════════════ */

/* Watermark drift in */
@keyframes aboutWmDrift {
  from { opacity: 0; transform: translate(-50%,-50%) scale(1.06); }
  to   { opacity: 1; transform: translate(-50%,-50%) scale(1); }
}

/* Sidenav slide down */
@keyframes aboutNavSlide {
  from { opacity: 0; transform: translateY(-10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Quote bar sweep */
@keyframes aboutQuoteSweep {
  from { transform: scaleX(0); transform-origin: left; }
  to   { transform: scaleX(1); transform-origin: left; }
}

/* Founder avatar pop */
@keyframes aboutAvatarPop {
  0%   { transform: scale(.82); opacity: 0; }
  70%  { transform: scale(1.06); opacity: 1; }
  100% { transform: scale(1); }
}

/* Number counter flash */
@keyframes aboutValFlash {
  0%,100% { color: var(--gold-ll); }
  50%      { color: #fff; }
}

/* Commit item slide */
.about-commit-item {
  transition: transform 380ms cubic-bezier(.34,1.4,.64,1),
              box-shadow 380ms ease,
              background 200ms ease !important;
}
.about-commit-item:hover {
  transform: translateX(5px) !important;
  background: #fff !important;
  box-shadow: 0 4px 16px rgba(26,59,34,.08) !important;
}

/* Legal doc item hover */
.legal-doc-item {
  transition: background 180ms ease, padding-left 220ms cubic-bezier(.34,1.4,.64,1) !important;
}
.legal-doc-item:hover { padding-left: 1.65rem !important; }

/* Distribution chain arrow pulse */
.dist-arrow { transition: color 200ms ease; }
.dist-col:hover .dist-arrow { color: var(--green-m); }
.dist-col-new:hover .dist-arrow span { color: var(--jade); }

/* VMP cards — coloured top border on hover */
.vmp-card { transition: transform 380ms cubic-bezier(.34,1.4,.64,1), box-shadow 380ms ease, border-top-color 220ms ease !important; }
.vmp-card:hover { border-top-color: var(--gold) !important; }

/* Why-item number highlight on hover */
.why-item { transition: transform 360ms cubic-bezier(.34,1.4,.64,1), box-shadow 360ms ease, border-color 220ms ease !important; }
.why-item:hover .why-num { color: var(--green); }
.why-num { transition: color 220ms ease; }

/* Sidenav active indicator smooth widen */
.about-sidenav-wrap a {
  transition: color 200ms ease, border-bottom-color 220ms ease,
              padding-bottom 180ms ease !important;
}

/* ── Hero banner ── */
.about-hero {
  background: linear-gradient(135deg, var(--green-dd) 0%, var(--green-d) 50%, var(--green-m) 100%);
  position: relative; overflow: hidden;
  padding: 5.5rem 0 5rem;
  border-bottom: 3px solid var(--gold);
  text-align: center;
}
.about-hero::before {
  content: '';
  position: absolute; inset: 0; pointer-events: none;
  background-image: radial-gradient(circle, rgba(200,146,42,.08) 1.5px, transparent 1.5px);
  background-size: 24px 24px;
}
.about-hero-wm {
  position: absolute; top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  font-family: 'DM Serif Display', serif;
  font-size: clamp(8rem, 18vw, 22rem);
  font-weight: 400; color: rgba(255,255,255,.03);
  white-space: nowrap; pointer-events: none;
  letter-spacing: -.04em; line-height: 1;
}
.about-hero-inner {
  position: relative; z-index: 1;
  max-width: 760px; margin: 0 auto; padding: 0 1.5rem;
}
.about-hero-tag {
  display: inline-flex; align-items: center; gap: .6rem;
  font-size: .6rem; font-weight: 700; letter-spacing: .18em;
  text-transform: uppercase; color: var(--gold-ll);
  margin-bottom: 1.5rem;
}
.about-hero-tag::before, .about-hero-tag::after {
  content: ''; width: 22px; height: 1px; background: var(--gold-ll);
}
.about-hero h1 {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(2.2rem, 4.5vw, 3.8rem);
  font-weight: 400; color: #fff;
  line-height: 1.12; letter-spacing: -.025em;
  margin-bottom: 1rem;
}
.about-hero h1 em { font-style: italic; color: var(--gold-ll); }
.about-hero-tagline {
  font-size: 1.05rem; color: rgba(255,255,255,.6);
  font-weight: 300; letter-spacing: .04em;
  font-style: italic;
}

/* ── Sticky side-nav tabs ── */
.about-sidenav-wrap {
  display: flex; gap: 0;
  border-bottom: 1.5px solid var(--b);
  background: rgba(255,255,255,.98);
  backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
  position: sticky; top: var(--navbar-h); z-index: 90;
  overflow-x: auto; scrollbar-width: none;
  box-shadow: 0 2px 8px rgba(26,59,34,.06);
  /* Dynamic top is updated by JS when navbar shrinks */
}
.about-sidenav-wrap::-webkit-scrollbar { display: none; }
.about-sidenav-wrap a {
  flex-shrink: 0;
  font-size: .6rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--t3);
  padding: .85rem 1.25rem; border-bottom: 2px solid transparent;
  transition: color .18s, border-color .18s; white-space: nowrap;
}
.about-sidenav-wrap a:hover { color: var(--green); border-bottom-color: var(--green-l); }
.about-sidenav-wrap a.active { color: var(--green); border-bottom-color: var(--green); }

/* ── Page content ── */
.about-wrap {
  max-width: 960px; margin: 0 auto;
  padding: 4rem 1.5rem 5rem;
}

/* ── Section anchors (offset for sticky nav) ── */
.about-anchor {
  display: block;
  height: calc(var(--navbar-h) + 56px);
  margin-top: calc(-1 * (var(--navbar-h) + 56px));
  visibility: hidden; pointer-events: none;
}

/* ── Section blocks ── */
.about-section { margin-bottom: 4rem; }
.about-section:last-child { margin-bottom: 0; }

.about-label {
  font-size: .58rem; font-weight: 700; letter-spacing: .2em;
  text-transform: uppercase; color: var(--gold);
  display: flex; align-items: center; gap: .6rem;
  margin-bottom: .85rem;
}
.about-label::before {
  content: ''; width: 22px; height: 1.5px; background: var(--gold);
}

.about-h2 {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(1.7rem, 3vw, 2.5rem);
  font-weight: 400; color: var(--t);
  line-height: 1.15; letter-spacing: -.02em;
  margin-bottom: 1.35rem;
}
.about-h2 em { font-style: italic; color: var(--green); }

.about-body {
  font-size: .95rem; color: var(--t2);
  line-height: 1.9; font-weight: 300;
  max-width: 760px;
}
.about-body p { margin-bottom: 1.25rem; }
.about-body p:last-child { margin-bottom: 0; }
.about-body strong { color: var(--t); font-weight: 600; }
.about-body ul { padding-left: 1.25rem; margin-bottom: 1.25rem; }
.about-body ul li { margin-bottom: .45rem; }

/* ── Philosophy quote block ── */
.about-quote {
  background: linear-gradient(135deg, var(--g2), var(--g1));
  border-left: 4px solid var(--gold);
  border-radius: 0 14px 14px 0;
  padding: 1.75rem 2rem;
  margin: 2rem 0;
}
.about-quote-text {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(1.15rem, 2vw, 1.6rem);
  font-weight: 400; color: var(--green-d);
  font-style: italic; line-height: 1.4;
  letter-spacing: -.01em;
}
.about-quote-attr {
  font-size: .7rem; color: var(--t3);
  margin-top: .65rem; letter-spacing: .06em;
  text-transform: uppercase; font-weight: 500;
}

/* ── Vision–Mission–Philosophy 3-col ── */
.vmp-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  margin-top: 2rem;
}
.vmp-card {
  background: #fff;
  border: 1.5px solid var(--b);
  border-top: 3px solid var(--green);
  border-radius: 14px;
  padding: 1.6rem 1.4rem;
  transition: box-shadow .2s, transform .2s;
}
.vmp-card:hover {
  box-shadow: 0 10px 32px rgba(26,59,34,.1);
  transform: translateY(-3px);
}
.vmp-card-icon { font-size: 2rem; margin-bottom: .85rem; line-height: 1; }
.vmp-card-label {
  font-size: .58rem; font-weight: 700; letter-spacing: .15em;
  text-transform: uppercase; color: var(--gold);
  margin-bottom: .35rem;
}
.vmp-card-title {
  font-family: 'Cinzel', serif;
  font-size: .88rem; font-weight: 700; color: var(--green-d);
  letter-spacing: .03em; margin-bottom: .65rem;
}
.vmp-card-body {
  font-size: .78rem; color: var(--t3);
  line-height: 1.7; font-weight: 300;
}

/* ── Values grid ── */
.about-values {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  margin-top: 2rem;
}
.about-value-card {
  background: #fff;
  border: 1.5px solid var(--b);
  border-top: 3px solid var(--green);
  border-radius: 12px;
  padding: 1.4rem 1.25rem;
  transition: box-shadow .2s, transform .2s;
}
.about-value-card:hover {
  box-shadow: 0 8px 28px rgba(26,59,34,.1);
  transform: translateY(-3px);
}
.about-value-icon { font-size: 1.8rem; margin-bottom: .75rem; line-height: 1; }
.about-value-title {
  font-family: 'Cinzel', serif;
  font-size: .78rem; font-weight: 700; color: var(--green-d);
  letter-spacing: .04em; margin-bottom: .5rem;
}
.about-value-desc {
  font-size: .78rem; color: var(--t3);
  line-height: 1.65; font-weight: 300;
}

/* ── Divider ── */
.about-divider {
  height: 1px; background: var(--b);
  margin: 3.5rem 0;
}

/* ── Stats row ── */
.about-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  background: linear-gradient(135deg, var(--green-dd), var(--green-d));
  border-radius: 16px;
  padding: 2rem 1.5rem;
  margin: 2.5rem 0;
  border: 1.5px solid rgba(200,146,42,.2);
}
.about-stat {
  text-align: center;
  padding: .5rem;
  border-right: 1px solid rgba(200,146,42,.15);
}
.about-stat:last-child { border-right: none; }
.about-stat-val {
  font-family: 'Cinzel', serif;
  font-size: 1.8rem; font-weight: 900;
  color: var(--gold-ll);
  line-height: 1; margin-bottom: .35rem;
}
.about-stat-label {
  font-size: .62rem; color: rgba(255,255,255,.55);
  letter-spacing: .08em; text-transform: uppercase;
  font-weight: 500;
}

/* ── Founder card ── */
.founder-card {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 2.5rem;
  align-items: start;
  background: #fff;
  border: 1.5px solid var(--b);
  border-radius: 18px;
  padding: 2rem 2.25rem;
  margin-top: 2rem;
}
.founder-avatar-wrap {
  display: flex; flex-direction: column; align-items: center; gap: 1rem;
}
.founder-avatar {
  width: 140px; height: 140px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--green-dd), var(--green-m));
  display: flex; align-items: center; justify-content: center;
  font-family: 'Cinzel', serif; font-size: 2.4rem; font-weight: 900;
  color: var(--gold-ll);
  border: 3px solid rgba(200,146,42,.3);
  box-shadow: 0 8px 32px rgba(26,59,34,.25);
  flex-shrink: 0;
}
.founder-title-block { text-align: center; }
.founder-name {
  font-family: 'Cinzel', serif; font-size: .88rem; font-weight: 700;
  color: var(--green-d); letter-spacing: .04em; margin-bottom: .2rem;
}
.founder-role {
  font-size: .68rem; color: var(--t3);
  letter-spacing: .07em; text-transform: uppercase;
}
.founder-message-body {
  font-size: .92rem; color: var(--t2);
  line-height: 1.9; font-weight: 300;
}
.founder-message-body p { margin-bottom: 1.15rem; }
.founder-message-body p:last-child { margin-bottom: 0; }
.founder-message-body strong { color: var(--t); font-weight: 600; }
.founder-signature {
  margin-top: 1.5rem;
  font-family: 'DM Serif Display', serif;
  font-size: 1.4rem; font-style: italic;
  color: var(--green-d);
}
.founder-sig-sub {
  font-size: .68rem; color: var(--t3); margin-top: .15rem;
  letter-spacing: .06em; text-transform: uppercase;
}

/* ── Why Different list ── */
.why-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.15rem;
  margin-top: 2rem;
}
.why-item {
  display: flex; gap: 1rem; align-items: flex-start;
  background: #fff; border: 1.5px solid var(--b);
  border-radius: 12px; padding: 1.25rem 1.2rem;
  transition: box-shadow .2s, transform .2s;
}
.why-item:hover {
  box-shadow: 0 8px 24px rgba(26,59,34,.09);
  transform: translateY(-2px);
}
.why-num {
  font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 900;
  color: var(--gold); flex-shrink: 0; min-width: 28px;
  line-height: 1.2;
}
.why-content-title {
  font-size: .82rem; font-weight: 700; color: var(--green-d);
  margin-bottom: .35rem; font-family: 'Cinzel', serif;
}
.why-content-desc {
  font-size: .77rem; color: var(--t3);
  line-height: 1.65; font-weight: 300;
}

/* ── Distribution Compare ── */
.dist-compare {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 1.5rem;
  align-items: start;
  margin: 2rem 0;
}
.dist-col {
  background: #fff; border: 1.5px solid var(--b);
  border-radius: 14px; padding: 1.5rem 1.25rem;
}
.dist-col-new {
  border-color: var(--green-l);
  background: linear-gradient(135deg, #f0f8f1, #fff);
}
.dist-col-label {
  font-size: .62rem; font-weight: 700; letter-spacing: .14em;
  text-transform: uppercase; color: var(--t3);
  margin-bottom: 1.25rem; text-align: center;
}
.dist-col-label-new { color: var(--green-m); }
.dist-chain { display: flex; flex-direction: column; align-items: center; gap: 0; }
.dist-node {
  background: var(--g2); border: 1.5px solid var(--b);
  border-radius: 8px; padding: .5rem 1.1rem;
  font-size: .78rem; font-weight: 600; color: var(--t2);
  text-align: center; width: 100%; max-width: 200px;
}
.dist-node-mfills { background: rgba(26,59,34,.08); border-color: var(--green-l); color: var(--green-d); }
.dist-node-end { background: var(--green); color: #fff; border-color: var(--green); }
.dist-arrow {
  font-size: .75rem; color: var(--t3);
  padding: .2rem 0; text-align: center; line-height: 1.8;
}
.dist-arrow span { font-size: .58rem; color: var(--coral); display: block; letter-spacing: .04em; }
.dist-arrow-new span { color: var(--green-l); }
.dist-note {
  font-size: .72rem; color: var(--t3);
  margin-top: 1rem; line-height: 1.55; text-align: center; font-weight: 300;
}
.dist-note-new { color: var(--green-m); }
.dist-vs {
  font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700;
  color: var(--t3); align-self: center; text-align: center; padding: 0 .5rem;
}

/* ── MBP Roles ── */
.mbp-roles {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 1rem; margin-top: 1.75rem;
}
.mbp-role-item {
  display: flex; gap: 1rem; align-items: flex-start;
  background: #fff; border: 1.5px solid var(--b); border-radius: 12px;
  padding: 1.1rem 1.15rem; transition: box-shadow .2s, transform .2s;
}
.mbp-role-item:hover { box-shadow: 0 6px 20px rgba(26,59,34,.09); transform: translateY(-2px); }
.mbp-role-icon { font-size: 1.5rem; flex-shrink: 0; line-height: 1; margin-top: .1rem; }
.mbp-role-title { font-size: .82rem; font-weight: 700; color: var(--green-d); margin-bottom: .3rem; font-family: 'Cinzel', serif; }
.mbp-role-desc { font-size: .75rem; color: var(--t3); line-height: 1.6; font-weight: 300; }

/* ── Commitment Grid ── */
.about-commit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; }
.about-commit-item {
  display: flex; gap: .85rem; align-items: flex-start;
  background: var(--g1); border: 1.5px solid var(--b);
  border-radius: 10px; padding: 1rem 1.1rem;
}
.about-commit-icon { font-size: 1.4rem; flex-shrink: 0; line-height: 1; }
.about-commit-text { font-size: .82rem; color: var(--t2); line-height: 1.6; font-weight: 300; }
.about-commit-text strong { color: var(--t); font-weight: 600; }

/* ── Legal & Compliance ── */
.legal-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.15rem;
  margin-top: 2rem;
}
.legal-card {
  background: #fff; border: 1.5px solid var(--b);
  border-radius: 12px; padding: 1.4rem 1.25rem;
  transition: box-shadow .2s, transform .2s;
}
.legal-card:hover {
  box-shadow: 0 6px 22px rgba(26,59,34,.09);
  transform: translateY(-2px);
}
.legal-card-icon { font-size: 1.6rem; margin-bottom: .75rem; line-height: 1; }
.legal-card-title {
  font-family: 'Cinzel', serif;
  font-size: .76rem; font-weight: 700; color: var(--green-d);
  letter-spacing: .04em; margin-bottom: .5rem;
}
.legal-card-desc { font-size: .76rem; color: var(--t3); line-height: 1.65; font-weight: 300; }

.legal-doc-list { margin-top: 2rem; }
.legal-doc-item {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--g2);
  transition: background .15s;
}
.legal-doc-item:first-child { border-radius: 12px 12px 0 0; }
.legal-doc-item:last-child { border-bottom: none; border-radius: 0 0 12px 12px; }
.legal-doc-item:hover { background: var(--g1); }
.legal-doc-wrap {
  background: #fff; border: 1.5px solid var(--b); border-radius: 12px; overflow: hidden;
}
.legal-doc-name { font-size: .85rem; font-weight: 600; color: var(--t); }
.legal-doc-desc { font-size: .72rem; color: var(--t3); margin-top: .15rem; }
.legal-doc-badge {
  font-size: .58rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; padding: .25rem .65rem; border-radius: 20px;
  white-space: nowrap; flex-shrink: 0;
}
.badge-active { background: rgba(15,123,92,.1); color: var(--jade-d); }
.badge-registered { background: rgba(184,128,24,.12); color: var(--gold-d); }
.badge-compliant { background: rgba(26,59,34,.09); color: var(--green-m); }

/* ── CTA band ── */
.about-cta {
  background: var(--g1);
  border: 1.5px solid var(--b);
  border-radius: 16px;
  padding: 2.75rem 2rem;
  text-align: center;
  margin-top: 3.5rem;
}
.about-cta h3 {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(1.4rem, 2.5vw, 2rem);
  color: var(--t); margin-bottom: .6rem;
}
.about-cta h3 em { font-style: italic; color: var(--green); }
.about-cta p { font-size: .85rem; color: var(--t3); margin-bottom: 1.5rem; font-weight: 300; }
.about-cta-btns {
  display: flex; gap: .85rem;
  justify-content: center; flex-wrap: wrap;
}

/* ── Responsive ── */
@media(max-width:900px) {
  .legal-grid { grid-template-columns: repeat(2, 1fr); }
  .vmp-grid   { grid-template-columns: repeat(2, 1fr); }
}
@media(max-width:768px) {
  .about-wrap { padding: 2.5rem 1rem 3.5rem; }
  .vmp-grid   { grid-template-columns: 1fr; }
  .about-values { grid-template-columns: 1fr 1fr; }
  .about-stats { grid-template-columns: repeat(2, 1fr); }
  .about-stat:nth-child(2) { border-right: none; }
  .about-stat:nth-child(3),
  .about-stat:nth-child(4) { border-top: 1px solid rgba(200,146,42,.15); }
  .about-hero { padding: 3.5rem 0 3rem; }
  .founder-card { grid-template-columns: 1fr; gap: 1.5rem; }
  .founder-avatar-wrap { flex-direction: row; align-items: center; gap: 1.25rem; }
  .founder-title-block { text-align: left; }
  .founder-avatar { width: 80px; height: 80px; font-size: 1.5rem; }
  .dist-compare { grid-template-columns: 1fr; }
  .dist-vs { display: none; }
  .mbp-roles { grid-template-columns: 1fr; }
  .about-commit-grid { grid-template-columns: 1fr; }
  .why-grid { grid-template-columns: 1fr; }
  .legal-grid { grid-template-columns: 1fr; }
}
@media(max-width:480px) {
  .about-values { grid-template-columns: 1fr; }
  .about-stats  { grid-template-columns: 1fr 1fr; gap: .75rem; padding: 1.5rem 1rem; }
  .about-stat-val { font-size: 1.4rem; }
  .vmp-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ══ HERO ══ -->
<div class="about-hero">
  <div class="about-hero-wm" style="animation:aboutWmDrift 1.4s cubic-bezier(.16,1,.3,1) .1s both">MFILLS</div>
  <div class="about-hero-inner">
    <div class="about-hero-tag anim-hero-tag">Our Story</div>
    <h1 class="anim-hero-h1">About <em>Mfills</em></h1>
    <p class="about-hero-tagline anim-hero-sub">"MFILLS — Filling Life with Wellness."</p>
  </div>
</div>

<!-- ══ STICKY SECTION NAV ══ -->
<div class="about-sidenav-wrap" id="aboutSidenav" style="animation:aboutNavSlide .5s cubic-bezier(.16,1,.3,1) .55s both">
  <a href="#about-us"      class="active">About Us</a>
  <a href="#founder">Founder's Message</a>
  <a href="#distribution">Distribution Model</a>
  <a href="#why-different">Why MFILLS?</a>
  <a href="#commitment">Our Commitment</a>
  <a href="#legal">Legal &amp; Compliance</a>
</div>

<!-- ══ PAGE CONTENT ══ -->
<div class="about-wrap">

  <!-- ──────────────────────────────────
       1. ABOUT US / VISION / PHILOSOPHY
  ─────────────────────────────────── -->
  <span class="about-anchor" id="about-us"></span>
  <div class="about-section rv">
    <div class="about-label">Who We Are</div>
    <h2 class="about-h2">India's Trusted <em>Wellness Partner</em></h2>
    <div class="about-body">
      <p>
        <strong>Mfills India Private Limited</strong>, operating under the brand name and trademark <strong>MFILLS</strong>, is dedicated to serving the health supplement and wellness industry by providing high-quality, reliable, and affordable products to consumers across India.
      </p>
      <p>
        Our brand philosophy — <em>"MFILLS — Filling Life with Wellness."</em> — reflects our commitment to supporting healthier lifestyles by making wellness products accessible to every individual and family, regardless of geography or income.
      </p>
    </div>

    <div class="about-quote rv rv-left">
      <div class="about-quote-text">"MFILLS — Filling Life with Wellness."</div>
      <div class="about-quote-attr">— Mfills Brand Philosophy</div>
    </div>

    <div class="vmp-grid rv rv-stagger-grid">
      <div class="vmp-card">
        <div class="vmp-card-icon">🎯</div>
        <div class="vmp-card-label">Our Vision</div>
        <div class="vmp-card-title">A Healthier India</div>
        <div class="vmp-card-body">
          To become India's most trusted direct-selling wellness brand — empowering every household with genuine, science-backed products and a transparent business opportunity.
        </div>
      </div>
      <div class="vmp-card">
        <div class="vmp-card-icon">🚀</div>
        <div class="vmp-card-label">Our Mission</div>
        <div class="vmp-card-title">Direct, Genuine, Affordable</div>
        <div class="vmp-card-body">
          To deliver high-quality health and wellness products directly to consumers through a trusted partner network — eliminating intermediaries and ensuring authenticity at every step.
        </div>
      </div>
      <div class="vmp-card">
        <div class="vmp-card-icon">💡</div>
        <div class="vmp-card-label">Our Philosophy</div>
        <div class="vmp-card-title">Wellness Meets Opportunity</div>
        <div class="vmp-card-body">
          We believe wellness and financial independence go hand-in-hand. Mfills creates value for consumers while offering real earning opportunities to our Business Partners through ethical, transparent practices.
        </div>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="about-stats rv rv-scale">
    <div class="about-stat">
      <div class="about-stat-val" data-count="7" data-suffix="">7</div>
      <div class="about-stat-label">Level PSB Network</div>
    </div>
    <div class="about-stat">
      <div class="about-stat-val" data-prefix="₹" data-count="0">₹0</div>
      <div class="about-stat-label">Join Fee</div>
    </div>
    <div class="about-stat">
      <div class="about-stat-val" data-count="40" data-suffix="%">40%</div>
      <div class="about-stat-label">Total PSB Payout</div>
    </div>
    <div class="about-stat">
      <div class="about-stat-val" data-count="100" data-suffix="%">100%</div>
      <div class="about-stat-label">Genuine Products</div>
    </div>
  </div>

  <div class="about-divider"></div>

  <!-- ──────────────────────────────────
       2. FOUNDER'S MESSAGE
  ─────────────────────────────────── -->
  <span class="about-anchor" id="founder"></span>
  <div class="about-section rv">
    <div class="about-label">Founder's Message</div>
    <h2 class="about-h2">A Word from Our <em>Founder</em></h2>

    <div class="founder-card rv rv-scale">
      <div class="founder-avatar-wrap">
        <div class="founder-avatar" style="animation:aboutAvatarPop .7s cubic-bezier(.34,1.4,.64,1) .3s both">MF</div>
        <div class="founder-title-block">
          <div class="founder-name">Founder &amp; Director</div>
          <div class="founder-role">Mfills India Pvt. Ltd.</div>
        </div>
      </div>
      <div>
        <div class="founder-message-body">
          <p>
            When we started Mfills, our vision was simple: <strong>every Indian family deserves access to genuine, high-quality wellness products at fair prices</strong> — without the confusion, inflated costs, and counterfeiting that plagues traditional retail channels.
          </p>
          <p>
            The wellness industry in India is booming, yet most consumers still struggle to identify genuine products from duplicates, and most small entrepreneurs lack a credible platform to build sustainable income. Mfills was built to solve both problems at once.
          </p>
          <p>
            Our direct selling model is not just a business strategy — it is a <strong>commitment to transparency, trust, and community</strong>. When a Mfills Business Partner earns their first PSB credit, it represents something larger: an ecosystem where wellness and livelihood reinforce each other.
          </p>
          <p>
            We remain deeply committed to <strong>product integrity, partner success, and ethical business practices</strong>. Every product we sell, every policy we write, and every partner we onboard reflects these values.
          </p>
          <p>
            Thank you for being part of the Mfills family. Together, we are filling life with wellness.
          </p>
        </div>
        <div class="founder-signature">Mfills Founder</div>
        <div class="founder-sig-sub">Mfills India Private Limited</div>
      </div>
    </div>
  </div>

  <div class="about-divider"></div>

  <!-- ──────────────────────────────────
       3. DISTRIBUTION MODEL
  ─────────────────────────────────── -->
  <span class="about-anchor" id="distribution"></span>
  <div class="about-section rv">
    <div class="about-label">Our Model</div>
    <h2 class="about-h2">Our Unique <em>Distribution Model</em></h2>
    <div class="about-body">
      <p>
        Unlike traditional marketing and distribution systems — which involve multiple layers of wholesalers, distributors, retailers, and advertising intermediaries — the Mfills model is built on <strong>direct product distribution</strong> through a network of verified Business Partners.
      </p>
    </div>

    <div class="dist-compare">
      <div class="dist-col dist-col-old">
        <div class="dist-col-label">Traditional System</div>
        <div class="dist-chain">
          <div class="dist-node">Manufacturer</div>
          <div class="dist-arrow">↓ <span>cost added</span></div>
          <div class="dist-node">Distributor</div>
          <div class="dist-arrow">↓ <span>cost added</span></div>
          <div class="dist-node">Wholesaler</div>
          <div class="dist-arrow">↓ <span>cost added</span></div>
          <div class="dist-node">Retailer</div>
          <div class="dist-arrow">↓ <span>price inflated</span></div>
          <div class="dist-node dist-node-end">Customer</div>
        </div>
        <p class="dist-note">Each step adds cost — increasing the final price paid by consumers while diluting authenticity.</p>
      </div>

      <div class="dist-vs">VS</div>

      <div class="dist-col dist-col-new">
        <div class="dist-col-label dist-col-label-new">Mfills Model</div>
        <div class="dist-chain">
          <div class="dist-node dist-node-mfills">Mfills</div>
          <div class="dist-arrow dist-arrow-new">↓ <span>direct supply</span></div>
          <div class="dist-node dist-node-mfills">Mfills Business Partners</div>
          <div class="dist-arrow dist-arrow-new">↓ <span>genuine products</span></div>
          <div class="dist-node dist-node-end">Customer</div>
        </div>
        <p class="dist-note dist-note-new">Fewer steps = more affordable pricing, guaranteed authenticity &amp; stronger partner relationships.</p>
      </div>
    </div>

    <div class="about-body" style="margin-top:1.5rem">
      <p>
        Because Mfills products are delivered through <strong>authorized Mfills Business Partners</strong>, customers receive products that originate directly from the company's official supply chain — significantly reducing the chances of <strong>duplicate or counterfeit products</strong> entering the market.
      </p>
      <p>
        Every purchase on MShop generates <strong>Business Volume (BV)</strong>. A total of <strong>40% of BV is distributed as Partner Sales Bonus (PSB)</strong> across 7 levels of the network — ensuring that every partner who builds a genuine customer base earns real, transparent income.
      </p>
    </div>

    <div class="mbp-roles rv rv-stagger-grid">
      <div class="mbp-role-item">
        <span class="mbp-role-icon">💬</span>
        <div>
          <div class="mbp-role-title">Introduce Products</div>
          <div class="mbp-role-desc">Introduce customers to Mfills wellness products and provide guidance on product usage and benefits.</div>
        </div>
      </div>
      <div class="mbp-role-item">
        <span class="mbp-role-icon">📦</span>
        <div>
          <div class="mbp-role-title">Direct Delivery</div>
          <div class="mbp-role-desc">Deliver products directly to customers — ensuring authenticity and trust in every transaction.</div>
        </div>
      </div>
      <div class="mbp-role-item">
        <span class="mbp-role-icon">🌐</span>
        <div>
          <div class="mbp-role-title">Build Community</div>
          <div class="mbp-role-desc">Build a trusted wellness community — expanding Mfills' reach to new individuals and families.</div>
        </div>
      </div>
      <div class="mbp-role-item">
        <span class="mbp-role-icon">📈</span>
        <div>
          <div class="mbp-role-title">Earn PSB Income</div>
          <div class="mbp-role-desc">Earn Partner Sales Bonus (PSB) across 7 levels of their network from every eligible product purchase.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="about-divider"></div>

  <!-- ──────────────────────────────────
       4. WHY MFILLS IS DIFFERENT
  ─────────────────────────────────── -->
  <span class="about-anchor" id="why-different"></span>
  <div class="about-section rv">
    <div class="about-label">Our Difference</div>
    <h2 class="about-h2">Why <em>MFILLS</em> Is Different</h2>
    <div class="about-body">
      <p>
        In a crowded wellness market filled with inflated claims and counterfeit products, Mfills stands apart through a combination of <strong>product integrity, business transparency, and genuine community empowerment</strong>.
      </p>
    </div>

    <div class="why-grid rv rv-stagger-grid">
      <div class="why-item">
        <div class="why-num">01</div>
        <div>
          <div class="why-content-title">Zero Entry Barrier</div>
          <div class="why-content-desc">Registration as an MBP is completely free. No joining kits, no mandatory purchase, no hidden fees — anyone can start building their wellness business with Mfills.</div>
        </div>
      </div>
      <div class="why-item">
        <div class="why-num">02</div>
        <div>
          <div class="why-content-title">Science-Backed Products</div>
          <div class="why-content-desc">Every Mfills product is formulated using researched ingredients and undergoes rigorous quality checks before reaching the customer — no shortcuts on efficacy or purity.</div>
        </div>
      </div>
      <div class="why-item">
        <div class="why-num">03</div>
        <div>
          <div class="why-content-title">Transparent Earnings</div>
          <div class="why-content-desc">PSB credits are instantly visible in your Mfills wallet after every eligible purchase. No ambiguity, no delays, no hidden deductions — what you earn is what you see.</div>
        </div>
      </div>
      <div class="why-item">
        <div class="why-num">04</div>
        <div>
          <div class="why-content-title">Anti-Counterfeit Guarantee</div>
          <div class="why-content-desc">By distributing exclusively through verified MBPs, Mfills ensures that every product in circulation is genuine — dramatically reducing counterfeit risk compared to open-market channels.</div>
        </div>
      </div>
      <div class="why-item">
        <div class="why-num">05</div>
        <div>
          <div class="why-content-title">7-Level Earning Structure</div>
          <div class="why-content-desc">Mfills distributes 40% of BV as PSB across 7 downline levels — one of the most generous and structured earning models in India's direct selling industry.</div>
        </div>
      </div>
      <div class="why-item">
        <div class="why-num">06</div>
        <div>
          <div class="why-content-title">Community-First Culture</div>
          <div class="why-content-desc">Leadership clubs, recognition programs, and a growing ecosystem of partners make Mfills more than a business — it is a community built on shared wellness goals and mutual success.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="about-divider"></div>

  <!-- ──────────────────────────────────
       5. OUR COMMITMENT
  ─────────────────────────────────── -->
  <span class="about-anchor" id="commitment"></span>
  <div class="about-section rv">
    <div class="about-label">Our Promise</div>
    <h2 class="about-h2">Our <em>Commitment</em> to You</h2>
    <div class="about-body">
      <p>
        Every decision at Mfills is guided by a single question: <em>does this create genuine value for our customers, partners, and communities?</em> Our commitment is not just a statement — it is reflected in our product standards, our payout structure, and our daily operations.
      </p>
    </div>

    <div class="about-commit-grid rv rv-stagger-grid">
      <div class="about-commit-item">
        <div class="about-commit-icon">🌿</div>
        <div class="about-commit-text">Delivering <strong>high-quality health and wellness products</strong> that genuinely support healthier lifestyles</div>
      </div>
      <div class="about-commit-item">
        <div class="about-commit-icon">✅</div>
        <div class="about-commit-text">Ensuring <strong>product authenticity and safety</strong> at every step of our supply chain</div>
      </div>
      <div class="about-commit-item">
        <div class="about-commit-icon">💰</div>
        <div class="about-commit-text">Making wellness products <strong>affordable and accessible</strong> to families across all communities</div>
      </div>
      <div class="about-commit-item">
        <div class="about-commit-icon">🔒</div>
        <div class="about-commit-text">Supporting <strong>responsible and transparent business practices</strong> at all levels of our network</div>
      </div>
      <div class="about-commit-item">
        <div class="about-commit-icon">📊</div>
        <div class="about-commit-text">Providing <strong>real, trackable earning opportunities</strong> to our Business Partners through clear PSB structures</div>
      </div>
      <div class="about-commit-item">
        <div class="about-commit-icon">🤝</div>
        <div class="about-commit-text">Upholding the <strong>dignity, trust, and best interests</strong> of every partner and customer in our ecosystem</div>
      </div>
    </div>

    <div class="about-body" style="margin-top:1.75rem">
      <p>
        By combining <strong>product innovation, ethical business practices, and a direct connection with customers</strong>, Mfills aims to build a sustainable wellness ecosystem that benefits <strong>consumers, partners, and communities alike</strong>.
      </p>
    </div>
  </div>

  <div class="about-divider"></div>

  <!-- ──────────────────────────────────
       6. LEGAL & COMPLIANCE
  ─────────────────────────────────── -->
  <span class="about-anchor" id="legal"></span>
  <div class="about-section rv">
    <div class="about-label">Legal &amp; Compliance</div>
    <h2 class="about-h2">Governed by <em>Law, Guided by Ethics</em></h2>
    <div class="about-body">
      <p>
        Mfills India Private Limited operates in full compliance with applicable Indian laws, regulations, and industry standards governing direct selling, food supplements, and consumer protection. Our commitment to legal compliance is not just mandatory — it is integral to the trust our customers and partners place in us.
      </p>
    </div>

    <div class="legal-grid rv rv-stagger-grid">
      <div class="legal-card">
        <div class="legal-card-icon">🏛️</div>
        <div class="legal-card-title">Direct Selling Guidelines</div>
        <div class="legal-card-desc">Mfills operates in accordance with the Consumer Protection (Direct Selling) Rules, 2021 issued by the Ministry of Consumer Affairs, Government of India.</div>
      </div>
      <div class="legal-card">
        <div class="legal-card-icon">🧪</div>
        <div class="legal-card-title">FSSAI Compliance</div>
        <div class="legal-card-desc">All Mfills health supplement products are manufactured and labeled in compliance with the Food Safety and Standards Authority of India (FSSAI) regulations.</div>
      </div>
      <div class="legal-card">
        <div class="legal-card-icon">🛡️</div>
        <div class="legal-card-title">Consumer Protection</div>
        <div class="legal-card-desc">We uphold consumer rights under the Consumer Protection Act, 2019 — including the right to genuine products, clear information, and a fair grievance redressal mechanism.</div>
      </div>
      <div class="legal-card">
        <div class="legal-card-icon">💼</div>
        <div class="legal-card-title">Income Tax Compliance</div>
        <div class="legal-card-desc">All PSB payouts and commissions are processed in accordance with applicable TDS provisions under the Income Tax Act. Partners receive proper documentation for their earnings.</div>
      </div>
      <div class="legal-card">
        <div class="legal-card-icon">🔐</div>
        <div class="legal-card-title">Data Privacy</div>
        <div class="legal-card-desc">Personal data of all customers and partners is collected and processed in accordance with the Information Technology Act, 2000 and applicable data privacy obligations.</div>
      </div>
      <div class="legal-card">
        <div class="legal-card-icon">📋</div>
        <div class="legal-card-title">Anti-Pyramid Assurance</div>
        <div class="legal-card-desc">Mfills' business model is strictly product-driven. Earnings are based solely on genuine product sales — no recruitment-only bonuses, no income from joining fees.</div>
      </div>
    </div>

    <!-- Policy Document List -->
    <div class="legal-doc-list" style="margin-top:2.5rem">
      <div class="about-label" style="margin-bottom:1rem">Governing Documents</div>
      <div class="legal-doc-wrap">
        <div class="legal-doc-item">
          <div>
            <div class="legal-doc-name">Terms &amp; Conditions</div>
            <div class="legal-doc-desc">Platform usage rules, purchase terms, and partner obligations</div>
          </div>
          <span class="legal-doc-badge badge-active">Active</span>
        </div>
        <div class="legal-doc-item">
          <div>
            <div class="legal-doc-name">Privacy Policy</div>
            <div class="legal-doc-desc">How we collect, store, and protect your personal information</div>
          </div>
          <span class="legal-doc-badge badge-active">Active</span>
        </div>
        <div class="legal-doc-item">
          <div>
            <div class="legal-doc-name">MBP Code of Conduct</div>
            <div class="legal-doc-desc">Ethical conduct standards for all Mfills Business Partners</div>
          </div>
          <span class="legal-doc-badge badge-active">Active</span>
        </div>
        <div class="legal-doc-item">
          <div>
            <div class="legal-doc-name">PSB Compensation Plan</div>
            <div class="legal-doc-desc">Full breakdown of the 7-level Partner Sales Bonus structure</div>
          </div>
          <span class="legal-doc-badge badge-registered">Registered</span>
        </div>
        <div class="legal-doc-item">
          <div>
            <div class="legal-doc-name">Refund &amp; Return Policy</div>
            <div class="legal-doc-desc">Customer rights, return window, and refund processing rules</div>
          </div>
          <span class="legal-doc-badge badge-compliant">Compliant</span>
        </div>
        <div class="legal-doc-item">
          <div>
            <div class="legal-doc-name">Grievance Redressal Policy</div>
            <div class="legal-doc-desc">How complaints are escalated and resolved within mandated timelines</div>
          </div>
          <span class="legal-doc-badge badge-compliant">Compliant</span>
        </div>
      </div>
    </div>

    <div class="about-quote rv rv-left" style="margin-top:2rem">
      <div class="about-quote-text">"We believe that a business built on ethics and transparency is the only business worth building."</div>
      <div class="about-quote-attr">— Mfills Core Principle</div>
    </div>
  </div>

  <!-- ── CTA ── -->
  <div class="about-cta rv rv-scale">
    <h3>Join the <em>Mfills Family</em></h3>
    <p>Register free, get your MBPIN instantly, and start your wellness journey today.</p>
    <div class="about-cta-btns anim-stagger-parent" style="opacity:1">
      <a href="<?= APP_URL ?>/register.php" class="btn btn-primary rv">Register Free — Get MBPIN</a>
      <a href="<?= APP_URL ?>/shop.php"      class="btn btn-outline rv">Explore MShop</a>
      <a href="<?= APP_URL ?>/contact.php"   class="btn btn-gold rv">Contact Us</a>
    </div>
  </div>

</div><!-- /.about-wrap -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
/* ── about.php page-specific animations ──
   animations.js already handles:
   • .rv / .rv-stagger-grid scroll reveal
   • #aboutSidenav active link highlight
   • data-count number tick-up
   • card tilt on .about-value-card / .vmp-card
   The block below adds only what's unique to this page.
*/

/* ── Dynamic sidenav top — tracks navbar height after shrink ── */
(function () {
  var sidenav = document.querySelector('.about-sidenav-wrap');
  var navbar  = document.querySelector('.nav-landing, .nav-dashboard');
  if (!sidenav || !navbar) return;

  function syncTop() {
    var h = navbar.getBoundingClientRect().height;
    sidenav.style.top = h + 'px';
  }

  /* Run on scroll (navbar shrinks at 40px) and on resize */
  window.addEventListener('scroll',  syncTop, { passive: true });
  window.addEventListener('resize',  syncTop, { passive: true });
  syncTop();
})();

/* Distribution diagram — animated arrows on scroll into view */
(function () {
  var chains = document.querySelectorAll('.dist-chain');
  if (!chains.length) return;

  var seen = false;
  var obs  = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting || seen) return;
      seen = true;
      var nodes = entry.target.querySelectorAll('.dist-node');
      var arrows = entry.target.querySelectorAll('.dist-arrow');
      nodes.forEach(function (n, i) {
        n.style.opacity = '0';
        n.style.transform = 'translateY(10px)';
        n.style.transition = 'opacity 350ms ease, transform 350ms cubic-bezier(.34,1.4,.64,1)';
        setTimeout(function () {
          n.style.opacity = '1';
          n.style.transform = 'none';
        }, i * 120);
      });
      arrows.forEach(function (a, i) {
        a.style.opacity = '0';
        a.style.transition = 'opacity 250ms ease';
        setTimeout(function () { a.style.opacity = '1'; }, i * 120 + 80);
      });
    });
  }, { threshold: .25 });

  chains.forEach(function (c) { obs.observe(c); });
})();

/* Legal doc items — stagger on scroll */
(function () {
  var wrap = document.querySelector('.legal-doc-wrap');
  if (!wrap) return;

  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      obs.unobserve(entry.target);
      var items = entry.target.querySelectorAll('.legal-doc-item');
      items.forEach(function (item, i) {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-12px)';
        item.style.transition = 'opacity 320ms ease, transform 320ms cubic-bezier(.34,1.4,.64,1)';
        setTimeout(function () {
          item.style.opacity = '1';
          item.style.transform = 'none';
        }, i * 70);
      });
    });
  }, { threshold: .15 });

  obs.observe(wrap);
})();

/* Founder signature — typewriter-style fade */
(function () {
  var sig = document.querySelector('.founder-signature');
  if (!sig) return;
  sig.style.opacity = '0';
  sig.style.transform = 'translateY(8px)';

  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      obs.unobserve(entry.target);
      setTimeout(function () {
        sig.style.transition = 'opacity 600ms ease, transform 600ms cubic-bezier(.34,1.4,.64,1)';
        sig.style.opacity = '1';
        sig.style.transform = 'none';
      }, 500);
    });
  }, { threshold: .5 });

  obs.observe(sig);
})();
</script>