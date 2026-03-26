<?php
// grievance.php — Mfills Grievance Redressal
require_once __DIR__ . '/includes/functions.php';
startSession();
$pageTitle = 'Grievance Redressal — Mfills';

// ── Handle form submission (AJAX or fallback POST) ───────────────────────────
$formSuccess = false;
$formError   = '';
$ticketId    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grv_submit'])) {
    $name     = trim($_POST['grv_name']    ?? '');
    $email    = trim($_POST['grv_email']   ?? '');
    $phone    = trim($_POST['grv_phone']   ?? '');
    $mbpin    = trim($_POST['grv_mbpin']   ?? '');
    $type     = trim($_POST['grv_type']    ?? '');
    $subject  = trim($_POST['grv_subject'] ?? '');
    $desc     = trim($_POST['grv_desc']    ?? '');
    $orderId  = trim($_POST['grv_order']   ?? '');

    if (!$name || !$email || !$type || !$subject || !$desc) {
        $formError = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please enter a valid email address.';
    } else {
        // In production: insert into grievances table, send confirmation email
        // $ticketId = saveGrievance([...]);
        $ticketId    = 'GRV-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $formSuccess = true;
        // setFlash('success', "Your grievance has been submitted. Ticket ID: $ticketId");
    }
}

// ── Handle complaint tracking (GET) ─────────────────────────────────────────
$trackResult = null;
$trackError  = '';
if (isset($_GET['track']) && !empty($_GET['ticket'])) {
    $ticket = strtoupper(trim($_GET['ticket']));
    // In production: query DB for ticket status
    // Demo mock data
    $mockTickets = [
        'GRV-DEMO0001' => [
            'id'       => 'GRV-DEMO0001',
            'subject'  => 'Product delivery delayed by 10 days',
            'type'     => 'Delivery Issue',
            'date'     => '2026-03-10',
            'status'   => 'resolved',
            'steps'    => [
                ['label' => 'Submitted',       'date' => 'Mar 10, 2026', 'done' => true],
                ['label' => 'Acknowledged',    'date' => 'Mar 11, 2026', 'done' => true],
                ['label' => 'Under Review',    'date' => 'Mar 12, 2026', 'done' => true],
                ['label' => 'Resolved',        'date' => 'Mar 14, 2026', 'done' => true],
            ],
            'note' => 'Your complaint has been resolved. A replacement order was dispatched on Mar 14. Please check your registered email for tracking details.',
        ],
        'GRV-DEMO0002' => [
            'id'       => 'GRV-DEMO0002',
            'subject'  => 'PSB credit not reflecting after purchase',
            'type'     => 'PSB / Commission Issue',
            'date'     => '2026-03-15',
            'status'   => 'in-progress',
            'steps'    => [
                ['label' => 'Submitted',       'date' => 'Mar 15, 2026', 'done' => true],
                ['label' => 'Acknowledged',    'date' => 'Mar 16, 2026', 'done' => true],
                ['label' => 'Under Review',    'date' => 'Mar 17, 2026', 'done' => true],
                ['label' => 'Resolved',        'date' => '—',            'done' => false],
            ],
            'note' => 'Your complaint is currently under review by our accounts team. Expected resolution by Mar 22, 2026.',
        ],
    ];
    if (isset($mockTickets[$ticket])) {
        $trackResult = $mockTickets[$ticket];
    } else {
        $trackError = 'No complaint found with Ticket ID <strong>' . htmlspecialchars($ticket) . '</strong>. Please check the ID and try again.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ══════════════════════════════════════
   GRIEVANCE PAGE
══════════════════════════════════════ */

/* ── Hero ── */
.grv-hero {
  background: linear-gradient(135deg, var(--green-dd) 0%, var(--green-d) 55%, var(--green-m) 100%);
  padding: 4.5rem 0 4rem; border-bottom: 3px solid var(--gold);
  position: relative; overflow: hidden; text-align: center;
}
.grv-hero::before {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background-image: radial-gradient(circle, rgba(200,146,42,.07) 1.5px, transparent 1.5px);
  background-size: 26px 26px;
}
.grv-hero-inner { position: relative; z-index: 1; max-width: 680px; margin: 0 auto; padding: 0 1.5rem; }
.grv-hero-tag {
  display: inline-flex; align-items: center; gap: .6rem;
  font-size: .58rem; font-weight: 700; letter-spacing: .2em;
  text-transform: uppercase; color: var(--gold-ll); margin-bottom: 1.25rem;
}
.grv-hero-tag::before,.grv-hero-tag::after { content:''; width:20px; height:1px; background:var(--gold-ll); }
.grv-hero h1 {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(2rem, 4vw, 3.2rem);
  color: #fff; font-weight: 400; line-height: 1.15;
  letter-spacing: -.025em; margin-bottom: .85rem;
}
.grv-hero h1 em { font-style: italic; color: var(--gold-ll); }
.grv-hero-sub { font-size: .9rem; color: rgba(255,255,255,.55); font-weight: 300; max-width: 500px; margin: 0 auto 1.75rem; }
.grv-hero-quick {
  display: inline-flex; flex-wrap: wrap; gap: .6rem;
  justify-content: center;
}
.grv-hero-quick a {
  font-size: .65rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
  border: 1.5px solid rgba(255,255,255,.25); color: rgba(255,255,255,.8);
  padding: .45rem 1.1rem; border-radius: 20px;
  transition: all .18s; text-decoration: none;
}
.grv-hero-quick a:hover { border-color: var(--gold-l); color: var(--gold-ll); }

/* ── Sticky section nav ── */
.grv-sidenav {
  background: rgba(255,255,255,.97); border-bottom: 1.5px solid var(--b);
  position: sticky; top: var(--navbar-h); z-index: 100;
  overflow-x: auto; scrollbar-width: none;
  backdrop-filter: blur(12px);
}
.grv-sidenav::-webkit-scrollbar { display: none; }
.grv-sidenav-inner {
  max-width: 1100px; margin: 0 auto; padding: 0 1.5rem;
  display: flex; align-items: center;
}
.grv-sidenav a {
  flex-shrink: 0; font-size: .62rem; font-weight: 700; letter-spacing: .09em;
  text-transform: uppercase; color: var(--t3); padding: .9rem 1rem;
  border-bottom: 2.5px solid transparent; transition: color .18s, border-color .18s;
  text-decoration: none; white-space: nowrap;
}
.grv-sidenav a:hover { color: var(--green); }
.grv-sidenav a.active { color: var(--green); border-bottom-color: var(--green); }

/* ── Page wrap ── */
.grv-wrap { max-width: 1100px; margin: 0 auto; padding: 3.5rem 1.5rem 5rem; }

/* ── Section anchors ── */
.grv-anchor {
  display: block;
  height: calc(var(--navbar-h) + 60px);
  margin-top: calc(-1 * (var(--navbar-h) + 60px));
  visibility: hidden; pointer-events: none;
}

/* ── Section block ── */
.grv-section { margin-bottom: 4rem; }
.grv-section:last-child { margin-bottom: 0; }

.grv-label {
  font-size: .58rem; font-weight: 700; letter-spacing: .2em;
  text-transform: uppercase; color: var(--gold);
  display: flex; align-items: center; gap: .6rem; margin-bottom: .85rem;
}
.grv-label::before { content: ''; width: 22px; height: 1.5px; background: var(--gold); }

.grv-h2 {
  font-family: 'DM Serif Display', serif;
  font-size: clamp(1.6rem, 2.8vw, 2.3rem);
  font-weight: 400; color: var(--t); line-height: 1.15;
  letter-spacing: -.02em; margin-bottom: 1.25rem;
}
.grv-h2 em { font-style: italic; color: var(--green); }

.grv-body {
  font-size: .92rem; color: var(--t2); line-height: 1.9; font-weight: 300; max-width: 780px;
}
.grv-body p { margin-bottom: 1.1rem; }
.grv-body p:last-child { margin-bottom: 0; }
.grv-body strong { color: var(--t); font-weight: 600; }

/* ── Divider ── */
.grv-divider { height: 1px; background: var(--b); margin: 3.5rem 0; }

/* ══ 1. POLICY GRID ══ */
.grv-policy-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 1.15rem; margin-top: 2rem;
}
.grv-policy-card {
  background: #fff; border: 1.5px solid var(--b); border-top: 3px solid var(--green);
  border-radius: 13px; padding: 1.4rem 1.25rem;
  transition: box-shadow .2s, transform .2s;
}
.grv-policy-card:hover { box-shadow: 0 8px 26px rgba(26,59,34,.1); transform: translateY(-3px); }
.grv-policy-icon { font-size: 1.75rem; margin-bottom: .75rem; line-height: 1; }
.grv-policy-title {
  font-family: 'Cinzel', serif; font-size: .78rem; font-weight: 700;
  color: var(--green-d); letter-spacing: .04em; margin-bottom: .5rem;
}
.grv-policy-desc { font-size: .77rem; color: var(--t3); line-height: 1.65; font-weight: 300; }

/* ── Rights list ── */
.grv-rights {
  background: linear-gradient(135deg, var(--g2), var(--g1));
  border-left: 4px solid var(--gold); border-radius: 0 12px 12px 0;
  padding: 1.5rem 1.75rem; margin-top: 1.75rem;
}
.grv-rights-title {
  font-family: 'Cinzel', serif; font-size: .78rem; font-weight: 700;
  color: var(--green-d); letter-spacing: .04em; margin-bottom: .85rem;
}
.grv-rights ul { list-style: none; padding: 0; margin: 0; }
.grv-rights ul li {
  display: flex; gap: .65rem; align-items: flex-start;
  font-size: .83rem; color: var(--t2); line-height: 1.7;
  font-weight: 300; margin-bottom: .5rem; padding-bottom: .5rem;
  border-bottom: 1px solid rgba(26,59,34,.07);
}
.grv-rights ul li:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.grv-rights ul li::before {
  content: '✓'; color: var(--jade); font-weight: 700;
  font-size: .8rem; flex-shrink: 0; margin-top: .1rem;
}

/* ══ 2. HOW-TO STEPS ══ */
.grv-steps { display: flex; flex-direction: column; gap: 0; margin-top: 2rem; position: relative; }
.grv-steps::before {
  content: ''; position: absolute;
  left: 22px; top: 0; bottom: 0; width: 2px;
  background: linear-gradient(to bottom, var(--green-l), var(--b));
}
.grv-step {
  display: flex; gap: 1.5rem; align-items: flex-start;
  padding-bottom: 2rem; position: relative; z-index: 1;
}
.grv-step:last-child { padding-bottom: 0; }
.grv-step-num {
  width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
  background: var(--green); color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Cinzel', serif; font-size: .88rem; font-weight: 900;
  box-shadow: 0 0 0 4px #fff, 0 0 0 6px var(--green-l);
  letter-spacing: .04em;
}
.grv-step-content { padding-top: .55rem; }
.grv-step-title {
  font-family: 'Cinzel', serif; font-size: .88rem; font-weight: 700;
  color: var(--green-d); margin-bottom: .4rem; letter-spacing: .03em;
}
.grv-step-desc { font-size: .83rem; color: var(--t2); line-height: 1.75; font-weight: 300; max-width: 580px; }
.grv-step-desc strong { color: var(--t); font-weight: 600; }
.grv-step-badge {
  display: inline-flex; margin-top: .6rem;
  font-size: .6rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
  padding: .22rem .65rem; border-radius: 20px;
  background: rgba(26,59,34,.08); color: var(--green-m);
}

/* ══ 3. SUBMISSION FORM ══ */
.grv-form-wrap {
  background: #fff; border: 1.5px solid var(--b); border-radius: 18px;
  overflow: hidden; margin-top: 2rem;
  box-shadow: 0 4px 24px rgba(26,59,34,.07);
}
.grv-form-head {
  background: linear-gradient(135deg, var(--green-dd), var(--green-d));
  padding: 1.5rem 2rem; border-bottom: 3px solid var(--gold);
  display: flex; align-items: center; gap: 1rem;
}
.grv-form-head-icon { font-size: 2rem; line-height: 1; }
.grv-form-head-title {
  font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700;
  color: var(--gold-ll); letter-spacing: .04em;
}
.grv-form-head-sub { font-size: .72rem; color: rgba(255,255,255,.5); margin-top: .2rem; }
.grv-form-body { padding: 2rem; }

/* Success state */
.grv-success {
  text-align: center; padding: 3rem 2rem;
}
.grv-success-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.grv-success-ticket {
  font-family: 'Cinzel', serif; font-size: 1.5rem; font-weight: 900;
  color: var(--green); letter-spacing: .06em; margin-bottom: .5rem;
}
.grv-success-msg { font-size: .88rem; color: var(--t2); font-weight: 300; line-height: 1.7; max-width: 440px; margin: 0 auto 1.5rem; }
.grv-success-note {
  font-size: .78rem; color: var(--t3); background: var(--g1);
  border-radius: 10px; padding: .85rem 1.1rem; display: inline-block;
}

/* Form layout */
.grv-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
.grv-form-grid .grv-full { grid-column: 1 / -1; }
.grv-field { display: flex; flex-direction: column; gap: .4rem; }
.grv-field label {
  font-size: .72rem; font-weight: 700; color: var(--t);
  text-transform: uppercase; letter-spacing: .05em;
}
.grv-field label .req { color: var(--coral); margin-left: 2px; }
.grv-input, .grv-select, .grv-textarea {
  width: 100%; padding: .68rem .9rem; border: 1.5px solid var(--b);
  border-radius: 8px; font-size: .9rem; font-family: 'Outfit', sans-serif;
  background: var(--g1); color: var(--t); outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.grv-input:focus, .grv-select:focus, .grv-textarea:focus {
  border-color: var(--green-l); background: #fff;
  box-shadow: 0 0 0 3px rgba(26,59,34,.09);
}
.grv-textarea { resize: vertical; min-height: 110px; line-height: 1.6; }
.grv-select { appearance: none; cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238A8A78' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right .9rem center; padding-right: 2.5rem;
}
.grv-hint { font-size: .72rem; color: var(--t3); margin-top: .2rem; }
.grv-form-footer {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; margin-top: 1.5rem; padding-top: 1.5rem;
  border-top: 1px solid var(--g2); flex-wrap: wrap;
}
.grv-form-note { font-size: .72rem; color: var(--t3); line-height: 1.5; max-width: 420px; }
.grv-form-note strong { color: var(--t2); font-weight: 600; }
.grv-submit {
  background: var(--green); color: #fff; border: none;
  padding: .75rem 2rem; border-radius: 9px;
  font-family: 'Outfit', sans-serif; font-size: .82rem; font-weight: 700;
  letter-spacing: .06em; cursor: pointer; transition: background .18s, transform .15s;
  display: inline-flex; align-items: center; gap: .5rem;
}
.grv-submit:hover { background: var(--green-m); transform: translateY(-1px); }

/* ══ 4. RESOLUTION TIMELINE ══ */
.grv-timeline-grid {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 0; margin-top: 2rem;
  border-radius: 14px; overflow: hidden;
  border: 1.5px solid var(--b);
}
.grv-tl-cell {
  padding: 1.6rem 1.25rem; background: #fff; position: relative;
  border-right: 1px solid var(--b);
}
.grv-tl-cell:last-child { border-right: none; }
.grv-tl-cell::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0;
  height: 3px;
}
.grv-tl-cell:nth-child(1)::before { background: var(--jade); }
.grv-tl-cell:nth-child(2)::before { background: var(--gold); }
.grv-tl-cell:nth-child(3)::before { background: #1565C0; }
.grv-tl-cell:nth-child(4)::before { background: var(--coral); }

.grv-tl-step {
  font-size: .58rem; font-weight: 700; letter-spacing: .14em;
  text-transform: uppercase; color: var(--t3); margin-bottom: .5rem;
}
.grv-tl-icon { font-size: 1.6rem; margin-bottom: .75rem; line-height: 1; }
.grv-tl-name {
  font-family: 'Cinzel', serif; font-size: .82rem; font-weight: 700;
  color: var(--t); margin-bottom: .35rem; letter-spacing: .03em;
}
.grv-tl-days {
  font-family: 'Cinzel', serif; font-size: 1.4rem; font-weight: 900;
  margin-bottom: .3rem;
}
.grv-tl-cell:nth-child(1) .grv-tl-days { color: var(--jade); }
.grv-tl-cell:nth-child(2) .grv-tl-days { color: var(--gold-d); }
.grv-tl-cell:nth-child(3) .grv-tl-days { color: #1565C0; }
.grv-tl-cell:nth-child(4) .grv-tl-days { color: var(--coral); }

.grv-tl-desc { font-size: .74rem; color: var(--t3); line-height: 1.6; font-weight: 300; }

/* Escalation row */
.grv-escalation {
  background: rgba(184,128,24,.07); border: 1.5px solid rgba(184,128,24,.25);
  border-radius: 12px; padding: 1.25rem 1.5rem; margin-top: 1.5rem;
  display: flex; gap: 1rem; align-items: flex-start;
}
.grv-esc-icon { font-size: 1.5rem; flex-shrink: 0; line-height: 1; }
.grv-esc-title { font-size: .82rem; font-weight: 700; color: var(--gold-d); margin-bottom: .3rem; }
.grv-esc-desc { font-size: .78rem; color: var(--t2); line-height: 1.65; font-weight: 300; }
.grv-esc-desc strong { font-weight: 600; color: var(--t); }
.grv-esc-desc a { color: var(--green); }

/* ══ 5. OFFICER DETAILS ══ */
.grv-officer-card {
  display: grid; grid-template-columns: auto 1fr;
  gap: 2rem; align-items: start;
  background: #fff; border: 1.5px solid var(--b);
  border-radius: 18px; padding: 2rem 2.25rem; margin-top: 2rem;
  box-shadow: 0 4px 20px rgba(26,59,34,.07);
}
.grv-officer-avatar {
  width: 90px; height: 90px; border-radius: 50%;
  background: linear-gradient(135deg, var(--green-dd), var(--green-m));
  display: flex; align-items: center; justify-content: center;
  font-family: 'Cinzel', serif; font-size: 1.5rem; font-weight: 900;
  color: var(--gold-ll); border: 3px solid rgba(200,146,42,.3);
  box-shadow: 0 4px 18px rgba(26,59,34,.2); flex-shrink: 0;
}
.grv-officer-name {
  font-family: 'DM Serif Display', serif; font-size: 1.4rem;
  color: var(--t); font-weight: 400; letter-spacing: -.01em;
  margin-bottom: .2rem;
}
.grv-officer-role {
  font-size: .7rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: var(--gold); margin-bottom: 1.25rem;
}
.grv-officer-details { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem 2rem; }
.grv-officer-row {
  display: flex; gap: .6rem; align-items: flex-start;
  font-size: .82rem; color: var(--t2); font-weight: 300;
}
.grv-officer-row-icon { font-size: .95rem; flex-shrink: 0; margin-top: .1rem; }
.grv-officer-row-label {
  font-size: .62rem; font-weight: 700; letter-spacing: .08em;
  text-transform: uppercase; color: var(--t3); display: block; margin-bottom: .1rem;
}
.grv-officer-row a { color: var(--green); }

.grv-officer-note {
  grid-column: 1 / -1; margin-top: 1.25rem; padding-top: 1.25rem;
  border-top: 1px solid var(--g2);
  font-size: .78rem; color: var(--t3); line-height: 1.65; font-weight: 300;
}
.grv-officer-note strong { color: var(--t2); font-weight: 600; }

.grv-legal-ref {
  background: var(--g1); border: 1.5px solid var(--b);
  border-radius: 12px; padding: 1.25rem 1.5rem; margin-top: 1.5rem;
  display: flex; gap: 1rem; align-items: flex-start;
}
.grv-legal-ref-icon { font-size: 1.5rem; flex-shrink: 0; }
.grv-legal-ref-title { font-size: .82rem; font-weight: 700; color: var(--t); margin-bottom: .3rem; }
.grv-legal-ref-desc { font-size: .78rem; color: var(--t3); line-height: 1.65; font-weight: 300; }

/* ══ 6. TRACK COMPLAINT ══ */
.grv-track-wrap {
  background: #fff; border: 1.5px solid var(--b); border-radius: 18px;
  overflow: hidden; margin-top: 2rem;
  box-shadow: 0 4px 24px rgba(26,59,34,.07);
}
.grv-track-head {
  background: linear-gradient(135deg, #0a1e0d, var(--green-d));
  padding: 1.5rem 2rem; border-bottom: 3px solid var(--gold);
  display: flex; align-items: center; gap: 1rem;
}
.grv-track-head-icon { font-size: 2rem; }
.grv-track-head-title {
  font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700;
  color: var(--gold-ll); letter-spacing: .04em;
}
.grv-track-head-sub { font-size: .72rem; color: rgba(255,255,255,.5); margin-top: .2rem; }
.grv-track-body { padding: 2rem; }

.grv-track-form {
  display: flex; gap: .75rem; max-width: 520px;
}
.grv-track-input {
  flex: 1; padding: .72rem 1rem; border: 1.5px solid var(--b);
  border-radius: 8px; font-family: 'Outfit', monospace; font-size: .9rem;
  letter-spacing: .06em; background: var(--g1); color: var(--t); outline: none;
  text-transform: uppercase; transition: border-color .2s;
}
.grv-track-input:focus { border-color: var(--green-l); background: #fff; }
.grv-track-btn {
  background: var(--green); color: #fff; border: none;
  padding: .72rem 1.5rem; border-radius: 8px;
  font-family: 'Outfit', sans-serif; font-size: .8rem; font-weight: 700;
  letter-spacing: .06em; cursor: pointer; transition: background .18s;
  white-space: nowrap;
}
.grv-track-btn:hover { background: var(--green-m); }

.grv-track-demo {
  margin-top: .85rem; font-size: .72rem; color: var(--t3);
}
.grv-track-demo a { color: var(--green); cursor: pointer; }

/* Track result */
.grv-track-result { margin-top: 2rem; }
.grv-track-result-head {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;
}
.grv-track-id {
  font-family: 'Cinzel', serif; font-size: 1.1rem; font-weight: 900;
  color: var(--green); letter-spacing: .06em;
}
.grv-track-subject { font-size: .82rem; color: var(--t2); font-weight: 300; margin-top: .2rem; }
.grv-track-status-badge {
  font-size: .65rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; padding: .35rem .9rem; border-radius: 20px;
  white-space: nowrap; flex-shrink: 0; margin-top: .2rem;
}
.status-resolved  { background: rgba(15,123,92,.12); color: var(--jade-d); }
.status-in-progress { background: rgba(184,128,24,.12); color: var(--gold-d); }
.status-pending   { background: rgba(26,59,34,.09);  color: var(--green-m); }
.status-closed    { background: var(--g2); color: var(--t3); }

/* Progress stepper */
.grv-stepper {
  display: flex; align-items: flex-start; gap: 0;
  margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: .25rem;
}
.grv-stepper::-webkit-scrollbar { height: 3px; }
.grv-stepper::-webkit-scrollbar-thumb { background: var(--green-l); }
.grv-stepper-step {
  flex: 1; min-width: 100px;
  display: flex; flex-direction: column; align-items: center;
  gap: .4rem; position: relative; text-align: center;
}
.grv-stepper-step::after {
  content: ''; position: absolute;
  top: 14px; left: 50%; width: 100%; height: 2px;
  background: var(--b); z-index: 0;
}
.grv-stepper-step:last-child::after { display: none; }
.grv-stepper-step.done::after { background: var(--jade); }
.grv-stepper-dot {
  width: 28px; height: 28px; border-radius: 50%; position: relative; z-index: 1;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; font-weight: 700;
  border: 2px solid var(--b); background: #fff; color: var(--t3);
  transition: all .2s;
}
.grv-stepper-step.done .grv-stepper-dot {
  background: var(--jade); border-color: var(--jade); color: #fff;
}
.grv-stepper-step.current .grv-stepper-dot {
  background: var(--gold); border-color: var(--gold); color: #fff;
  box-shadow: 0 0 0 4px rgba(184,128,24,.2);
}
.grv-stepper-label { font-size: .68rem; font-weight: 600; color: var(--t3); margin-top: .2rem; white-space: nowrap; }
.grv-stepper-step.done .grv-stepper-label { color: var(--jade-d); }
.grv-stepper-step.current .grv-stepper-label { color: var(--gold-d); }
.grv-stepper-date { font-size: .6rem; color: var(--t3); }

/* Track note */
.grv-track-note {
  background: var(--g1); border: 1.5px solid var(--b); border-radius: 10px;
  padding: 1rem 1.25rem; font-size: .82rem; color: var(--t2); line-height: 1.7;
  font-weight: 300;
}

/* ── Responsive ── */
@media(max-width:900px) {
  .grv-policy-grid { grid-template-columns: repeat(2, 1fr); }
  .grv-timeline-grid { grid-template-columns: repeat(2, 1fr); }
  .grv-tl-cell:nth-child(2) { border-right: none; }
  .grv-tl-cell:nth-child(3), .grv-tl-cell:nth-child(4) { border-top: 1px solid var(--b); }
  .grv-officer-card { grid-template-columns: 1fr; gap: 1.25rem; }
  .grv-officer-avatar { width: 70px; height: 70px; font-size: 1.1rem; }
}
@media(max-width:768px) {
  .grv-wrap { padding: 2.5rem 1rem 3.5rem; }
  .grv-form-grid { grid-template-columns: 1fr; }
  .grv-form-body { padding: 1.5rem 1.25rem; }
  .grv-officer-details { grid-template-columns: 1fr; }
  .grv-track-form { flex-direction: column; }
  .grv-form-footer { flex-direction: column; align-items: flex-start; }
}
@media(max-width:560px) {
  .grv-policy-grid { grid-template-columns: 1fr; }
  .grv-timeline-grid { grid-template-columns: 1fr; }
  .grv-tl-cell { border-right: none; border-bottom: 1px solid var(--b); }
  .grv-tl-cell:last-child { border-bottom: none; }
}
</style>

<!-- ══ HERO ══ -->
<div class="grv-hero">
  <div class="grv-hero-inner">
    <div class="grv-hero-tag">Grievance Redressal</div>
    <h1>We're Here to <em>Make It Right</em></h1>
    <p class="grv-hero-sub">Your feedback matters. Every complaint is taken seriously, handled transparently, and resolved within mandated timelines.</p>
    <div class="grv-hero-quick">
      <a href="#policy">Our Policy</a>
      <a href="#how-to">How to Complain</a>
      <a href="#submit">Submit Grievance</a>
      <a href="#track">Track Status</a>
    </div>
  </div>
</div>

<!-- ══ STICKY NAV ══ -->
<nav class="grv-sidenav" id="grvSidenav">
  <div class="grv-sidenav-inner">
    <a href="#policy"   class="active">Policy</a>
    <a href="#how-to">How to Complain</a>
    <a href="#submit">Submit Grievance</a>
    <a href="#timeline">Resolution Timeline</a>
    <a href="#officer">Grievance Officer</a>
    <a href="#track">Track Status</a>
  </div>
</nav>

<div class="grv-wrap">

  <!-- ════════════════════════════════
       1. GRIEVANCE POLICY
  ═════════════════════════════════ -->
  <span class="grv-anchor" id="policy"></span>
  <div class="grv-section rv">
    <div class="grv-label">Section 01</div>
    <h2 class="grv-h2">Our <em>Grievance Policy</em></h2>
    <div class="grv-body">
      <p>
        Mfills India Private Limited is committed to providing a fair, transparent, and efficient grievance redressal mechanism for all customers and Mfills Business Partners (MBPs). This policy is established in compliance with the <strong>Consumer Protection (Direct Selling) Rules, 2021</strong> and the <strong>Consumer Protection Act, 2019</strong>.
      </p>
      <p>
        All grievances are treated with seriousness and confidentiality. We do not permit any form of retaliation against any customer or partner who raises a complaint in good faith.
      </p>
    </div>

    <div class="grv-policy-grid">
      <div class="grv-policy-card">
        <div class="grv-policy-icon">⚡</div>
        <div class="grv-policy-title">Timely Resolution</div>
        <div class="grv-policy-desc">All complaints are acknowledged within 24 hours and resolved within mandated regulatory timelines — typically 7 to 21 working days depending on complexity.</div>
      </div>
      <div class="grv-policy-card">
        <div class="grv-policy-icon">🔒</div>
        <div class="grv-policy-title">Strict Confidentiality</div>
        <div class="grv-policy-desc">Your personal details and complaint information are kept strictly confidential and accessed only by authorised personnel involved in resolution.</div>
      </div>
      <div class="grv-policy-card">
        <div class="grv-policy-icon">⚖️</div>
        <div class="grv-policy-title">Fair & Impartial Review</div>
        <div class="grv-policy-desc">Every complaint is reviewed impartially. Our Grievance Officer operates independently to ensure no bias toward the company or any individual partner.</div>
      </div>
      <div class="grv-policy-card">
        <div class="grv-policy-icon">📋</div>
        <div class="grv-policy-title">Full Transparency</div>
        <div class="grv-policy-desc">You receive a unique Ticket ID on submission and can track your complaint's status at any time using the tracking tool on this page.</div>
      </div>
      <div class="grv-policy-card">
        <div class="grv-policy-icon">🚫</div>
        <div class="grv-policy-title">No Retaliation</div>
        <div class="grv-policy-desc">No customer or MBP shall face any adverse action — including account suspension, PSB reduction, or network penalties — for raising a legitimate grievance.</div>
      </div>
      <div class="grv-policy-card">
        <div class="grv-policy-icon">🔁</div>
        <div class="grv-policy-title">Right of Escalation</div>
        <div class="grv-policy-desc">If you are not satisfied with the resolution, you have the right to escalate to the Grievance Officer and thereafter to the appropriate regulatory authority.</div>
      </div>
    </div>

    <div class="grv-rights" style="margin-top:2rem">
      <div class="grv-rights-title">Your Rights as a Consumer / MBP</div>
      <ul>
        <li>Right to receive genuine, accurately labelled products as described on MShop</li>
        <li>Right to a full refund or replacement for defective, damaged, or incorrect products</li>
        <li>Right to accurate and timely PSB credits for all eligible purchases in your network</li>
        <li>Right to a written response to every formally submitted grievance</li>
        <li>Right to escalate unresolved grievances to the National Consumer Helpline (1800-11-4000)</li>
        <li>Right to approach the Consumer Disputes Redressal Commission if not satisfied with our resolution</li>
      </ul>
    </div>
  </div>

  <div class="grv-divider"></div>

  <!-- ════════════════════════════════
       2. HOW TO RAISE A COMPLAINT
  ═════════════════════════════════ -->
  <span class="grv-anchor" id="how-to"></span>
  <div class="grv-section rv">
    <div class="grv-label">Section 02</div>
    <h2 class="grv-h2">How to <em>Raise a Complaint</em></h2>
    <div class="grv-body">
      <p>
        You can raise a grievance through any of the three channels below. The online submission form on this page is the fastest and most trackable method.
      </p>
    </div>

    <div class="grv-steps">
      <div class="grv-step">
        <div class="grv-step-num">01</div>
        <div class="grv-step-content">
          <div class="grv-step-title">Gather Your Information</div>
          <div class="grv-step-desc">
            Before submitting, collect relevant details: your <strong>MBPIN or registered email</strong>, the <strong>Order ID</strong> (if complaint relates to a purchase), a clear description of the issue, and any supporting documents (screenshots, photos of damaged products, transaction receipts).
          </div>
          <span class="grv-step-badge">📁 Preparation</span>
        </div>
      </div>
      <div class="grv-step">
        <div class="grv-step-num">02</div>
        <div class="grv-step-content">
          <div class="grv-step-title">Choose Your Channel</div>
          <div class="grv-step-desc">
            Submit via the <strong>online form</strong> on this page (fastest, tracked), email us at <strong>mfillsindia@gmail.com</strong>, or call our Grievance Helpline at <strong>+91 XXXXX XXXXX</strong> (Mon–Sat, 10 AM – 6 PM). All channels generate a Ticket ID.
          </div>
          <span class="grv-step-badge">📬 Submission</span>
        </div>
      </div>
      <div class="grv-step">
        <div class="grv-step-num">03</div>
        <div class="grv-step-content">
          <div class="grv-step-title">Receive Your Ticket ID</div>
          <div class="grv-step-desc">
            Upon submission, you will immediately receive a unique <strong>Ticket ID</strong> (format: GRV-XXXXXXXX) via email and/or SMS. Save this ID — you will need it to track the status of your complaint using the tracker on this page.
          </div>
          <span class="grv-step-badge">🎫 Acknowledgement</span>
        </div>
      </div>
      <div class="grv-step">
        <div class="grv-step-num">04</div>
        <div class="grv-step-content">
          <div class="grv-step-title">Our Team Reviews Your Complaint</div>
          <div class="grv-step-desc">
            The assigned team will review your submission, contact you for additional information if required, and work to resolve your complaint within the applicable timeline. You may be contacted via your <strong>registered email or phone number</strong>.
          </div>
          <span class="grv-step-badge">🔍 Review</span>
        </div>
      </div>
      <div class="grv-step">
        <div class="grv-step-num">05</div>
        <div class="grv-step-content">
          <div class="grv-step-title">Resolution & Closure</div>
          <div class="grv-step-desc">
            Once resolved, you will receive a <strong>written resolution notice</strong> at your registered email. If you are unsatisfied with the outcome, you may escalate to the Grievance Officer within <strong>7 days</strong> of receiving the resolution notice.
          </div>
          <span class="grv-step-badge">✅ Resolution</span>
        </div>
      </div>
    </div>
  </div>

  <div class="grv-divider"></div>

  <!-- ════════════════════════════════
       3. SUBMISSION FORM
  ═════════════════════════════════ -->
  <span class="grv-anchor" id="submit"></span>
  <div class="grv-section rv">
    <div class="grv-label">Section 03</div>
    <h2 class="grv-h2">Submit a <em>Grievance</em></h2>
    <div class="grv-body">
      <p>Fill in the form below. All fields marked <span style="color:var(--coral);font-weight:700">*</span> are required. You will receive a Ticket ID by email immediately after submission.</p>
    </div>

    <div class="grv-form-wrap">
      <div class="grv-form-head">
        <div class="grv-form-head-icon">📝</div>
        <div>
          <div class="grv-form-head-title">Grievance Submission Form</div>
          <div class="grv-form-head-sub">Secure · Tracked · Acknowledged within 24 hours</div>
        </div>
      </div>
      <div class="grv-form-body">

        <?php if ($formSuccess): ?>
        <div class="grv-success">
          <div class="grv-success-icon">✅</div>
          <div class="grv-success-ticket"><?= htmlspecialchars($ticketId) ?></div>
          <div class="grv-success-msg">
            Your grievance has been successfully submitted. Please save your Ticket ID above — you will need it to track the status of your complaint.
          </div>
          <div class="grv-success-note">
            📧 A confirmation email with your Ticket ID has been sent to your registered email address.<br>
            ⏱️ Expect acknowledgement within <strong>24 hours</strong> and resolution within <strong>7–21 working days</strong>.
          </div>
          <div style="margin-top:1.5rem">
            <a href="#track" class="btn btn-primary" style="margin-right:.5rem">Track My Complaint</a>
            <a href="<?= APP_URL ?>/grievance.php" class="btn btn-outline">Submit Another</a>
          </div>
        </div>

        <?php else: ?>

        <?php if ($formError): ?>
        <div class="alert alert-danger" style="margin-bottom:1.25rem"><?= htmlspecialchars($formError) ?></div>
        <?php endif; ?>

        <form method="POST" action="#submit" id="grvForm">
          <input type="hidden" name="grv_submit" value="1">
          <div class="grv-form-grid">
            <!-- Full name -->
            <div class="grv-field">
              <label>Full Name <span class="req">*</span></label>
              <input type="text" name="grv_name" class="grv-input"
                     placeholder="Your full name"
                     value="<?= htmlspecialchars($_POST['grv_name'] ?? '') ?>" required>
            </div>
            <!-- Email -->
            <div class="grv-field">
              <label>Email Address <span class="req">*</span></label>
              <input type="email" name="grv_email" class="grv-input"
                     placeholder="your@email.com"
                     value="<?= htmlspecialchars($_POST['grv_email'] ?? '') ?>" required>
            </div>
            <!-- Phone -->
            <div class="grv-field">
              <label>Phone Number</label>
              <input type="tel" name="grv_phone" class="grv-input"
                     placeholder="+91 XXXXX XXXXX"
                     value="<?= htmlspecialchars($_POST['grv_phone'] ?? '') ?>">
            </div>
            <!-- MBPIN -->
            <div class="grv-field">
              <label>MBPIN (if applicable)</label>
              <input type="text" name="grv_mbpin" class="grv-input"
                     placeholder="Your Mfills Business Partner ID"
                     value="<?= htmlspecialchars($_POST['grv_mbpin'] ?? '') ?>">
              <span class="grv-hint">Leave blank if you are a customer without an MBPIN</span>
            </div>
            <!-- Complaint type -->
            <div class="grv-field">
              <label>Complaint Type <span class="req">*</span></label>
              <select name="grv_type" class="grv-select" required>
                <option value="" disabled <?= empty($_POST['grv_type']) ? 'selected' : '' ?>>— Select a category —</option>
                <option value="Product Quality"         <?= ($_POST['grv_type']??'')==='Product Quality'         ?'selected':'' ?>>Product Quality / Defective Item</option>
                <option value="Delivery Issue"          <?= ($_POST['grv_type']??'')==='Delivery Issue'          ?'selected':'' ?>>Delivery Issue / Missing Order</option>
                <option value="Wrong Product"           <?= ($_POST['grv_type']??'')==='Wrong Product'           ?'selected':'' ?>>Wrong Product Received</option>
                <option value="Refund / Return"         <?= ($_POST['grv_type']??'')==='Refund / Return'         ?'selected':'' ?>>Refund / Return Request</option>
                <option value="PSB / Commission Issue"  <?= ($_POST['grv_type']??'')==='PSB / Commission Issue'  ?'selected':'' ?>>PSB / Commission Issue</option>
                <option value="Account / Login Issue"   <?= ($_POST['grv_type']??'')==='Account / Login Issue'   ?'selected':'' ?>>Account / Login Issue</option>
                <option value="MBP Conduct"             <?= ($_POST['grv_type']??'')==='MBP Conduct'             ?'selected':'' ?>>MBP / Partner Conduct</option>
                <option value="Billing / Payment"       <?= ($_POST['grv_type']??'')==='Billing / Payment'       ?'selected':'' ?>>Billing / Payment Issue</option>
                <option value="Other"                   <?= ($_POST['grv_type']??'')==='Other'                   ?'selected':'' ?>>Other</option>
              </select>
            </div>
            <!-- Order ID -->
            <div class="grv-field">
              <label>Order ID (if applicable)</label>
              <input type="text" name="grv_order" class="grv-input"
                     placeholder="e.g. MF-20260301-XXXX"
                     value="<?= htmlspecialchars($_POST['grv_order'] ?? '') ?>">
            </div>
            <!-- Subject -->
            <div class="grv-field grv-full">
              <label>Subject <span class="req">*</span></label>
              <input type="text" name="grv_subject" class="grv-input"
                     placeholder="Brief one-line summary of your complaint"
                     maxlength="120"
                     value="<?= htmlspecialchars($_POST['grv_subject'] ?? '') ?>" required>
            </div>
            <!-- Description -->
            <div class="grv-field grv-full">
              <label>Detailed Description <span class="req">*</span></label>
              <textarea name="grv_desc" class="grv-textarea"
                        placeholder="Please describe your complaint in detail — include dates, amounts, product names, and any steps you have already taken to resolve this issue."
                        required><?= htmlspecialchars($_POST['grv_desc'] ?? '') ?></textarea>
              <span class="grv-hint">The more detail you provide, the faster we can resolve your complaint.</span>
            </div>
          </div>

          <div class="grv-form-footer">
            <div class="grv-form-note">
              <strong>🔒 Privacy Notice:</strong> Your complaint details are handled in strict confidence in accordance with our Privacy Policy and the Consumer Protection Act, 2019. They will not be shared with any third party without your consent.
            </div>
            <button type="submit" class="grv-submit">
              📨 Submit Grievance
            </button>
          </div>
        </form>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <div class="grv-divider"></div>

  <!-- ════════════════════════════════
       4. RESOLUTION TIMELINE
  ═════════════════════════════════ -->
  <span class="grv-anchor" id="timeline"></span>
  <div class="grv-section rv">
    <div class="grv-label">Section 04</div>
    <h2 class="grv-h2">Resolution <em>Timeline</em></h2>
    <div class="grv-body">
      <p>
        Mfills is committed to resolving all grievances within the timelines mandated by the Consumer Protection (Direct Selling) Rules, 2021. The following stages apply to all submitted complaints.
      </p>
    </div>

    <div class="grv-timeline-grid">
      <div class="grv-tl-cell">
        <div class="grv-tl-step">Stage 01</div>
        <div class="grv-tl-icon">📬</div>
        <div class="grv-tl-name">Acknowledgement</div>
        <div class="grv-tl-days">24 hrs</div>
        <div class="grv-tl-desc">Your complaint is logged, a Ticket ID is assigned, and an acknowledgement is sent to your registered email and/or phone number.</div>
      </div>
      <div class="grv-tl-cell">
        <div class="grv-tl-step">Stage 02</div>
        <div class="grv-tl-icon">🔍</div>
        <div class="grv-tl-name">Initial Review</div>
        <div class="grv-tl-days">3 days</div>
        <div class="grv-tl-desc">The relevant team (logistics, accounts, product quality, or MBP relations) conducts an initial review and may contact you for additional details.</div>
      </div>
      <div class="grv-tl-cell">
        <div class="grv-tl-step">Stage 03</div>
        <div class="grv-tl-icon">⚙️</div>
        <div class="grv-tl-name">Investigation</div>
        <div class="grv-tl-days">7 days</div>
        <div class="grv-tl-desc">A thorough investigation is conducted. For complex complaints involving multiple parties or financial transactions, this stage may extend to 14 working days.</div>
      </div>
      <div class="grv-tl-cell">
        <div class="grv-tl-step">Stage 04</div>
        <div class="grv-tl-icon">✅</div>
        <div class="grv-tl-name">Final Resolution</div>
        <div class="grv-tl-days">21 days</div>
        <div class="grv-tl-desc">A written resolution is issued to your email. For simple cases (delivery, product), resolution is typically within 7 working days from acknowledgement.</div>
      </div>
    </div>

    <div class="grv-escalation">
      <div class="grv-esc-icon">⬆️</div>
      <div>
        <div class="grv-esc-title">Escalation Pathway</div>
        <div class="grv-esc-desc">
          If you are not satisfied with the resolution within 21 working days, you may escalate your complaint by emailing <strong>grievance.officer@mfills.in</strong> with your Ticket ID and reason for escalation. If the matter remains unresolved, you may approach the <a href="https://consumerhelpline.gov.in" target="_blank" rel="noopener">National Consumer Helpline (1800-11-4000)</a> or the appropriate Consumer Disputes Redressal Commission.
        </div>
      </div>
    </div>
  </div>

  <div class="grv-divider"></div>

  <!-- ════════════════════════════════
       5. GRIEVANCE OFFICER DETAILS
  ═════════════════════════════════ -->
  <span class="grv-anchor" id="officer"></span>
  <div class="grv-section rv">
    <div class="grv-label">Section 05</div>
    <h2 class="grv-h2">Grievance <em>Officer Details</em></h2>
    <div class="grv-body">
      <p>
        In compliance with Rule 8 of the Consumer Protection (Direct Selling) Rules, 2021, Mfills India Private Limited has appointed a Grievance Officer responsible for receiving, processing, and resolving consumer and MBP complaints.
      </p>
    </div>

    <div class="grv-officer-card">
      <div class="grv-officer-avatar">GO</div>
      <div>
        <div class="grv-officer-name">Grievance Officer</div>
        <div class="grv-officer-role">Appointed under Consumer Protection (Direct Selling) Rules, 2021</div>
        <div class="grv-officer-details">
          <div class="grv-officer-row">
            <span class="grv-officer-row-icon">🏢</span>
            <div>
              <span class="grv-officer-row-label">Organisation</span>
              Mfills India Private Limited
            </div>
          </div>
          <div class="grv-officer-row">
            <span class="grv-officer-row-icon">📍</span>
            <div>
              <span class="grv-officer-row-label">Registered Address</span>
              [Registered Office Address], India
            </div>
          </div>
          <div class="grv-officer-row">
            <span class="grv-officer-row-icon">📧</span>
            <div>
              <span class="grv-officer-row-label">Email (Grievances)</span>
              <a href="mailto:grievance.officer@mfills.in">grievance.officer@mfills.in</a>
            </div>
          </div>
          <div class="grv-officer-row">
            <span class="grv-officer-row-icon">📞</span>
            <div>
              <span class="grv-officer-row-label">Helpline</span>
              +91 XXXXX XXXXX (Mon–Sat, 10 AM – 6 PM)
            </div>
          </div>
          <div class="grv-officer-row">
            <span class="grv-officer-row-icon">⏱️</span>
            <div>
              <span class="grv-officer-row-label">Response Time</span>
              Acknowledgement within 24 hours
            </div>
          </div>
          <div class="grv-officer-row">
            <span class="grv-officer-row-icon">📋</span>
            <div>
              <span class="grv-officer-row-label">Jurisdiction</span>
              All customers &amp; MBPs across India
            </div>
          </div>
        </div>
        <div class="grv-officer-note">
          <strong>📌 How to Contact the Grievance Officer:</strong> Email your complaint with your <strong>Ticket ID, full name, MBPIN/Order ID</strong>, and a clear description of the issue to the address above. For escalated matters, attach a copy of the original resolution received along with your reason for dissatisfaction. The Grievance Officer will respond within <strong>3 working days</strong> of receiving your escalation.
        </div>
      </div>
    </div>

    <div class="grv-legal-ref">
      <div class="grv-legal-ref-icon">⚖️</div>
      <div>
        <div class="grv-legal-ref-title">Legal Compliance Reference</div>
        <div class="grv-legal-ref-desc">
          This grievance mechanism is established in compliance with <strong>Rule 8, Consumer Protection (Direct Selling) Rules, 2021</strong> (Ministry of Consumer Affairs, Food and Public Distribution, Government of India), and the <strong>Consumer Protection Act, 2019</strong>. Consumers retain the right to approach the appropriate Consumer Disputes Redressal Commission at the District, State, or National level if their complaint is not satisfactorily resolved through this mechanism.
        </div>
      </div>
    </div>
  </div>

  <div class="grv-divider"></div>

  <!-- ════════════════════════════════
       6. TRACK COMPLAINT STATUS
  ═════════════════════════════════ -->
  <span class="grv-anchor" id="track"></span>
  <div class="grv-section rv">
    <div class="grv-label">Section 06</div>
    <h2 class="grv-h2">Track Your <em>Complaint Status</em></h2>
    <div class="grv-body">
      <p>Enter your Ticket ID below to view the current status of your complaint. Your Ticket ID was emailed to you when your grievance was submitted (format: GRV-XXXXXXXX).</p>
    </div>

    <div class="grv-track-wrap">
      <div class="grv-track-head">
        <div class="grv-track-head-icon">🔍</div>
        <div>
          <div class="grv-track-head-title">Complaint Tracker</div>
          <div class="grv-track-head-sub">Real-time status updates for your submitted grievances</div>
        </div>
      </div>
      <div class="grv-track-body">
        <form method="GET" action="#track" id="trackForm">
          <input type="hidden" name="track" value="1">
          <div class="grv-track-form">
            <input type="text" name="ticket" class="grv-track-input"
                   placeholder="GRV-XXXXXXXX"
                   maxlength="20"
                   value="<?= htmlspecialchars($_GET['ticket'] ?? '') ?>"
                   autocomplete="off">
            <button type="submit" class="grv-track-btn">🔎 Track</button>
          </div>
        </form>
        <div class="grv-track-demo">
          Try demo tickets:
          <a onclick="document.querySelector('.grv-track-input').value='GRV-DEMO0001'">GRV-DEMO0001</a> (resolved) ·
          <a onclick="document.querySelector('.grv-track-input').value='GRV-DEMO0002'">GRV-DEMO0002</a> (in-progress)
        </div>

        <?php if ($trackError): ?>
        <div class="alert alert-danger" style="margin-top:1.25rem"><?= $trackError ?></div>

        <?php elseif ($trackResult): ?>
        <div class="grv-track-result">
          <div class="grv-track-result-head">
            <div>
              <div class="grv-track-id"><?= htmlspecialchars($trackResult['id']) ?></div>
              <div class="grv-track-subject"><?= htmlspecialchars($trackResult['subject']) ?></div>
            </div>
            <?php
              $statusClass = [
                'resolved'    => 'status-resolved',
                'in-progress' => 'status-in-progress',
                'pending'     => 'status-pending',
                'closed'      => 'status-closed',
              ][$trackResult['status']] ?? 'status-pending';
              $statusLabel = [
                'resolved'    => '✅ Resolved',
                'in-progress' => '🔄 In Progress',
                'pending'     => '⏳ Pending',
                'closed'      => '🔒 Closed',
              ][$trackResult['status']] ?? 'Unknown';
            ?>
            <span class="grv-track-status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
          </div>

          <!-- Progress stepper -->
          <div class="grv-stepper">
            <?php
            $totalSteps = count($trackResult['steps']);
            $lastDone   = -1;
            foreach ($trackResult['steps'] as $si => $step) {
              if ($step['done']) $lastDone = $si;
            }
            $currentIdx = ($lastDone < $totalSteps - 1) ? $lastDone + 1 : -1;
            foreach ($trackResult['steps'] as $si => $step):
              $cls = '';
              if ($step['done'])             $cls = 'done';
              elseif ($si === $currentIdx)   $cls = 'current';
            ?>
            <div class="grv-stepper-step <?= $cls ?>">
              <div class="grv-stepper-dot">
                <?= $step['done'] ? '✓' : ($si === $currentIdx ? '●' : ($si + 1)) ?>
              </div>
              <div class="grv-stepper-label"><?= htmlspecialchars($step['label']) ?></div>
              <div class="grv-stepper-date"><?= htmlspecialchars($step['date']) ?></div>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="grv-track-note">
            📋 <strong>Status Note:</strong> <?= htmlspecialchars($trackResult['note']) ?>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

</div><!-- /.grv-wrap -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
/* ── Scroll reveals ── */
const ro = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (!e.isIntersecting) return;
    e.target.classList.add('on');
    ro.unobserve(e.target);
  });
}, { threshold: .05 });
document.querySelectorAll('.rv').forEach(el => ro.observe(el));

/* ── Active sidenav on scroll ── */
(function () {
  const ids   = ['policy','how-to','submit','timeline','officer','track'];
  const links = document.querySelectorAll('#grvSidenav a');
  const OFF   = 100;
  function setActive() {
    let cur = ids[0];
    ids.forEach(id => {
      const el = document.getElementById(id);
      if (el && window.scrollY >= el.getBoundingClientRect().top + window.scrollY - OFF) cur = id;
    });
    links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + cur));
  }
  window.addEventListener('scroll', setActive, { passive: true });
  setActive();
})();

/* ── Auto-uppercase ticket input ── */
const ti = document.querySelector('.grv-track-input');
if (ti) ti.addEventListener('input', () => { ti.value = ti.value.toUpperCase(); });

/* ── Submit form feedback ── */
const gf = document.getElementById('grvForm');
if (gf) {
  gf.addEventListener('submit', function () {
    const btn = gf.querySelector('.grv-submit');
    btn.textContent = '⏳ Submitting…';
    btn.disabled = true;
  });
}
</script>