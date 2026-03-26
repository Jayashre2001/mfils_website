<?php
// wallet.php — Mfills Wallet (GREEN THEME — matches MShop)
date_default_timezone_set('Asia/Kolkata'); // ✅ IST fix — MUST be first line

$pageTitle = 'Wallet – Mfills';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId = currentUserId();
$pdo    = db();

function freshUser(PDO $pdo, int $id): array {
    $s = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $s->execute([$id]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: [];
}

// ── Convert any datetime string to IST ───────────
function toIST(string $datetime): int {
    try {
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
        return $dt->getTimestamp();
    } catch (Exception $e) {
        return strtotime($datetime) ?: 0;
    }
}

$user = freshUser($pdo, $userId);

if (isset($_GET['demo_topup'])) {
    $topAmt = min(max((float)($_GET['amt'] ?? 0), 0), 10000);
    if ($topAmt > 0) {
        $pdo->prepare("UPDATE users SET wallet = wallet + ? WHERE id = ?")
            ->execute([$topAmt, $userId]);
        $user = freshUser($pdo, $userId);
        setFlash('success', '✅ Demo: ₹' . number_format($topAmt, 2) . ' added for testing.');
    }
    header('Location: wallet.php');
    exit;
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = round((float)filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), 2);
    $method = trim($_POST['method'] ?? '');

    $user          = freshUser($pdo, $userId);
    $walletBalance = (float)($user['wallet'] ?? 0);

    // ── Duplicate pending request check ─────────────────────────────────
    // Agar already ek pending withdrawal hai toh nayi request block karo
    try {
        $dupStmt = $pdo->prepare("
            SELECT id, amount, created_at
            FROM withdrawals
            WHERE user_id = ? AND status = 'pending'
            ORDER BY created_at DESC LIMIT 1
        ");
        $dupStmt->execute([$userId]);
        $existingPending = $dupStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingPending) {
            $errors[] = 'Aapki ek withdrawal request (₹'
                      . number_format((float)$existingPending['amount'], 2)
                      . ') already PENDING hai — submitted on '
                      . date('d M Y \a\t h:i A', toIST($existingPending['created_at']))
                      . '. Pehli request approve/reject hone ke baad hi nayi request karein.';
        }
    } catch (Exception $e) {
        // silently continue if check fails
    }

    if ($amount < 100) {
        $errors[] = 'Minimum withdrawal amount is ₹100.';
    } elseif ($amount > $walletBalance) {
        $errors[] = 'Insufficient wallet balance. Available: ₹' . number_format($walletBalance, 2);
    }

    if ($method === 'bank') {
        $accName   = trim($_POST['acc_name']   ?? '');
        $accNumber = preg_replace('/\D/', '', $_POST['acc_number'] ?? '');
        $ifsc      = strtoupper(trim($_POST['ifsc'] ?? ''));
        $bankName  = trim($_POST['bank_name']  ?? '');

        if (empty($accName))
            $errors[] = 'Account holder name is required.';
        if (!preg_match('/^\d{9,18}$/', $accNumber))
            $errors[] = 'Enter a valid account number (9–18 digits).';
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc))
            $errors[] = 'Enter a valid IFSC code (e.g. SBIN0001234).';
        if (empty($bankName))
            $errors[] = 'Bank name is required.';

    } elseif ($method === 'upi') {
        $upiId = trim($_POST['upi_id'] ?? '');
        if (!preg_match('/^[\w.\-+]+@[\w.\-]+$/', $upiId))
            $errors[] = 'Enter a valid UPI ID (e.g. name@okaxis).';

    } else {
        $errors[] = 'Please select a withdrawal method (Bank or UPI).';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare("SELECT wallet FROM users WHERE id = ? FOR UPDATE");
            $lockStmt->execute([$userId]);
            $lockedWallet = (float)($lockStmt->fetchColumn() ?? 0);

            if ($amount > $lockedWallet) {
                $pdo->rollBack();
                $errors[] = 'Balance changed during request. Please try again.';
            } else {
                $detail = ($method === 'bank')
                    ? json_encode([
                        'acc_name'   => $accName,
                        'acc_number' => $accNumber,
                        'ifsc'       => $ifsc,
                        'bank_name'  => $bankName,
                      ])
                    : json_encode(['upi_id' => $upiId]);

                $pdo->prepare("
                    INSERT INTO withdrawals (user_id, amount, method, account_details, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
                ")->execute([$userId, $amount, $method, $detail]);

                $pdo->commit();

                $user    = freshUser($pdo, $userId);
                $_POST   = [];
                $success = 'Withdrawal request of ₹' . number_format($amount, 2) . ' submitted! Processing in 2–3 business days.';
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Wallet withdrawal PDO error [uid=' . $userId . ']: ' . $e->getMessage());
            $errors[] = 'A database error occurred. Please try again later.';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Wallet withdrawal error [uid=' . $userId . ']: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$walletBalance = (float)($user['wallet'] ?? 0);

try {
    $stmt = $pdo->prepare(
        'SELECT c.commission_amt, c.level, c.created_at,
                u.username AS from_user, p.name AS product
         FROM commissions c
         JOIN orders   o ON o.id = c.order_id
         JOIN users    u ON u.id = c.buyer_id
         JOIN products p ON p.id = o.product_id
         WHERE c.beneficiary_id = ?
         ORDER BY c.created_at DESC LIMIT 15'
    );
    $stmt->execute([$userId]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $credits = []; }

try {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE beneficiary_id = ?');
    $stmt->execute([$userId]);
    $lifetime = (float)$stmt->fetchColumn();
} catch (Exception $e) { $lifetime = 0; }

try {
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(commission_amt),0) FROM commissions
         WHERE beneficiary_id = ?
           AND YEAR(created_at) = YEAR(NOW())
           AND MONTH(created_at) = MONTH(NOW())'
    );
    $stmt->execute([$userId]);
    $monthPsb = (float)$stmt->fetchColumn();
} catch (Exception $e) { $monthPsb = 0; }

try {
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$userId]);
    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $withdrawals = []; }

try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id = ? AND status = 'approved'");
    $stmt->execute([$userId]);
    $totalWithdrawn = (float)$stmt->fetchColumn();
} catch (Exception $e) { $totalWithdrawn = 0; }

try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pendingAmount = (float)$stmt->fetchColumn();
} catch (Exception $e) { $pendingAmount = 0; }

// ── Single pending request details (for frontend warning) ──
try {
    $chkStmt = $pdo->prepare("SELECT id, amount, created_at FROM withdrawals WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $chkStmt->execute([$userId]);
    $pendingRequest = $chkStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Exception $e) { $pendingRequest = null; }

include __DIR__ . '/includes/header.php';
?>

<style>
/* ══════════════════════════════════════════
   COLOR SYSTEM — matches MShop exactly
══════════════════════════════════════════ */
:root {
  --green-dd:  #0e2414;
  --green-d:   #1a3b22;
  --green-m:   #2a6336;
  --green-l:   #3a8a4a;
  --green-ll:  #5db870;
  --gold:      #c8922a;
  --gold-l:    #e0aa40;
  --gold-d:    #a0721a;
  --jade:      #0F7B5C;
  --jade-l:    #14A376;
  --jade-ll:   #1CC48D;
  --coral:     #E8534A;
  --ivory:     #f8f5ef;
  --ivory-d:   #ede8de;
  --ivory-dd:  #ddd5c4;
  --ink:       #152018;
  --muted:     #5a7a60;
}

html, body { background: var(--ivory) !important; }
*, *::before, *::after { box-sizing: border-box; }

/* ── Page header ── */
.page-header {
  background: linear-gradient(135deg, var(--green-dd) 0%, var(--green-d) 55%, var(--green-m) 100%);
  padding: 2.5rem 0 2rem;
  position: relative; overflow: hidden;
  border-bottom: 3px solid var(--gold);
}
.page-header::before {
  content: ''; position: absolute; inset: 0;
  background-image: radial-gradient(circle, rgba(200,146,42,.08) 1.5px, transparent 1.5px);
  background-size: 24px 24px; pointer-events: none;
}
.ph-arc { position: absolute; border-radius: 50%; border: 2px solid rgba(200,146,42,.1); pointer-events: none; }
.ph-arc-1 { width: 300px; height: 200px; bottom: -70px; left: -50px; }
.ph-arc-2 { width: 260px; height: 260px; top: -90px; right: -70px; border-color: rgba(15,123,92,.1); }
.page-header .container { position: relative; z-index: 1; }
.page-header h1 {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 900; color: #fff; margin-bottom: .25rem;
}
.page-header h1 em { color: var(--gold-l); font-style: italic; }
.page-header p { color: rgba(255,255,255,.52); font-size: .88rem; }
.page-wrap { background: var(--ivory); padding-bottom: 3rem; }

/* ── Wallet hero ── */
.wallet-hero {
  background: linear-gradient(135deg, var(--green-dd) 0%, var(--green-d) 55%, var(--green-m) 100%);
  border-radius: 16px; padding: 2rem 2rem 1.75rem;
  color: #fff; position: relative; overflow: hidden;
  box-shadow: 0 8px 40px rgba(14,36,20,.35); margin-bottom: 2rem;
  border: 1.5px solid rgba(200,146,42,.18);
}
.wallet-hero::before {
  content: ''; position: absolute; right: -60px; top: -60px;
  width: 260px; height: 260px; border-radius: 50%;
  background: rgba(200,146,42,.13); filter: blur(60px);
  animation: blobDrift 14s ease-in-out infinite; pointer-events: none;
}
.wallet-hero::after {
  content: ''; position: absolute; bottom: -50px; left: -40px;
  width: 200px; height: 200px; border-radius: 50%;
  background: rgba(15,123,92,.1); filter: blur(55px);
  animation: blobDrift 18s 3s ease-in-out infinite reverse; pointer-events: none;
}
@keyframes blobDrift {
  0%,100% { transform: translate(0,0) scale(1) }
  33%      { transform: translate(18px,-14px) scale(1.04) }
  66%      { transform: translate(-12px,18px) scale(.97) }
}
.wallet-hero .wh-label {
  font-size: .72rem; text-transform: uppercase; letter-spacing: .1em;
  opacity: .6; font-weight: 700; margin-bottom: .35rem;
  position: relative; z-index: 1; font-family: 'Nunito', sans-serif;
}
.wallet-hero .wh-amount {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: 2.8rem; font-weight: 900;
  color: var(--gold-l); line-height: 1.1; position: relative; z-index: 1;
}
.wallet-hero .wh-sub {
  font-size: .82rem; opacity: .55; margin-top: .35rem;
  position: relative; z-index: 1; font-family: 'Nunito', sans-serif;
}
.pending-notice {
  background: rgba(200,146,42,.15);
  border: 1px solid rgba(200,146,42,.35);
  border-radius: 10px; padding: .6rem 1rem; margin-top: .85rem;
  font-size: .78rem; color: var(--gold-l); font-family: 'Nunito', sans-serif;
  position: relative; z-index: 1; display: flex; align-items: center; gap: .5rem;
}
.wallet-hero-demo {
  margin-top: .85rem; display: flex; gap: .5rem;
  flex-wrap: wrap; position: relative; z-index: 1; align-items: center;
}
.wallet-hero-demo span { font-size: .7rem; opacity: .55; }
.wallet-hero-demo a {
  background: rgba(200,146,42,.2); color: var(--gold-l);
  border: 1px solid rgba(200,146,42,.38); border-radius: 20px;
  padding: .22rem .8rem; font-size: .73rem; font-weight: 700;
  text-decoration: none; transition: background .2s; font-family: 'Nunito', sans-serif;
}
.wallet-hero-demo a:hover { background: rgba(200,146,42,.38); }
.wallet-hero-stats {
  display: flex; gap: 2rem; flex-wrap: wrap;
  border-top: 1px solid rgba(200,146,42,.2);
  padding-top: 1rem; margin-top: 1rem; position: relative; z-index: 1;
}
.wallet-hero-stat .stat-v {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: 1.2rem; font-weight: 700; color: var(--gold-l);
}
.wallet-hero-stat .stat-l {
  font-size: .68rem; opacity: .55;
  text-transform: uppercase; letter-spacing: .06em; font-family: 'Nunito', sans-serif;
}

/* ── Section divider ── */
.section-divider {
  display: flex; align-items: center; gap: 1rem;
  margin: 2rem 0 1.25rem;
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: 1.15rem; color: var(--green-d); font-weight: 700;
}
.section-divider::after { content: ''; flex: 1; height: 2px; background: var(--ivory-dd); }

/* ── Method tabs ── */
.method-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: 1.5rem; }
.method-tab { position: relative; cursor: pointer; }
.method-tab input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
.method-tab-label {
  display: flex; align-items: center; gap: .75rem;
  padding: .9rem 1.1rem; border: 2px solid var(--ivory-dd);
  border-radius: 12px; background: #fff; cursor: pointer;
  transition: all .2s; font-weight: 700; font-size: .875rem; font-family: 'Nunito', sans-serif;
}
.method-tab-label:hover { border-color: var(--gold); background: #FFFDF5; }
.method-tab input:checked + .method-tab-label {
  border-color: var(--green-d);
  background: linear-gradient(135deg, #e8f2ea, #d4e8d8);
  color: var(--green-d);
  box-shadow: 0 0 0 3px rgba(26,59,34,.1);
}
.method-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; background: var(--ivory-d); flex-shrink: 0; transition: background .2s;
}
.method-tab input:checked + .method-tab-label .method-icon { background: var(--green-d); }
.method-tab-text { line-height: 1.25; }
.method-tab-text small { font-size: .72rem; color: var(--muted); font-weight: 400; display: block; }
.method-tab input:checked + .method-tab-label .method-tab-text small { color: var(--green-m); }

/* ── Form panels ── */
.form-panel { display: none; animation: fadeUp .22s ease; }
.form-panel.active { display: block; }
@keyframes fadeUp { from { opacity:0; transform:translateY(6px) } to { opacity:1; transform:translateY(0) } }

/* ── Amount input ── */
.amount-wrap { position: relative; }
.amount-wrap .currency-prefix {
  position: absolute; left: .9rem; top: 50%; transform: translateY(-50%);
  font-family: 'Cinzel', 'Georgia', serif; font-size: 1.1rem;
  color: var(--green-d); font-weight: 700; pointer-events: none;
}
.amount-wrap .form-control {
  padding-left: 2rem; font-size: 1.05rem; font-weight: 700;
  font-family: 'Cinzel', 'Georgia', serif;
}
.quick-amounts { display: flex; gap: .45rem; flex-wrap: wrap; margin-top: .55rem; }
.quick-amt {
  background: var(--ivory-d); border: 1.5px solid transparent;
  border-radius: 20px; padding: .22rem .7rem; font-size: .78rem;
  font-weight: 700; cursor: pointer; color: var(--green-d);
  transition: all .15s; font-family: 'Nunito', sans-serif; user-select: none;
}
.quick-amt:hover { border-color: var(--gold); background: #FFFDF5; color: var(--gold-d); }
.quick-amt.disabled-amt { opacity: .35; cursor: not-allowed; pointer-events: none; }

/* ── Balance bar ── */
.balance-bar {
  background: linear-gradient(135deg, #e8f2ea, #d4e8d8);
  border: 1.5px solid rgba(26,59,34,.2);
  border-radius: 10px; padding: .7rem 1rem;
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 1.1rem; font-family: 'Nunito', sans-serif;
}
.balance-bar .bl { font-size: .75rem; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.balance-bar .bv { font-family: 'Cinzel', 'Georgia', serif; font-weight: 700; color: var(--green-d); font-size: 1.05rem; }

/* ── Submit button ── */
.btn-withdraw {
  width: 100%;
  background: linear-gradient(135deg, var(--green-dd), var(--green-d));
  color: #fff; padding: .85rem; font-size: 1rem; font-weight: 800;
  border-radius: 10px; border: none; cursor: pointer; transition: all .2s;
  display: flex; align-items: center; justify-content: center; gap: .5rem;
  font-family: 'Nunito', sans-serif; letter-spacing: .03em; margin-top: 1.25rem;
  box-shadow: 0 4px 16px rgba(14,36,20,.28);
}
.btn-withdraw:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--green-d), var(--green-m));
  transform: translateY(-1px);
  box-shadow: 0 6px 24px rgba(14,36,20,.38);
}
.btn-withdraw:disabled { opacity: .4; cursor: not-allowed; transform: none !important; }

/* ── Info note ── */
.info-note {
  background: linear-gradient(135deg, #e8f2ea, #d4e8d8);
  border: 1.5px solid rgba(26,59,34,.18);
  border-radius: 10px; padding: .8rem 1rem;
  font-size: .8rem; color: var(--green-d);
  display: flex; gap: .55rem; align-items: flex-start;
  margin-top: .9rem; font-family: 'Nunito', sans-serif;
}
.info-note-icon { font-size: .95rem; flex-shrink: 0; margin-top: .05rem; }

/* ── Cards ── */
.card {
  background: #fff; border-radius: 16px;
  border: 1.5px solid var(--ivory-dd);
  box-shadow: 0 2px 12px rgba(26,59,34,.07); overflow: hidden;
}
.card-header {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: .95rem; font-weight: 700; color: var(--green-d);
  padding: 1rem 1.25rem; border-bottom: 1.5px solid var(--ivory-dd);
  background: var(--ivory-d);
  display: flex; align-items: center; gap: .5rem;
}
.card-body { padding: 1.25rem; }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

/* ── Form controls ── */
.form-group { margin-bottom: 1.1rem; }
.form-label {
  display: block; font-size: .78rem; font-weight: 800; color: var(--ink);
  margin-bottom: .45rem; text-transform: uppercase; letter-spacing: .05em;
  font-family: 'Nunito', sans-serif;
}
.form-control {
  width: 100%; padding: .65rem .9rem;
  border: 1.5px solid var(--ivory-dd); border-radius: 8px;
  background: #fff; font-family: 'Nunito', sans-serif;
  font-size: .9rem; color: var(--ink); outline: none;
  transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
}
.form-control:focus { border-color: var(--green-l); box-shadow: 0 0 0 3px rgba(26,59,34,.1); }
.form-control.is-valid   { border-color: var(--jade-ll) !important; }
.form-control.is-invalid { border-color: var(--coral)   !important; }
.form-hint { font-size: .75rem; color: var(--muted); margin-top: .35rem; font-family: 'Nunito', sans-serif; }
.form-err  { font-size: .75rem; color: var(--coral); margin-top: .35rem; font-family: 'Nunito', sans-serif; display: none; }

/* ── Badges / status ── */
.badge {
  display: inline-block; border-radius: 20px;
  font-size: .72rem; font-weight: 800; padding: .2rem .65rem;
  font-family: 'Nunito', sans-serif;
}
.badge-green  { background: rgba(26,59,34,.1);  color: var(--green-d); }
.badge-jade   { background: rgba(15,123,92,.12); color: var(--jade); }
.status-pending   { background: rgba(200,146,42,.15); color: var(--gold-d); }
.status-approved  { background: rgba(15,123,92,.12);  color: var(--jade); }
.status-processed { background: rgba(26,59,34,.12);   color: var(--green-d); }
.status-rejected  { background: rgba(232,83,74,.12);  color: var(--coral); }

/* ── Tables ── */
.table-wrap { overflow-x: auto; }
.table-wrap table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.table-wrap thead th {
  padding: .65rem 1rem; text-align: left;
  font-size: .68rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: .08em; color: var(--muted);
  background: var(--ivory-d); border-bottom: 1.5px solid var(--ivory-dd);
}
.table-wrap tbody tr { border-bottom: 1px solid var(--ivory-d); transition: background .15s; }
.table-wrap tbody tr:last-child { border-bottom: none; }
.table-wrap tbody tr:hover { background: var(--ivory-d); }
.table-wrap tbody td { padding: .75rem 1rem; vertical-align: middle; color: var(--ink); }
.credit-amt { color: var(--jade); font-weight: 800; font-size: 1rem; }
.credit-amt::before { content: '+'; }
.lvl-badge-sm { font-size: .7rem; font-weight: 800; border-radius: 20px; padding: .15rem .55rem; display: inline-block; }

/* ── Alerts ── */
.alert { padding: .85rem 1.1rem; border-radius: 10px; font-size: .875rem; font-weight: 600; margin-bottom: 1rem; font-family: 'Nunito', sans-serif; }
.alert-success { background: rgba(15,123,92,.1);  color: var(--jade);   border-left: 3px solid var(--jade-ll); }
.alert-danger  { background: rgba(232,83,74,.1);  color: #9A1A09;       border-left: 3px solid var(--coral); }
.alert-info    { background: rgba(26,59,34,.08);  color: var(--green-d); border-left: 3px solid var(--green-l); }

/* ── Empty states ── */
.empty-state { text-align: center; padding: 2.5rem 1rem; color: var(--muted); font-family: 'Nunito', sans-serif; }
.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: .75rem; }
.empty-state p { font-size: .88rem; margin: 0; }

/* ── Pending block banner ── */
.pending-block-banner {
  display: flex; gap: 1rem; align-items: flex-start;
  background: linear-gradient(135deg, #fff8ed, #fff3e0);
  border: 2px solid var(--gold);
  border-radius: 14px; padding: 1.25rem 1.2rem;
  box-shadow: 0 4px 18px rgba(200,146,42,.18);
}
.pbb-icon {
  font-size: 2rem; flex-shrink: 0; line-height: 1;
  margin-top: .1rem;
  animation: pbbPulse 2s ease-in-out infinite;
}
@keyframes pbbPulse {
  0%,100% { transform: scale(1); }
  50%      { transform: scale(1.15); }
}
.pbb-body { flex: 1; }
.pbb-title {
  font-family: 'Cinzel', 'Georgia', serif;
  font-size: 1rem; font-weight: 900; color: var(--gold-d);
  margin-bottom: .4rem;
}
.pbb-msg {
  font-size: .85rem; color: #7a4f10; line-height: 1.6;
  font-family: 'Nunito', sans-serif;
}
.pbb-date {
  font-size: .78rem; color: var(--muted);
  background: rgba(200,146,42,.1); border-radius: 6px;
  padding: .1rem .45rem; display: inline-block; margin-top: .3rem;
}
.pbb-sub {
  margin-top: .6rem; font-size: .78rem; color: var(--muted);
  font-family: 'Nunito', sans-serif;
  border-top: 1px solid rgba(200,146,42,.2); padding-top: .5rem;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .wallet-hero .wh-amount { font-size: 2.1rem; }
  .wallet-hero { padding: 1.5rem 1.25rem; }
  .wallet-hero-stats { gap: 1.25rem; }
  .grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
  .method-tabs { grid-template-columns: 1fr; }
  .wallet-hero .wh-amount { font-size: 1.8rem; }
  .wallet-hero-stats { gap: .9rem; }
}
</style>

<div class="page-header">
  <div class="ph-arc ph-arc-1"></div>
  <div class="ph-arc ph-arc-2"></div>
  <div class="container">
    <h1>💰 My <em>Wallet</em></h1>
    <p>Track your PSB earnings &amp; withdraw funds</p>
  </div>
</div>

<div class="container page-wrap" style="padding-top:1.5rem">

  <?php if (!empty($success)): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $err): ?>
        <div>⚠ <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Wallet Hero -->
  <div class="wallet-hero">
    <div class="wh-label">Current Wallet Balance</div>
    <div class="wh-amount">₹<?= number_format($walletBalance, 2) ?></div>
    <div class="wh-sub">
      Member since <?= date('d M Y', strtotime($user['created_at'])) ?>
      &nbsp;·&nbsp; Code: <strong><?= htmlspecialchars($user['referral_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>

    <?php if ($pendingAmount > 0): ?>
    <div class="pending-notice">
      ⏳ <strong>₹<?= number_format($pendingAmount, 2) ?></strong> pending approval — will be deducted once admin approves.
    </div>
    <?php endif; ?>

    <!-- DEMO buttons — remove before going live -->
    <div class="wallet-hero-demo">
      <span>Demo:</span>
      <a href="?demo_topup=1&amt=500">＋₹500</a>
      <a href="?demo_topup=1&amt=1000">＋₹1,000</a>
      <a href="?demo_topup=1&amt=5000">＋₹5,000</a>
    </div>

    <div class="wallet-hero-stats">
      <div class="wallet-hero-stat">
        <div class="stat-v">₹<?= number_format($lifetime, 2) ?></div>
        <div class="stat-l">Lifetime PSB Earned</div>
      </div>
      <div class="wallet-hero-stat">
        <div class="stat-v">₹<?= number_format($monthPsb, 2) ?></div>
        <div class="stat-l">This Month PSB</div>
      </div>
      <div class="wallet-hero-stat">
        <div class="stat-v">₹<?= number_format($totalWithdrawn, 2) ?></div>
        <div class="stat-l">Total Withdrawn</div>
      </div>
      <div class="wallet-hero-stat">
        <div class="stat-v">₹<?= number_format($pendingAmount, 2) ?></div>
        <div class="stat-l">Pending Approval</div>
      </div>
    </div>
  </div>

  <!-- Withdraw Section -->
  <div class="section-divider">🏧 Withdraw Funds</div>

  <div class="grid-2" style="align-items:start; margin-bottom:2.5rem">

    <!-- Withdrawal form card -->
    <div class="card">
      <div class="card-header">🏧 Request Withdrawal</div>
      <div class="card-body">

        <?php if ($pendingRequest): ?>
          <!-- ⛔ PENDING REQUEST BLOCKER -->
          <div class="pending-block-banner">
            <div class="pbb-icon">⏳</div>
            <div class="pbb-body">
              <div class="pbb-title">Withdrawal Request Pending</div>
              <div class="pbb-msg">
                Aapki <strong>₹<?= number_format((float)$pendingRequest['amount'], 2) ?></strong> ki withdrawal request
                already pending hai.<br>
                <span class="pbb-date">📅 Submitted: <?= date('d M Y \a\t h:i A', toIST($pendingRequest['created_at'])) ?></span>
              </div>
              <div class="pbb-sub">Admin approve ya reject karne ke baad hi nayi request kar sakte hain.</div>
            </div>
          </div>
        <?php elseif ($walletBalance < 100): ?>
          <div class="alert alert-info" style="margin:0">
            💡 Your balance (₹<?= number_format($walletBalance, 2) ?>) is below the minimum of ₹100.
            Earn more PSB commissions to withdraw.
          </div>
        <?php else: ?>

        <form method="POST" id="withdrawForm" novalidate>

          <div class="balance-bar">
            <span class="bl">Available Balance</span>
            <span class="bv">₹<?= number_format($walletBalance, 2) ?></span>
          </div>

          <div class="form-group">
            <label class="form-label" for="amountInput">Withdrawal Amount</label>
            <div class="amount-wrap">
              <span class="currency-prefix">₹</span>
              <input type="number" name="amount" id="amountInput"
                class="form-control"
                placeholder="Enter amount"
                min="100"
                max="<?= floor($walletBalance) ?>"
                step="1"
                value="<?= htmlspecialchars($_POST['amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="off">
            </div>
            <div class="quick-amounts">
              <?php foreach ([500, 1000, 2000, 5000] as $qv): ?>
                <span class="quick-amt <?= $qv > $walletBalance ? 'disabled-amt' : '' ?>"
                  <?= $qv <= $walletBalance ? "onclick=\"setAmount($qv)\"" : '' ?>>
                  ₹<?= number_format($qv) ?>
                </span>
              <?php endforeach; ?>
              <span class="quick-amt" onclick="setAmount(<?= floor($walletBalance) ?>)">
                Max ₹<?= number_format(floor($walletBalance)) ?>
              </span>
            </div>
            <div class="form-err" id="amountError"></div>
          </div>

          <div class="form-group">
            <label class="form-label">Withdrawal Method</label>
            <div class="method-tabs">
              <label class="method-tab">
                <input type="radio" name="method" value="bank"
                  onchange="switchMethod('bank')"
                  <?= (($_POST['method'] ?? '') === 'bank') ? 'checked' : '' ?>>
                <span class="method-tab-label">
                  <span class="method-icon">🏦</span>
                  <span class="method-tab-text">Bank Transfer<small>NEFT / IMPS</small></span>
                </span>
              </label>
              <label class="method-tab">
                <input type="radio" name="method" value="upi"
                  onchange="switchMethod('upi')"
                  <?= (($_POST['method'] ?? '') === 'upi') ? 'checked' : '' ?>>
                <span class="method-tab-label">
                  <span class="method-icon">📱</span>
                  <span class="method-tab-text">UPI Transfer<small>Instant</small></span>
                </span>
              </label>
            </div>
          </div>

          <!-- Bank Panel -->
          <div class="form-panel <?= (($_POST['method'] ?? '') === 'bank') ? 'active' : '' ?>" id="panel-bank">
            <div class="form-group">
              <label class="form-label" for="acc_name">Account Holder Name</label>
              <input type="text" name="acc_name" id="acc_name" class="form-control"
                placeholder="As per bank records"
                value="<?= htmlspecialchars($_POST['acc_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="acc_number">Account Number</label>
              <input type="text" name="acc_number" id="acc_number" class="form-control"
                placeholder="9–18 digit account number"
                value="<?= htmlspecialchars($_POST['acc_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                inputmode="numeric" maxlength="18">
            </div>
            <div class="form-group">
              <label class="form-label" for="ifsc">IFSC Code</label>
              <input type="text" name="ifsc" id="ifsc" class="form-control"
                placeholder="e.g. SBIN0001234"
                value="<?= htmlspecialchars(strtoupper($_POST['ifsc'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                style="text-transform:uppercase" maxlength="11">
              <p class="form-hint">11-character code on your cheque book / passbook</p>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label" for="bank_name">Bank Name</label>
              <input type="text" name="bank_name" id="bank_name" class="form-control"
                placeholder="e.g. State Bank of India"
                value="<?= htmlspecialchars($_POST['bank_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <!-- UPI Panel -->
          <div class="form-panel <?= (($_POST['method'] ?? '') === 'upi') ? 'active' : '' ?>" id="panel-upi">
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label" for="upi_id">UPI ID</label>
              <input type="text" name="upi_id" id="upi_id" class="form-control"
                placeholder="yourname@okaxis  /  yourname@ybl"
                value="<?= htmlspecialchars($_POST['upi_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <p class="form-hint">✔ Works with PhonePe, GPay, Paytm, BHIM &amp; all UPI apps</p>
            </div>
          </div>

          <div class="info-note">
            <span class="info-note-icon">ℹ️</span>
            <span>Your balance will be deducted only when admin <strong>approves</strong> your request. Processed in 2–3 business days. Min ₹100.</span>
          </div>

          <button type="submit" class="btn-withdraw" id="submitBtn" disabled>
            🏧 Submit Withdrawal Request
          </button>

        </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Withdrawal history card -->
    <div class="card">
      <div class="card-header">
        📋 Withdrawal History
        <span class="badge badge-green"><?= count($withdrawals) ?></span>
      </div>
      <?php if (empty($withdrawals)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <p>No withdrawal requests yet.<br>Submit your first request.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($withdrawals as $w):
            $det = [];
            if (!empty($w['account_details'])) $det = json_decode($w['account_details'], true) ?? [];
            $methodLabel = ($w['method'] === 'bank')
              ? '🏦 ' . htmlspecialchars($det['bank_name'] ?? 'Bank', ENT_QUOTES, 'UTF-8')
              : '📱 ' . htmlspecialchars($det['upi_id']   ?? 'UPI',  ENT_QUOTES, 'UTF-8');
            $wts    = !empty($w['created_at']) ? toIST($w['created_at']) : null;
            $status = strtolower($w['status'] ?? 'pending');
          ?>
          <tr>
            <td style="font-size:.8rem;color:var(--muted);white-space:nowrap">
              <?php if ($wts): ?>
                <?= date('d M Y', $wts) ?>
                <br><span style="font-size:.7rem;color:var(--jade)"><?= date('h:i A', $wts) ?></span>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td style="font-family:'Cinzel','Georgia',serif;font-weight:700;color:var(--green-d);white-space:nowrap">
              ₹<?= number_format((float)$w['amount'], 2) ?>
            </td>
            <td style="font-size:.82rem"><?= $methodLabel ?></td>
            <td>
              <span class="badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <?= ucfirst(htmlspecialchars($status, ENT_QUOTES, 'UTF-8')) ?>
              </span>
              <?php if ($status === 'rejected' && !empty($w['admin_note'])): ?>
                <div style="font-size:.68rem;color:var(--coral);margin-top:2px">
                  <?= htmlspecialchars($w['admin_note'], ENT_QUOTES, 'UTF-8') ?>
                </div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- PSB Credit History -->
  <div class="section-divider">💸 PSB Credit History</div>

  <div class="card">
    <div class="card-header">
      Recent PSB Credits
      <span class="badge badge-jade"><?= count($credits) ?></span>
    </div>
    <?php if (empty($credits)): ?>
      <div class="empty-state">
        <div class="empty-icon">🔗</div>
        <p>No PSB credits yet.<br>Share your referral link to start earning commissions!</p>
      </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date &amp; Time</th>
            <th>From (Buyer)</th>
            <th>Product</th>
            <th>Level</th>
            <th>PSB Amount</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $lvlColors = [
          1 => ['bg'=>'rgba(200,146,42,.15)', 'fg'=>'#a0721a'],
          2 => ['bg'=>'rgba(26,59,34,.13)',   'fg'=>'#1a3b22'],
          3 => ['bg'=>'rgba(15,123,92,.12)',  'fg'=>'#0F7B5C'],
          4 => ['bg'=>'rgba(19,160,119,.1)',  'fg'=>'#13a077'],
          5 => ['bg'=>'rgba(232,83,74,.1)',   'fg'=>'#E8534A'],
          6 => ['bg'=>'rgba(90,122,96,.1)',   'fg'=>'#5a7a60'],
          7 => ['bg'=>'rgba(90,122,96,.1)',   'fg'=>'#5a7a60'],
        ];
        foreach ($credits as $c):
          $lc = $lvlColors[(int)$c['level']] ?? $lvlColors[7];
        ?>
        <tr>
          <td style="font-size:.8rem;white-space:nowrap">
            <span style="color:var(--muted)"><?= date('d M Y', toIST($c['created_at'])) ?></span>
            <br><span style="font-size:.7rem;color:var(--jade)"><?= date('h:i A', toIST($c['created_at'])) ?></span>
          </td>
          <td style="font-weight:600"><?= htmlspecialchars($c['from_user'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="font-size:.83rem"><?= htmlspecialchars($c['product'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <span class="lvl-badge-sm" style="background:<?= $lc['bg'] ?>;color:<?= $lc['fg'] ?>">
              Lvl <?= (int)$c['level'] ?>
            </span>
          </td>
          <td>
            <span class="credit-amt">₹<?= number_format((float)$c['commission_amt'], 2) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
(function () {
  'use strict';

  var form        = document.getElementById('withdrawForm');
  var amountInput = document.getElementById('amountInput');
  var submitBtn   = document.getElementById('submitBtn');
  var amountError = document.getElementById('amountError');
  var maxBalance  = amountInput ? (parseFloat(amountInput.getAttribute('max')) || 0) : 0;

  window.switchMethod = function (m) {
    document.querySelectorAll('.form-panel').forEach(function (p) { p.classList.remove('active'); });
    var panel = document.getElementById('panel-' + m);
    if (panel) panel.classList.add('active');
    validateForm();
  };

  window.setAmount = function (val) {
    if (!amountInput) return;
    var v = Math.min(parseFloat(val) || 0, maxBalance);
    amountInput.value = v > 0 ? Math.floor(v) : '';
    validateForm();
    amountInput.focus();
  };

  function validateForm() {
    if (!submitBtn) return;
    var amount   = parseFloat(amountInput ? amountInput.value : 0) || 0;
    var methodEl = document.querySelector('input[name="method"]:checked');
    var methodOk = (methodEl !== null);
    var amountOk = false;
    var errMsg   = '';

    if (amount > 0) {
      if (amount < 100) {
        errMsg = '⚠ Minimum withdrawal ₹100 hai.';
      } else if (maxBalance > 0 && amount > maxBalance) {
        errMsg = '⚠ Balance se zyada — Available: ₹' + maxBalance.toLocaleString('en-IN', { minimumFractionDigits: 2 });
      } else {
        amountOk = true;
      }
    }

    if (amountError) {
      amountError.textContent = errMsg;
      amountError.style.display = (errMsg && amount > 0) ? 'block' : 'none';
    }

    if (amountInput) {
      amountInput.classList.remove('is-valid', 'is-invalid');
      if (amount > 0) amountInput.classList.add(amountOk ? 'is-valid' : 'is-invalid');
    }

    if (amountOk && !methodOk) {
      submitBtn.textContent = '👆 Pehle method chunein (Bank / UPI)';
    } else {
      submitBtn.innerHTML = '🏧 Submit Withdrawal Request';
    }

    submitBtn.disabled = !(amountOk && methodOk);
  }

  if (amountInput) {
    ['input', 'change', 'keyup'].forEach(function (ev) { amountInput.addEventListener(ev, validateForm); });
    amountInput.addEventListener('paste', function () { setTimeout(validateForm, 60); });
  }

  document.querySelectorAll('input[name="method"]').forEach(function (r) { r.addEventListener('change', validateForm); });

  if (form) {
    form.addEventListener('submit', function (e) {
      if (submitBtn && submitBtn.disabled) { e.preventDefault(); return false; }
      if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '⏳ Submitting...'; }
    });
  }

  var ifscEl = document.getElementById('ifsc');
  if (ifscEl) {
    ifscEl.addEventListener('input', function () {
      var pos = this.selectionStart;
      this.value = this.value.toUpperCase();
      try { this.setSelectionRange(pos, pos); } catch (e) {}
    });
  }

  var accEl = document.getElementById('acc_number');
  if (accEl) { accEl.addEventListener('input', function () { this.value = this.value.replace(/\D/g, ''); }); }

  var preChecked = document.querySelector('input[name="method"]:checked');
  if (preChecked) switchMethod(preChecked.value);
  validateForm();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>