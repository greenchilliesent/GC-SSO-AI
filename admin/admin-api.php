<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

chillies_admin_wrap( 'API Key Manager', function() {
    $api_inst = Chillies_API::get_instance();
    $keys     = $api_inst->get_all_keys();
    ?>
    <div class="chillies-card">
        <h3><span class="dashicons dashicons-admin-network"></span> Generate New API Key</h3>
        <div class="chillies-form-grid">
            <div class="chillies-field-group">
                <label>Label</label>
                <input type="text" id="chillies-apikey-label" placeholder="e.g. Mobile App Key">
            </div>
            <div class="chillies-field-group">
                <label>Rate Limit (requests)</label>
                <input type="number" id="chillies-apikey-rate" value="1000" min="1">
            </div>
        </div>
        <button class="chillies-btn" id="chillies-gen-apikey">
            <span class="dashicons dashicons-plus-alt"></span> Generate Key
        </button>
        <div id="chillies-new-key-display" style="margin-top:14px;display:none;">
            <strong>Your new API key (copy it now — it won't be shown again):</strong><br>
            <code id="chillies-new-key-value" style="font-size:15px;word-break:break-all;"></code>
        </div>
    </div>

    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-list-view"></span> All API Keys</h3>
        <table class="chillies-table widefat" id="chillies-api-keys-table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Key (truncated)</th>
                    <th>Usage</th>
                    <th>Rate Limit</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $keys ) : foreach ( $keys as $k ) : ?>
                <tr id="apikey-row-<?php echo esc_attr( $k->id ); ?>">
                    <td><?php echo esc_html( $k->label ); ?></td>
                    <td><code><?php echo esc_html( substr( $k->api_key, 0, 12 ) . '...' ); ?></code></td>
                    <td><?php echo esc_html( number_format( $k->usage ) ); ?></td>
                    <td><?php echo esc_html( number_format( $k->rate_limit ) ); ?></td>
                    <td><?php echo esc_html( $k->created_at ); ?></td>
                    <td>
                        <button class="chillies-btn-sm chillies-btn-danger chillies-revoke-apikey" data-id="<?php echo esc_attr( $k->id ); ?>">
                            <span class="dashicons dashicons-trash"></span> Revoke
                        </button>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="6">No API keys generated yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-admin-generic"></span> API Endpoint Reference</h3>
        <table class="chillies-table widefat">
            <thead><tr><th>Method</th><th>Endpoint</th><th>Auth</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td>GET</td><td><code>/wp-json/chillies/v1/posts</code></td><td>Public</td><td>Fetch posts</td></tr>
                <tr><td>GET</td><td><code>/wp-json/chillies/v1/profile</code></td><td>Logged in</td><td>Current user profile</td></tr>
                <tr><td>GET</td><td><code>/wp-json/chillies/v1/sso/validate</code></td><td>Bearer JWT</td><td>Validate SSO token</td></tr>
                <tr><td>POST</td><td><code>/wp-json/chillies/v1/admin/push-settings</code></td><td>Admin</td><td>Push settings from superadmin</td></tr>
            </tbody>
        </table>
        <p style="margin-top:8px;">Pass your API key as header: <code>X-Chillies-API-Key: ck_...</code> or query param <code>?api_key=ck_...</code></p>
    </div>
    <?php
});
