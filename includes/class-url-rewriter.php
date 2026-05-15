<?php
/**
 * Chillies URL Rewriter — rename WordPress slugs (wp-admin, wp-login, etc.)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_URL_Rewriter {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init',           [ $this, 'setup_rewrites' ], 1 );
        add_filter( 'login_url',      [ $this, 'filter_login_url' ], 10, 3 );
        add_filter( 'logout_url',     [ $this, 'filter_logout_url' ] );
        add_filter( 'admin_url',      [ $this, 'filter_admin_url' ] );
        add_filter( 'site_url',       [ $this, 'filter_site_url' ], 10, 4 );
        add_filter( 'content_url',    [ $this, 'filter_content_url' ] );
        add_action( 'parse_request',  [ $this, 'intercept_slugs' ], 1 );
    }

    /**
     * Get current rewrite rules stored in DB.
     *
     * @return array  [ 'original' => 'custom', … ]
     */
    public function get_rules() {
        $defaults = [
            'wp-admin'    => 'admin',
            'wp-login.php'=> 'login',
            'wp-content'  => 'content',
            'wp-includes' => 'includes',
            'wp-json'     => 'api',
            'wp-uploads'  => 'uploads',
        ];
        $saved = get_option( 'chillies_url_rewriter_rules', [] );
        return wp_parse_args( $saved, $defaults );
    }

    /**
     * Register WordPress rewrite rules.
     */
    public function setup_rewrites() {
        $rules = $this->get_rules();
        foreach ( $rules as $original => $custom ) {
            if ( $custom !== $original ) {
                add_rewrite_rule( '^' . preg_quote( $custom, '/' ) . '(/(.*))?$',
                    'index.php?' . $original . '=$matches[2]', 'top' );
            }
        }
    }

    /**
     * Intercept requests for custom slugs and internally rewrite.
     */
    public function intercept_slugs() {
        $rules     = $this->get_rules();
        $request   = trim( $_SERVER['REQUEST_URI'] ?? '', '/' );
        $first_seg = strtok( $request, '/?' );

        // Check admin slug
        $admin_slug = $rules['wp-admin'] ?? 'admin';
        if ( $first_seg === $admin_slug ) {
            $_SERVER['REQUEST_URI'] = '/' . str_replace( $first_seg, 'wp-admin', $request );
            return;
        }

        // Check login slug
        $login_slug = $rules['wp-login.php'] ?? 'login';
        if ( $first_seg === $login_slug ) {
            $_SERVER['REQUEST_URI'] = '/wp-login.php' . ( strpos( $request, '?' ) !== false
                ? '?' . substr( $request, strpos( $request, '?' ) + 1 ) : '' );
            return;
        }
    }

    public function filter_login_url( $url, $redirect, $force_reauth ) {
        return $this->replace_segment( $url, 'wp-login.php', $this->get_rules()['wp-login.php'] ?? 'login' );
    }

    public function filter_logout_url( $url ) {
        return $this->replace_segment( $url, 'wp-login.php', $this->get_rules()['wp-login.php'] ?? 'login' );
    }

    public function filter_admin_url( $url ) {
        return $this->replace_segment( $url, 'wp-admin', $this->get_rules()['wp-admin'] ?? 'admin' );
    }

    public function filter_site_url( $url, $path, $scheme, $blog_id ) {
        $rules = $this->get_rules();
        foreach ( $rules as $original => $custom ) {
            $url = $this->replace_segment( $url, $original, $custom );
        }
        return $url;
    }

    public function filter_content_url( $url ) {
        return $this->replace_segment( $url, 'wp-content', $this->get_rules()['wp-content'] ?? 'content' );
    }

    /**
     * Replace a path segment in a URL.
     */
    private function replace_segment( $url, $from, $to ) {
        if ( $from === $to ) {
            return $url;
        }
        return str_replace( '/' . $from, '/' . $to, $url );
    }

    /**
     * Save rewrite rules and flush WP rewrites.
     *
     * @param array $rules
     */
    public function save_rules( $rules ) {
        // Sanitize
        $clean = [];
        foreach ( $rules as $orig => $custom ) {
            $clean[ sanitize_key( $orig ) ] = sanitize_text_field( $custom );
        }
        update_option( 'chillies_url_rewriter_rules', $clean );
        flush_rewrite_rules();
    }
}
