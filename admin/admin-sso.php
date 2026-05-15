<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

chillies_admin_wrap( 'SSO Settings', function() {
    $s = wp_parse_args( get_option( 'chillies_sso_settings', [] ), [
        'enabled'       => 0,
        'auth_domain'   => 'https://auth.greenchilliesent.com',
        'cookie_domain' => '.greenchilliesent.com',
        'cookie_name'   => 'chillies_sso_token',
        'token_ttl'     => 604800,
        'jwt_secret'    => '',
    ] );
    ?>
    <div class="chillies-card">
        <form method="post">
            <?php wp_nonce_field( 'chillies_sso_save' ); ?>
            <div class="chillies-field-group">
                <label>
                    <input type="checkbox" name="sso_enabled" <?php checked( $s['enabled'] ); ?>>
                    Enable SSO
                </label>
            </div>
            <div class="chillies-form-grid">
                <div class="chillies-field-group">
                    <label>Auth Domain</label>
                    <input type="url" name="auth_domain" value="<?php echo esc_attr( $s['auth_domain'] ); ?>">
                    <small>e.g. https://auth.greenchilliesent.com</small>
                </div>
                <div class="chillies-field-group">
                    <label>Cookie Domain</label>
                    <input type="text" name="cookie_domain" value="<?php echo esc_attr( $s['cookie_domain'] ); ?>">
                    <small>e.g. .greenchilliesent.com</small>
                </div>
                <div class="chillies-field-group">
                    <label>Cookie Name</label>
                    <input type="text" name="cookie_name" value="<?php echo esc_attr( $s['cookie_name'] ); ?>">
                </div>
                <div class="chillies-field-group">
                    <label>Token TTL (seconds)</label>
                    <input type="number" name="token_ttl" value="<?php echo esc_attr( $s['token_ttl'] ); ?>" min="3600">
                    <small>Default: 604800 (7 days)</small>
                </div>
                <div class="chillies-field-group" style="grid-column:span 2;">
                    <label>JWT Secret</label>
                    <input type="text" name="jwt_secret" value="<?php echo esc_attr( $s['jwt_secret'] ); ?>" placeholder="Leave blank to auto-generate">
                </div>
            </div>
            <button type="submit" name="chillies_save_sso" class="chillies-btn">
                <span class="dashicons dashicons-yes"></span> Save SSO Settings
            </button>
        </form>
    </div>

    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-lock"></span> Active SSO Sessions</h3>
        <?php
        global $wpdb;
        $sessions = $wpdb->get_results( "SELECT s.*, u.user_login FROM {$wpdb->prefix}chillies_sso_sessions s LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id ORDER BY s.created_at DESC LIMIT 20" );
        if ( $sessions ) : ?>
        <table class="chillies-table widefat">
            <thead><tr><th>User</th><th>Subdomain</th><th>Expires</th><th>Created</th></tr></thead>
            <tbody>
                <?php foreach ( $sessions as $sess ) : ?>
                <tr>
                    <td><?php echo esc_html( $sess->user_login ); ?></td>
                    <td><?php echo esc_html( $sess->subdomain ); ?></td>
                    <td><?php echo esc_html( $sess->expires_at ); ?></td>
                    <td><?php echo esc_html( $sess->created_at ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p>No active sessions.</p>
        <?php endif; ?>
    </div>
    <?php
});
