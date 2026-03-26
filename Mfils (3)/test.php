<?php
/**
 * test_register_post.php
 * Upload to root, open in browser, DELETE after!
 * URL: https://geinca.com/Mfils/test_register_post.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<style>
body{font-family:monospace;padding:2rem;background:#0e2414;color:#f8f5ef}
.ok{background:rgba(15,123,92,.3);border:1px solid #0F7B5C;padding:.75rem 1rem;border-radius:8px;margin:.4rem 0;color:#1CC48D}
.err{background:rgba(232,83,74,.2);border:1px solid #E8534A;padding:.75rem 1rem;border-radius:8px;margin:.4rem 0;color:#fca5a5}
.warn{background:rgba(200,146,42,.15);border:1px solid #c8922a;padding:.75rem 1rem;border-radius:8px;margin:.4rem 0;color:#e0aa40}
h2{color:#e0aa40;margin:1.2rem 0 .5rem;font-size:.9rem;text-transform:uppercase;letter-spacing:.1em}
pre{background:rgba(0,0,0,.4);padding:.75rem;border-radius:6px;overflow:auto;font-size:.8rem;color:#4dac5e}
</style>';

echo '<h1 style="color:#e0aa40;font-size:1.2rem;margin-bottom:1rem">🔧 Register Flow Debug</h1>';

// ── Step 1: Load config ──────────────────────────────────────
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
echo '<div class="ok">✅ config.php + functions.php loaded</div>';

// ── Step 2: Check mailer.php ─────────────────────────────────
echo '<h2>Checking mailer.php</h2>';
$mailerPath = __DIR__ . '/includes/mailer.php';
if (!file_exists($mailerPath)) {
    echo '<div class="err">❌ mailer.php NOT FOUND at: ' . $mailerPath . '<br>
    This is the problem! register.php tries to load mailer.php and FAILS silently.<br>
    Fix: Create the mailer.php file in /includes/ folder.</div>';
} else {
    echo '<div class="ok">✅ mailer.php exists</div>';
    try {
        require_once $mailerPath;
        echo '<div class="ok">✅ mailer.php loaded without errors</div>';
        if (function_exists('sendWelcomeMail')) {
            echo '<div class="ok">✅ sendWelcomeMail() function exists</div>';
        } else {
            echo '<div class="err">❌ sendWelcomeMail() function NOT FOUND in mailer.php</div>';
        }
    } catch (Throwable $e) {
        echo '<div class="err">❌ mailer.php FAILED to load: ' . $e->getMessage() . '</div>';
    }
}

// ── Step 3: Simulate full register POST ──────────────────────
echo '<h2>Simulating Full Registration Flow</h2>';

$pdo = db();
$tu  = 'sim_' . substr(md5(microtime()), 0, 6);
$te  = $tu . '@testmail.com';

echo '<div class="warn">Testing with username: <strong>' . $tu . '</strong></div>';

// Simulate exactly what register.php does
$fullName = 'Sim Test User';
$username = $tu;
$email    = $te;
$password = 'Test@1234';
$confirm  = 'Test@1234';
$ref      = '';

// Validate
$error = '';
if (!$fullName || !$username || !$email || !$password || !$confirm) {
    $error = 'All fields are required.';
} elseif (strlen($fullName) < 3 || strlen($fullName) > 60) {
    $error = 'Full name must be 3–60 characters.';
} elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    $error = 'Username must be 3–30 characters.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email.';
} elseif (strlen($password) < 6) {
    $error = 'Password too short.';
} elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
}

if ($error) {
    echo '<div class="err">❌ Validation failed: ' . $error . '</div>';
} else {
    echo '<div class="ok">✅ Validation passed</div>';

    try {
        $result = registerUser($username, $email, $password, $ref ?: null, $fullName);
        echo '<pre>' . print_r($result, true) . '</pre>';

        if ($result['success']) {
            echo '<div class="ok">✅ registerUser() SUCCESS — MBPIN: ' . $result['mbpin'] . '</div>';

            // Now test welcome mail (non-fatal)
            if (function_exists('sendWelcomeMail')) {
                echo '<h2>Testing Welcome Mail (non-fatal)</h2>';
                try {
                    $mailResult = sendWelcomeMail(
                        $email,
                        $username,
                        $result['mbpin'],
                        $result['referral_code'],
                        $result['referrer_name'] ?? ''
                    );
                    if ($mailResult['success']) {
                        echo '<div class="ok">✅ Welcome mail sent!</div>';
                    } else {
                        echo '<div class="warn">⚠️ Mail failed (non-fatal): ' . htmlspecialchars($mailResult['message']) . '</div>';
                    }
                } catch (Throwable $e) {
                    echo '<div class="warn">⚠️ Mail exception (non-fatal): ' . $e->getMessage() . '</div>';
                }
            }

            // Cleanup
            $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$username]);
            echo '<div class="warn">🧹 Test user deleted</div>';

        } else {
            echo '<div class="err">❌ registerUser() FAILED: ' . htmlspecialchars($result['message']) . '</div>';
        }
    } catch (Throwable $e) {
        echo '<div class="err">❌ Exception: ' . $e->getMessage() . '<br>File: ' . $e->getFile() . ' Line: ' . $e->getLine() . '</div>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}

// ── Step 4: Check what register.php is actually doing ────────
echo '<h2>register.php File Check</h2>';
$regPath = __DIR__ . '/register.php';
if (file_exists($regPath)) {
    $content = file_get_contents($regPath);
    $size    = strlen($content);
    echo '<div class="ok">✅ register.php exists (' . $size . ' bytes)</div>';

    // Check key things
    $checks = [
        "require_once __DIR__ . '/includes/mailer.php'" => 'mailer.php is loaded',
        'full_name'        => 'full_name field handled',
        'registerUser('    => 'registerUser() called',
        '$fullName'        => '$fullName variable used',
    ];
    foreach ($checks as $needle => $label) {
        if (strpos($content, $needle) !== false) {
            echo '<div class="ok">✅ ' . $label . '</div>';
        } else {
            echo '<div class="err">❌ ' . $label . ' — NOT FOUND in register.php! Old version?</div>';
        }
    }

    // Check if mailer require is BEFORE registerUser call
    $mailerPos = strpos($content, "require_once __DIR__ . '/includes/mailer.php'");
    $regPos    = strpos($content, 'registerUser(');
    if ($mailerPos !== false && $regPos !== false) {
        if ($mailerPos < $regPos) {
            echo '<div class="ok">✅ mailer.php loaded BEFORE registerUser() — correct order</div>';
        } else {
            echo '<div class="err">❌ mailer.php loaded AFTER registerUser() — wrong order!</div>';
        }
    }
} else {
    echo '<div class="err">❌ register.php NOT FOUND at: ' . $regPath . '</div>';
}

echo '<hr style="border-color:rgba(200,146,42,.2);margin:2rem 0">';
echo '<p style="color:rgba(255,255,255,.3);font-size:.75rem">⚠️ DELETE this file from server after debugging!</p>';