<?php
/**
 * mailer.php — Mfills Email Helper
 * Pure PHP SMTP — No PHPMailer / No Composer needed!
 *
 * Configure SMTP settings in /includes/config.php:
 *   define('MAIL_HOST',       'smtp.gmail.com');
 *   define('MAIL_PORT',       587);
 *   define('MAIL_USERNAME',   'your@gmail.com');
 *   define('MAIL_PASSWORD',   'your_app_password');
 *   define('MAIL_FROM',       'your@gmail.com');
 *   define('MAIL_FROM_NAME',  'Mfills Partner Portal');
 *   define('MAIL_ENCRYPTION', 'tls');
 */

/**
 * Core SMTP send function — Pure PHP, no library needed
 */
function mfillsSendMail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): array
{
    $host       = defined('MAIL_HOST')       ? MAIL_HOST       : 'smtp.gmail.com';
    $port       = defined('MAIL_PORT')       ? MAIL_PORT       : 587;
    $username   = defined('MAIL_USERNAME')   ? MAIL_USERNAME   : '';
    $password   = defined('MAIL_PASSWORD')   ? MAIL_PASSWORD   : '';
    $fromEmail  = defined('MAIL_FROM')       ? MAIL_FROM       : $username;
    $fromName   = defined('MAIL_FROM_NAME')  ? MAIL_FROM_NAME  : 'Mfills Partner Portal';
    $encryption = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'tls';

    // Try PHPMailer first if available
    $autoload   = __DIR__ . '/../vendor/autoload.php';
    $manualLoad = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';

    if (file_exists($autoload)) {
        require_once $autoload;
    } elseif (file_exists($manualLoad)) {
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    }

    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return _sendViaPHPMailer($toEmail, $toName, $subject, $htmlBody, $textBody,
            $host, $port, $username, $password, $fromEmail, $fromName, $encryption);
    }

    // ── Pure PHP SMTP ──
    return _sendViaRawSMTP($toEmail, $toName, $subject, $htmlBody, $textBody,
        $host, $port, $username, $password, $fromEmail, $fromName);
}

/**
 * Pure PHP Raw SMTP sender (TLS/STARTTLS on port 587)
 */
function _sendViaRawSMTP(
    string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody,
    string $host, int $port, string $username, string $password,
    string $fromEmail, string $fromName
): array {

    $altBody = $textBody ?: strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $htmlBody));

    // Build MIME message
    $boundary  = md5(uniqid((string)time()));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromEncoded    = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $toEncoded      = '=?UTF-8?B?' . base64_encode($toName)   . '?=';
    $date           = date('r');

    $message  = "Date: {$date}\r\n";
    $message .= "From: {$fromEncoded} <{$fromEmail}>\r\n";
    $message .= "To: {$toEncoded} <{$toEmail}>\r\n";
    $message .= "Subject: {$encodedSubject}\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $message .= "\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($altBody)) . "\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $message .= "--{$boundary}--\r\n";

    try {
        // Connect
        $errno = 0; $errstr = '';
        $socket = fsockopen($host, $port, $errno, $errstr, 15);
        if (!$socket) {
            return ['success' => false, 'message' => "SMTP connect failed: {$errstr} ({$errno})"];
        }

        $read = fgets($socket, 512);
        if (substr($read, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'message' => "SMTP greeting failed: {$read}"];
        }

        // EHLO
        $serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        fwrite($socket, "EHLO {$serverName}\r\n");
        $ehlo = '';
        while ($line = fgets($socket, 512)) {
            $ehlo .= $line;
            if ($line[3] === ' ') break;
        }

        // STARTTLS
        fwrite($socket, "STARTTLS\r\n");
        $tls = fgets($socket, 512);
        if (substr($tls, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'message' => "STARTTLS failed: {$tls}"];
        }

        // Upgrade to TLS
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        // Re-EHLO after TLS
        fwrite($socket, "EHLO {$serverName}\r\n");
        while ($line = fgets($socket, 512)) {
            if ($line[3] === ' ') break;
        }

        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $auth = fgets($socket, 512);
        if (substr($auth, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'message' => "AUTH LOGIN failed: {$auth}"];
        }

        fwrite($socket, base64_encode($username) . "\r\n");
        $userResp = fgets($socket, 512);
        if (substr($userResp, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'message' => "Username rejected: {$userResp}"];
        }

        fwrite($socket, base64_encode($password) . "\r\n");
        $passResp = fgets($socket, 512);
        if (substr($passResp, 0, 3) !== '235') {
            fclose($socket);
            return ['success' => false, 'message' => "Authentication failed: {$passResp}"];
        }

        // MAIL FROM
        fwrite($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        $mf = fgets($socket, 512);
        if (substr($mf, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "MAIL FROM failed: {$mf}"];
        }

        // RCPT TO
        fwrite($socket, "RCPT TO:<{$toEmail}>\r\n");
        $rt = fgets($socket, 512);
        if (substr($rt, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "RCPT TO failed: {$rt}"];
        }

        // DATA
        fwrite($socket, "DATA\r\n");
        $data = fgets($socket, 512);
        if (substr($data, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'message' => "DATA failed: {$data}"];
        }

        fwrite($socket, $message . ".\r\n");
        $sent = fgets($socket, 512);
        if (substr($sent, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "Message send failed: {$sent}"];
        }

        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return ['success' => true, 'message' => 'Email sent successfully via SMTP'];

    } catch (\Throwable $e) {
        return ['success' => false, 'message' => 'SMTP error: ' . $e->getMessage()];
    }
}

/**
 * PHPMailer sender (used if PHPMailer is available)
 */
function _sendViaPHPMailer(
    string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody,
    string $host, int $port, string $username, string $password,
    string $fromEmail, string $fromName, string $encryption
): array {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = $encryption;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addReplyTo($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $htmlBody));
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully via PHPMailer'];
    } catch (\Exception $e) {
        error_log("Mfills PHPMailer error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'PHPMailer error: ' . $mail->ErrorInfo];
    }
}

/**
 * Send Welcome / Registration email with MBPIN
 */
function sendWelcomeMail(string $email, string $username, string $mbpin, string $referralCode, string $referrerName = ''): array
{
    $appUrl   = defined('APP_URL') ? APP_URL : 'https://www.mfills.com';
    $dashUrl  = $appUrl . '/dashboard.php';
    $shopUrl  = $appUrl . '/shop.php';
    $refUrl   = $appUrl . '/register.php?ref=' . urlencode($referralCode);
    $logoUrl  = $appUrl . '/includes/images/logo2.png';
    $year     = date('Y');

    $subject = "Welcome to Mfills! Your MBPIN: {$mbpin}";

    $referrerLine = $referrerName
        ? "<p style='margin:.35rem 0 0;font-size:.82rem;color:#5a7a60'>Referred by: <strong style='color:#1a3b22'>{$referrerName}</strong></p>"
        : '';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Welcome to Mfills</title>
</head>
<body style="margin:0;padding:0;background:#0e2414;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0e2414;padding:32px 16px">
<tr><td align="center">
<table width="100%" style="max-width:560px;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.5)">

  <!-- HEADER -->
  <tr>
    <td style="background:linear-gradient(135deg,#1a3b22 0%,#2a6336 100%);padding:36px 40px;text-align:center;border-bottom:3px solid #c8922a">
      <img src="{$logoUrl}" alt="Mfills" height="48" style="display:block;margin:0 auto 14px">
      <div style="font-family:Georgia,serif;font-size:1.5rem;font-weight:900;color:#fff;letter-spacing:.04em">MFILLS</div>
      <div style="font-size:.6rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:rgba(200,146,42,.7);margin-top:2px">Business Partner Portal</div>
    </td>
  </tr>

  <!-- WELCOME BAND -->
  <tr>
    <td style="background:#c8922a;padding:14px 40px;text-align:center">
      <span style="font-size:1.05rem;font-weight:800;color:#0e2414;letter-spacing:.05em">Welcome, {$username}! You're officially an MBP.</span>
    </td>
  </tr>

  <!-- BODY -->
  <tr>
    <td style="background:#f8f5ef;padding:36px 40px">

      <!-- OFFICIAL REGISTRATION INFO -->
      <div style="background:#fff;border:1.5px solid #ddd5c4;border-radius:12px;padding:20px 24px;margin-bottom:24px;border-left:4px solid #c8922a">
        <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#1a3b22;margin-bottom:10px">About Your Registration</div>
        <p style="font-size:.88rem;color:#152018;line-height:1.75;margin:0 0 10px">
          Joining Mfills is <strong>simple and completely free of cost</strong>. As an individual 18 years of age or older, you have successfully become a <strong>Mfills Business Partner (MBP)</strong> through the official website <strong>www.mfills.com</strong>.
        </p>
        <p style="font-size:.88rem;color:#152018;line-height:1.75;margin:0">
          This system ensures that every partner has the necessary tools to <strong>monitor, manage, and grow</strong> their Mfills business efficiently.
        </p>
      </div>

      <p style="font-size:.95rem;color:#1a3b22;line-height:1.7;margin:0 0 1.5rem">
        Your registration is complete and your account is now active. Below are your partner credentials — please save them safely.
      </p>

      <!-- MBPIN CARD -->
      <div style="background:linear-gradient(135deg,#1a3b22,#2a6336);border-radius:14px;padding:24px 28px;margin-bottom:24px;text-align:center">
        <div style="font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.15em;color:rgba(200,146,42,.7);margin-bottom:8px">Your MBPIN</div>
        <div style="font-size:2rem;font-weight:900;color:#e0aa40;letter-spacing:.08em;font-family:'Courier New',monospace">{$mbpin}</div>
        <div style="font-size:.72rem;color:rgba(255,255,255,.45);margin-top:6px">Mfills Business Partner Identification Number</div>
        {$referrerLine}
      </div>

      <!-- USERNAME + REFERRAL CODE -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px">
        <tr>
          <td style="padding:0 6px 12px 0" width="50%">
            <div style="background:#fff;border:1.5px solid #ddd5c4;border-radius:10px;padding:14px 16px;border-top:3px solid #1a3b22">
              <div style="font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#5a7a60;margin-bottom:4px">Username</div>
              <div style="font-weight:700;color:#152018;font-size:.95rem">{$username}</div>
            </div>
          </td>
          <td style="padding:0 0 12px 6px" width="50%">
            <div style="background:#fff;border:1.5px solid #ddd5c4;border-radius:10px;padding:14px 16px;border-top:3px solid #c8922a">
              <div style="font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#5a7a60;margin-bottom:4px">Referral Code</div>
              <div style="font-weight:700;color:#152018;font-size:.95rem">{$referralCode}</div>
            </div>
          </td>
        </tr>
      </table>

      <!-- BENEFITS LIST — Official Content -->
      <div style="background:#fff;border:1.5px solid #ddd5c4;border-radius:12px;padding:20px 24px;margin-bottom:24px">
        <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#1a3b22;margin-bottom:14px">What You Get as an MBP</div>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding:7px 0;font-size:.875rem;color:#152018;border-bottom:1px solid #f0ebe0">
              <strong style="color:#c8922a">MBPIN</strong> — Your unique identification number (sent to this email)
            </td>
          </tr>
          <tr>
            <td style="padding:7px 0;font-size:.875rem;color:#152018;border-bottom:1px solid #f0ebe0">
              <strong style="color:#1a3b22">Advanced Partner Dashboard</strong> — Monitor and manage your entire business activity
            </td>
          </tr>
          <tr>
            <td style="padding:7px 0;font-size:.875rem;color:#152018;border-bottom:1px solid #f0ebe0">
              <strong style="color:#1a3b22">ID Card Download</strong> — Track purchases, update KYC &amp; receive company notifications
            </td>
          </tr>
          <tr>
            <td style="padding:7px 0;font-size:.875rem;color:#152018">
              <strong style="color:#1a3b22">MShop Access</strong> — Full access to the official Mfills product-purchasing platform
            </td>
          </tr>
        </table>
      </div>

      <!-- REFERRAL LINK -->
      <div style="background:rgba(15,123,92,.07);border:1.5px solid rgba(15,123,92,.2);border-radius:10px;padding:16px 20px;margin-bottom:24px">
        <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#0F7B5C;margin-bottom:8px">Your Referral Link</div>
        <div style="background:#fff;border:1px solid #ddd5c4;border-radius:8px;padding:10px 14px;font-size:.8rem;color:#5a7a60;word-break:break-all;font-family:'Courier New',monospace">{$refUrl}</div>
        <p style="font-size:.75rem;color:#5a7a60;margin:8px 0 0;line-height:1.5">Share this link. When your network purchases Mfills products, you earn PSB commissions automatically!</p>
      </div>

      <!-- CTA BUTTONS -->
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center" style="padding:0 6px 0 0">
            <a href="{$dashUrl}" style="display:block;background:linear-gradient(135deg,#1a3b22,#2a6336);color:#fff;text-decoration:none;border-radius:50px;padding:13px 24px;font-weight:800;font-size:.88rem;text-align:center">Go to Dashboard</a>
          </td>
          <td align="center" style="padding:0 0 0 6px">
            <a href="{$shopUrl}" style="display:block;background:linear-gradient(135deg,#c8922a,#e0aa40);color:#0e2414;text-decoration:none;border-radius:50px;padding:13px 24px;font-weight:800;font-size:.88rem;text-align:center">Visit MShop</a>
          </td>
        </tr>
      </table>

    </td>
  </tr>

  <!-- PSB STRIP -->
  <tr>
    <td style="background:#1a3b22;padding:16px 40px">
      <div style="text-align:center;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(200,146,42,.6);margin-bottom:10px">Partner Sales Bonus (PSB) — 7 Levels</div>
      <table width="100%" cellpadding="0" cellspacing="6">
        <tr>
          <td align="center" style="background:rgba(200,146,42,.15);border-radius:6px;padding:5px 3px"><div style="font-size:.58rem;color:rgba(255,255,255,.4)">L1</div><div style="font-size:.8rem;font-weight:800;color:#e0aa40">15%</div></td>
          <td align="center" style="background:rgba(255,255,255,.05);border-radius:6px;padding:5px 3px"><div style="font-size:.58rem;color:rgba(255,255,255,.4)">L2</div><div style="font-size:.8rem;font-weight:800;color:rgba(255,255,255,.7)">8%</div></td>
          <td align="center" style="background:rgba(255,255,255,.05);border-radius:6px;padding:5px 3px"><div style="font-size:.58rem;color:rgba(255,255,255,.4)">L3</div><div style="font-size:.8rem;font-weight:800;color:rgba(255,255,255,.7)">6%</div></td>
          <td align="center" style="background:rgba(255,255,255,.05);border-radius:6px;padding:5px 3px"><div style="font-size:.58rem;color:rgba(255,255,255,.4)">L4</div><div style="font-size:.8rem;font-weight:800;color:rgba(255,255,255,.7)">4%</div></td>
          <td align="center" style="background:rgba(255,255,255,.05);border-radius:6px;padding:5px 3px"><div style="font-size:.58rem;color:rgba(255,255,255,.4)">L5</div><div style="font-size:.8rem;font-weight:800;color:rgba(255,255,255,.7)">3%</div></td>
          <td align="center" style="background:rgba(255,255,255,.05);border-radius:6px;padding:5px 3px"><div style="font-size:.58rem;color:rgba(255,255,255,.4)">L6</div><div style="font-size:.8rem;font-weight:800;color:rgba(255,255,255,.7)">2%</div></td>
          <td align="center" style="background:rgba(255,255,255,.05);border-radius:6px;padding:5px 3px"><div style="font-size:.58rem;color:rgba(255,255,255,.4)">L7</div><div style="font-size:.8rem;font-weight:800;color:rgba(255,255,255,.7)">2%</div></td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- FOOTER -->
  <tr>
    <td style="background:#0e2414;padding:20px 40px;text-align:center;border-top:1px solid rgba(200,146,42,.1)">
      <p style="font-size:.72rem;color:rgba(255,255,255,.25);margin:0 0 6px;line-height:1.6">
        This email was sent because you registered at Mfills Business Partner Portal.<br>
        If you did not register, please ignore this email.
      </p>
      <p style="font-size:.68rem;color:rgba(200,146,42,.35);margin:0">
        &copy; {$year} Mfills &middot; All rights reserved &middot;
        <a href="{$appUrl}" style="color:rgba(200,146,42,.5);text-decoration:none">www.mfills.com</a>
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;

    return mfillsSendMail($email, $username, $subject, $htmlBody);
}

/**
 * Send KYC status update email
 */
function sendKycStatusMail(string $email, string $username, string $status, string $reason = ''): array
{
    $appUrl  = defined('APP_URL') ? APP_URL : 'https://www.mfills.com';
    $dashUrl = $appUrl . '/dashboard.php';
    $year    = date('Y');

    $isApproved  = strtolower($status) === 'approved';
    $statusColor = $isApproved ? '#0F7B5C' : '#E8534A';
    $statusIcon  = $isApproved ? 'KYC Approved' : 'KYC Rejected';
    $subject     = "Mfills KYC Update — {$statusIcon}";

    $reasonBlock = (!$isApproved && $reason)
        ? "<div style='background:rgba(232,83,74,.08);border:1px solid rgba(232,83,74,.2);border-radius:8px;padding:12px 16px;margin:16px 0;font-size:.83rem;color:#B91C1C'><strong>Reason:</strong> {$reason}</div>"
        : '';

    $bodyText = $isApproved
        ? "Your KYC has been successfully verified. You can now withdraw your wallet balance and access all MBP features."
        : "Unfortunately, your KYC verification could not be completed. Please re-submit with correct documents.";

    $htmlBody = <<<HTML
<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f8f5ef;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px"><tr><td align="center">
<table style="max-width:520px;width:100%;background:#fff;border-radius:16px;overflow:hidden;border:1.5px solid #ddd5c4;box-shadow:0 8px 32px rgba(0,0,0,.08)">
  <tr><td style="background:linear-gradient(135deg,#1a3b22,#2a6336);padding:28px 36px;text-align:center;border-bottom:3px solid #c8922a">
    <div style="font-size:1.2rem;font-weight:900;color:#fff;letter-spacing:.04em">MFILLS</div>
    <div style="font-size:.6rem;color:rgba(200,146,42,.7);letter-spacing:.15em;text-transform:uppercase;margin-top:2px">Business Partner Portal</div>
  </td></tr>
  <tr><td style="background:{$statusColor};padding:12px 36px;text-align:center">
    <span style="font-size:.95rem;font-weight:800;color:#fff">{$statusIcon} — {$username}</span>
  </td></tr>
  <tr><td style="padding:28px 36px">
    <p style="font-size:.9rem;color:#152018;line-height:1.7;margin:0 0 12px">{$bodyText}</p>
    {$reasonBlock}
    <a href="{$dashUrl}" style="display:inline-block;background:linear-gradient(135deg,#1a3b22,#2a6336);color:#fff;text-decoration:none;border-radius:50px;padding:11px 28px;font-weight:800;font-size:.85rem;margin-top:8px">Go to Dashboard</a>
  </td></tr>
  <tr><td style="background:#0e2414;padding:16px 36px;text-align:center">
    <p style="font-size:.68rem;color:rgba(255,255,255,.3);margin:0">&copy; {$year} Mfills &middot; All rights reserved</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;

    return mfillsSendMail($email, $username, $subject, $htmlBody);
}

/**
 * Send notification/announcement email to a partner
 */
function sendNotificationMail(string $email, string $username, string $title, string $message, string $ctaText = '', string $ctaUrl = ''): array
{
    $appUrl  = defined('APP_URL') ? APP_URL : 'https://www.mfills.com';
    $year    = date('Y');
    $subject = "Mfills Update: {$title}";

    $ctaBlock = ($ctaText && $ctaUrl)
        ? "<a href='{$ctaUrl}' style='display:inline-block;background:linear-gradient(135deg,#c8922a,#e0aa40);color:#0e2414;text-decoration:none;border-radius:50px;padding:11px 28px;font-weight:800;font-size:.85rem;margin-top:12px'>{$ctaText}</a>"
        : '';

    $htmlBody = <<<HTML
<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f8f5ef;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px"><tr><td align="center">
<table style="max-width:520px;width:100%;background:#fff;border-radius:16px;overflow:hidden;border:1.5px solid #ddd5c4;box-shadow:0 8px 32px rgba(0,0,0,.08)">
  <tr><td style="background:linear-gradient(135deg,#1a3b22,#2a6336);padding:28px 36px;text-align:center;border-bottom:3px solid #c8922a">
    <div style="font-size:1.2rem;font-weight:900;color:#fff;letter-spacing:.04em">MFILLS</div>
    <div style="font-size:.6rem;color:rgba(200,146,42,.7);letter-spacing:.15em;text-transform:uppercase;margin-top:2px">Business Partner Portal</div>
  </td></tr>
  <tr><td style="background:#1a3b22;padding:12px 36px">
    <span style="font-size:.9rem;font-weight:800;color:#e0aa40">{$title}</span>
  </td></tr>
  <tr><td style="padding:28px 36px">
    <p style="font-size:.72rem;color:#5a7a60;margin:0 0 14px">Dear <strong style="color:#1a3b22">{$username}</strong>,</p>
    <div style="font-size:.9rem;color:#152018;line-height:1.75">{$message}</div>
    {$ctaBlock}
  </td></tr>
  <tr><td style="background:#0e2414;padding:16px 36px;text-align:center">
    <p style="font-size:.68rem;color:rgba(255,255,255,.3);margin:0">&copy; {$year} Mfills &middot; All rights reserved &middot; <a href="{$appUrl}" style="color:rgba(200,146,42,.4);text-decoration:none">www.mfills.com</a></p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;

    return mfillsSendMail($email, $username, $subject, $htmlBody);
}