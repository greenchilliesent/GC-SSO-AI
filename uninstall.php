<?php
/**
 * Uninstall Chillies SSO AI — removes all plugin data.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables
$tables = [
    $wpdb->prefix . 'chillies_skills',
    $wpdb->prefix . 'chillies_api_keys',
    $wpdb->prefix . 'chillies_cross_post_log',
    $wpdb->prefix . 'chillies_sso_sessions',
];
foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS `$table`" );
}

// Remove all plugin options
$options = [
    'chillies_sso_settings',
    'chillies_cdn_settings',
    'chillies_url_rewriter_rules',
    'chillies_ai_settings',
    'chillies_cross_post_settings',
    'chillies_api_settings',
    'chillies_subdomains',
    'chillies_custom_css',
    'chillies_db_settings',
    'chillies_appearance',
];
foreach ( $options as $option ) {
    delete_option( $option );
}
