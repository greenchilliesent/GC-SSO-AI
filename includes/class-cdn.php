<?php
/**
 * Chillies CDN — redirect uploads and rewrite media URLs.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Chillies_CDN {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function get_settings() {
        return wp_parse_args( get_option( 'chillies_cdn_settings', [] ), [
            'enabled'    => 0,
            'cdn_domain' => 'https://cdn.greenchilliesent.com',
        ] );
    }

    private function init_hooks() {
        add_filter( 'upload_dir',           [ $this, 'rewrite_upload_dir' ] );
        add_filter( 'the_content',          [ $this, 'rewrite_content_urls' ] );
        add_filter( 'wp_get_attachment_url',[ $this, 'rewrite_attachment_url' ] );
        add_filter( 'wp_calculate_image_srcset', [ $this, 'rewrite_srcset' ] );
    }

    /**
     * Build a CDN path for an upload sub-folder.
     * Structure: /Images/2026/05/15/
     */
    private function cdn_base_url( $subdir = '' ) {
        $s = $this->get_settings();
        if ( empty( $s['enabled'] ) || empty( $s['cdn_domain'] ) ) {
            return '';
        }
        $date = current_time( 'timestamp' );
        $path = '/Uploads/' . date( 'Y/m/d', $date );
        if ( $subdir ) {
            $path = '/' . ltrim( $subdir, '/' );
        }
        return rtrim( $s['cdn_domain'], '/' ) . $path;
    }

    /**
     * Override WordPress upload directory.
     */
    public function rewrite_upload_dir( $dirs ) {
        $s = $this->get_settings();
        if ( empty( $s['enabled'] ) ) {
            return $dirs;
        }
        $cdn = rtrim( $s['cdn_domain'], '/' );
        $date = current_time( 'timestamp' );
        $year_month_day = '/Uploads/' . date( 'Y/m/d', $date );

        $dirs['url']     = $cdn . $year_month_day;
        $dirs['baseurl'] = $cdn . '/Uploads';
        // Keep physical path the same so WP can still write files locally
        return $dirs;
    }

    /**
     * Replace local domain URLs in post content with CDN domain.
     */
    public function rewrite_content_urls( $content ) {
        $s = $this->get_settings();
        if ( empty( $s['enabled'] ) || empty( $s['cdn_domain'] ) ) {
            return $content;
        }
        $local = home_url();
        $cdn   = rtrim( $s['cdn_domain'], '/' );
        // Replace src/href pointing to /wp-content/uploads with CDN
        $content = str_replace(
            rtrim( $local, '/' ) . '/wp-content/uploads/',
            $cdn . '/Uploads/',
            $content
        );
        return $content;
    }

    /**
     * Rewrite attachment URLs to CDN.
     */
    public function rewrite_attachment_url( $url ) {
        $s = $this->get_settings();
        if ( empty( $s['enabled'] ) ) {
            return $url;
        }
        $uploads = wp_upload_dir();
        $url     = str_replace( $uploads['baseurl'], rtrim( $s['cdn_domain'], '/' ) . '/Uploads', $url );
        return $url;
    }

    /**
     * Rewrite srcset entries.
     */
    public function rewrite_srcset( $sources ) {
        $s = $this->get_settings();
        if ( empty( $s['enabled'] ) || ! is_array( $sources ) ) {
            return $sources;
        }
        $uploads = wp_upload_dir();
        $cdn     = rtrim( $s['cdn_domain'], '/' ) . '/Uploads';
        foreach ( $sources as &$source ) {
            if ( isset( $source['url'] ) ) {
                $source['url'] = str_replace( $uploads['baseurl'], $cdn, $source['url'] );
            }
        }
        return $sources;
    }
}
