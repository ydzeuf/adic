<?php
/**
 * ADIC — private HTML-template mailer endpoint.
 *
 * Receives a POST from mailer.html:
 *   - token   : the admin password (must match ADMIN_TOKEN below)
 *   - to       : recipient email address (typed each time)
 *   - subject  : email subject
 *   - fromname : (optional) display name for the sender
 *   - template : an uploaded .html file — its contents become the email body
 *
 * Sends the HTML via PHP mail() and returns a small JSON result.
 *
 * SECURITY: this is an ADMIN-ONLY tool. Anyone with the token can send mail
 * from this domain, so keep the token secret and change it if it ever leaks.
 * Change ADMIN_TOKEN below to rotate the password.
 */

// ---------------------------------------------------------------------------
// CONFIG — admin password, stored as a SHA-256 hash (never in plaintext, so
// this file is safe to commit). To change the password, compute the new hash:
//   PHP:   echo hash('sha256', 'YOUR-NEW-PASSWORD');
//   shell: printf '%s' 'YOUR-NEW-PASSWORD' | sha256sum
// and paste the result below. The current password is the one you were given.
// ---------------------------------------------------------------------------
const ADMIN_TOKEN_HASH = 'a701e304d007968ab6a2119888735897abdeafe46978da0068b454546a53a920';

// The "From" address. On shared hosting mail() is far more likely to be
// accepted/delivered when the From is a real mailbox ON this domain.
const FROM_EMAIL  = 'info@adic.sa';
const FROM_NAME   = 'ADIC';

// Max template size (bytes) — guards against giant uploads.
const MAX_BYTES   = 2 * 1024 * 1024; // 2 MB

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
$token = (string) ($_POST['token'] ?? '');
if (!hash_equals(ADMIN_TOKEN_HASH, hash('sha256', $token))) {
    fail('Wrong password.', 403);
}

// --- Inputs ---------------------------------------------------------------
$to       = trim($_POST['to'] ?? '');
$subject  = trim($_POST['subject'] ?? '');
$fromname = trim($_POST['fromname'] ?? '') ?: FROM_NAME;

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

// --- Send -----------------------------------------------------------------
// Header injection guard: strip CR/LF from anything that goes into headers.
$clean = static fn ($s) => str_replace(["\r", "\n"], '', $s);
$subject  = $clean($subject);
$fromname = $clean($fromname);

$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
$headers .= 'From: ' . $fromname . ' <' . FROM_EMAIL . '>' . "\r\n";
$headers .= 'Reply-To: ' . FROM_EMAIL . "\r\n";

$sent = @mail($to, $subject, $html, $headers, '-f' . FROM_EMAIL);

if (!$sent) {
    fail('mail() returned false — the host likely blocks PHP mail(). '
        . 'You will need an SMTP account instead.', 502);
}

echo json_encode(['ok' => true, 'to' => $to]);
