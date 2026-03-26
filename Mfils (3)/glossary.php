<?php
// glossary.php — Mfills Business Glossary
require_once __DIR__ . '/includes/functions.php';
startSession();
$pageTitle = 'Business Glossary — Mfills';
require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ══════════════════════════════════════
   GLOSSARY PAGE
══════════════════════════════════════ */
.glos-hero {
  background: linear-gradient(135deg, var(--green-dd) 0%, var(--green-d) 55%, var(--green-m) 100%);
  position: relative; overflow: hidden;
  padding: 4rem 0 3.5rem; border-bottom: 3px solid var(--gold);
  text-align: center;
}
.glos-hero::before {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background-image: radial-gradient(circle, rgba(200,146,42,.08) 1.5px, transparent 1.5px);
  background-size: 24px 24px;
}
.glos-hero-wm {
  position: absolute; top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  font-family: 'DM Serif Display', serif;
  font-size: clamp(6rem, 15vw, 18rem); font-weight: 400;
  color: rgba(255,255,255,.03); white-space: nowrap;
  pointer-events: none; letter-spacing: -.04em; line-height: 1;
}
.glos-hero-inner { position: relative; z-index: 1; max-width: 640px; margin: 0 auto; padding: 0 1.5rem; }
.glos-hero-tag {
  display: inline-flex; align-items: center; gap: .6rem;
  font-size: .6rem; font-weight: 700; letter-spacing: .18em;
  text-transform: uppercase; color: var(--gold-ll); margin-bottom: 1.25rem;
}
.glos-hero-tag::before, .glos-hero-tag::after { content: ''; width: 22px; height: 1px; background: var(--gold-ll); }
.glos-hero h1 {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 400; color: #fff;
  line-height: 1.15; letter-spacing: -.025em; margin-bottom: .75rem;
}
.glos-hero h1 em { font-style: italic; color: var(--gold-ll); }
.glos-hero-sub { font-size: .85rem; color: rgba(255,255,255,.5); font-weight: 300; }

/* Search bar inside hero */
.glos-search-wrap {
  margin-top: 1.75rem; position: relative; max-width: 440px; margin-left: auto; margin-right: auto;
}
.glos-search-input {
  width: 100%; padding: .75rem 3rem .75rem 1.1rem;
  border: 1.5px solid rgba(255,255,255,.2);
  border-radius: 8px; background: rgba(255,255,255,.12);
  font-family: 'Outfit', sans-serif; font-size: .88rem;
  color: #fff; outline: none; transition: border-color .2s, background .2s;
}
.glos-search-input::placeholder { color: rgba(255,255,255,.4); }
.glos-search-input:focus { border-color: var(--gold-ll); background: rgba(255,255,255,.18); }
.glos-search-icon {
  position: absolute; right: .85rem; top: 50%; transform: translateY(-50%);
  color: rgba(255,255,255,.4); pointer-events: none;
}

/* ── Wrap ── */
.glos-wrap { max-width: 960px; margin: 0 auto; padding: 3rem 1.5rem 5rem; }

/* Category tabs */
.glos-tabs {
  display: flex; gap: .4rem; flex-wrap: wrap;
  margin-bottom: 2.5rem; border-bottom: 1.5px solid var(--b);
  padding-bottom: .75rem;
}
.glos-tab {
  padding: .38rem .9rem; border-radius: 20px;
  border: 1.5px solid var(--b); background: #fff;
  font-size: .72rem; font-weight: 600; color: var(--t3);
  cursor: pointer; transition: all .18s; white-space: nowrap;
  font-family: 'Outfit', sans-serif;
}
.glos-tab:hover { border-color: var(--green-l); color: var(--green-m); }
.glos-tab.active { border-color: var(--green-d); background: var(--green-d); color: #fff; }

/* Section heading */
.glos-section { margin-bottom: 2.5rem; }
.glos-section-head {
  display: flex; align-items: center; gap: .75rem;
  margin-bottom: 1.1rem; padding-bottom: .6rem;
  border-bottom: 1.5px solid var(--b);
}
.glos-section-icon { font-size: 1.2rem; }
.glos-section-title {
  font-family: 'Cinzel', serif; font-size: .78rem; font-weight: 700;
  color: var(--green-d); letter-spacing: .06em; text-transform: uppercase;
}
.glos-section-count {
  margin-left: auto; font-size: .62rem; color: var(--t3);
  background: var(--g1); border: 1px solid var(--b);
  padding: .1rem .5rem; border-radius: 10px;
}

/* Term cards */
.glos-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; }
.glos-card {
  background: #fff; border: 1.5px solid var(--b); border-radius: 10px;
  padding: 1rem 1.1rem; transition: box-shadow .18s, transform .18s, border-color .18s;
  border-left: 3px solid var(--b);
}
.glos-card:hover { box-shadow: 0 4px 18px rgba(26,59,34,.09); transform: translateY(-2px); border-left-color: var(--green-l); }
.glos-card.hidden { display: none; }
.glos-term {
  font-family: 'Cinzel', serif; font-size: .88rem; font-weight: 700;
  color: var(--green-d); margin-bottom: .2rem;
}
.glos-abbr {
  font-size: .62rem; color: var(--t3); letter-spacing: .08em;
  text-transform: uppercase; margin-bottom: .55rem; font-weight: 500;
}
.glos-def {
  font-size: .8rem; color: var(--t2); line-height: 1.65; font-weight: 300;
}

/* No results */
.glos-no-results {
  text-align: center; padding: 3rem 1rem; color: var(--t3);
  display: none; grid-column: 1/-1;
}
.glos-no-results.show { display: block; }

/* ── Purpose note ── */
.glos-purpose {
  background: linear-gradient(135deg, var(--g2), var(--g1));
  border: 1.5px solid var(--b); border-left: 4px solid var(--gold);
  border-radius: 0 12px 12px 0;
  padding: 1.5rem 1.75rem; margin-bottom: 2.5rem;
}
.glos-purpose p { font-size: .82rem; color: var(--t2); line-height: 1.7; font-weight: 300; margin: 0; }
.glos-purpose strong { color: var(--t); font-weight: 600; }

/* Responsive */
@media(max-width:768px) {
  .glos-wrap { padding: 2rem 1rem 3.5rem; }
  .glos-grid { grid-template-columns: 1fr; }
}
@media(max-width:480px) {
  .glos-hero { padding: 2.5rem 0 2.5rem; }
}
</style>

<!-- HERO -->
<div class="glos-hero">
  <div class="glos-hero-wm">GLOSSARY</div>
  <div class="glos-hero-inner">
    <div class="glos-hero-tag">Reference Guide</div>
    <h1>Business <em>Glossary</em></h1>
    <p class="glos-hero-sub">Every term every MBP should know — clearly explained.</p>
    <div class="glos-search-wrap">
      <input type="text" class="glos-search-input" id="glosSearch"
             placeholder="Search terms… e.g. BV, PSB, MBPIN"
             oninput="glosFilter(this.value)">
      <span class="glos-search-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </span>
    </div>
  </div>
</div>

<!-- PAGE -->
<div class="glos-wrap">

  <!-- Purpose -->
  <div class="glos-purpose rv">
    <p>
      This glossary ensures that every <strong>Mfills Business Partner clearly understands the terminology</strong> used in the Mfills business model — making it easier to understand the compensation plan, track business performance, build and manage a partner network, and grow within the Mfills leadership system.
    </p>
  </div>

  <!-- Category Tabs -->
  <div class="glos-tabs" id="glosTabs">
    <button class="glos-tab active" onclick="glosTab('all', this)">All Terms</button>
    <button class="glos-tab" onclick="glosTab('basic', this)">📘 Business Basics</button>
    <button class="glos-tab" onclick="glosTab('shopping', this)">🛒 Shopping & Retail</button>
    <button class="glos-tab" onclick="glosTab('network', this)">📊 Network & Structure</button>
    <button class="glos-tab" onclick="glosTab('income', this)">💰 Income & Earnings</button>
    <button class="glos-tab" onclick="glosTab('clubs', this)">👑 Leadership Clubs</button>
  </div>

  <!-- ═══ SECTION: Business Basics ═══ -->
  <div class="glos-section rv" id="sec-basic">
    <div class="glos-section-head">
      <span class="glos-section-icon">📘</span>
      <span class="glos-section-title">Basic Business Fundamentals</span>
      <span class="glos-section-count">9 terms</span>
    </div>
    <div class="glos-grid">
      <div class="glos-card" data-cat="basic" data-terms="mbp mfills business partner">
        <div class="glos-term">MBP</div>
        <div class="glos-abbr">Mfills Business Partner</div>
        <div class="glos-def">A registered independent partner participating in the Mfills direct selling network.</div>
      </div>
      <div class="glos-card" data-cat="basic" data-terms="mbpin mfills business partner identification number">
        <div class="glos-term">MBPIN</div>
        <div class="glos-abbr">Mfills Business Partner Identification Number</div>
        <div class="glos-def">A unique identification number assigned to every partner after successful registration. Sent to the partner's registered email address.</div>
      </div>
      <div class="glos-card" data-cat="basic" data-terms="bv business volume">
        <div class="glos-term">BV</div>
        <div class="glos-abbr">Business Volume</div>
        <div class="glos-def">The numerical value assigned to each product used for calculating commissions, bonuses, and network performance.</div>
      </div>
      <div class="glos-card" data-cat="basic" data-terms="gv group volume">
        <div class="glos-term">GV</div>
        <div class="glos-abbr">Group Volume</div>
        <div class="glos-def">The total BV generated by the entire partner network within an organization.</div>
      </div>
      <div class="glos-card" data-cat="basic" data-terms="mrp maximum retail price">
        <div class="glos-term">MRP</div>
        <div class="glos-abbr">Maximum Retail Price</div>
        <div class="glos-def">The official retail price printed on the product packaging. Products are sold to end customers at MRP.</div>
      </div>
      <div class="glos-card" data-cat="basic" data-terms="psb partner sales bonus">
        <div class="glos-term">PSB</div>
        <div class="glos-abbr">Partner Sales Bonus</div>
        <div class="glos-def">Commission earned from the BV generated by purchases made by partners within the network. Distributed across 7 levels — totaling up to 40% of BV.</div>
      </div>
      <div class="glos-card" data-cat="basic" data-terms="ds direct selling">
        <div class="glos-term">DS</div>
        <div class="glos-abbr">Direct Selling</div>
        <div class="glos-def">A business model where products are sold directly to consumers through independent partners instead of traditional retail stores.</div>
      </div>
      <div class="glos-card" data-cat="basic" data-terms="kyc know your customer">
        <div class="glos-term">KYC</div>
        <div class="glos-abbr">Know Your Customer</div>
        <div class="glos-def">The identity verification process required to confirm the partner's identity and ensure regulatory compliance.</div>
      </div>
      <div class="glos-card" data-cat="basic" data-terms="business turnover">
        <div class="glos-term">Business Turnover</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The total value of product sales generated by the Mfills network during a specific time period.</div>
      </div>
    </div>
  </div>

  <!-- ═══ SECTION: Shopping & Retail ═══ -->
  <div class="glos-section rv" id="sec-shopping">
    <div class="glos-section-head">
      <span class="glos-section-icon">🛒</span>
      <span class="glos-section-title">Shopping & Retail System</span>
      <span class="glos-section-count">6 terms</span>
    </div>
    <div class="glos-grid">
      <div class="glos-card" data-cat="shopping" data-terms="mshop official shopping portal">
        <div class="glos-term">MShop</div>
        <div class="glos-abbr">Official Mfills Shopping Portal</div>
        <div class="glos-def">The official Mfills online shopping portal where partners can purchase products at MRP while generating Business Volume (BV).</div>
      </div>
      <div class="glos-card" data-cat="shopping" data-terms="mshop plus exclusive platform discounted">
        <div class="glos-term">MShop Plus</div>
        <div class="glos-abbr">Exclusive Partner Platform</div>
        <div class="glos-def">An exclusive partner platform that provides discounted product pricing and additional business benefits to qualified Business Club members.</div>
      </div>
      <div class="glos-card" data-cat="shopping" data-terms="product bv business volume">
        <div class="glos-term">Product BV</div>
        <div class="glos-abbr">Business Volume per Product</div>
        <div class="glos-def">The specific Business Volume value assigned to each product for bonus calculations.</div>
      </div>
      <div class="glos-card" data-cat="shopping" data-terms="personal purchase consumption">
        <div class="glos-term">Personal Purchase</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">Product purchases made by an MBP for personal consumption or monthly activity requirements.</div>
      </div>
      <div class="glos-card" data-cat="shopping" data-terms="customer sale retail selling">
        <div class="glos-term">Customer Sale</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">Selling Mfills products directly to retail customers at MRP or permitted pricing.</div>
      </div>
      <div class="glos-card" data-cat="shopping" data-terms="retail profit margin earning">
        <div class="glos-term">Retail Profit</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The margin earned by partners when selling products to customers at MRP after purchasing them at a discounted partner price through MShop Plus.</div>
      </div>
    </div>
  </div>

  <!-- ═══ SECTION: Network & Structure ═══ -->
  <div class="glos-section rv" id="sec-network">
    <div class="glos-section-head">
      <span class="glos-section-icon">📊</span>
      <span class="glos-section-title">Network & Structure Terms</span>
      <span class="glos-section-count">8 terms</span>
    </div>
    <div class="glos-grid">
      <div class="glos-card" data-cat="network" data-terms="sponsor introduce register">
        <div class="glos-term">Sponsor</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The Mfills Business Partner who introduces and registers a new partner into the Mfills network.</div>
      </div>
      <div class="glos-card" data-cat="network" data-terms="downline partners below indirect">
        <div class="glos-term">Downline</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">All partners who join the business under your position — either directly or indirectly through any level of the network.</div>
      </div>
      <div class="glos-card" data-cat="network" data-terms="upline sponsors above chain">
        <div class="glos-term">Upline</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The chain of sponsors above a partner who provide support, mentorship, and leadership guidance.</div>
      </div>
      <div class="glos-card" data-cat="network" data-terms="level position distance sponsor">
        <div class="glos-term">Level</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The position of partners within the network structure based on their distance from the sponsor. PSB is distributed across 7 levels.</div>
      </div>
      <div class="glos-card" data-cat="network" data-terms="active partner monthly purchase eligible">
        <div class="glos-term">Active Partner</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">A partner who completes the required monthly purchase and remains eligible to receive commissions and bonuses.</div>
      </div>
      <div class="glos-card" data-cat="network" data-terms="inactive partner ineligible bonuses">
        <div class="glos-term">Inactive Partner</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">A partner who does not complete the required monthly purchase and therefore becomes temporarily ineligible for bonuses.</div>
      </div>
      <div class="glos-card" data-cat="network" data-terms="network structure complete sponsorship">
        <div class="glos-term">Network</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The complete structure of partners created through sponsorship and business expansion across all levels.</div>
      </div>
      <div class="glos-card" data-cat="network" data-terms="team business total bv all levels">
        <div class="glos-term">Team Business</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The total BV generated by the entire partner network across all levels.</div>
      </div>
    </div>
  </div>

  <!-- ═══ SECTION: Income & Earnings ═══ -->
  <div class="glos-section rv" id="sec-income">
    <div class="glos-section-head">
      <span class="glos-section-icon">💰</span>
      <span class="glos-section-title">Income & Earnings System</span>
      <span class="glos-section-count">5 terms</span>
    </div>
    <div class="glos-grid">
      <div class="glos-card" data-cat="income" data-terms="partner sales bonus psb commission network">
        <div class="glos-term">Partner Sales Bonus (PSB)</div>
        <div class="glos-abbr">7-Level Commission</div>
        <div class="glos-def">Commission earned from the Business Volume generated by partner purchases within the network. Distributed as: L1–15%, L2–8%, L3–6%, L4–4%, L5–3%, L6–2%, L7–2% = total 40% of BV.</div>
      </div>
      <div class="glos-card" data-cat="income" data-terms="retail profit direct selling mrp customer">
        <div class="glos-term">Retail Profit</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">Immediate income earned when selling Mfills products directly to customers at MRP after purchasing at partner price through MShop Plus.</div>
      </div>
      <div class="glos-card" data-cat="income" data-terms="club income leadership reward pool">
        <div class="glos-term">Club Income</div>
        <div class="glos-abbr">Leadership Reward</div>
        <div class="glos-def">Special leadership rewards shared among partners who qualify for Mfills Leadership Clubs (RSC, PC, GAC, CC) based on network BV.</div>
      </div>
      <div class="glos-card" data-cat="income" data-terms="team business bv network total">
        <div class="glos-term">Team Business</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The total Business Volume generated by the partner's entire network, used for leadership club qualification.</div>
      </div>
      <div class="glos-card" data-cat="income" data-terms="monthly repurchase active eligible purchase">
        <div class="glos-term">Monthly Repurchase</div>
        <div class="glos-abbr">—</div>
        <div class="glos-def">The required monthly product purchase that keeps a partner active and eligible for bonuses and income opportunities.</div>
      </div>
    </div>
  </div>

  <!-- ═══ SECTION: Leadership Clubs ═══ -->
  <div class="glos-section rv" id="sec-clubs">
    <div class="glos-section-head">
      <span class="glos-section-icon">👑</span>
      <span class="glos-section-title">Leadership Club Ranks</span>
      <span class="glos-section-count">4 terms</span>
    </div>
    <div class="glos-grid">
      <div class="glos-card" data-cat="clubs" data-terms="rsc rising star club 25000 bv first milestone">
        <div class="glos-term">RSC — Rising Star Club</div>
        <div class="glos-abbr">Indicative Network BV: 25,000</div>
        <div class="glos-def">The first leadership milestone — recognizing partners who begin building a growing team. Build a small active team to qualify.</div>
      </div>
      <div class="glos-card" data-cat="clubs" data-terms="pc prestige club 100000 bv strong stable">
        <div class="glos-term">PC — Prestige Club</div>
        <div class="glos-abbr">Indicative Network BV: 1,00,000</div>
        <div class="glos-def">Recognition for partners who build a strong and stable network organization. Build a larger team organization to qualify.</div>
      </div>
      <div class="glos-card" data-cat="clubs" data-terms="gac global ambassador club 500000 bv international">
        <div class="glos-term">GAC — Global Ambassador Club</div>
        <div class="glos-abbr">Indicative Network BV: 5,00,000</div>
        <div class="glos-def">A high-level leadership position representing significant business growth and influence. Build a strong international network to qualify.</div>
      </div>
      <div class="glos-card" data-cat="clubs" data-terms="cc chairman club 2000000 bv highest rank">
        <div class="glos-term">CC — Chairman Club</div>
        <div class="glos-abbr">Indicative Network BV: 20,00,000</div>
        <div class="glos-def">The highest leadership rank within the Mfills business organization. Build a large leadership organization to qualify.</div>
      </div>
    </div>
  </div>

  <!-- No results -->
  <div id="glosNoResults" class="glos-no-results">
    <div style="font-size:2.5rem;margin-bottom:.75rem;opacity:.3">🔍</div>
    <p style="font-size:.9rem">No terms found for "<span id="glosSearchVal"></span>"</p>
    <p style="font-size:.78rem;margin-top:.35rem;color:var(--t3)">Try a shorter keyword like "BV", "PSB", or "MBP"</p>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
/* ── Scroll reveal ── */
const ro = new IntersectionObserver(entries => {
  entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('on'); ro.unobserve(e.target); } });
}, { threshold: .05 });
document.querySelectorAll('.rv').forEach(el => ro.observe(el));

/* ── Category filter ── */
function glosTab(cat, btn) {
  document.querySelectorAll('.glos-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.glos-card').forEach(c => {
    c.classList.toggle('hidden', cat !== 'all' && c.dataset.cat !== cat);
  });
  document.querySelectorAll('.glos-section').forEach(s => {
    var visible = s.querySelectorAll('.glos-card:not(.hidden)').length;
    s.style.display = visible === 0 ? 'none' : '';
  });
  checkNoResults();
  // clear search
  document.getElementById('glosSearch').value = '';
}

/* ── Search filter ── */
function glosFilter(q) {
  q = q.toLowerCase().trim();
  // Reset tab to All
  document.querySelectorAll('.glos-tab').forEach((t,i) => t.classList.toggle('active', i===0));
  document.querySelectorAll('.glos-card').forEach(c => {
    var terms = c.dataset.terms + ' ' + c.querySelector('.glos-term').textContent + ' ' + c.querySelector('.glos-def').textContent;
    c.classList.toggle('hidden', q !== '' && !terms.toLowerCase().includes(q));
  });
  document.querySelectorAll('.glos-section').forEach(s => {
    var visible = s.querySelectorAll('.glos-card:not(.hidden)').length;
    s.style.display = visible === 0 && q !== '' ? 'none' : '';
  });
  document.getElementById('glosSearchVal').textContent = q;
  checkNoResults();
}

function checkNoResults() {
  var total = document.querySelectorAll('.glos-card:not(.hidden)').length;
  var nr = document.getElementById('glosNoResults');
  if(nr) nr.classList.toggle('show', total === 0);
}
</script>