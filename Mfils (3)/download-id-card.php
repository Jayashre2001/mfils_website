<?php
// download-id-card.php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId = currentUserId();
$user = getUser($userId);
$stats = getUserStats($userId);
$userCard = getUserIDCard($userId);

if (!$userCard) {
    // Create card if doesn't exist
    $cardResult = createUserIdCard($userId);
    $userCard = getUserIDCard($userId);
}

// Update download count
if ($userCard) {
    updateCardDownloadTime($userCard['id']);
}

// Get rank details
$clubRanks = CLUB_RANKS;
$rank = $stats['clubRank'] ?? 'NONE';
$rankName = 'Member';
$rankIcon = '🌿';
if ($rank != 'NONE' && isset($clubRanks[$rank])) {
    $rankName = $clubRanks[$rank]['name'];
    $rankIcon = $clubRanks[$rank]['icon'];
}

// Set headers for HTML download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="mfills-id-card-' . $user['username'] . '.html"');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mfills ID Card - <?= e($user['username']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .id-card {
            width: 400px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
        }
        .card-header {
            background: linear-gradient(135deg, #1a3b22 0%, #2a6336 100%);
            padding: 20px;
            color: white;
            text-align: center;
            border-bottom: 3px solid #c8922a;
            position: relative;
            overflow: hidden;
        }
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
        }
        .card-logo {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: 2px;
            margin-bottom: 5px;
            font-family: 'Georgia', serif;
        }
        .card-logo span {
            color: #c8922a;
        }
        .card-type {
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 4px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .card-photo {
            text-align: center;
            margin-top: -40px;
        }
        .avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #c8922a, #e0aa40);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 800;
            color: white;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .card-body {
            padding: 20px 25px 25px;
            text-align: center;
        }
        .member-name {
            font-size: 24px;
            font-weight: 800;
            color: #1a3b22;
            margin-bottom: 5px;
            font-family: 'Georgia', serif;
        }
        .member-username {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ddd;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
            text-align: left;
        }
        .info-item {
            background: #f8f5ef;
            padding: 10px;
            border-radius: 10px;
        }
        .info-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 700;
            color: #1a3b22;
            word-break: break-word;
        }
        .card-number {
            background: #1a3b22;
            color: white;
            padding: 12px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 16px;
            letter-spacing: 2px;
            margin: 15px 0;
        }
        .rank-badge {
            background: <?php 
                if ($rank == 'CC') echo 'linear-gradient(135deg, #FFD700, #FFA500)';
                elseif ($rank == 'GAC') echo 'linear-gradient(135deg, #C0C0C0, #E8E8E8)';
                elseif ($rank == 'PC') echo 'linear-gradient(135deg, #CD7F32, #B87333)';
                elseif ($rank == 'RSC') echo 'linear-gradient(135deg, #87CEEB, #4682B4)';
                else echo 'linear-gradient(135deg, #667eea, #764ba2)';
            ?>;
            color: <?php echo ($rank == 'CC' || $rank == 'GAC') ? '#1a3b22' : 'white'; ?>;
            padding: 8px 20px;
            border-radius: 25px;
            display: inline-block;
            font-weight: 700;
            font-size: 14px;
            margin: 10px 0;
        }
        .dates {
            display: flex;
            justify-content: space-between;
            background: #f0f0f0;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 12px;
            color: #666;
            margin: 15px 0;
        }
        .barcode {
            font-family: monospace;
            font-size: 24px;
            letter-spacing: 5px;
            color: #333;
            background: white;
            padding: 10px;
            border: 1px dashed #ccc;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            color: #999;
        }
        .qr-placeholder {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #999;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="id-card">
        <div class="card-header">
            <div class="card-logo">🌿 MFILLS <span>ID</span></div>
            <div class="card-type">DIGITAL IDENTITY CARD</div>
        </div>
        
        <div class="card-photo">
            <div class="avatar">
                <?= strtoupper(substr($user['username'], 0, 2)) ?>
            </div>
        </div>
        
        <div class="card-body">
            <div class="member-name"><?= e($userCard['full_name'] ?? $user['username']) ?></div>
            <div class="member-username">@<?= e($user['username']) ?></div>
            
            <div class="rank-badge">
                <?= $rankIcon ?> <?= $rankName ?>
            </div>
            
            <div class="card-number">
                <?= e($userCard['card_number'] ?? '') ?>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">MBPIN</div>
                    <div class="info-value"><?= e($stats['mbpin'] ?? $user['mbpin'] ?? 'N/A') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Referral Code</div>
                    <div class="info-value"><?= e($user['referral_code'] ?? 'N/A') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Wallet Balance</div>
                    <div class="info-value">₹<?= number_format($user['wallet'] ?? 0, 2) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Group BV</div>
                    <div class="info-value"><?= number_format($stats['groupBv'] ?? 0, 0) ?></div>
                </div>
            </div>
            
            <div class="dates">
                <span>📅 Issued: <?= date('d M Y', strtotime($userCard['issue_date'] ?? 'now')) ?></span>
                <span>⏰ Expires: <?= date('d M Y', strtotime($userCard['expiry_date'] ?? '+2 years')) ?></span>
            </div>
            
            <div class="barcode">
                <?= str_repeat('█', 30) ?>
            </div>
            
            <div class="qr-placeholder">
                SCAN QR
            </div>
            
            <div class="footer">
                This is a digitally generated ID card • Valid for Mfills Business Partners
            </div>
        </div>
    </div>
</body>
</html>