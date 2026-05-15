<?php
/**
 * Chillies Shortcodes — all built-in shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_Shortcodes {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'chillies_login',        [ $this, 'sc_login' ] );
        add_shortcode( 'chillies_register',     [ $this, 'sc_register' ] );
        add_shortcode( 'chillies_sso_status',   [ $this, 'sc_sso_status' ] );
        add_shortcode( 'chillies_cdn_url',      [ $this, 'sc_cdn_url' ] );
        add_shortcode( 'chillies_cross_post',   [ $this, 'sc_cross_post' ] );
        add_shortcode( 'chillies_news_feed',    [ $this, 'sc_news_feed' ] );
        add_shortcode( 'chillies_page_builder', [ $this, 'sc_page_builder' ] );
        add_shortcode( 'chillies_api_key',      [ $this, 'sc_api_key' ] );
    }

    /** [chillies_login] — renders SSO login form */
    public function sc_login( $atts ) {
        $sso     = get_option( 'chillies_sso_settings', [] );
        $auth    = $sso['auth_domain'] ?? 'https://auth.greenchilliesent.com';
        $redirect= esc_url( home_url() );
        ob_start();
        ?>
        <div class="chillies-login-wrap">
            <form method="post" action="<?php echo esc_url( $auth . '/wp-login.php' ); ?>" class="chillies-form">
                <input type="hidden" name="redirect_to" value="<?php echo $redirect; ?>">
                <?php wp_nonce_field( 'chillies_login' ); ?>
                <div class="chillies-field">
                    <label><?php esc_html_e( 'Username / Email', 'chillies-sso-ai' ); ?></label>
                    <input type="text" name="log" required autocomplete="username">
                </div>
                <div class="chillies-field">
                    <label><?php esc_html_e( 'Password', 'chillies-sso-ai' ); ?></label>
                    <input type="password" name="pwd" required autocomplete="current-password">
                </div>
                <button type="submit" class="chillies-btn"><?php esc_html_e( 'Sign In', 'chillies-sso-ai' ); ?></button>
                <a href="<?php echo esc_url( $auth . '/wp-login.php?action=register' ); ?>" class="chillies-link">
                    <?php esc_html_e( 'Create account', 'chillies-sso-ai' ); ?>
                </a>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /** [chillies_register] */
    public function sc_register( $atts ) {
        $sso  = get_option( 'chillies_sso_settings', [] );
        $auth = $sso['auth_domain'] ?? 'https://auth.greenchilliesent.com';
        ob_start();
        ?>
        <div class="chillies-register-wrap">
            <form method="post" action="<?php echo esc_url( $auth . '/wp-login.php?action=register' ); ?>" class="chillies-form">
                <?php wp_nonce_field( 'chillies_register' ); ?>
                <div class="chillies-field">
                    <label><?php esc_html_e( 'Username', 'chillies-sso-ai' ); ?></label>
                    <input type="text" name="user_login" required>
                </div>
                <div class="chillies-field">
                    <label><?php esc_html_e( 'Email', 'chillies-sso-ai' ); ?></label>
                    <input type="email" name="user_email" required>
                </div>
                <button type="submit" class="chillies-btn"><?php esc_html_e( 'Register', 'chillies-sso-ai' ); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /** [chillies_sso_status] */
    public function sc_sso_status( $atts ) {
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            return '<span class="chillies-sso-status chillies-sso-active">SSO Active &mdash; ' . esc_html( $user->display_name ) . '</span>';
        }
        return '<span class="chillies-sso-status chillies-sso-inactive">Not signed in</span>';
    }

    /** [chillies_cdn_url file="filename.jpg"] */
    public function sc_cdn_url( $atts ) {
        $atts = shortcode_atts( [ 'file' => '', 'folder' => 'Uploads' ], $atts );
        $cdn  = get_option( 'chillies_cdn_settings', [] );
        $base = rtrim( $cdn['cdn_domain'] ?? 'https://cdn.greenchilliesent.com', '/' );
        return esc_url( $base . '/' . $atts['folder'] . '/' . ltrim( $atts['file'], '/' ) );
    }

    /** [chillies_cross_post] — admin-facing cross-post trigger widget */
    public function sc_cross_post( $atts ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return '';
        }
        $subdomains = Chillies_Superadmin::get_instance()->get_subdomains();
        ob_start();
        ?>
        <div class="chillies-cross-post-widget">
            <h4><?php esc_html_e( 'Cross-Post This Content', 'chillies-sso-ai' ); ?></h4>
            <select id="chillies-cp-post">
                <?php foreach ( get_posts( [ 'numberposts' => 50, 'post_status' => 'publish' ] ) as $p ) : ?>
                    <option value="<?php echo esc_attr( $p->ID ); ?>"><?php echo esc_html( $p->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
            <div>
                <?php foreach ( $subdomains as $sd ) : ?>
                    <label><input type="checkbox" name="cp_subdomain[]" value="<?php echo esc_attr( $sd ); ?>"> <?php echo esc_html( $sd ); ?></label><br>
                <?php endforeach; ?>
            </div>
            <button id="chillies-cp-btn" class="chillies-btn"><?php esc_html_e( 'Cross Post', 'chillies-sso-ai' ); ?></button>
            <div id="chillies-cp-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /** [chillies_news_feed] */
    public function sc_news_feed( $atts ) {
        $cached = get_transient( 'chillies_ai_news_feed' );
        if ( $cached ) {
            return '<div class="chillies-news-feed">' . nl2br( esc_html( $cached ) ) . '</div>';
        }
        return '<div class="chillies-news-feed">' . esc_html__( 'Loading trending topics…', 'chillies-sso-ai' ) . '</div>';
    }

    /** [chillies_page_builder template="landing"] */
    public function sc_page_builder( $atts ) {
        $atts    = shortcode_atts( [ 'template' => 'landing' ], $atts );
        $file    = CHILLIES_PLUGIN_DIR . 'templates/page-builder/template-' . sanitize_key( $atts['template'] ) . '.php';
        if ( file_exists( $file ) ) {
            ob_start();
            include $file;
            return ob_get_clean();
        }
        return '<p>' . esc_html__( 'Template not found.', 'chillies-sso-ai' ) . '</p>';
    }

    /** [chillies_api_key] — show current user's API key */
    public function sc_api_key( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '';
        }
        global $wpdb;
        $user_id = get_current_user_id();
        $key     = $wpdb->get_var( $wpdb->prepare(
            "SELECT api_key FROM {$wpdb->prefix}chillies_api_keys WHERE user_id = %d LIMIT 1", $user_id
        ) );
        if ( ! $key ) {
            return '<p>' . esc_html__( 'No API key assigned.', 'chillies-sso-ai' ) . '</p>';
        }
        return '<code class="chillies-api-key">' . esc_html( $key ) . '</code>';
    }
}
