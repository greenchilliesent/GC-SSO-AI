<?php
/**
 * Chillies Skills — manage plugin skills with token costs.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_Skills {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_chillies_add_skill',    [ $this, 'ajax_add_skill' ] );
        add_action( 'wp_ajax_chillies_delete_skill', [ $this, 'ajax_delete_skill' ] );
        add_action( 'wp_ajax_chillies_toggle_skill', [ $this, 'ajax_toggle_skill' ] );
        add_action( 'wp_ajax_chillies_ai_suggest_skills', [ $this, 'ajax_ai_suggest_skills' ] );
        add_shortcode( 'chillies_skill', [ $this, 'shortcode_skill' ] );
    }

    /** Get all skills from DB. */
    public function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}chillies_skills ORDER BY name ASC" );
    }

    /** Get single skill. */
    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}chillies_skills WHERE id = %d", $id
        ) );
    }

    /** Add a skill. */
    public function add( $name, $description, $shortcode, $token_cost = 'Unlimited' ) {
        global $wpdb;
        return $wpdb->insert( $wpdb->prefix . 'chillies_skills', [
            'name'        => sanitize_text_field( $name ),
            'description' => sanitize_textarea_field( $description ),
            'shortcode'   => sanitize_text_field( $shortcode ),
            'token_cost'  => sanitize_text_field( $token_cost ),
            'status'      => 1,
        ] );
    }

    /** Delete a skill. */
    public function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'chillies_skills', [ 'id' => absint( $id ) ] );
    }

    /** Toggle skill status. */
    public function toggle( $id ) {
        global $wpdb;
        $current = $wpdb->get_var( $wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}chillies_skills WHERE id = %d", $id
        ) );
        $wpdb->update( $wpdb->prefix . 'chillies_skills', [ 'status' => $current ? 0 : 1 ], [ 'id' => absint( $id ) ] );
        return ! $current;
    }

    // ── Shortcode ─────────────────────────────────────────────────────────────

    public function shortcode_skill( $atts ) {
        $atts  = shortcode_atts( [ 'name' => '' ], $atts );
        global $wpdb;
        $skill = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}chillies_skills WHERE name = %s AND status = 1",
            $atts['name']
        ) );
        if ( ! $skill ) {
            return '';
        }
        ob_start();
        ?>
        <div class="chillies-skill-card">
            <strong><?php echo esc_html( $skill->name ); ?></strong>
            <p><?php echo esc_html( $skill->description ); ?></p>
            <span class="chillies-skill-token">Tokens: <?php echo esc_html( $skill->token_cost ); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    // ── AJAX ─────────────────────────────────────────────────────────────────

    public function ajax_add_skill() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $this->add(
            $_POST['name']        ?? '',
            $_POST['description'] ?? '',
            $_POST['shortcode']   ?? '',
            $_POST['token_cost']  ?? 'Unlimited'
        );
        wp_send_json_success();
    }

    public function ajax_delete_skill() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $this->delete( intval( $_POST['id'] ?? 0 ) );
        wp_send_json_success();
    }

    public function ajax_toggle_skill() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $new = $this->toggle( intval( $_POST['id'] ?? 0 ) );
        wp_send_json_success( [ 'new_status' => $new ] );
    }

    public function ajax_ai_suggest_skills() {
        check_ajax_referer( 'chillies_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( -1 ); }
        $active_plugins = implode( ', ', array_keys( get_option( 'active_plugins', [] ) ) );
        $prompt  = "Based on these active WordPress plugins: $active_plugins — suggest 5 useful skill definitions for a skills manager. Return JSON: [{\"name\":\"...\",\"description\":\"...\",\"shortcode\":\"...\",\"token_cost\":\"Unlimited\"}]";
        $result  = Chillies_AI::get_instance()->request( $prompt );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success( [ 'suggestions' => $result ] );
    }
}
