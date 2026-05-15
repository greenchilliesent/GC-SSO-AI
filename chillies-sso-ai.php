<?php
/**
 * Plugin Name: Chillies SSO AI
 * Plugin URI:  https://www.greenchilliesent.com
 * Description: All-in-one SSO, CDN, AI, URL Rewriting, Cross-Posting, and Subdomain Management Plugin
 * Version:     2.0.1
 * Author:      Chillies Entertainment
 * Author URI:  https://www.greenchilliesent.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: chillies-sso-ai
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'CHILLIES_VERSION',     '2.0.1' );
define( 'CHILLIES_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'CHILLIES_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'CHILLIES_PLUGIN_FILE', __FILE__ );

// Load includes
require_once CHILLIES_PLUGIN_DIR . 'includes/class-sso.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-cdn.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-url-rewriter.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-ai.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-cross-post.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-skills.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-api.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-page-builder.php';
require_once CHILLIES_PLUGIN_DIR . 'includes/class-superadmin.php';

// Load admin
if ( is_admin() ) {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-dashboard.php';
}

/**
 * Main plugin class
 */
class Chillies_SSO_AI {

    /** @var Chillies_SSO_AI */
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded',    [ $this, 'init' ] );
        register_activation_hook( CHILLIES_PLUGIN_FILE,   [ $this, 'activate' ] );
        register_deactivation_hook( CHILLIES_PLUGIN_FILE, [ $this, 'deactivate' ] );
    }

    public function init() {
        // Boot all modules
        Chillies_SSO::get_instance();
        Chillies_CDN::get_instance();
        Chillies_URL_Rewriter::get_instance();
        Chillies_AI::get_instance();
        Chillies_Cross_Post::get_instance();
        Chillies_Skills::get_instance();
        Chillies_API::get_instance();
        Chillies_Shortcodes::get_instance();
        Chillies_Page_Builder::get_instance();
        Chillies_Superadmin::get_instance();

        // Enqueue frontend custom CSS
        add_action( 'wp_head', [ $this, 'inject_custom_css' ] );
        // Enqueue admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Inject custom CSS on front-end.
     */
    public function inject_custom_css() {
        $css = get_option( 'chillies_custom_css', '' );
        if ( ! empty( $css ) ) {
            echo '<style id="chillies-custom-css">' . wp_strip_all_tags( $css ) . '</style>';
        }
    }

    /**
     * Enqueue admin CSS/JS.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'chillies' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'chillies-admin-style',
            CHILLIES_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            CHILLIES_VERSION
        );
        wp_enqueue_script(
            'chillies-admin-script',
            CHILLIES_PLUGIN_URL . 'assets/js/admin-script.js',
            [ 'jquery', 'wp-color-picker' ],
            CHILLIES_VERSION,
            true
        );
        wp_enqueue_style( 'wp-color-picker' );
        wp_localize_script( 'chillies-admin-script', 'chilliesAdmin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'chillies_nonce' ),
        ] );
    }

    /**
     * Plugin activation: create custom DB tables.
     */
    public function activate() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // Skills table
        $sql_skills = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chillies_skills (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(255)        NOT NULL,
            description TEXT              NOT NULL,
            shortcode  VARCHAR(255)        NOT NULL,
            status     TINYINT(1)          NOT NULL DEFAULT 1,
            token_cost VARCHAR(50)         NOT NULL DEFAULT 'Unlimited',
            created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        // API keys table
        $sql_api_keys = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chillies_api_keys (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            api_key    VARCHAR(64)         NOT NULL UNIQUE,
            label      VARCHAR(255)        NOT NULL,
            user_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            usage      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            rate_limit INT(11)             NOT NULL DEFAULT 1000,
            created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        // Cross-post log table
        $sql_cross_log = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chillies_cross_post_log (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id    BIGINT(20) UNSIGNED NOT NULL,
            subdomain  VARCHAR(255)        NOT NULL,
            status     VARCHAR(50)         NOT NULL DEFAULT 'pending',
            message    TEXT,
            created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        // SSO sessions table
        $sql_sso = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chillies_sso_sessions (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id    BIGINT(20) UNSIGNED NOT NULL,
            token      VARCHAR(512)        NOT NULL UNIQUE,
            subdomain  VARCHAR(255)        NOT NULL,
            expires_at DATETIME            NOT NULL,
            created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_skills );
        dbDelta( $sql_api_keys );
        dbDelta( $sql_cross_log );
        dbDelta( $sql_sso );

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

Chillies_SSO_AI::get_instance();
