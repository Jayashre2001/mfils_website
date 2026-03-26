<?php
// includes/config.php
// ── Database credentials (Hostinger) ─────────────────────────
define('DB_HOST',    '127.0.0.1');
define('DB_NAME',    'u302534731_Mlm_task');
define('DB_USER',    'u302534731_Mlm_task');
define('DB_PASS',    'Mlm@9047');
define('DB_CHARSET', 'utf8mb4');
// ── App settings ──────────────────────────────────────────────
define('APP_NAME',    'Mfills');
define('APP_URL', 'https://geinca.com/Mfils');
define('SESSION_NAME','mfills_session');
// ── Admin credentials ─────────────────────────────────────────
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');
// ══════════════════════════════════════════════════════════════
// MAIL / SMTP SETTINGS — Gmail
// ══════════════════════════════════════════════════════════════
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_USERNAME',   'jayashreepatra2001@gmail.com');
define('MAIL_PASSWORD',   'acqi uiik acdl vuhp');
define('MAIL_FROM',       'jayashreepatra2001@gmail.com'); // ✅ FIXED: was 'patar', now 'patra'
define('MAIL_FROM_NAME',  'Mfills Partner Portal');


define('RZP_KEY_ID',     'rzp_test_SVONQE4uPWIWaK');   // apni key
define('RZP_KEY_SECRET', 'gb2ZCd7HL1N1t2hr59ydSv3J');     // apna secret
// ── Partner Sales Bonus (PSB) rates — 7 levels, total 40% ────
define('COMMISSION_RATES', [
    1 => 15.00,
    2 =>  8.00,
    3 =>  6.00,
    4 =>  4.00,
    5 =>  3.00,
    6 =>  2.00,
    7 =>  2.00,
]);
// ── Mfills Business Club – monthly BV requirement ─────────────
define('CLUB_BV_REQUIRED', 2500);
// ── Leadership Club ranks & monthly group BV thresholds ───────
define('CLUB_RANKS', [
    'RSC' => [
        'name'       => 'Rising Star Club',
        'icon'       => '⭐',
        'monthly_bv' => 50000,
    ],
    'PC'  => [
        'name'       => 'Prestige Club',
        'icon'       => '🏆',
        'monthly_bv' => 200000,
    ],
    'GAC' => [
        'name'       => 'Global Ambassador Club',
        'icon'       => '🌍',
        'monthly_bv' => 1000000,
    ],
    'CC'  => [
        'name'       => 'Chairman Club',
        'icon'       => '👑',
        'monthly_bv' => 5000000,
    ],
]);
// ── Leadership Reward Pool — 15% of total company sales ───────
define('CLUB_POOL_PCT', 15.00);
// ── PDO connection (singleton) ────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die('<div style="font-family:sans-serif;padding:2rem;color:#7f1d1d;background:#fee2e2;border-radius:8px;max-width:500px;margin:3rem auto">
             <strong>Database connection failed.</strong><br><br>
             Please check DB_PASS in includes/config.php<br><br>
             <small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>
             </div>');
    }
    return $pdo;
}