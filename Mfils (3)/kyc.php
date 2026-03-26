<?php
// kyc.php — Mfills KYC Verification Page
$pageTitle = 'KYC Verification – Mfills';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$userId = currentUserId();
$user   = getUser($userId);

/* ── KYC DB table expected:
CREATE TABLE IF NOT EXISTS kyc_submissions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  full_name       VARCHAR(100) NOT NULL,
  dob             DATE NOT NULL,
  gender          ENUM('male','female','other') NOT NULL,
  aadhaar_number  VARCHAR(12) NOT NULL,
  pan_number      VARCHAR(10),
  address         TEXT NOT NULL,
  city            VARCHAR(60) NOT NULL,
  state           VARCHAR(60) NOT NULL,
  pincode         VARCHAR(6) NOT NULL,
  bank_account    VARCHAR(20),
  ifsc_code       VARCHAR(11),
  bank_name       VARCHAR(80),
  aadhaar_front   VARCHAR(255),
  aadhaar_back    VARCHAR(255),
  pan_photo       VARCHAR(255),
  selfie          VARCHAR(255),
  status          ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_note      TEXT,
  submitted_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  reviewed_at     DATETIME,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
── */

// Fetch existing KYC
$kyc = null;
try {
    $stmt = db()->prepare("SELECT * FROM kyc_submissions WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table may not exist yet
}

$kycStatus  = $kyc['status'] ?? null;
$error      = '';
$successMsg = '';

/* ── Handle form submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_kyc'])) {

    // Block re-submission if approved
    if ($kycStatus === 'approved') {
        $error = 'Your KYC is already approved.';
    } else {

        $fullName     = trim($_POST['full_name']    ?? '');
        $dob          = trim($_POST['dob']          ?? '');
        $gender       = trim($_POST['gender']       ?? '');
        $aadhaar      = preg_replace('/\D/', '', trim($_POST['aadhaar_number'] ?? ''));
        $pan          = strtoupper(trim($_POST['pan_number'] ?? ''));
        $address      = trim($_POST['address']      ?? '');
        $city         = trim($_POST['city']         ?? '');
        $state        = trim($_POST['state']        ?? '');
        $pincode      = trim($_POST['pincode']      ?? '');
        $bankAccount  = trim($_POST['bank_account'] ?? '');
        $ifsc         = strtoupper(trim($_POST['ifsc_code']    ?? ''));
        $bankName     = trim($_POST['bank_name']    ?? '');

        // Validate
        if (!$fullName || !$dob || !$gender || !$aadhaar || !$address || !$city || !$state || !$pincode) {
            $error = 'Please fill all required fields.';
        } elseif (strlen($aadhaar) !== 12) {
            $error = 'Aadhaar number must be exactly 12 digits.';
        } elseif ($pan && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
            $error = 'Invalid PAN format. Example: ABCDE1234F';
        } elseif ($ifsc && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            $error = 'Invalid IFSC format. Example: SBIN0001234';
        } else {
            // Upload dir
            $uploadDir = __DIR__ . '/uploads/kyc/' . $userId . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $uploadedFiles = [];
            $fileFields = ['aadhaar_front', 'aadhaar_back', 'pan_photo', 'selfie'];
            $allowed    = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','pdf'=>'application/pdf'];
            $uploadOk   = true;

            foreach ($fileFields as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = finfo_file($finfo, $_FILES[$field]['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($mime, $allowed)) {
                        $error = "Invalid file type for " . str_replace('_', ' ', $field) . ". Only JPG, PNG, PDF allowed.";
                        $uploadOk = false; break;
                    }
                    if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
                        $error = "File size too large for " . str_replace('_', ' ', $field) . ". Max 5MB.";
                        $uploadOk = false; break;
                    }

                    $ext      = array_search($mime, $allowed);
                    $filename = $field . '_' . time() . '.' . $ext;
                    $destPath = $uploadDir . $filename;

                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $destPath)) {
                        $uploadedFiles[$field] = 'uploads/kyc/' . $userId . '/' . $filename;
                    }
                } else {
                    // Keep existing file if re-submitting
                    $uploadedFiles[$field] = $kyc[$field] ?? null;
                }
            }

            if ($uploadOk) {
                try {
                    if ($kyc && $kycStatus === 'rejected') {
                        // Update existing rejected submission
                        $sql = "UPDATE kyc_submissions SET full_name=?,dob=?,gender=?,aadhaar_number=?,pan_number=?,
                                address=?,city=?,state=?,pincode=?,bank_account=?,ifsc_code=?,bank_name=?,
                                aadhaar_front=?,aadhaar_back=?,pan_photo=?,selfie=?,status='pending',
                                admin_note=NULL,submitted_at=NOW() WHERE user_id=? AND id=?";
                        db()->prepare($sql)->execute([
                            $fullName,$dob,$gender,$aadhaar,$pan,$address,$city,$state,$pincode,
                            $bankAccount,$ifsc,$bankName,
                            $uploadedFiles['aadhaar_front'],$uploadedFiles['aadhaar_back'],
                            $uploadedFiles['pan_photo'],$uploadedFiles['selfie'],
                            $userId,$kyc['id']
                        ]);
                    } else {
                        // New submission
                        $sql = "INSERT INTO kyc_submissions
                                (user_id,full_name,dob,gender,aadhaar_number,pan_number,address,city,state,pincode,
                                 bank_account,ifsc_code,bank_name,aadhaar_front,aadhaar_back,pan_photo,selfie)
                                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                        db()->prepare($sql)->execute([
                            $userId,$fullName,$dob,$gender,$aadhaar,$pan,$address,$city,$state,$pincode,
                            $bankAccount,$ifsc,$bankName,
                            $uploadedFiles['aadhaar_front'],$uploadedFiles['aadhaar_back'],
                            $uploadedFiles['pan_photo'],$uploadedFiles['selfie']
                        ]);
                    }

                    // Refresh KYC
                    $stmt = db()->prepare("SELECT * FROM kyc_submissions WHERE user_id=? ORDER BY submitted_at DESC LIMIT 1");
                    $stmt->execute([$userId]);
                    $kyc = $stmt->fetch(PDO::FETCH_ASSOC);
                    $kycStatus  = $kyc['status'];
                    $successMsg = '✅ KYC submitted successfully! Our team will review within 24–48 hours.';

                    // Send email notification
                    if (function_exists('sendKycStatusMail')) {
                        // Only on review, not submission — admin will trigger this
                    }

                } catch (Exception $e) {
                    $error = 'Database error. Please try again. ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : '');
                }
            }
        }
    }
}

$indianStates = ["Andhra Pradesh","Arunachal Pradesh","Assam","Bihar","Chhattisgarh","Goa","Gujarat","Haryana","Himachal Pradesh","Jharkhand","Karnataka","Kerala","Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram","Nagaland","Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura","Uttar Pradesh","Uttarakhand","West Bengal","Andaman & Nicobar Islands","Chandigarh","Dadra & Nagar Haveli","Daman & Diu","Delhi","Jammu & Kashmir","Ladakh","Lakshadweep","Puducherry"];

include __DIR__ . '/includes/header.php';
?>

<style>
:root{
  --green-dd:#0e2414;--green-d:#1a3b22;--green-m:#2a6336;--green-l:#3a8a4a;
  --gold:#c8922a;--gold-l:#e0aa40;--gold-d:#a0721a;
  --jade:#0F7B5C;--jade-l:#14A376;--coral:#E8534A;
  --ivory:#f8f5ef;--ivory-d:#ede8de;--ivory-dd:#ddd5c4;
  --ink:#152018;--muted:#5a7a60;
}

/* ── Page ── */
.kyc-header{background:linear-gradient(135deg,var(--green-dd),var(--green-d),var(--green-m));padding:2rem 0 3rem;border-bottom:3px solid var(--gold);position:relative;overflow:hidden;}
.kyc-header::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(200,146,42,.07) 1.5px,transparent 1.5px);background-size:24px 24px;pointer-events:none;}
.kyc-header h1{font-family:'Cinzel',serif;font-size:1.7rem;font-weight:900;color:#fff;}
.kyc-header h1 em{color:var(--gold-l);font-style:italic;}
.kyc-header p{color:rgba(255,255,255,.5);font-size:.85rem;margin-top:.3rem;}
.kyc-body{background:var(--ivory);padding:2rem 0 4rem;}
.kyc-wrap{max-width:720px;margin:0 auto;padding:0 1.5rem;}

/* ── Status banner ── */
.kyc-status-banner{
  border-radius:14px;padding:1.25rem 1.5rem;margin-bottom:1.75rem;
  display:flex;align-items:flex-start;gap:1rem;
  animation:fadeIn .5s ease both;
}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:none}}
.ksb-pending{background:rgba(200,146,42,.1);border:1.5px solid rgba(200,146,42,.25);}
.ksb-approved{background:rgba(15,123,92,.1);border:1.5px solid rgba(15,123,92,.25);}
.ksb-rejected{background:rgba(232,83,74,.1);border:1.5px solid rgba(232,83,74,.25);}
.ksb-icon{font-size:1.75rem;flex-shrink:0;}
.ksb-title{font-family:'Cinzel',serif;font-size:.95rem;font-weight:800;margin-bottom:.2rem;}
.ksb-pending .ksb-title{color:#92400E;}
.ksb-approved .ksb-title{color:var(--jade);}
.ksb-rejected .ksb-title{color:var(--coral);}
.ksb-desc{font-size:.82rem;line-height:1.6;color:var(--muted);}

/* ── Form card ── */
.kyc-card{background:#fff;border-radius:16px;border:1.5px solid var(--ivory-dd);box-shadow:0 4px 20px rgba(26,59,34,.06);overflow:hidden;margin-bottom:1.5rem;}
.kyc-card-header{
  background:linear-gradient(90deg,rgba(26,59,34,.05),rgba(26,59,34,.02));
  padding:.9rem 1.5rem;border-bottom:1.5px solid var(--ivory-dd);
  display:flex;align-items:center;gap:.6rem;
  font-family:'Cinzel',serif;font-size:.88rem;font-weight:800;color:var(--green-d);
}
.kyc-card-header .step-badge{
  width:26px;height:26px;border-radius:50%;
  background:var(--green-d);color:#fff;
  display:flex;align-items:center;justify-content:center;
  font-size:.7rem;font-weight:800;flex-shrink:0;
}
.kyc-card-body{padding:1.5rem;}

/* ── Form elements ── */
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;}
.form-group{margin-bottom:1rem;}
.form-label{
  display:block;font-size:.67rem;font-weight:800;
  color:var(--green-d);margin-bottom:.38rem;
  text-transform:uppercase;letter-spacing:.1em;
  font-family:'Cinzel',serif;
}
.form-label .req{color:var(--coral);}
.form-label .opt{color:var(--muted);font-weight:500;text-transform:none;letter-spacing:0;font-family:'Nunito',sans-serif;}
.form-control{
  width:100%;padding:.68rem .9rem;
  border:1.5px solid var(--ivory-dd);border-radius:10px;
  background:#fff;font-family:'Nunito',sans-serif;
  font-size:.9rem;color:var(--ink);
  transition:border-color .2s,box-shadow .2s;outline:none;
}
.form-control:focus{border-color:var(--green-l);box-shadow:0 0 0 3px rgba(26,59,34,.08);}
.form-control::placeholder{color:#b0c0b4;}
.form-control[disabled]{background:var(--ivory-d);color:var(--muted);cursor:not-allowed;}
select.form-control{cursor:pointer;}
.form-hint{font-size:.72rem;color:var(--muted);margin-top:.3rem;line-height:1.5;}

/* ── File upload ── */
.file-upload-zone{
  border:2px dashed var(--ivory-dd);border-radius:12px;
  padding:1.1rem 1rem;text-align:center;cursor:pointer;
  transition:all .2s;position:relative;overflow:hidden;
  background:var(--ivory);
}
.file-upload-zone:hover,.file-upload-zone.dragover{
  border-color:var(--green-l);background:rgba(26,59,34,.03);
}
.file-upload-zone input[type="file"]{
  position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;
}
.fuz-icon{font-size:1.6rem;margin-bottom:.4rem;display:block;}
.fuz-label{font-size:.78rem;font-weight:700;color:var(--green-d);margin-bottom:.15rem;}
.fuz-hint{font-size:.68rem;color:var(--muted);}
.fuz-preview{
  display:none;margin-top:.6rem;
  background:rgba(15,123,92,.08);border-radius:8px;
  padding:.5rem .75rem;font-size:.75rem;color:var(--jade);
  font-weight:700;align-items:center;gap:.4rem;
}
.fuz-preview.show{display:flex;}

/* ── Alert / messages ── */
.alert{padding:.85rem 1.1rem;border-radius:10px;font-size:.875rem;font-weight:600;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.5rem;}
.alert-danger{background:rgba(232,83,74,.09);color:#B91C1C;border:1px solid rgba(232,83,74,.25);}
.alert-success{background:rgba(15,123,92,.09);color:var(--jade);border:1px solid rgba(15,123,92,.25);}

/* ── Submit btn ── */
.kyc-submit-btn{
  width:100%;padding:.9rem;
  background:linear-gradient(135deg,var(--green-d),var(--green-m));
  color:#fff;border:none;border-radius:50px;
  font-family:'Cinzel',serif;font-size:.95rem;font-weight:800;
  cursor:pointer;transition:all .25s;
  display:flex;align-items:center;justify-content:center;gap:.5rem;
  box-shadow:0 4px 18px rgba(26,59,34,.3);
}
.kyc-submit-btn:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(26,59,34,.4);}
.kyc-submit-btn:disabled{opacity:.5;cursor:not-allowed;transform:none;}

/* ── Progress steps ── */
.kyc-steps{display:flex;gap:0;margin:1.5rem 0 2rem;position:relative;}
.kyc-steps::before{content:'';position:absolute;top:15px;left:15px;right:15px;height:2px;background:var(--ivory-dd);z-index:0;}
.kyc-step{flex:1;text-align:center;position:relative;z-index:1;}
.kstep-dot{
  width:30px;height:30px;border-radius:50%;
  background:var(--ivory-dd);color:var(--muted);
  display:flex;align-items:center;justify-content:center;
  font-size:.75rem;font-weight:800;margin:0 auto .4rem;
  transition:all .3s;border:2px solid transparent;
}
.kyc-step.done .kstep-dot{background:var(--jade);color:#fff;border-color:var(--jade-l);}
.kyc-step.active .kstep-dot{background:var(--green-d);color:#fff;border-color:var(--green-l);box-shadow:0 0 0 4px rgba(26,59,34,.12);}
.kstep-label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);}
.kyc-step.active .kstep-label{color:var(--green-d);}
.kyc-step.done .kstep-label{color:var(--jade);}

@media(max-width:640px){
  .form-row-2,.form-row-3{grid-template-columns:1fr;}
}
</style>

<!-- PAGE HEADER -->
<div class="kyc-header">
  <div class="container">
    <h1>📋 KYC <em>Verification</em></h1>
    <p>Complete your KYC to enable withdrawals and unlock full MBP privileges</p>
  </div>
</div>

<div class="kyc-body">
<div class="kyc-wrap">

  <!-- KYC Status Banner -->
  <?php if ($kycStatus === 'pending'): ?>
    <div class="kyc-status-banner ksb-pending">
      <div class="ksb-icon">⏳</div>
      <div>
        <div class="ksb-title">KYC Under Review</div>
        <div class="ksb-desc">Your KYC documents have been submitted on <?= date('d M Y', strtotime($kyc['submitted_at'])) ?>. Our team will verify within <strong>24–48 hours</strong>. You'll receive an email once reviewed.</div>
      </div>
    </div>

  <?php elseif ($kycStatus === 'approved'): ?>
    <div class="kyc-status-banner ksb-approved">
      <div class="ksb-icon">✅</div>
      <div>
        <div class="ksb-title">KYC Approved</div>
        <div class="ksb-desc">Your identity has been successfully verified on <?= date('d M Y', strtotime($kyc['reviewed_at'] ?? $kyc['submitted_at'])) ?>. You can now withdraw your wallet balance.</div>
      </div>
    </div>

  <?php elseif ($kycStatus === 'rejected'): ?>
    <div class="kyc-status-banner ksb-rejected">
      <div class="ksb-icon">❌</div>
      <div>
        <div class="ksb-title">KYC Rejected — Please Re-submit</div>
        <div class="ksb-desc">
          <?php if ($kyc['admin_note']): ?>
            <strong>Reason:</strong> <?= e($kyc['admin_note']) ?><br>
          <?php endif; ?>
          Please correct the issues and re-submit your KYC below.
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Success / Error alerts -->
  <?php if ($successMsg): ?>
    <div class="alert alert-success"><?= $successMsg ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
  <?php endif; ?>

  <!-- Don't show form if pending or approved -->
  <?php if ($kycStatus === 'pending' || $kycStatus === 'approved'): ?>
    <div style="text-align:center;padding:1rem 0">
      <a href="<?= APP_URL ?>/dashboard.php" style="display:inline-flex;align-items:center;gap:.4rem;background:var(--green-d);color:#fff;text-decoration:none;border-radius:50px;padding:.75rem 1.75rem;font-family:'Cinzel',serif;font-size:.88rem;font-weight:800">← Back to Dashboard</a>
    </div>

  <?php else: /* Show form for new or rejected */ ?>

  <!-- Progress steps -->
  <div class="kyc-steps">
    <div class="kyc-step active">
      <div class="kstep-dot">1</div>
      <div class="kstep-label">Personal</div>
    </div>
    <div class="kyc-step">
      <div class="kstep-dot">2</div>
      <div class="kstep-label">Address</div>
    </div>
    <div class="kyc-step">
      <div class="kstep-dot">3</div>
      <div class="kstep-label">Bank</div>
    </div>
    <div class="kyc-step">
      <div class="kstep-dot">4</div>
      <div class="kstep-label">Documents</div>
    </div>
  </div>

  <form method="POST" enctype="multipart/form-data" id="kycForm" novalidate>
    <input type="hidden" name="submit_kyc" value="1">

    <!-- ── Step 1: Personal Info ── -->
    <div class="kyc-card" id="step1">
      <div class="kyc-card-header">
        <div class="step-badge">1</div>
        Personal Information
      </div>
      <div class="kyc-card-body">
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Full Name <span class="req">*</span></label>
            <input type="text" name="full_name" class="form-control"
              placeholder="As on Aadhaar Card" required
              value="<?= e($kyc['full_name'] ?? '') ?>">
            <p class="form-hint">Enter name exactly as it appears on Aadhaar.</p>
          </div>
          <div class="form-group">
            <label class="form-label">Date of Birth <span class="req">*</span></label>
            <input type="date" name="dob" class="form-control" required
              max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
              value="<?= e($kyc['dob'] ?? '') ?>">
            <p class="form-hint">Must be 18 years or older.</p>
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Gender <span class="req">*</span></label>
            <select name="gender" class="form-control" required>
              <option value="">— Select —</option>
              <option value="male"   <?= ($kyc['gender']??'')==='male'  ?'selected':'' ?>>Male</option>
              <option value="female" <?= ($kyc['gender']??'')==='female'?'selected':'' ?>>Female</option>
              <option value="other"  <?= ($kyc['gender']??'')==='other' ?'selected':'' ?>>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Aadhaar Number <span class="req">*</span></label>
            <input type="text" name="aadhaar_number" class="form-control"
              placeholder="12-digit Aadhaar number" maxlength="12" required
              value="<?= e($kyc['aadhaar_number'] ?? '') ?>">
            <p class="form-hint">Your 12-digit unique identification number.</p>
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">PAN Number <span class="opt">(optional)</span></label>
            <input type="text" name="pan_number" class="form-control"
              placeholder="ABCDE1234F" maxlength="10"
              style="text-transform:uppercase"
              value="<?= e($kyc['pan_number'] ?? '') ?>">
            <p class="form-hint">Required for withdrawals above ₹50,000.</p>
          </div>
          <div class="form-group">
            <label class="form-label">Registered Mobile</label>
            <input type="text" class="form-control" value="<?= e($user['phone'] ?? 'Not set') ?>" disabled>
            <p class="form-hint">Update in profile settings.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Step 2: Address ── -->
    <div class="kyc-card" id="step2">
      <div class="kyc-card-header">
        <div class="step-badge">2</div>
        Address Details
      </div>
      <div class="kyc-card-body">
        <div class="form-group">
          <label class="form-label">Full Address <span class="req">*</span></label>
          <textarea name="address" class="form-control" rows="2"
            placeholder="House/Flat No., Street, Area"
            required><?= e($kyc['address'] ?? '') ?></textarea>
        </div>
        <div class="form-row-3">
          <div class="form-group">
            <label class="form-label">City <span class="req">*</span></label>
            <input type="text" name="city" class="form-control"
              placeholder="City" required value="<?= e($kyc['city'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">State <span class="req">*</span></label>
            <select name="state" class="form-control" required>
              <option value="">— State —</option>
              <?php foreach ($indianStates as $s): ?>
                <option value="<?= e($s) ?>" <?= ($kyc['state']??'')===$s?'selected':'' ?>><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Pincode <span class="req">*</span></label>
            <input type="text" name="pincode" class="form-control"
              placeholder="6-digit" maxlength="6" required
              value="<?= e($kyc['pincode'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ── Step 3: Bank Details ── -->
    <div class="kyc-card" id="step3">
      <div class="kyc-card-header">
        <div class="step-badge">3</div>
        Bank Account <span style="font-family:'Nunito',sans-serif;font-size:.75rem;font-weight:500;color:var(--muted);margin-left:.4rem">(for wallet withdrawals)</span>
      </div>
      <div class="kyc-card-body">
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Bank Account Number <span class="opt">(optional)</span></label>
            <input type="text" name="bank_account" class="form-control"
              placeholder="Enter account number"
              value="<?= e($kyc['bank_account'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">IFSC Code <span class="opt">(optional)</span></label>
            <input type="text" name="ifsc_code" class="form-control"
              placeholder="e.g. SBIN0001234" maxlength="11"
              style="text-transform:uppercase"
              value="<?= e($kyc['ifsc_code'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Bank Name <span class="opt">(optional)</span></label>
          <input type="text" name="bank_name" class="form-control"
            placeholder="e.g. State Bank of India"
            value="<?= e($kyc['bank_name'] ?? '') ?>">
        </div>
        <p class="form-hint" style="margin-top:0">Bank details are optional now but required before your first withdrawal request.</p>
      </div>
    </div>

    <!-- ── Step 4: Documents ── -->
    <div class="kyc-card" id="step4">
      <div class="kyc-card-header">
        <div class="step-badge">4</div>
        Upload Documents <span style="font-family:'Nunito',sans-serif;font-size:.75rem;font-weight:500;color:var(--muted);margin-left:.4rem">(JPG / PNG / PDF, max 5MB each)</span>
      </div>
      <div class="kyc-card-body">
        <div class="form-row-2">

          <div class="form-group">
            <label class="form-label">Aadhaar Front <span class="req">*</span></label>
            <div class="file-upload-zone" id="zone_aadhaar_front">
              <input type="file" name="aadhaar_front" accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this,'zone_aadhaar_front')">
              <span class="fuz-icon">🪪</span>
              <div class="fuz-label">Upload Aadhaar Front</div>
              <div class="fuz-hint">Click or drag file here</div>
              <div class="fuz-preview" id="prev_aadhaar_front">
                ✅ <span></span>
              </div>
              <?php if (!empty($kyc['aadhaar_front'])): ?>
                <div class="fuz-preview show">✅ <span>Existing file uploaded</span></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Aadhaar Back <span class="req">*</span></label>
            <div class="file-upload-zone" id="zone_aadhaar_back">
              <input type="file" name="aadhaar_back" accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this,'zone_aadhaar_back')">
              <span class="fuz-icon">🪪</span>
              <div class="fuz-label">Upload Aadhaar Back</div>
              <div class="fuz-hint">Click or drag file here</div>
              <div class="fuz-preview" id="prev_aadhaar_back">✅ <span></span></div>
              <?php if (!empty($kyc['aadhaar_back'])): ?>
                <div class="fuz-preview show">✅ <span>Existing file uploaded</span></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">PAN Card <span class="opt">(optional)</span></label>
            <div class="file-upload-zone" id="zone_pan_photo">
              <input type="file" name="pan_photo" accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this,'zone_pan_photo')">
              <span class="fuz-icon">🗂️</span>
              <div class="fuz-label">Upload PAN Card</div>
              <div class="fuz-hint">Click or drag file here</div>
              <div class="fuz-preview" id="prev_pan_photo">✅ <span></span></div>
              <?php if (!empty($kyc['pan_photo'])): ?>
                <div class="fuz-preview show">✅ <span>Existing file uploaded</span></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Selfie with Aadhaar <span class="opt">(optional)</span></label>
            <div class="file-upload-zone" id="zone_selfie">
              <input type="file" name="selfie" accept=".jpg,.jpeg,.png" onchange="previewFile(this,'zone_selfie')">
              <span class="fuz-icon">🤳</span>
              <div class="fuz-label">Upload Selfie</div>
              <div class="fuz-hint">Hold Aadhaar card next to your face</div>
              <div class="fuz-preview" id="prev_selfie">✅ <span></span></div>
              <?php if (!empty($kyc['selfie'])): ?>
                <div class="fuz-preview show">✅ <span>Existing file uploaded</span></div>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- Agree checkbox -->
        <div style="display:flex;align-items:flex-start;gap:.65rem;background:var(--ivory);border:1.5px solid var(--ivory-dd);border-radius:10px;padding:.85rem 1rem;margin:1rem 0">
          <input type="checkbox" name="agree" id="agreeChk" required
            style="margin-top:.2rem;width:16px;height:16px;flex-shrink:0;cursor:pointer;">
          <label for="agreeChk" style="font-size:.8rem;color:var(--ink);line-height:1.6;cursor:pointer">
            I confirm that all information provided is <strong>accurate and genuine</strong>. I consent to Mfills collecting, storing and verifying my KYC documents as per applicable laws. I understand that providing false information may result in account suspension.
          </label>
        </div>

        <button type="submit" class="kyc-submit-btn" id="kycSubmitBtn">
          📋 Submit KYC for Verification
        </button>

      </div>
    </div>

  </form>
  <?php endif; ?>

</div><!-- /.kyc-wrap -->
</div><!-- /.kyc-body -->

<script>
/* ── File preview ── */
function previewFile(input, zoneId) {
  var zone = document.getElementById(zoneId);
  var preview = zone.querySelector('.fuz-preview');
  if (input.files && input.files[0]) {
    var file = input.files[0];
    var name = file.name.length > 28 ? file.name.substring(0,26)+'…' : file.name;
    preview.querySelector('span').textContent = name;
    preview.classList.add('show');
    zone.style.borderColor = 'var(--jade)';
    zone.style.background  = 'rgba(15,123,92,.04)';
  }
}

/* ── Drag & drop ── */
document.querySelectorAll('.file-upload-zone').forEach(function(zone) {
  zone.addEventListener('dragover',  function(e){ e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', function(){ zone.classList.remove('dragover'); });
  zone.addEventListener('drop', function(e){
    e.preventDefault(); zone.classList.remove('dragover');
    var input = zone.querySelector('input[type="file"]');
    if (input) { input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change')); }
  });
});

/* ── Aadhaar formatter ── */
var aadhaarInput = document.querySelector('input[name="aadhaar_number"]');
if (aadhaarInput) {
  aadhaarInput.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g,'').substring(0,12);
  });
}

/* ── PAN uppercase ── */
var panInput = document.querySelector('input[name="pan_number"]');
if (panInput) {
  panInput.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
}

/* ── IFSC uppercase ── */
var ifscInput = document.querySelector('input[name="ifsc_code"]');
if (ifscInput) {
  ifscInput.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
}

/* ── Pincode digits only ── */
var pinInput = document.querySelector('input[name="pincode"]');
if (pinInput) {
  pinInput.addEventListener('input', function() { this.value = this.value.replace(/\D/g,'').substring(0,6); });
}

/* ── Form submit validation + loading ── */
var kycForm = document.getElementById('kycForm');
if (kycForm) {
  kycForm.addEventListener('submit', function(e) {
    var agree = document.getElementById('agreeChk');
    if (agree && !agree.checked) {
      e.preventDefault();
      agree.style.outline = '2px solid var(--coral)';
      agree.closest('div').style.borderColor = 'var(--coral)';
      alert('Please agree to the terms before submitting.');
      return;
    }
    var btn = document.getElementById('kycSubmitBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Submitting…'; }
  });
}

/* ── Step indicator auto-update ── */
function updateSteps() {
  var steps = document.querySelectorAll('.kyc-step');
  // Simple scroll-based: mark steps as done/active as user scrolls
  var cards = ['step1','step2','step3','step4'];
  var activeIdx = 0;
  cards.forEach(function(id, i) {
    var card = document.getElementById(id);
    if (!card) return;
    var rect = card.getBoundingClientRect();
    if (rect.top < window.innerHeight * 0.4) activeIdx = i;
  });
  steps.forEach(function(step, i) {
    step.classList.remove('active','done');
    if (i < activeIdx) step.classList.add('done');
    else if (i === activeIdx) step.classList.add('active');
  });
}
window.addEventListener('scroll', updateSteps);
updateSteps();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>