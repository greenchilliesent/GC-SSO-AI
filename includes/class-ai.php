<?php
/**
 * Chillies AI — AI integration and features.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_AI {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
        add_action( 'wp_ajax_chillies_ai_request',      [ $this, 'ajax_ai_request' ] );
        add_action( 'wp_ajax_chillies_ai_auto_post',    [ $this, 'ajax_auto_post' ] );
        add_action( 'wp_ajax_chillies_ai_css',          [ $this, 'ajax_ai_css' ] );
        add_action( 'wp_ajax_chillies_ai_template',     [ $this, 'ajax_ai_template' ] );
        add_action( 'wp_ajax_chillies_ai_bug_detect',   [ $this, 'ajax_bug_detect' ] );
        add_action( 'wp_ajax_chillies_ai_shortcode_gen',[ $this, 'ajax_shortcode_gen' ] );
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    private function get_settings() {
        return wp_parse_args( get_option( 'chillies_ai_settings', [] ), [
            'api_key'           => '',
            'github_ai_key'     => '',
            'enable_bug_detect' => 1,
            'enable_news_feed'  => 1,
            'enable_auto_post'  => 1,
            'enable_custom_css' => 1,
            'enable_templates'  => 1,
            'enable_shortcode'  => 1,
        ] );
    }

    /**
     * Get stored API key (decrypted).
     */
    public function get_api_key() {
        $s = $this->get_settings();
        return ! empty( $s['api_key'] ) ? $this->decrypt( $s['api_key'] ) : '';
    }

    // ── Encryption helpers ────────────────────────────────────────────────────

    private function encrypt( $plain ) {
        if ( ! extension_loaded( 'openssl' ) ) {
            return base64_encode( $plain );
        }
        $iv  = openssl_random_pseudo_bytes( 16 );
        $enc = openssl_encrypt( $plain, 'AES-256-CBC', wp_salt( 'secure_auth' ), 0, $iv );
        return base64_encode( $iv . '::' . $enc );
    }

    private function decrypt( $cipher ) {
        if ( ! extension_loaded( 'openssl' ) ) {
            return base64_decode( $cipher );
        }
        $decoded = base64_decode( $cipher );
        if ( strpos( $decoded, '::' ) === false ) {
            return $cipher; // Already plain (legacy)
        }
        [ $iv, $enc ] = explode( '::', $decoded, 2 );
        return openssl_decrypt( $enc, 'AES-256-CBC', wp_salt( 'secure_auth' ), 0, $iv );
    }

    /**
     * Encrypt and save API key.
     */
    public function save_api_key( $plain_key ) {
        $settings             = get_option( 'chillies_ai_settings', [] );
        $settings['api_key']  = $this->encrypt( sanitize_text_field( $plain_key ) );
        update_option( 'chillies_ai_settings', $settings );
    }

    // ── Core AI request ───────────────────────────────────────────────────────

    /**
     * Send a prompt to the AI API and return the text response.
     *
     * @param  string $prompt
     * @param  string $model   Optional model name.
     * @return string|WP_Error
     */
    public function request( $prompt, $model = 'gpt-4o-mini' ) {
        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'AI API key not configured.', 'chillies-sso-ai' ) );
        }

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode( [
                'model'    => $model,
                'messages' => [
                    [ 'role' => 'system', 'content' => 'You are a helpful WordPress assistant.' ],
                    [ 'role' => 'user',   'content' => $prompt ],
                ],
                'max_tokens' => 2048,
            ] ),
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body['choices'][0]['message']['content'] ?? new WP_Error( 'ai_error', 'No response from AI.' );
    }

    // ── AJAX handlers ─────────────────────────────────────────────────────────

    public function ajax_ai_request() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }

        $prompt = sanitize_textarea_field( $_POST['prompt'] ?? '' );
        $result = $this->request( $prompt );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success( [ 'result' => $result ] );
    }

    public function ajax_auto_post() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'publish_posts' ) ) { wp_die( -1 ); }

        $topic  = sanitize_text_field( $_POST['topic'] ?? '' );
        $prompt = "Write a detailed, engaging WordPress blog post about: $topic. Include a title, introduction, 3-5 sections with headings, and a conclusion. Format in HTML suitable for WordPress.";
        $content = $this->request( $prompt );

        if ( is_wp_error( $content ) ) {
            wp_send_json_error( $content->get_error_message() );
        }

        // Extract title
        preg_match( '/<h1[^>]*>(.*?)<\/h1>/i', $content, $m );
        $title   = ! empty( $m[1] ) ? wp_strip_all_tags( $m[1] ) : $topic;
        $post_id = wp_insert_post( [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_author'  => get_current_user_id(),
        ] );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( $post_id->get_error_message() );
        }
        wp_send_json_success( [
            'post_id'   => $post_id,
            'edit_link' => get_edit_post_link( $post_id, 'raw' ),
            'title'     => $title,
        ] );
    }

    public function ajax_ai_css() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }

        $desc  = sanitize_textarea_field( $_POST['description'] ?? '' );
        $prompt = "Generate clean, valid CSS for a WordPress site. Description: $desc. Return only the CSS code, no explanation.";
        $css   = $this->request( $prompt );

        if ( is_wp_error( $css ) ) {
            wp_send_json_error( $css->get_error_message() );
        }
        $css = preg_replace( '/^```css\s*/i', '', trim( $css ) );
        $css = preg_replace( '/\s*```$/', '', $css );
        wp_send_json_success( [ 'css' => $css ] );
    }

    public function ajax_ai_template() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }

        $prompt_text = sanitize_textarea_field( $_POST['prompt'] ?? '' );
        $prompt      = "Generate a WordPress page template in PHP/HTML for: $prompt_text. Use WordPress functions. Return only the PHP/HTML code.";
        $template    = $this->request( $prompt );

        if ( is_wp_error( $template ) ) {
            wp_send_json_error( $template->get_error_message() );
        }
        wp_send_json_success( [ 'template' => $template ] );
    }

    public function ajax_bug_detect() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }

        // Scan plugin main file for basic issues
        $file    = CHILLIES_PLUGIN_DIR . 'chillies-sso-ai.php';
        $code    = file_get_contents( $file );
        $prompt  = "Review this PHP WordPress plugin code and list any bugs, security issues, or improvements:\n\n$code\n\nBe concise.";
        $result  = $this->request( $prompt );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success( [ 'report' => $result ] );
    }

    public function ajax_shortcode_gen() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }

        $desc   = sanitize_textarea_field( $_POST['description'] ?? '' );
        $prompt = "Generate a WordPress shortcode in PHP for: $desc. Follow WordPress coding standards. Return only the PHP code.";
        $code   = $this->request( $prompt );

        if ( is_wp_error( $code ) ) {
            wp_send_json_error( $code->get_error_message() );
        }
        wp_send_json_success( [ 'shortcode' => $code ] );
    }

    // ── Dashboard widget ──────────────────────────────────────────────────────

    public function add_dashboard_widget() {
        $s = $this->get_settings();
        if ( empty( $s['enable_news_feed'] ) ) {
            return;
        }
        wp_add_dashboard_widget( 'chillies_ai_news', 'Chillies AI — Trending Topics', [ $this, 'render_news_widget' ] );
    }

    public function render_news_widget() {
        $cached = get_transient( 'chillies_ai_news_feed' );
        if ( ! $cached ) {
            $result = $this->request( 'List 5 trending topics in technology and WordPress development today. Keep it brief.' );
            $cached = is_wp_error( $result ) ? 'Unable to fetch trending topics.' : esc_html( $result );
            set_transient( 'chillies_ai_news_feed', $cached, HOUR_IN_SECONDS );
        }
        echo '<div class="chillies-news-feed">' . nl2br( $cached ) . '</div>';
    }
}
