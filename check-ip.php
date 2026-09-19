<?php

$ch = curl_init('https://api.ipify.org');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);

$ip = curl_exec($ch);
$error = curl_error($ch);

curl_close($ch);

header('Content-Type: text/plain; charset=UTF-8');

if ($ip === false) {
    echo 'Error: ' . $error;
} else {
    echo 'Outbound public IP: ' . trim($ip);
}