<?php
/**
 * Chillies Cross-Post — push posts to multiple subdomains simultaneously.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_Cross_Post {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_chillies_cross_post', [ $this, 'ajax_cross_post' ] );
    }

    /**
     * Get list of all subdomains from the DB option.
     */
    private function get_subdomains() {
        return get_option( 'chillies_subdomains', [] );
    }

    /**
     * Push a post to a single subdomain's REST API.
     *
     * @param  int    $post_id
     * @param  string $subdomain  e.g. "blog.greenchilliesent.com"
     * @param  string $api_key    Auth key for the target subdomain.
     * @return array  [ 'success' => bool, 'message' => string ]
     */
    public function push_to_subdomain( $post_id, $subdomain, $api_key = '' ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return [ 'success' => false, 'message' => 'Post not found.' ];
        }

        $endpoint = 'https://' . $subdomain . '/wp-json/wp/v2/posts';
        $body     = wp_json_encode( [
            'title'   => $post->post_title,
            'content' => $post->post_content,
            'status'  => 'draft',
            'excerpt' => $post->post_excerpt,
        ] );

        $headers = [
            'Content-Type' => 'application/json',
        ];
        if ( $api_key ) {
            $headers['X-Chillies-API-Key'] = $api_key;
        }

        $response = wp_remote_post( $endpoint, [
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            $msg = $response->get_error_message();
            $this->log( $post_id, $subdomain, 'failed', $msg );
            return [ 'success' => false, 'message' => $msg ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $ok   = in_array( $code, [ 200, 201 ], true );
        $msg  = $ok ? "Cross-posted successfully (HTTP $code)." : "Failed with HTTP $code.";
        $this->log( $post_id, $subdomain, $ok ? 'success' : 'failed', $msg );
        return [ 'success' => $ok, 'message' => $msg ];
    }

    /**
     * Log a cross-post event to the DB table.
     */
    private function log( $post_id, $subdomain, $status, $message ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'chillies_cross_post_log', [
            'post_id'   => absint( $post_id ),
            'subdomain' => sanitize_text_field( $subdomain ),
            'status'    => sanitize_text_field( $status ),
            'message'   => sanitize_textarea_field( $message ),
        ] );
    }

    /**
     * Retrieve cross-post log entries.
     */
    public function get_log( $limit = 50 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}chillies_cross_post_log ORDER BY created_at DESC LIMIT %d",
            $limit
        ) );
    }

    // ── AJAX ─────────────────────────────────────────────────────────────────

    public function ajax_cross_post() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) { wp_die( -1 ); }

        $post_id    = absint( $_POST['post_id'] ?? 0 );
        $subdomains = array_map( 'sanitize_text_field', (array) ( $_POST['subdomains'] ?? [] ) );
        $api_key    = sanitize_text_field( $_POST['api_key'] ?? '' );

        $results = [];
        foreach ( $subdomains as $subdomain ) {
            $results[ $subdomain ] = $this->push_to_subdomain( $post_id, $subdomain, $api_key );
        }

        wp_send_json_success( [ 'results' => $results ] );
    }
}
