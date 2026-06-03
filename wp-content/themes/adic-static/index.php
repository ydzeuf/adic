<?php
/**
 * ADIC Static Site theme — front controller.
 *
 * WordPress routes every "pretty" URL (including "/") to the active theme's
 * index.php. We simply output the static homepage (index.html, which sits in
 * the WordPress root) and stop. Real files like /products.html, /contact.html
 * and /assets/... are served directly by the web server and never reach here.
 */
// Ask the CDN / browsers not to serve a stale copy of the homepage, so updates
// (and the switch away from the old WordPress page) show up everywhere quickly.
if ( function_exists( 'nocache_headers' ) ) {
    nocache_headers();
}
header( 'Cache-Control: no-cache, max-age=0, must-revalidate' );

$home = ABSPATH . 'index.html';
if ( is_readable( $home ) ) {
    readfile( $home );
    exit;
}
// Fallback if the static file is missing for some reason.
echo 'ADIC - Aqd Darin Industrial Co.';
