<?php
// includes/functions.php

require_once __DIR__ . '/config.php';

date_default_timezone_set('Asia/Kolkata');

// ── Session ───────────────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}
function isLoggedIn(): bool { startSession(); return !empty($_SESSION['user_id']); }
function currentUserId(): ?int { startSession(); return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; }
function requireLogin(): void {
    if (!isLoggedIn()) { header('Location: ' . APP_URL . '/login.php'); exit; }
}
function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    { $m=floor($diff/60);   return $m.' minute'.($m>1?'s':'').' ago'; }
    if ($diff < 86400)   { $h=floor($diff/3600);  return $h.' hour'.($h>1?'s':'').' ago'; }
    if ($diff < 2592000) { $d=floor($diff/86400); return $d.' day'.($d>1?'s':'').' ago'; }
    return date('d M Y', strtotime($datetime));
}

// ══════════════════════════════════════════════════════════════
//  REGISTER USER
// ══════════════════════════════════════════════════════════════
function registerUser(string $username, string $email, string $password, ?string $referralCode = null, string $fullName = ''): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) return ['success' => false, 'message' => 'Username or email already exists.'];

    $referrerId = null; $referrerName = '';
    if ($referralCode) {
        $r = $pdo->prepare('SELECT id, username FROM users WHERE referral_code = ?');
        $r->execute([trim($referralCode)]);
        $ref = $r->fetch();
        if (!$ref) return ['success' => false, 'message' => 'Invalid referral code.'];
        $referrerId = (int)$ref['id']; $referrerName = (string)$ref['username'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $code = generateReferralCode($username);
    try {
        $pdo->prepare('INSERT INTO users (username, email, password, full_name, referrer_id, referral_code) VALUES (?, ?, ?, ?, ?, ?)')->execute([$username, $email, $hash, $fullName, $referrerId, $code]);
        $newId = (int)$pdo->lastInsertId();
    } catch (\Throwable $e) {
        error_log('registerUser INSERT failed: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }

    $mbpin = 'MBP-' . date('Y') . '-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
    try { $pdo->prepare('UPDATE users SET mbpin = ? WHERE id = ?')->execute([$mbpin, $newId]); } catch (\Throwable $e) {}
    try { createUserIdCard($newId); } catch (\Throwable $e) {}
    try {
        createNotification($newId, 'system', 'Welcome to Mfills!',
            'Thank you for joining. Purchase 2500 BV to activate your Business Club membership and unlock all benefits!',
            '/dashboard.php', false);
    } catch (\Throwable $e) {}

    return ['success' => true, 'message' => 'Registration successful! Please login.', 'mbpin' => $mbpin, 'referral_code' => $code, 'referrer_name' => $referrerName];
}

// ── Login ─────────────────────────────────────────────────────
function loginUser(string $username, string $password): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) return ['success' => false, 'message' => 'Invalid username or password.'];
    startSession();
    $_SESSION['user_id'] = (int)$user['id']; $_SESSION['username'] = $user['username'];
    try { createNotification($user['id'], 'system', 'New Login Detected', 'You have successfully logged into your account.', null, false); } catch (\Throwable $e) {}
    return ['success' => true];
}
function logoutUser(): void { startSession(); session_destroy(); header('Location: ' . APP_URL . '/login.php'); exit; }
function generateReferralCode(string $username): string { return strtoupper(substr($username, 0, 3)) . strtoupper(substr(md5(uniqid()), 0, 6)); }

// ── Upline chain (up to 7 levels) ────────────────────────────
function getUplineChain(int $userId, int $maxLevels = 7): array {
    $pdo = db(); $chain = []; $currentId = (int)$userId;
    for ($lvl = 1; $lvl <= $maxLevels; $lvl++) {
        $stmt = $pdo->prepare('SELECT referrer_id FROM users WHERE id = ?');
        $stmt->execute([$currentId]); $row = $stmt->fetch();
        if (!$row || empty($row['referrer_id'])) break;
        $stmt2 = $pdo->prepare('SELECT id, username, email, wallet FROM users WHERE id = ? AND is_active = 1');
        $stmt2->execute([(int)$row['referrer_id']]); $uplineUser = $stmt2->fetch();
        if (!$uplineUser) break;
        $chain[$lvl] = $uplineUser; $currentId = (int)$row['referrer_id'];
    }
    return $chain;
}

// ══════════════════════════════════════════════════════════════
//  BUSINESS CLUB — MEMBERSHIP FUNCTIONS
//
//  Document rules:
//  Stage 1 — One-time Activation:
//    Partner must complete a purchase of 2500 BV at ANY point
//    in their lifetime to activate Business Club membership.
//
//  Stage 2 — Monthly Renewal:
//    Partner must accumulate >= 2500 BV each month through
//    self-purchases (MShop or MShop Plus) to remain active.
//
//  Benefits unlocked ONLY when BOTH stages are true:
//    ✔ Access to MShop Plus
//    ✔ PSB earnings from upline purchases
//    ✔ Full participation in Mfills income system
//
//  If EITHER stage fails → ALL benefits are LOCKED.
// ══════════════════════════════════════════════════════════════

/**
 * Simple true/false — used by purchase flow, PSB gate, MShop Plus gate.
 */
function isActiveClubMember(int $userId): bool {
    $pdo = db();
    // Stage 1: lifetime BV >= 2500
    $s = $pdo->prepare('SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id=? AND status="completed"');
    $s->execute([$userId]);
    if ((float)$s->fetchColumn() < 2500) return false;
    // Stage 2: this month BV >= 2500
    $s = $pdo->prepare('SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id=? AND status="completed" AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())');
    $s->execute([$userId]);
    return (float)$s->fetchColumn() >= 2500;
}

/**
 * Detailed 3-state status for dashboard display.
 * Returns: status (inactive|lapsed|active), label, message,
 *          is_active, lifetime_bv, monthly_bv, lifetime_pct,
 *          monthly_pct, bv_left, target.
 */
function getClubActivationStatus(int $userId): array {
    $pdo = db(); $target = 2500;
    $s = $pdo->prepare('SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id=? AND status="completed"');
    $s->execute([$userId]); $lifetimeBv = (float)$s->fetchColumn();
    $s = $pdo->prepare('SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id=? AND status="completed" AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())');
    $s->execute([$userId]); $monthlyBv = (float)$s->fetchColumn();
    $base = [
        'target'       => $target,
        'lifetime_bv'  => $lifetimeBv,
        'monthly_bv'   => $monthlyBv,
        'lifetime_pct' => min(100, (int)round($lifetimeBv / $target * 100)),
        'monthly_pct'  => min(100, (int)round($monthlyBv  / $target * 100)),
        'is_active'    => false,
    ];
    if ($lifetimeBv < $target) return array_merge($base, [
        'status'  => 'inactive',
        'label'   => 'Not Yet Activated',
        'bv_left' => (int)ceil($target - $lifetimeBv),
        'message' => 'Purchase ' . number_format($target - $lifetimeBv, 0) . ' more BV to activate your Business Club and unlock all benefits.',
    ]);
    if ($monthlyBv < $target) return array_merge($base, [
        'status'  => 'lapsed',
        'label'   => 'Renewal Required',
        'bv_left' => (int)ceil($target - $monthlyBv),
        'message' => 'Purchase ' . number_format($target - $monthlyBv, 0) . ' more BV this month to renew your membership.',
    ]);
    return array_merge($base, [
        'status'    => 'active',
        'label'     => 'Active Member',
        'bv_left'   => 0,
        'is_active' => true,
        'message'   => 'All benefits unlocked — MShop Plus, PSB earnings, and full income participation.',
    ]);
}

// ── Commission Distribution ───────────────────────────────────
// Rules:
//   1. PSB paid to upline L1-L7 only.
//   2. Upline member MUST be an active Business Club member
//      to receive PSB. Inactive = commission skipped (not credited).
//   3. Skipped commissions are recorded in DB with status='skipped'
//      for audit trail — wallet is NOT touched.
function distributeCommission(int $orderId, int $buyerId, float $bvAmount): array {
    $pdo = db();
    $rates = defined('COMMISSION_RATES') ? COMMISSION_RATES : [1=>15,2=>8,3=>6,4=>4,5=>3,6=>2,7=>2];
    $upline = getUplineChain($buyerId, 7);
    $log = []; $skipped = [];
    if (empty($upline)) return ['distributed' => false, 'message' => 'No upline found.', 'log' => [], 'skipped' => []];

    foreach ($upline as $level => $user) {
        if ($level < 1 || $level > 7) continue;
        $rate = isset($rates[$level]) ? (float)$rates[$level] : 0.0;
        if ($rate <= 0) continue;
        $commAmt = round($bvAmount * $rate / 100, 2);
        if ($commAmt <= 0) continue;

        // BUSINESS CLUB GATE — inactive members do NOT receive PSB
        if (!isActiveClubMember((int)$user['id'])) {
            try {
                $pdo->prepare('INSERT INTO commissions (order_id,buyer_id,beneficiary_id,level,rate,commission_amt,status) VALUES (?,?,?,?,?,?,"skipped")')
                    ->execute([$orderId, $buyerId, (int)$user['id'], $level, $rate, $commAmt]);
            } catch (\Throwable $e) {}
            $skipped[] = ['level'=>$level,'user'=>$user['username'],'reason'=>'Business Club inactive','amount'=>$commAmt];
            continue;
        }

        // Active member — credit PSB
        $pdo->prepare('INSERT INTO commissions (order_id,buyer_id,beneficiary_id,level,rate,commission_amt,status) VALUES (?,?,?,?,?,?,"credited")')
            ->execute([$orderId, $buyerId, (int)$user['id'], $level, $rate, $commAmt]);
        $pdo->prepare('UPDATE users SET wallet = wallet + ? WHERE id = ?')->execute([$commAmt, (int)$user['id']]);
        try {
            createNotification((int)$user['id'], 'commission', 'New Commission Earned!',
                "You earned ₹{$commAmt} (Level {$level} · {$rate}%) from a purchase in your network.",
                '/dashboard.php', true);
        } catch (\Throwable $e) {}
        $log[] = ['level'=>$level,'user'=>$user['username'],'rate'=>$rate,'amount'=>$commAmt];
    }

    $cc = count($log); $sc = count($skipped);
    return [
        'distributed' => $cc > 0,
        'log'         => $log,
        'skipped'     => $skipped,
        'message'     => $cc > 0
            ? "{$cc} partner(s) credited with PSB." . ($sc > 0 ? " {$sc} skipped (club inactive)." : '')
            : "No PSB credited." . ($sc > 0 ? " {$sc} upline member(s) not active in Business Club." : ''),
    ];
}

// ── Purchase a product ────────────────────────────────────────
function purchaseProduct(int $userId, int $productId, int $qty = 1, string $source = 'mshop'): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
    $stmt->execute([$productId]); $product = $stmt->fetch();
    if (!$product) return ['success' => false, 'message' => 'Product not found or inactive.'];

    $price = (float)$product['price'];
    // MShop Plus discount ONLY for active Business Club members
    if ($source === 'mshop_plus' && !empty($product['discount_pct']) && isActiveClubMember($userId))
        $price = round($price * (1 - (float)$product['discount_pct'] / 100), 2);

    $amount  = round($price * $qty, 2);
    $bv      = isset($product['bv']) && (float)$product['bv'] > 0 ? (float)$product['bv'] : (float)$product['price'];
    $totalBv = round($bv * $qty, 2);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO orders (user_id,product_id,quantity,amount,bv,status,delivery_status) VALUES (?,?,?,?,?,"pending","processing")')
            ->execute([$userId, $productId, $qty, $amount, $totalBv]);
        $orderId = (int)$pdo->lastInsertId();

        $commResult = distributeCommission($orderId, $userId, $totalBv);

        try { createNotification($userId, 'order_update', 'Order Confirmed!', "Your order #{$orderId} has been placed. Total: ₹{$amount}", "/order-details.php?id={$orderId}", false); } catch (\Throwable $e) {}
        $pdo->commit();

        // Check if this purchase just activated the Business Club
        try {
            $cs = getClubActivationStatus($userId);
            if ($cs['is_active'] && $cs['lifetime_bv'] > 0 && ($cs['lifetime_bv'] - $totalBv) < 2500 && $cs['lifetime_bv'] >= 2500) {
                createNotification($userId, 'system', '🎉 Business Club Activated!',
                    'Congratulations! Your Business Club membership is now active. MShop Plus and PSB earnings are unlocked!',
                    '/dashboard.php', true);
            }
        } catch (\Throwable $e) {}

        $cc = count($commResult['log'] ?? []);
        return [
            'success'  => true,
            'message'  => '✅ Purchase successful! ₹' . number_format($amount, 2) . ' order placed.' . ($cc > 0 ? " PSB credited to {$cc} upline partner(s)." : ''),
            'order_id' => $orderId, 'amount' => $amount, 'bv' => $totalBv,
            'comm_log' => $commResult['log'] ?? [], 'skipped' => $commResult['skipped'] ?? [],
        ];
    } catch (\Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Purchase failed: ' . $e->getMessage()];
    }
}

// ── Monthly BV ────────────────────────────────────────────────
function getMonthlyBv(int $userId): float {
    $s = db()->prepare('SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id=? AND status="completed" AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())');
    $s->execute([$userId]); return (float)$s->fetchColumn();
}

// ── Personal BV (all-time) ────────────────────────────────────
function getPersonalBv(int $userId): float {
    $s = db()->prepare('SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id=? AND status="completed"');
    $s->execute([$userId]); return (float)$s->fetchColumn();
}

// ── Group BV (ALL levels, no cap — for Leadership Club) ───────
function getGroupBv(int $userId): float {
    $pdo = db(); $allIds = [$userId]; $toCheck = [$userId];
    while (!empty($toCheck)) {
        $in = implode(',', array_map('intval', $toCheck));
        $rows = $pdo->query("SELECT id FROM users WHERE referrer_id IN ($in) AND is_active=1")->fetchAll();
        $newIds = array_column($rows, 'id');
        $allIds = array_merge($allIds, $newIds); $toCheck = $newIds;
        if (count($allIds) > 5000) break;
    }
    if (empty($allIds)) return 0.0;
    $in = implode(',', array_map('intval', $allIds));
    return (float)$pdo->query("SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id IN ($in) AND status='completed'")->fetchColumn();
}

// ── PSB-eligible Group BV (L1–L7 downline only) ──────────────
function getPsbGroupBv(int $userId, int $maxLevel = 7): float {
    $pdo = db(); $allIds = []; $toCheck = [$userId];
    for ($lvl = 1; $lvl <= $maxLevel; $lvl++) {
        if (empty($toCheck)) break;
        $in = implode(',', array_map('intval', $toCheck));
        $rows = $pdo->query("SELECT id FROM users WHERE referrer_id IN ($in) AND is_active=1")->fetchAll();
        $levelIds = array_column($rows, 'id');
        $allIds = array_merge($allIds, $levelIds); $toCheck = $levelIds;
        if (count($allIds) > 5000) break;
    }
    if (empty($allIds)) return 0.0;
    $in = implode(',', array_map('intval', $allIds));
    return (float)$pdo->query("SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id IN ($in) AND status='completed'")->fetchColumn();
}

// ── Club rank ─────────────────────────────────────────────────
function getClubRank(float $groupBv): string {
    if ($groupBv >= 5000000) return 'CC';
    if ($groupBv >= 1000000) return 'GAC';
    if ($groupBv >= 200000)  return 'PC';
    if ($groupBv >= 50000)   return 'RSC';
    return 'NONE';
}

// ── Dashboard stats ───────────────────────────────────────────
function getUserStats(int $userId): array {
    $pdo = db();
    $s = $pdo->prepare('SELECT wallet FROM users WHERE id=?'); $s->execute([$userId]);
    $wallet = (float)($s->fetchColumn() ?? 0);
    $mbpin = null;
    try { $s = $pdo->prepare('SELECT mbpin FROM users WHERE id=?'); $s->execute([$userId]); $mbpin = $s->fetchColumn() ?: null; } catch (\Throwable $e) {}
    $s = $pdo->prepare('SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE beneficiary_id=? AND status="credited"'); $s->execute([$userId]);
    $totalComm = (float)$s->fetchColumn();
    $s = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total FROM orders WHERE user_id=?'); $s->execute([$userId]);
    $orders = $s->fetch(); $totalOrders = (int)($orders['cnt']??0); $totalSpent = (float)($orders['total']??0);
    $s = $pdo->prepare('SELECT COUNT(*) FROM users WHERE referrer_id=?'); $s->execute([$userId]);
    $directReferrals = (int)$s->fetchColumn();
    $s = $pdo->prepare('SELECT level,COUNT(*) AS cnt,SUM(commission_amt) AS total FROM commissions WHERE beneficiary_id=? AND status="credited" GROUP BY level ORDER BY level'); $s->execute([$userId]);
    $commByLevel = $s->fetchAll();
    $s = $pdo->prepare('SELECT c.*,u.username AS buyer_name,p.name AS product_name FROM commissions c JOIN users u ON u.id=c.buyer_id JOIN orders o ON o.id=c.order_id JOIN products p ON p.id=o.product_id WHERE c.beneficiary_id=? AND c.status="credited" ORDER BY c.created_at DESC LIMIT 10'); $s->execute([$userId]);
    $recentComm = $s->fetchAll();
    $monthlyBv  = getMonthlyBv($userId);
    $personalBv = getPersonalBv($userId);
    $groupBv    = getGroupBv($userId);
    $psbGroupBv = getPsbGroupBv($userId, 7);
    $isClub     = isActiveClubMember($userId);
    $clubStatus = getClubActivationStatus($userId);
    $clubRank   = getClubRank($groupBv);
    $clubIncomeTotal = 0.0;
    try { $s = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM club_income WHERE user_id=?'); $s->execute([$userId]); $clubIncomeTotal = (float)$s->fetchColumn(); } catch (\Throwable $e) {}
    return compact('wallet','mbpin','totalComm','totalOrders','totalSpent','directReferrals','commByLevel','recentComm','monthlyBv','personalBv','groupBv','psbGroupBv','isClub','clubStatus','clubRank','clubIncomeTotal');
}

// ── Products ──────────────────────────────────────────────────
function getProducts(string $mode = 'mshop'): array {
    $pdo = db();
    if ($mode === 'mshop_plus') {
        try { $stmt = $pdo->query('SELECT * FROM products WHERE is_active=1 AND discount_pct>0 ORDER BY price ASC'); }
        catch (\Throwable $e) { $stmt = $pdo->query('SELECT * FROM products WHERE is_active=1 ORDER BY price ASC'); }
    } else { $stmt = $pdo->query('SELECT * FROM products WHERE is_active=1 ORDER BY price ASC'); }
    return $stmt->fetchAll();
}

// ── Downline tree (max 7 levels, with BV per node) ────────────
function getDownlineTree(int $userId, int $currentLevel = 1, int $maxLevel = 7): array {
    if ($currentLevel > $maxLevel) return [];
    $pdo = db();
    $stmt = $pdo->prepare('SELECT u.id,u.username,u.email,u.wallet,u.created_at,COALESCE(SUM(o.bv),0) AS total_bv FROM users u LEFT JOIN orders o ON o.user_id=u.id AND o.status="completed" WHERE u.referrer_id=? AND u.is_active=1 GROUP BY u.id,u.username,u.email,u.wallet,u.created_at');
    $stmt->execute([$userId]); $children = $stmt->fetchAll();
    foreach ($children as &$child) {
        $child['level'] = $currentLevel;
        $child['total_bv'] = (float)$child['total_bv'];
        $child['children'] = getDownlineTree((int)$child['id'], $currentLevel + 1, $maxLevel);
    }
    unset($child); return $children;
}

// ── PSB earned by level ───────────────────────────────────────
function getPsbEarnedByLevel(int $userId): array {
    $pdo = db(); $rates = defined('COMMISSION_RATES') ? COMMISSION_RATES : [1=>15,2=>8,3=>6,4=>4,5=>3,6=>2,7=>2]; $out = [];
    for ($l = 1; $l <= 7; $l++) {
        $s = $pdo->prepare('SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE beneficiary_id=? AND level=? AND status="credited"');
        $s->execute([$userId, $l]); $out[$l] = ['rate'=>$rates[$l]??0,'earned'=>(float)$s->fetchColumn()];
    }
    return $out;
}

function getTotalPsbEarned(int $userId): float {
    $s = db()->prepare('SELECT COALESCE(SUM(commission_amt),0) FROM commissions WHERE beneficiary_id=? AND status="credited"');
    $s->execute([$userId]); return (float)$s->fetchColumn();
}

// ── Get single user (with BV) ─────────────────────────────────
function getUser(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE id=?'); $stmt->execute([$id]);
    $user = $stmt->fetch(); if (!$user) return null;
    $bvStmt = db()->prepare('SELECT COALESCE(SUM(bv),0) FROM orders WHERE user_id=? AND status="completed"');
    $bvStmt->execute([$id]); $user['total_bv'] = (float)$bvStmt->fetchColumn();
    return $user;
}

function setFlash(string $type, string $msg): void { startSession(); $_SESSION['flash'] = ['type'=>$type,'msg'=>$msg]; }
function getFlash(): ?array { startSession(); $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function e(mixed $val): string { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }

// ════════════════════════════════════════════════════════════
// ID CARD FUNCTIONS
// ════════════════════════════════════════════════════════════
function generateIdCardNumber($userId, $mbpin) { return 'MF'.date('y').'-'.strtoupper(substr(md5(uniqid()),0,6)).'-'.str_pad($userId,5,'0',STR_PAD_LEFT); }
function createUserIdCard($userId) {
    $pdo = db(); $user = getUser($userId); $stats = getUserStats($userId);
    $pdo->prepare("UPDATE user_id_cards SET is_active=0 WHERE user_id=?")->execute([$userId]);
    $cardNumber = generateIdCardNumber($userId, $stats['mbpin']??($user['mbpin']??''));
    $cardData = json_encode(['username'=>$user['username'],'email'=>$user['email'],'referral_code'=>$user['referral_code'],'wallet'=>$user['wallet'],'group_bv'=>$stats['groupBv']??0,'rank'=>$stats['clubRank']??'NONE']);
    $pdo->prepare("INSERT INTO user_id_cards (user_id,card_number,full_name,mbpin,rank,issue_date,expiry_date,card_data) VALUES (?,?,?,?,?,?,?,?)")->execute([$userId,$cardNumber,$user['full_name']??$user['username'],$stats['mbpin']??($user['mbpin']??''),$stats['clubRank']??'NONE',date('Y-m-d'),date('Y-m-d',strtotime('+2 years')),$cardData]);
    return ['success'=>true,'card_number'=>$cardNumber,'card_id'=>$pdo->lastInsertId()];
}
function getUserIDCard($userId) { $stmt=db()->prepare("SELECT * FROM user_id_cards WHERE user_id=? AND is_active=1 ORDER BY created_at DESC LIMIT 1"); $stmt->execute([$userId]); return $stmt->fetch(PDO::FETCH_ASSOC); }
function updateCardDownloadTime($cardId) { return db()->prepare("UPDATE user_id_cards SET last_downloaded_at=NOW() WHERE id=?")->execute([$cardId]); }

// ════════════════════════════════════════════════════════════
// ORDER TRACKING
// ════════════════════════════════════════════════════════════
function getUserOrders($userId, $limit=null, $status=null) {
    $pdo=db(); $sql="SELECT o.*,p.name AS product_name,p.image_url AS product_image FROM orders o JOIN products p ON o.product_id=p.id WHERE o.user_id=?"; $params=[$userId];
    if ($status) { $sql.=" AND o.status=?"; $params[]=$status; }
    $sql.=" ORDER BY o.created_at DESC";
    if ($limit)  { $sql.=" LIMIT ?"; $params[]=$limit; }
    $stmt=$pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getOrderDetails($orderId, $userId) {
    $stmt=db()->prepare("SELECT o.*,p.name AS product_name,p.description,p.image,p.bv AS product_bv FROM orders o JOIN products p ON o.product_id=p.id WHERE o.id=? AND o.user_id=?");
    $stmt->execute([$orderId,$userId]); return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ════════════════════════════════════════════════════════════
// NOTIFICATION FUNCTIONS
// ════════════════════════════════════════════════════════════
function createNotification($userId,$type,$title,$message,$link=null,$isImportant=false) { return db()->prepare("INSERT INTO notifications (user_id,type,title,message,link,is_important) VALUES (?,?,?,?,?,?)")->execute([$userId,$type,$title,$message,$link,$isImportant]); }
function getUserNotifications($userId,$unreadOnly=false,$limit=20) { $pdo=db(); $sql="SELECT * FROM notifications WHERE user_id=?"; $params=[$userId]; if($unreadOnly){$sql.=" AND is_read=0";} $sql.=" ORDER BY created_at DESC LIMIT ?"; $params[]=$limit; $stmt=$pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
function markNotificationRead($notificationId,$userId) { return db()->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$notificationId,$userId]); }
function markAllNotificationsRead($userId) { return db()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$userId]); }
function getUnreadNotificationCount($userId) { $stmt=db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $stmt->execute([$userId]); return $stmt->fetchColumn(); }

// ════════════════════════════════════════════════════════════
// ANNOUNCEMENT FUNCTIONS
// ════════════════════════════════════════════════════════════
function getActiveAnnouncements($userId=null) {
    $pdo=db(); $user=$userId?getUser($userId):null; $stats=$userId?getUserStats($userId):null;
    $sql="SELECT a.*,(SELECT COUNT(*) FROM announcement_reads WHERE announcement_id=a.id AND user_id=?) AS is_read FROM announcements a WHERE a.is_active=1 AND (a.expires_at IS NULL OR a.expires_at>NOW())"; $params=[$userId];
    if($user&&$stats){$sql.=" AND (a.target_rank IS NULL OR a.target_rank=? OR a.target_rank='ALL')"; $params[]=$stats['clubRank']??'NONE'; $sql.=" AND (a.target_bv_min IS NULL OR a.target_bv_min<=?)"; $params[]=$stats['groupBv']??0;}
    $sql.=" ORDER BY a.type='urgent' DESC,a.created_at DESC"; $stmt=$pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function markAnnouncementRead($announcementId,$userId) { return db()->prepare("INSERT IGNORE INTO announcement_reads (announcement_id,user_id) VALUES (?,?)")->execute([$announcementId,$userId]); }

// ════════════════════════════════════════════════════════════
// KYC FUNCTIONS
// ════════════════════════════════════════════════════════════
function getKycHistory($userId) { $stmt=db()->prepare("SELECT * FROM kyc_submissions WHERE user_id=? ORDER BY submitted_at DESC"); $stmt->execute([$userId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
function requestKycUpdate($userId,$updateReason) {
    $pdo=db(); $stmt=$pdo->prepare("SELECT id FROM kyc_submissions WHERE user_id=? AND status='pending' AND admin_note LIKE 'UPDATE_REQUEST:%'"); $stmt->execute([$userId]);
    if($stmt->fetch()) return ['success'=>false,'message'=>'Update request already pending'];
    $result=$pdo->prepare("INSERT INTO kyc_submissions (user_id,full_name,status,admin_note,submitted_at) VALUES (?,'UPDATE_REQUEST','pending',?,NOW())")->execute([$userId,"UPDATE_REQUEST: {$updateReason}"]);
    if($result){try{createNotification($userId,'kyc_update','KYC Update Request Submitted','Your request has been submitted. Admin will review shortly.','/kyc.php',false);}catch(\Throwable $e){}}
    return ['success'=>$result];
}

// ════════════════════════════════════════════════════════════
// SAMPLE ANNOUNCEMENTS
// ════════════════════════════════════════════════════════════
function createSampleAnnouncements() {
    $pdo=db();
    foreach([['New Product Launch!','Check out our new organic wellness products in MShop.','info',null,null],['KYC Deadline Approaching','Complete your KYC before March 31st to continue withdrawals.','warning',null,null],['Chairman Club Event','Leadership retreat for CC members in Goa. Contact support.','urgent','CC',5000000],['Double PSB Weekend!','This weekend only, earn double PSB on all purchases!','success',null,null]] as [$t,$c,$ty,$r,$b])
        $pdo->prepare("INSERT INTO announcements (title,content,type,target_rank,target_bv_min,expires_at) VALUES (?,?,?,?,?,?)")->execute([$t,$c,$ty,$r,$b,date('Y-m-d H:i:s',strtotime('+30 days'))]);
}