<?php
/**
 * ADIC — private HTML-template mailer endpoint (Brevo HTTPS API version).
 *
 * This GoDaddy host BLOCKS outbound SMTP (ports 25/465/587 time out), and its
 * PHP mail() hands off to a low-reputation relay that Gmail drops. So we send
 * through Brevo's transactional email API over HTTPS (port 443), which is not
 * blocked. Nothing extra to install — just PHP cURL.
 *
 * The Brevo API key is supplied with each request from mailer.html and is
 * NEVER stored on the server or committed to git.
 *
 * POST fields:
 *   token     admin password (must match ADMIN_TOKEN_HASH)
 *   api_key   Brevo API key (starts with "xkeysib-")
 *   sender    sender email — must be a VERIFIED sender in your Brevo account
 *   fromname  optional display name (default = ADIC)
 *   to        recipient email
 *   subject   email subject
 *   template  the uploaded .html / .htm file (becomes htmlContent)
 */

// ---------------------------------------------------------------------------
// CONFIG — admin gate, stored as a SHA-256 hash (safe to commit). Rotate with:
//   printf '%s' 'YOUR-NEW-PASSWORD' | sha256sum
// ---------------------------------------------------------------------------
const ADMIN_TOKEN_HASH = 'a701e304d007968ab6a2119888735897abdeafe46978da0068b454546a53a920';

const MAX_BYTES  = 2 * 1024 * 1024; // 2 MB template cap
const BREVO_URL  = 'https://api.brevo.com/v3/smtp/email';

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
$apiKey   = trim($_POST['api_key'] ?? '');
$sender   = trim($_POST['sender'] ?? '');
$fromname = trim($_POST['fromname'] ?? '') ?: 'ADIC';
$to       = trim($_POST['to'] ?? '');
$subject  = trim($_POST['subject'] ?? '');

if ($apiKey === '') {
    fail('Brevo API key is required.');
}
if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) {
    fail('Sender must be a valid email address that is verified in your Brevo account.');
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

// --- Send via Brevo transactional API over HTTPS -------------------------
if (!function_exists('curl_init')) {
    fail('PHP cURL is not available on this server.', 500);
}

$payload = json_encode([
    'sender'      => ['name' => $fromname, 'email' => $sender],
    'to'          => [['email' => $to]],
    'subject'     => $subject,
    'htmlContent' => $html,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init(BREVO_URL);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_HTTPHEADER     => [
        'accept: application/json',
        'content-type: application/json',
        'api-key: ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS     => $payload,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

if ($body === false) {
    fail('Could not reach the Brevo API: ' . $cerr, 502);
}

$resp = json_decode($body, true);

// Brevo returns 201 Created with a messageId on success.
if ($code === 201) {
    echo json_encode(['ok' => true, 'to' => $to, 'messageId' => $resp['messageId'] ?? null]);
    exit;
}

// Otherwise surface Brevo's own error message (e.g. unrecognised key, sender
// not verified) so the admin can fix it.
$brevoMsg = is_array($resp) ? ($resp['message'] ?? $body) : $body;
fail('Brevo rejected the send (HTTP ' . $code . '): ' . $brevoMsg, 502);
