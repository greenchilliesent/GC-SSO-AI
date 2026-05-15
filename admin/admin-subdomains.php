<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

chillies_admin_wrap( 'Superadmin — Subdomain Control', function() {
    $subdomains = Chillies_Superadmin::get_instance()->get_subdomains();
    ?>
    <div class="chillies-card">
        <h3><span class="dashicons dashicons-networking"></span> Add Subdomain</h3>
        <div style="display:flex;gap:10px;align-items:flex-end;">
            <div class="chillies-field-group" style="flex:1;">
                <label>Subdomain</label>
                <input type="text" id="chillies-new-subdomain" placeholder="e.g. shop.greenchilliesent.com">
            </div>
            <button class="chillies-btn" id="chillies-add-subdomain-btn">
                <span class="dashicons dashicons-plus-alt"></span> Add
            </button>
        </div>
    </div>

    <div class="chillies-card" style="margin-top:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3><span class="dashicons dashicons-list-view"></span> Connected Subdomains</h3>
            <button class="chillies-btn" id="chillies-push-all-btn">
                <span class="dashicons dashicons-upload"></span> Push Settings to All
            </button>
        </div>
        <table class="chillies-table widefat" id="chillies-subdomains-table">
            <thead>
                <tr>
                    <th>Subdomain</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $subdomains as $sd ) : ?>
                <tr id="sd-row-<?php echo esc_attr( md5( $sd ) ); ?>">
                    <td><?php echo esc_html( $sd ); ?></td>
                    <td class="sd-status" data-domain="<?php echo esc_attr( $sd ); ?>">
                        <span class="chillies-status-badge status-checking">
                            <span class="dashicons dashicons-update chillies-spin"></span> Checking...
                        </span>
                    </td>
                    <td>
                        <a href="https://<?php echo esc_attr( $sd ); ?>" target="_blank" class="chillies-btn-sm">
                            <span class="dashicons dashicons-external"></span> Visit
                        </a>
                        <button class="chillies-btn-sm chillies-btn-danger chillies-remove-subdomain" data-domain="<?php echo esc_attr( $sd ); ?>">
                            <span class="dashicons dashicons-trash"></span> Remove
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="chillies-push-all-result" style="margin-top:12px;"></div>
    </div>
    <?php
});
