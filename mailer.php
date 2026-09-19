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

    /*
     * CRITICAL:
     *
     * This awareness endpoint intentionally refuses
     * payment information.
     */

    $forbiddenFields = [
        'card_number',
        'card_cvc',
        'card_expiry',
        'cvv',
        'cvc',
        'password',
        'otp'
    ];


    foreach ($forbiddenFields as $field) {

        if (
            isset($_POST[$field]) &&
            trim((string) $_POST[$field]) !== ''
        ) {

            fail(
                'Security-awareness endpoint does not accept payment or authentication data.',
                400
            );
        }
    }


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


    /*
     * Only harmless lesson metadata.
     */

    $site = trim(
        (string) ($_POST['site'] ?? 'adic.sa')
    );


    $plan = trim(
        (string) ($_POST['plan'] ?? 'Unknown')
    );


    $price = trim(
        (string) ($_POST['price'] ?? '')
    );


    /*
     * Limit lengths so somebody cannot send giant payloads.
     */

    $site =
        mb_substr(
            $site,
            0,
            150
        );


    $plan =
        mb_substr(
            $plan,
            0,
            100
        );


    $price =
        mb_substr(
            $price,
            0,
            50
        );


    $date =
        gmdate('Y-m-d H:i:s') . ' UTC';


    $safeSite =
        htmlEscape($site);


    $safePlan =
        htmlEscape($plan);


    $safePrice =
        htmlEscape($price);


    $safeDate =
        htmlEscape($date);


    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ADIC Security Awareness Lesson</title>
</head>

<body style="
    margin:0;
    padding:30px;
    background:#f5f5f5;
    font-family:Arial,Helvetica,sans-serif;
    color:#16181d;
">

<div style="
    max-width:620px;
    margin:auto;
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:28px;
">

    <h2 style="
        margin:0 0 18px;
        font-size:20px;
    ">
        ADIC Security Awareness Lesson
    </h2>

    <p style="
        margin:0 0 20px;
        line-height:1.6;
    ">
        A participant submitted the simulated checkout page.
    </p>

    <table
        cellpadding="8"
        cellspacing="0"
        style="
            width:100%;
            border-collapse:collapse;
            font-size:14px;
        "
    >

        <tr>
            <td style="border-bottom:1px solid #eee;">
                <strong>Site</strong>
            </td>

            <td style="border-bottom:1px solid #eee;">
                {$safeSite}
            </td>
        </tr>

        <tr>
            <td style="border-bottom:1px solid #eee;">
                <strong>Plan</strong>
            </td>

            <td style="border-bottom:1px solid #eee;">
                {$safePlan}
            </td>
        </tr>

        <tr>
            <td style="border-bottom:1px solid #eee;">
                <strong>Displayed price</strong>
            </td>

            <td style="border-bottom:1px solid #eee;">
                {$safePrice}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Time</strong>
            </td>

            <td>
                {$safeDate}
            </td>
        </tr>

    </table>

    <div style="
        margin-top:22px;
        padding:14px;
        background:#eafaf0;
        border-radius:8px;
        color:#166534;
        font-size:13px;
        line-height:1.5;
    ">
        No card numbers, security codes, passwords,
        OTPs or other payment credentials were collected
        or included in this notification.
    </div>

</div>

</body>
</html>
HTML;


    try {

        $result =
            sendBrevoEmail(
                $apiKey,
                $senderEmail,
                'ADIC Security School',
                $instructorEmail,
                'ADIC Lesson — Simulated checkout submitted',
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

$to = trim(
    (string) ($_POST['to'] ?? '')
);


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
        $to,
        FILTER_VALIDATE_EMAIL
    )
) {

    fail(
        'Recipient is not a valid email address.'
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
            $senderEmail,
            $fromName,
            $to,
            $subject,
            $html
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
    'to'        => $to,
    'messageId' => $result['messageId']
]);