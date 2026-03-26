<!-- ══════════════════════════════════════
     MFILLS FOOTER — footer.php
     Drop-in replacement. Requires APP_URL constant.
══════════════════════════════════════ -->
<style>
/* ══════════════════════════════════════
   FOOTER VARIABLES & RESET
══════════════════════════════════════ */
.mf-footer *,
.mf-footer *::before,
.mf-footer *::after { box-sizing: border-box; margin: 0; padding: 0; }

.mf-footer {
  --f-bg:       #0d1f11;
  --f-bg2:      #111e14;
  --f-surface:  rgba(255,255,255,.04);
  --f-border:   rgba(255,255,255,.07);
  --f-green:    #4E9A60;
  --f-green-l:  #7ec490;
  --f-gold:     #B88018;
  --f-gold-l:   #D4A030;
  --f-gold-ll:  #EFBF50;
  --f-white:    #ffffff;
  --f-off:      rgba(255,255,255,.75);
  --f-muted:    rgba(255,255,255,.42);
  --f-dim:      rgba(255,255,255,.22);
  --f-stripe:   rgba(200,146,42,.12);
  font-family: 'Outfit','Nunito',sans-serif;
  background: var(--f-bg);
  color: var(--f-off);
  position: relative;
  overflow: hidden;
}

/* Subtle background texture */
.mf-footer::before {
  content: '';
  position: absolute; inset: 0; pointer-events: none; z-index: 0;
  background-image: radial-gradient(circle, rgba(200,146,42,.05) 1px, transparent 1px);
  background-size: 28px 28px;
}
.mf-footer > * { position: relative; z-index: 1; }

/* ══════════════════════════════════════
   NEWSLETTER BAR
══════════════════════════════════════ */
.mf-ft-nl {
  border-bottom: 1px solid var(--f-border);
  background: rgba(200,146,42,.06);
  padding: 1.6rem 0;
}
.mf-ft-nl-inner {
  max-width: 1440px; margin: 0 auto; padding: 0 2rem;
  display: flex; align-items: center; justify-content: space-between;
  gap: 1.5rem; flex-wrap: wrap;
}
.mf-ft-nl-text {}
.mf-ft-nl-label {
  font-size: .72rem; font-weight: 700; letter-spacing: .2em;
  text-transform: uppercase; color: var(--f-gold-ll); display: block;
  margin-bottom: .25rem;
}
.mf-ft-nl-sub {
  font-size: .95rem; color: var(--f-muted); font-weight: 300;
}
.mf-ft-nl-form { display: flex; gap: 0; flex-shrink: 0; }
.mf-ft-nl-input {
  width: 240px; padding: .62rem 1rem;
  border: 1px solid rgba(255,255,255,.12); border-right: none;
  background: rgba(255,255,255,.06); color: var(--f-white);
  font-family: 'Outfit',sans-serif; font-size: .95rem;
  outline: none; transition: border-color .18s, background .18s;
}
.mf-ft-nl-input::placeholder { color: var(--f-dim); }
.mf-ft-nl-input:focus { border-color: var(--f-gold); background: rgba(255,255,255,.1); }
.mf-ft-nl-btn {
  padding: .62rem 1.4rem; background: var(--f-gold); color: #fff;
  border: none; font-family: 'Outfit',sans-serif;
  font-size: .82rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; cursor: pointer; white-space: nowrap;
  transition: background .18s;
}
.mf-ft-nl-btn:hover { background: var(--f-gold-l); }

/* ══════════════════════════════════════
   BRAND ZONE
══════════════════════════════════════ */
.mf-ft-brand-zone {
  padding: 3rem 0 2.25rem;
  border-bottom: 1px solid var(--f-border);
}
.mf-ft-brand-inner {
  max-width: 1440px; margin: 0 auto; padding: 0 2rem;
  display: grid; grid-template-columns: 1fr auto; gap: 3rem;
  align-items: start;
}
.mf-ft-brand-left {}
.mf-ft-logo-wrap { display: inline-block; margin-bottom: 1.1rem; line-height: 0; }
.mf-ft-logo {
  height: auto; max-height: 58px; width: auto; max-width: 170px;
  object-fit: contain; object-position: left center; display: block;
  background: rgba(255,255,255,.94); border-radius: 6px;
  padding: 4px 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,.35), 0 0 0 1px rgba(200,146,42,.2);
  transition: opacity .2s;
}
.mf-ft-logo:hover { opacity: .88; }
.mf-ft-brand-tagline {
  font-family: 'DM Serif Display',serif;
  font-size: clamp(1rem, 1.6vw, 1.25rem);
  font-weight: 400; color: var(--f-gold-ll);
  letter-spacing: -.01em; margin-bottom: .65rem;
  font-style: italic;
}
.mf-ft-brand-desc {
  font-size: .92rem; color: var(--f-muted); line-height: 1.8;
  font-weight: 300; max-width: 380px; margin-bottom: 1.25rem;
}
.mf-ft-trust {
  display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.5rem;
}
.mf-ft-trust-pill {
  font-size: .68rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--f-green-l);
  border: 1px solid rgba(126,196,144,.25); padding: .24rem .7rem;
  border-radius: 20px; display: flex; align-items: center; gap: .35rem;
}
.mf-ft-trust-pill::before {
  content: ''; width: 5px; height: 5px; border-radius: 50%;
  background: var(--f-green); flex-shrink: 0;
}

/* Social icons */
.mf-ft-social { display: flex; gap: .45rem; align-items: center; flex-wrap: wrap; }
.mf-ft-social-icon {
  width: 38px; height: 38px; border: 1px solid var(--f-border);
  display: flex; align-items: center; justify-content: center;
  color: var(--f-muted); border-radius: 4px;
  transition: border-color .18s, color .18s, background .18s;
  text-decoration: none;
}
.mf-ft-social-icon svg { width: 17px; height: 17px; display: block; }
.mf-ft-social-icon:hover { border-color: var(--f-gold); color: var(--f-gold-l); background: var(--f-stripe); }

/* Right: cert badges */
.mf-ft-brand-right {
  display: flex; flex-direction: column; gap: .65rem;
  align-items: flex-end;
}
.mf-ft-cert {
  background: var(--f-surface); border: 1px solid var(--f-border);
  border-radius: 8px; padding: .6rem 1rem;
  display: flex; align-items: center; gap: .6rem;
  min-width: 200px;
}
.mf-ft-cert-icon { font-size: 1.1rem; flex-shrink: 0; }
.mf-ft-cert-label {
  font-size: .65rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--f-gold); display: block;
  margin-bottom: .12rem;
}
.mf-ft-cert-val { font-size: .85rem; color: var(--f-muted); font-weight: 300; }

/* ══════════════════════════════════════
   8-COLUMN LINKS GRID
══════════════════════════════════════ */
.mf-ft-links-zone {
  padding: 2.75rem 0 2.5rem;
  border-bottom: 1px solid var(--f-border);
}
.mf-ft-links-inner {
  max-width: 1440px; margin: 0 auto; padding: 0 2rem;
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1.5rem 2rem;
}

.mf-ft-col-title {
  font-size: .7rem; font-weight: 700; letter-spacing: .16em;
  text-transform: uppercase; color: var(--f-white);
  margin-bottom: 1.1rem; display: flex; align-items: center; gap: .5rem;
}
.mf-ft-col-title::after {
  content: ''; flex: 1; height: 1px; background: var(--f-border);
}
.mf-ft-col-links {
  list-style: none; display: flex; flex-direction: column; gap: .5rem;
}
.mf-ft-col-links li a {
  font-size: .9rem; color: var(--f-muted); font-weight: 300;
  text-decoration: none; line-height: 1.4;
  transition: color .15s, padding-left .15s;
  display: block;
}
.mf-ft-col-links li a:hover { color: var(--f-gold-ll); padding-left: 4px; }

/* ══════════════════════════════════════
   LEGAL / BOTTOM STRIP
══════════════════════════════════════ */
.mf-ft-legal {
  padding: 1.35rem 0 1.1rem;
  border-bottom: 1px solid var(--f-border);
}
.mf-ft-legal-inner {
  max-width: 1440px; margin: 0 auto; padding: 0 2rem;
  display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;
}
.mf-ft-legal-reg {
  font-size: .82rem; color: var(--f-dim); font-weight: 300;
  letter-spacing: .02em;
}
.mf-ft-legal-reg strong { color: var(--f-muted); font-weight: 500; }
.mf-ft-legal-sep {
  width: 1px; height: 14px; background: var(--f-border); flex-shrink: 0;
}
.mf-ft-legal-id {
  font-size: .78rem; color: var(--f-dim); font-weight: 300;
  display: flex; align-items: center; gap: .4rem;
}
.mf-ft-legal-id-label {
  font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
  color: rgba(200,146,42,.55); font-size: .64rem;
}

/* ══════════════════════════════════════
   COPYRIGHT BAR
══════════════════════════════════════ */
.mf-ft-copy-bar {
  padding: 1rem 0;
}
.mf-ft-copy-inner {
  max-width: 1440px; margin: 0 auto; padding: 0 2rem;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: .65rem;
}
.mf-ft-copy {
  font-size: .82rem; color: var(--f-dim); font-weight: 300;
}
.mf-ft-badges { display: flex; gap: .4rem; flex-wrap: wrap; }
.mf-ft-badge {
  font-size: .7rem; font-weight: 600; letter-spacing: .05em;
  color: var(--f-dim); border: 1px solid var(--f-border);
  padding: .18rem .6rem; border-radius: 3px;
  transition: border-color .15s, color .15s;
}
.mf-ft-badge:hover { border-color: var(--f-gold); color: var(--f-gold-l); }
.mf-ft-credit {
  font-size: .82rem; color: var(--f-dim); font-weight: 300;
  white-space: nowrap;
}
.mf-ft-credit a {
  color: var(--f-muted); font-weight: 500; text-decoration: none;
  border-bottom: 1px solid var(--f-border); transition: color .15s, border-color .15s;
}
.mf-ft-credit a:hover { color: var(--f-gold-l); border-color: var(--f-gold); }

/* ══════════════════════════════════════
   SCROLL TO TOP
══════════════════════════════════════ */
.mf-stt {
  position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 600;
  width: 40px; height: 40px;
  background: var(--green, #1C3D24); color: #fff; border: none;
  font-size: .9rem; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  border-radius: 4px;
  opacity: 0; transform: translateY(8px);
  transition: opacity .3s, transform .3s, background .15s;
  pointer-events: none;
  box-shadow: 0 4px 16px rgba(0,0,0,.35);
}
.mf-stt.visible { opacity: 1; transform: translateY(0); pointer-events: all; }
.mf-stt:hover { background: var(--green-m, #2E6244); }

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media(max-width:1200px) {
  .mf-ft-links-inner {
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
  }
}
@media(max-width:900px) {
  .mf-ft-brand-inner { grid-template-columns: 1fr; gap: 1.5rem; }
  .mf-ft-brand-right { align-items: flex-start; flex-direction: row; flex-wrap: wrap; }
  .mf-ft-cert { min-width: auto; flex: 1 1 180px; }
  .mf-ft-links-inner { grid-template-columns: repeat(3, 1fr); }
}
@media(max-width:768px) {
  .mf-ft-nl-inner { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .mf-ft-nl-form { width: 100%; }
  .mf-ft-nl-input { flex: 1; width: auto; }
  .mf-ft-links-inner { grid-template-columns: repeat(2, 1fr); }
  .mf-ft-legal-inner { flex-direction: column; gap: .5rem; }
  .mf-ft-legal-sep { display: none; }
  .mf-ft-copy-inner { flex-direction: column; align-items: flex-start; gap: .5rem; }
}
@media(max-width:480px) {
  .mf-ft-links-inner { grid-template-columns: 1fr 1fr; gap: 1.25rem 1rem; }
  .mf-ft-brand-inner { padding: 0 1rem; }
  .mf-ft-links-inner,
  .mf-ft-nl-inner,
  .mf-ft-brand-inner,
  .mf-ft-legal-inner,
  .mf-ft-copy-inner { padding-left: 1rem; padding-right: 1rem; }
}
</style>

<footer class="mf-footer">

  <!-- ══ NEWSLETTER BAR ══ -->
  <div class="mf-ft-nl">
    <div class="mf-ft-nl-inner">
      <div class="mf-ft-nl-text">
        <span class="mf-ft-nl-label">Stay Updated</span>
        <p class="mf-ft-nl-sub">Science-backed wellness tips &amp; exclusive partner offers — twice a month.</p>
      </div>
      <form class="mf-ft-nl-form" onsubmit="mfNlSubmit(event)">
        <input type="email" class="mf-ft-nl-input" placeholder="Your email address" required>
        <button type="submit" class="mf-ft-nl-btn" id="mfNlBtn">Subscribe</button>
      </form>
    </div>
  </div>

  <!-- ══ BRAND ZONE ══ -->
  <div class="mf-ft-brand-zone">
    <div class="mf-ft-brand-inner">

      <div class="mf-ft-brand-left">
        <a href="<?= APP_URL ?>/" class="mf-ft-logo-wrap">
          <img src="<?= APP_URL ?>/includes/images/logo2.png"
               alt="Mfills" class="mf-ft-logo"
               onerror="this.style.display='none'">
        </a>
        <div class="mf-ft-brand-tagline">"MFILLS® — Filling Life with Wellness."</div>
        <p class="mf-ft-brand-desc">
          Mfills India Private Limited is a direct selling wellness company committed to delivering genuine, science-backed health supplements through a transparent 7-level Partner Sales Bonus network. Free to join. Instant MBPIN. Real earnings.
        </p>
        <div class="mf-ft-trust">
          <span class="mf-ft-trust-pill">Lab Tested</span>
          <span class="mf-ft-trust-pill">Free to Join</span>
          <span class="mf-ft-trust-pill">Instant MBPIN</span>
          <span class="mf-ft-trust-pill">FSSAI Compliant</span>
          <span class="mf-ft-trust-pill">Direct Selling</span>
        </div>
        <div class="mf-ft-social">
          <!-- Facebook -->
          <a href="#" class="mf-ft-social-icon" title="Facebook" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          </a>
          <!-- Instagram -->
          <a href="#" class="mf-ft-social-icon" title="Instagram" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
          </a>
          <!-- YouTube -->
          <a href="#" class="mf-ft-social-icon" title="YouTube" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="currentColor" stroke="none"/></svg>
          </a>
          <!-- WhatsApp -->
          <a href="#" class="mf-ft-social-icon" title="WhatsApp" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          </a>
          <!-- Telegram -->
          <a href="#" class="mf-ft-social-icon" title="Telegram" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 5L2 12.5l7 1M21 5l-2.5 15L9 13.5M21 5L9 13.5m0 0v5l3.5-3"/></svg>
          </a>
        </div>
      </div>

      <div class="mf-ft-brand-right">
        <div class="mf-ft-cert">
          <span class="mf-ft-cert-icon">🏛️</span>
          <div>
            <span class="mf-ft-cert-label">CIN</span>
            <span class="mf-ft-cert-val">U74999JH2021PTC016067</span>
          </div>
        </div>
        <div class="mf-ft-cert">
          <span class="mf-ft-cert-icon">🧾</span>
          <div>
            <span class="mf-ft-cert-label">GST Number</span>
            <span class="mf-ft-cert-val"><?= defined('MFILLS_GST') ? htmlspecialchars(MFILLS_GST) : '[GST No.]' ?></span>
          </div>
        </div>
        <div class="mf-ft-cert">
          <span class="mf-ft-cert-icon">🧪</span>
          <div>
            <span class="mf-ft-cert-label">FSSAI License</span>
            <span class="mf-ft-cert-val"><?= defined('MFILLS_FSSAI') ? htmlspecialchars(MFILLS_FSSAI) : '[FSSAI No.]' ?></span>
          </div>
        </div>
        <div class="mf-ft-cert">
          <span class="mf-ft-cert-icon">⚖️</span>
          <div>
            <span class="mf-ft-cert-label">Reg. Under</span>
            <span class="mf-ft-cert-val">Direct Selling Rules, 2021</span>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ══ 7-COLUMN LINKS GRID ══ -->
  <div class="mf-ft-links-zone">
    <div class="mf-ft-links-inner">

      <!-- Quick Links -->
      <div class="mf-ft-col">
        <div class="mf-ft-col-title">Quick Links</div>
        <ul class="mf-ft-col-links">
          <li><a href="<?= APP_URL ?>/">Home</a></li>
          <li><a href="<?= APP_URL ?>/about.php">About</a></li>
          <li><a href="<?= APP_URL ?>/shop.php">MShop</a></li>
          <li><a href="<?= APP_URL ?>/about.php#distribution">Business</a></li>
          <li><a href="<?= APP_URL ?>/blog.php">Blog</a></li>
          <li><a href="<?= APP_URL ?>/register.php">Register</a></li>
          <li><a href="<?= APP_URL ?>/login.php">Login</a></li>
          <li><a href="<?= APP_URL ?>/contact.php">Contact</a></li>
        </ul>
      </div>

      <!-- Company -->
      <div class="mf-ft-col">
        <div class="mf-ft-col-title">Company</div>
        <ul class="mf-ft-col-links">
          <li><a href="<?= APP_URL ?>/about.php#about-us">About Us</a></li>
          <li><a href="<?= APP_URL ?>/about.php#about-us">Vision &amp; Mission</a></li>
          <li><a href="<?= APP_URL ?>/about.php#founder">Founder's Message</a></li>
          <li><a href="<?= APP_URL ?>/about.php#about-us">Our Philosophy</a></li>
          <li><a href="<?= APP_URL ?>/about.php#commitment">Our Commitment</a></li>
        </ul>
      </div>

      <!-- Products -->
      <div class="mf-ft-col">
        <div class="mf-ft-col-title">Products</div>
        <ul class="mf-ft-col-links">
          <li><a href="<?= APP_URL ?>/shop.php">All Products</a></li>
          <li><a href="<?= APP_URL ?>/shop.php?cat=health">Health Supplements</a></li>
          <li><a href="<?= APP_URL ?>/shop.php?cat=wellness">Wellness Products</a></li>
          <li><a href="<?= APP_URL ?>/shop.php?cat=combo">Combo Offers</a></li>
          <li><a href="<?= APP_URL ?>/shop.php?sort=new">New Arrivals</a></li>
          <li><a href="<?= APP_URL ?>/mshop_plus.php">MShop Plus</a></li>
          <li><a href="<?= APP_URL ?>/shop.php" class="mf-ft-shop-link">Shop Now →</a></li>
        </ul>
      </div>

      <!-- Business -->
      <div class="mf-ft-col">
        <div class="mf-ft-col-title">Business</div>
        <ul class="mf-ft-col-links">
          <li><a href="<?= APP_URL ?>/about.php#distribution">Business Opportunity</a></li>
          <li><a href="<?= APP_URL ?>/about.php#distribution">Business Plan</a></li>
          <li><a href="<?= APP_URL ?>/commissions.php">Income Opportunities</a></li>
          <li><a href="<?= APP_URL ?>/about.php#about-us">Mfills Business Club</a></li>
          <li><a href="<?= APP_URL ?>/shop.php">MShop / MShop Plus</a></li>
          <li><a href="<?= APP_URL ?>/register.php">Join as Partner</a></li>
        </ul>
      </div>

      <!-- Support -->
      <div class="mf-ft-col">
        <div class="mf-ft-col-title">Support</div>
        <ul class="mf-ft-col-links">
          <li><a href="<?= APP_URL ?>/contact.php">Contact Us</a></li>
          <li><a href="<?= APP_URL ?>/grievance.php">Grievance Redressal</a></li>
          <li><a href="<?= APP_URL ?>/faq.php">FAQ</a></li>
          <li><a href="<?= APP_URL ?>/order-tracking.php">Order Tracking</a></li>
          <li><a href="<?= APP_URL ?>/grievance.php#track">Track Complaint</a></li>
        </ul>
      </div>

      <!-- Legal -->
      <div class="mf-ft-col">
        <div class="mf-ft-col-title">Legal</div>
        <ul class="mf-ft-col-links">
          <li><a href="<?= APP_URL ?>/terms.php">Terms &amp; Conditions</a></li>
          <li><a href="<?= APP_URL ?>/privacy.php">Privacy Policy</a></li>
          <li><a href="<?= APP_URL ?>/refund-policy.php">Refund &amp; Cancellation</a></li>
          <li><a href="<?= APP_URL ?>/shipping-policy.php">Shipping Policy</a></li>
          <li><a href="<?= APP_URL ?>/income-disclaimer.php">Income Disclaimer</a></li>
        </ul>
      </div>

      <!-- Direct Seller + Disclosures (combined into one column) -->
      <div class="mf-ft-col">
        <div class="mf-ft-col-title">Direct Seller</div>
        <ul class="mf-ft-col-links" style="margin-bottom:1.25rem">
          <li><a href="<?= APP_URL ?>/direct-seller-guidelines.php">DS Guidelines</a></li>
          <li><a href="<?= APP_URL ?>/code-of-ethics.php">Code of Ethics</a></li>
          <li><a href="<?= APP_URL ?>/kyc-policy.php">KYC Policy</a></li>
          <li><a href="<?= APP_URL ?>/agreement-form.php">Agreement Form</a></li>
        </ul>
        <div class="mf-ft-col-title" style="margin-top:.1rem">Disclosures</div>
        <ul class="mf-ft-col-links">
          <li><a href="<?= APP_URL ?>/income-disclosure.php">Income Disclosure</a></li>
          <li><a href="<?= APP_URL ?>/certificates.php">Company Certificates</a></li>
          <li><a href="<?= APP_URL ?>/compliance.php">Compliance Documents</a></li>
          <li><a href="<?= APP_URL ?>/notices.php">Important Notices</a></li>
        </ul>
      </div>

    </div>
  </div>

  <!-- ══ LEGAL REGISTRATION STRIP ══ -->
  <div class="mf-ft-legal">
    <div class="mf-ft-legal-inner">
      <div class="mf-ft-legal-reg">
        <strong>Mfills India Private Limited</strong>
      </div>
      <div class="mf-ft-legal-sep"></div>
      <div class="mf-ft-legal-id">
        <span class="mf-ft-legal-id-label">CIN</span>
        U74999JH2021PTC016067
      </div>
      <div class="mf-ft-legal-sep"></div>
      <div class="mf-ft-legal-id">
        <span class="mf-ft-legal-id-label">GST</span>
        <?= defined('MFILLS_GST') ? htmlspecialchars(MFILLS_GST) : '[GST No. to be added]' ?>
      </div>
      <div class="mf-ft-legal-sep"></div>
      <div class="mf-ft-legal-id">
        <span class="mf-ft-legal-id-label">FSSAI</span>
        <?= defined('MFILLS_FSSAI') ? htmlspecialchars(MFILLS_FSSAI) : '[FSSAI Lic. No. to be added]' ?>
      </div>
      <div class="mf-ft-legal-sep"></div>
      <div class="mf-ft-legal-id">
        <span class="mf-ft-legal-id-label">Reg. under</span>
        Consumer Protection (Direct Selling) Rules, 2021
      </div>
    </div>
  </div>

  <!-- ══ COPYRIGHT BAR ══ -->
  <div class="mf-ft-copy-bar">
    <div class="mf-ft-copy-inner">
      <span class="mf-ft-copy">
        &copy; <?= date('Y') ?> Mfills India Private Limited. All rights reserved.
        Mfills&reg; is a registered trademark.
      </span>
      <div class="mf-ft-badges">
        <span class="mf-ft-badge">Science-Backed</span>
        <span class="mf-ft-badge">Secure Wallet</span>
        <span class="mf-ft-badge">Genuine Products</span>
        <span class="mf-ft-badge">&#8377;0 Join Fee</span>
        <span class="mf-ft-badge">7-Level PSB</span>
        <span class="mf-ft-badge">FSSAI Compliant</span>
      </div>
     <span class="mf-ft-credit">Designed & Developed by <a href="#" rel="noopener">Geinca</a></span>
    </div>
  </div>

</footer>

<!-- ══ SCROLL TO TOP ══ -->
<button class="mf-stt" id="mfScrollTop"
        onclick="window.scrollTo({top:0,behavior:'smooth'})"
        title="Back to top" aria-label="Back to top">↑</button>

<script>
/* ── Scroll-to-top visibility ── */
(function () {
  var btn = document.getElementById('mfScrollTop');
  if (!btn) return;
  window.addEventListener('scroll', function () {
    btn.classList.toggle('visible', window.scrollY > 320);
  }, { passive: true });
})();

/* ── Newsletter submit ── */
function mfNlSubmit(e) {
  e.preventDefault();
  var btn = document.getElementById('mfNlBtn');
  var inp = e.target.querySelector('.mf-ft-nl-input');
  if (!btn || !inp) return;
  btn.textContent = '✓ Subscribed!';
  btn.style.background = '#0F7B5C';
  btn.disabled = true;
  inp.disabled = true;
}
</script>

</body>
</html>