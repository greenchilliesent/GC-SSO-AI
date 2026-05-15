<?php
/**
 * Chillies SSO — Standalone Cookie Bridge
 *
 * Drop this file in any subdomain's root. On page load it reads the shared
 * SSO cookie, validates the JWT with the main domain API, and — if valid —
 * creates a local WordPress session for that user.
 *
 * No WordPress dependency required; pure PHP.
 */

// ── Configuration ────────────────────────────────────────────────────────────
define( 'CHILLIES_MAIN_DOMAIN',  'https://www.greenchilliesent.com' );
define( 'CHILLIES_AUTH_DOMAIN',  'https://auth.greenchilliesent.com' );
define( 'CHILLIES_COOKIE_DOMAIN', '.greenchilliesent.com' );
define( 'CHILLIES_COOKIE_NAME',  'chillies_sso_token' );
define( 'CHILLIES_API_ENDPOINT', '/api/v1/sso/validate' );
define( 'CHILLIES_LOG_FILE',     __DIR__ . '/chillies-sso.log' );
// ─────────────────────────────────────────────────────────────────────────────

// CORS headers — required for cross-subdomain requests
header( 'Access-Control-Allow-Origin: ' . CHILLIES_MAIN_DOMAIN );
header( 'Access-Control-Allow-Credentials: true' );
header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );

if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
    http_response_code( 204 );
    exit;
}

/**
 * Write a timestamped entry to the local log file.
 */
function chillies_log( $message ) {
    $entry = '[' . date( 'Y-m-d H:i:s' ) . '] ' . $message . PHP_EOL;
    file_put_contents( CHILLIES_LOG_FILE, $entry, FILE_APPEND | LOCK_EX );
}

/**
 * Retrieve the SSO token from the cookie.
 *
 * @return string|null
 */
function chillies_get_token() {
    return $_COOKIE[ CHILLIES_COOKIE_NAME ] ?? null;
}

/**
 * Validate the token against the main domain REST API.
 *
 * @param  string $token
 * @return array|null  Decoded user data or null on failure.
 */
function chillies_validate_token( $token ) {
    $url = rtrim( CHILLIES_MAIN_DOMAIN, '/' ) . CHILLIES_API_ENDPOINT;

    $ch = curl_init( $url );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 10,
    ] );

    $response = curl_exec( $ch );
    $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    curl_close( $ch );

    if ( $http_code !== 200 || empty( $response ) ) {
        chillies_log( "Token validation failed. HTTP $http_code." );
        return null;
    }

    $data = json_decode( $response, true );
    if ( ! isset( $data['valid'] ) || ! $data['valid'] ) {
        chillies_log( 'Token invalid per main domain API.' );
        return null;
    }

    chillies_log( 'Token validated. User ID: ' . ( $data['user_id'] ?? 'unknown' ) );
    return $data;
}

/**
 * Refresh the SSO cookie with a fresh token returned by the API.
 *
 * @param string $new_token
 */
function chillies_refresh_cookie( $new_token ) {
    setcookie(
        CHILLIES_COOKIE_NAME,
        $new_token,
        [
            'expires'  => time() + ( 7 * DAY_IN_SECONDS ),
            'path'     => '/',
            'domain'   => CHILLIES_COOKIE_DOMAIN,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None',
        ]
    );
    chillies_log( 'SSO cookie refreshed.' );
}

/**
 * Create a local WordPress session if WP is loaded on this subdomain.
 *
 * @param array $user_data
 */
function chillies_create_wp_session( $user_data ) {
    if ( ! function_exists( 'wp_set_auth_cookie' ) ) {
        return; // WP not loaded — skip
    }
    $user_id = intval( $user_data['user_id'] ?? 0 );
    if ( $user_id <= 0 ) {
        return;
    }
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
    chillies_log( "Local WP session created for user $user_id." );
}

// ── Main logic ────────────────────────────────────────────────────────────────
$token = chillies_get_token();

if ( empty( $token ) ) {
    chillies_log( 'No SSO cookie found. Skipping.' );
    return;
}

$user_data = chillies_validate_token( $token );

if ( null === $user_data ) {
    // Clear the stale cookie
    setcookie( CHILLIES_COOKIE_NAME, '', time() - 3600, '/', CHILLIES_COOKIE_DOMAIN );
    return;
}

// Refresh if a new token was returned
if ( ! empty( $user_data['new_token'] ) ) {
    chillies_refresh_cookie( $user_data['new_token'] );
}

chillies_create_wp_session( $user_data );
