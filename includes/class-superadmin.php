<?php
/**
 * Chillies Superadmin — multi-subdomain control panel.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_Superadmin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_chillies_add_subdomain',    [ $this, 'ajax_add_subdomain' ] );
        add_action( 'wp_ajax_chillies_remove_subdomain', [ $this, 'ajax_remove_subdomain' ] );
        add_action( 'wp_ajax_chillies_check_subdomain',  [ $this, 'ajax_check_subdomain' ] );
        add_action( 'wp_ajax_chillies_push_settings_all',[ $this, 'ajax_push_settings_all' ] );
    }

    public function get_subdomains() {
        $saved = get_option( 'chillies_subdomains', [] );
        if ( empty( $saved ) ) {
            // Seed from domains.txt
            $txt_file = CHILLIES_PLUGIN_DIR . 'domains.txt';
            if ( file_exists( $txt_file ) ) {
                $lines = file( $txt_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
                $saved = array_map( 'trim', $lines );
                update_option( 'chillies_subdomains', $saved );
            }
        }
        return $saved;
    }

    public function add_subdomain( $domain ) {
        $domains = $this->get_subdomains();
        $domain  = sanitize_text_field( trim( $domain ) );
        if ( ! in_array( $domain, $domains, true ) ) {
            $domains[] = $domain;
            update_option( 'chillies_subdomains', $domains );
        }
    }

    public function remove_subdomain( $domain ) {
        $domains = $this->get_subdomains();
        $domains = array_filter( $domains, fn( $d ) => $d !== $domain );
        update_option( 'chillies_subdomains', array_values( $domains ) );
    }

    /**
     * Ping a subdomain's health endpoint to check status.
     */
    public function check_subdomain( $domain ) {
        $url      = 'https://' . $domain . '/wp-json/chillies/v1/posts';
        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $response ) ) {
            return [ 'status' => 'offline', 'message' => $response->get_error_message() ];
        }
        $code = wp_remote_retrieve_response_code( $response );
        return [ 'status' => $code === 200 ? 'online' : 'error', 'code' => $code ];
    }

    /**
     * Push current plugin settings to all subdomains.
     */
    public function push_settings_to_all() {
        $domains  = $this->get_subdomains();
        $settings = [
            'sso' => get_option( 'chillies_sso_settings', [] ),
            'cdn' => get_option( 'chillies_cdn_settings', [] ),
        ];
        $results = [];
        foreach ( $domains as $domain ) {
            $url  = 'https://' . $domain . '/wp-json/chillies/v1/admin/push-settings';
            $resp = wp_remote_post( $url, [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $settings ),
                'timeout' => 15,
            ] );
            $results[ $domain ] = is_wp_error( $resp )
                ? [ 'success' => false, 'message' => $resp->get_error_message() ]
                : [ 'success' => wp_remote_retrieve_response_code( $resp ) === 200 ];
        }
        return $results;
    }

    // ── AJAX ─────────────────────────────────────────────────────────────────

    public function ajax_add_subdomain() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $this->add_subdomain( $_POST['domain'] ?? '' );
        wp_send_json_success();
    }

    public function ajax_remove_subdomain() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $this->remove_subdomain( sanitize_text_field( $_POST['domain'] ?? '' ) );
        wp_send_json_success();
    }

    public function ajax_check_subdomain() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $result = $this->check_subdomain( sanitize_text_field( $_POST['domain'] ?? '' ) );
        wp_send_json_success( $result );
    }

    public function ajax_push_settings_all() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $results = $this->push_settings_to_all();
        wp_send_json_success( [ 'results' => $results ] );
    }
}
