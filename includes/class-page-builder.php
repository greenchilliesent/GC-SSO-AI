<?php
/**
 * Chillies Page Builder — template management.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_Page_Builder {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Nothing to register at boot; templates loaded on demand by shortcode
    }

    /** Return list of available built-in templates. */
    public function get_templates() {
        return [
            'landing' => __( 'Landing Page', 'chillies-sso-ai' ),
            'blog'    => __( 'Blog Layout',   'chillies-sso-ai' ),
            'auth'    => __( 'Auth / Login',  'chillies-sso-ai' ),
        ];
    }
}
