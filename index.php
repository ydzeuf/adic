<?php
/**
 * ADIC static site — serve the static homepage at the domain root.
 *
 * GoDaddy Managed WordPress serves index.php for "/". This replaces the
 * default WordPress index.php so visitors get the static index.html instead.
 * All other pages (products.html, contact.html, assets/...) are served
 * directly as static files.
 */
header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/index.html');
