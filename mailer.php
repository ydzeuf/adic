<?php
/**
 * ADIC — private HTML-template mailer endpoint (SMTP version).
 *
 * Sends an uploaded .html file as the body of an HTML email, through an
 * AUTHENTICATED SMTP account (Gmail by default) — NOT PHP mail(), which this
 * GoDaddy host accepts but does not actually deliver to Gmail/Outlook.
 *
 * Reuses the PHPMailer library that ships with WordPress (wp-includes), so
 * nothing extra needs installing.
 *
 * The SMTP username + app-password are supplied with each request from
 * mailer.html and are NEVER stored on the server or committed to git.
 *
 * POST fields:
 *   token     admin password (must match ADMIN_TOKEN_HASH)
 *   smtp_user SMTP login = the sending email (e.g. you@gmail.com)
 *   smtp_pass SMTP password — for Gmail, a 16-char App Password
 *   smtp_host optional, default smtp.gmail.com
 *   smtp_port optional, default 587 (STARTTLS)
 *   to        recipient email
 *   subject   email subject
 *   fromname  optional display name (default = ADIC)
 *   template  the uploaded .html / .htm file (becomes the email body)
 */

// ---------------------------------------------------------------------------
// CONFIG — admin gate, stored as a SHA-256 hash (safe to commit). Rotate with:
//   printf '%s' 'YOUR-NEW-PASSWORD' | sha256sum
// ---------------------------------------------------------------------------
const ADMIN_TOKEN_HASH = 'a701e304d007968ab6a2119888735897abdeafe46978da0068b454546a53a920';

const MAX_BYTES = 2 * 1024 * 1024; // 2 MB template cap

header('Content-Type: application/json; charset=UTF-8');

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Use POST.', 405);
}

// --- Auth -----------------------------------------------------------------
if (!hash_equals(ADMIN_TOKEN_HASH, hash('sha256', (string) ($_POST['token'] ?? '')))) {
    fail('Wrong admin password.', 403);
}

// --- Inputs ---------------------------------------------------------------
$smtpUser = trim($_POST['smtp_user'] ?? '');
$smtpPass = (string) ($_POST['smtp_pass'] ?? '');
$smtpHost = trim($_POST['smtp_host'] ?? '') ?: 'smtp.gmail.com';
$smtpPort = (int) (trim($_POST['smtp_port'] ?? '') ?: 587);
$to       = trim($_POST['to'] ?? '');
$subject  = trim($_POST['subject'] ?? '');
$fromname = trim($_POST['fromname'] ?? '') ?: 'ADIC';

if (!filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
    fail('SMTP username must be the sending email address (e.g. you@gmail.com).');
}
if ($smtpPass === '') {
    fail('SMTP app password is required.');
}
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fail('Recipient is not a valid email address.');
}
if ($subject === '') {
    fail('Subject is required.');
}

// --- Uploaded HTML template ----------------------------------------------
if (empty($_FILES['template']) || $_FILES['template']['error'] !== UPLOAD_ERR_OK) {
    fail('Please attach an HTML template file.');
}
if ($_FILES['template']['size'] > MAX_BYTES) {
    fail('Template is too large (max 2 MB).');
}
$ext = strtolower(pathinfo($_FILES['template']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['html', 'htm'], true)) {
    fail('Only .html / .htm templates are allowed.');
}
$html = file_get_contents($_FILES['template']['tmp_name']);
if ($html === false || $html === '') {
    fail('Could not read the uploaded template.');
}

// --- Load PHPMailer from the WordPress that sits beneath this static site --
$pm = __DIR__ . '/wp-includes/PHPMailer/';
if (!is_file($pm . 'PHPMailer.php')) {
    fail('PHPMailer not found on the server (expected under wp-includes/PHPMailer). '
        . 'Tell the developer so they can bundle it.', 500);
}
require_once $pm . 'Exception.php';
require_once $pm . 'PHPMailer.php';
require_once $pm . 'SMTP.php';

// --- Send via authenticated SMTP -----------------------------------------
$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->Port       = $smtpPort;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    // 587 => STARTTLS, 465 => implicit TLS
    $mail->SMTPSecure = ($smtpPort === 465)
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Timeout    = 20;
    $mail->CharSet    = 'UTF-8';

    // Gmail forces the From to the authenticated account; match it to avoid
    // rejection, but keep the friendly display name from the form.
    $mail->setFrom($smtpUser, $fromname);
    $mail->addAddress($to);
    $mail->addReplyTo($smtpUser, $fromname);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    $mail->AltBody = 'This message contains an HTML template. Please view it in an HTML-capable email client.';

    $mail->send();
    echo json_encode(['ok' => true, 'to' => $to]);
} catch (\Throwable $e) {
    // ErrorInfo carries the SMTP server's own message (e.g. bad app password).
    $detail = $mail->ErrorInfo ?: $e->getMessage();
    fail('SMTP send failed: ' . $detail, 502);
}
