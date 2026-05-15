<?php
/**
 * Chillies API — REST API key management and custom endpoints.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_API {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init',          [ $this, 'register_routes' ] );
        add_action( 'wp_ajax_chillies_gen_api_key',    [ $this, 'ajax_generate_key' ] );
        add_action( 'wp_ajax_chillies_revoke_api_key', [ $this, 'ajax_revoke_key' ] );
        add_filter( 'determine_current_user', [ $this, 'auth_via_api_key' ], 20 );
    }

    // ── Key management ────────────────────────────────────────────────────────

    public function generate_key( $label = '', $user_id = 0, $rate_limit = 1000 ) {
        global $wpdb;
        $key = 'ck_' . bin2hex( random_bytes( 20 ) );
        $wpdb->insert( $wpdb->prefix . 'chillies_api_keys', [
            'api_key'    => $key,
            'label'      => sanitize_text_field( $label ),
            'user_id'    => absint( $user_id ),
            'rate_limit' => absint( $rate_limit ),
        ] );
        return $key;
    }

    public function revoke_key( $key_id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'chillies_api_keys', [ 'id' => absint( $key_id ) ] );
    }

    public function get_all_keys() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}chillies_api_keys ORDER BY created_at DESC" );
    }

    // ── API key authentication ────────────────────────────────────────────────

    public function auth_via_api_key( $user_id ) {
        if ( ! empty( $user_id ) ) {
            return $user_id;
        }
        $api_key = '';
        // Accept key in header or query param
        if ( ! empty( $_SERVER['HTTP_X_CHILLIES_API_KEY'] ) ) {
            $api_key = sanitize_text_field( $_SERVER['HTTP_X_CHILLIES_API_KEY'] );
        } elseif ( ! empty( $_GET['api_key'] ) ) {
            $api_key = sanitize_text_field( $_GET['api_key'] );
        }
        if ( empty( $api_key ) ) {
            return $user_id;
        }
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}chillies_api_keys WHERE api_key = %s",
            $api_key
        ) );
        if ( ! $row ) {
            return $user_id;
        }
        // Increment usage
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}chillies_api_keys SET usage = usage + 1 WHERE id = %d",
            $row->id
        ) );
        // Rate limit check
        if ( $row->usage >= $row->rate_limit ) {
            return new WP_Error( 'rate_limit', 'API rate limit exceeded.', [ 'status' => 429 ] );
        }
        return $row->user_id ?: get_option( 'admin_email' );
    }

    // ── Custom REST routes ────────────────────────────────────────────────────

    public function register_routes() {
        // Public: get posts
        register_rest_route( 'chillies/v1', '/posts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_posts' ],
            'permission_callback' => '__return_true',
        ] );
        // Public: get user profile
        register_rest_route( 'chillies/v1', '/profile', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_profile' ],
            'permission_callback' => 'is_user_logged_in',
        ] );
        // Admin: push settings
        register_rest_route( 'chillies/v1', '/admin/push-settings', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'push_settings' ],
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ] );
        // Public: SSO validate (delegated to SSO class)
        register_rest_route( 'chillies/v1', '/sso/validate', [
            'methods'             => 'GET',
            'callback'            => [ Chillies_SSO::get_instance(), 'rest_validate_token' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function get_posts( WP_REST_Request $r ) {
        $args  = [
            'post_type'      => sanitize_text_field( $r->get_param( 'type' ) ?: 'post' ),
            'posts_per_page' => absint( $r->get_param( 'per_page' ) ?: 10 ),
            'paged'          => absint( $r->get_param( 'page' ) ?: 1 ),
        ];
        $query = new WP_Query( $args );
        $posts = [];
        foreach ( $query->posts as $p ) {
            $posts[] = [
                'id'      => $p->ID,
                'title'   => get_the_title( $p ),
                'excerpt' => get_the_excerpt( $p ),
                'url'     => get_permalink( $p ),
                'date'    => get_the_date( 'c', $p ),
            ];
        }
        return new WP_REST_Response( [ 'posts' => $posts, 'total' => $query->found_posts ], 200 );
    }

    public function get_profile( WP_REST_Request $r ) {
        $user = wp_get_current_user();
        return new WP_REST_Response( [
            'id'         => $user->ID,
            'login'      => $user->user_login,
            'email'      => $user->user_email,
            'name'       => $user->display_name,
            'registered' => $user->user_registered,
        ], 200 );
    }

    public function push_settings( WP_REST_Request $r ) {
        $settings = $r->get_json_params();
        update_option( 'chillies_pushed_settings', $settings );
        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    // ── AJAX ─────────────────────────────────────────────────────────────────

    public function ajax_generate_key() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $key = $this->generate_key(
            sanitize_text_field( $_POST['label'] ?? 'API Key' ),
            get_current_user_id(),
            absint( $_POST['rate_limit'] ?? 1000 )
        );
        wp_send_json_success( [ 'key' => $key ] );
    }

    public function ajax_revoke_key() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $this->revoke_key( intval( $_POST['key_id'] ?? 0 ) );
        wp_send_json_success();
    }
}
