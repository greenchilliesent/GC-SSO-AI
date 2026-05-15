<?php
/**
 * Chillies SSO — Single Sign-On core logic.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_SSO {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init',       [ $this, 'maybe_authenticate_via_sso' ], 1 );
        add_action( 'wp_login',   [ $this, 'on_login' ], 10, 2 );
        add_action( 'wp_logout',  [ $this, 'on_logout' ] );

        // REST routes
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
    }

    // ── Settings helpers ──────────────────────────────────────────────────────

    private function get_settings() {
        return wp_parse_args( get_option( 'chillies_sso_settings', [] ), [
            'enabled'      => 1,
            'jwt_secret'   => wp_generate_password( 64, true, true ),
            'cookie_name'  => 'chillies_sso_token',
            'cookie_domain'=> '.greenchilliesent.com',
            'auth_domain'  => 'https://auth.greenchilliesent.com',
            'token_ttl'    => 604800, // 7 days
        ] );
    }

    // ── JWT helpers ───────────────────────────────────────────────────────────

    /**
     * Build a JWT (HMAC-SHA256).
     *
     * @param  int    $user_id
     * @return string Signed JWT.
     */
    public function generate_jwt( $user_id ) {
        $settings = $this->get_settings();
        $header   = $this->base64url_encode( wp_json_encode( [ 'alg' => 'HS256', 'typ' => 'JWT' ] ) );
        $payload  = $this->base64url_encode( wp_json_encode( [
            'sub' => $user_id,
            'iat' => time(),
            'exp' => time() + intval( $settings['token_ttl'] ),
        ] ) );
        $signature = $this->base64url_encode(
            hash_hmac( 'sha256', "$header.$payload", $settings['jwt_secret'], true )
        );
        return "$header.$payload.$signature";
    }

    /**
     * Verify and decode a JWT.
     *
     * @param  string     $token
     * @return array|null Decoded payload or null on failure.
     */
    public function verify_jwt( $token ) {
        $settings = $this->get_settings();
        $parts = explode( '.', $token );
        if ( count( $parts ) !== 3 ) {
            return null;
        }
        [ $header, $payload, $sig ] = $parts;
        $expected = $this->base64url_encode(
            hash_hmac( 'sha256', "$header.$payload", $settings['jwt_secret'], true )
        );
        if ( ! hash_equals( $expected, $sig ) ) {
            return null;
        }
        $data = json_decode( $this->base64url_decode( $payload ), true );
        if ( ! $data || $data['exp'] < time() ) {
            return null;
        }
        return $data;
    }

    private function base64url_encode( $data ) {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }

    private function base64url_decode( $data ) {
        return base64_decode( strtr( $data, '-_', '+/' ) . str_repeat( '=', 3 - ( 3 + strlen( $data ) ) % 4 ) );
    }

    // ── Cookie management ─────────────────────────────────────────────────────

    public function set_sso_cookie( $token ) {
        $s = $this->get_settings();
        setcookie( $s['cookie_name'], $token, [
            'expires'  => time() + intval( $s['token_ttl'] ),
            'path'     => '/',
            'domain'   => $s['cookie_domain'],
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None',
        ] );
    }

    public function clear_sso_cookie() {
        $s = $this->get_settings();
        setcookie( $s['cookie_name'], '', time() - 3600, '/', $s['cookie_domain'] );
    }

    // ── Hooks ─────────────────────────────────────────────────────────────────

    /**
     * On every page load: if SSO cookie present but user not logged in, authenticate.
     */
    public function maybe_authenticate_via_sso() {
        if ( is_user_logged_in() ) {
            return;
        }
        $settings = $this->get_settings();
        if ( empty( $settings['enabled'] ) ) {
            return;
        }
        $token = $_COOKIE[ $settings['cookie_name'] ] ?? '';
        if ( empty( $token ) ) {
            return;
        }
        $payload = $this->verify_jwt( $token );
        if ( ! $payload ) {
            $this->clear_sso_cookie();
            return;
        }
        $user = get_user_by( 'id', intval( $payload['sub'] ) );
        if ( ! $user ) {
            return;
        }
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
    }

    /**
     * On successful login: issue SSO cookie + store session in DB.
     */
    public function on_login( $user_login, $user ) {
        $settings = $this->get_settings();
        if ( empty( $settings['enabled'] ) ) {
            return;
        }
        $token = $this->generate_jwt( $user->ID );
        $this->set_sso_cookie( $token );

        // Persist session in DB
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'chillies_sso_sessions', [
            'user_id'    => $user->ID,
            'token'      => $token,
            'subdomain'  => parse_url( home_url(), PHP_URL_HOST ),
            'expires_at' => gmdate( 'Y-m-d H:i:s', time() + intval( $settings['token_ttl'] ) ),
        ] );
    }

    /**
     * On logout: clear SSO cookie + remove DB sessions.
     */
    public function on_logout() {
        $user_id  = get_current_user_id();
        $settings = $this->get_settings();
        $this->clear_sso_cookie();

        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'chillies_sso_sessions', [ 'user_id' => $user_id ] );
    }

    // ── REST API ──────────────────────────────────────────────────────────────

    public function register_rest_routes() {
        register_rest_route( 'chillies/v1', '/sso/validate', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_validate_token' ],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( 'chillies/v1', '/sso/sessions', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_sessions' ],
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ] );
    }

    public function rest_validate_token( WP_REST_Request $request ) {
        $auth  = $request->get_header( 'Authorization' );
        $token = str_replace( 'Bearer ', '', $auth );
        $data  = $this->verify_jwt( $token );
        if ( ! $data ) {
            return new WP_REST_Response( [ 'valid' => false ], 401 );
        }
        return new WP_REST_Response( [
            'valid'   => true,
            'user_id' => $data['sub'],
        ], 200 );
    }

    public function rest_get_sessions( WP_REST_Request $request ) {
        global $wpdb;
        $sessions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}chillies_sso_sessions ORDER BY created_at DESC LIMIT 100" );
        return new WP_REST_Response( $sessions, 200 );
    }
}
