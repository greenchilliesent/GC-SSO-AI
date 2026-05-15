<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

chillies_admin_wrap( 'CDN Settings', function() {
    $s = wp_parse_args( get_option( 'chillies_cdn_settings', [] ), [
        'enabled'    => 0,
        'cdn_domain' => 'https://cdn.greenchilliesent.com',
    ] );
    ?>
    <div class="chillies-card">
        <form method="post">
            <?php wp_nonce_field( 'chillies_cdn_save' ); ?>
            <div class="chillies-field-group">
                <label>
                    <input type="checkbox" name="cdn_enabled" <?php checked( $s['enabled'] ); ?>>
                    Enable CDN Rewriting
                </label>
            </div>
            <div class="chillies-field-group">
                <label>CDN Domain</label>
                <input type="url" name="cdn_domain" value="<?php echo esc_attr( $s['cdn_domain'] ); ?>">
                <small>All media files will be rewritten to this domain. e.g. https://cdn.greenchilliesent.com</small>
            </div>
            <button type="submit" name="chillies_save_cdn" class="chillies-btn">
                <span class="dashicons dashicons-yes"></span> Save CDN Settings
            </button>
        </form>
    </div>

    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-portfolio"></span> CDN Folder Structure</h3>
        <p>Files are automatically organized on the CDN with this structure:</p>
        <pre style="background:var(--chillies-bg);padding:16px;border-radius:var(--chillies-radius);font-size:13px;">
/Uploads/  YYYY/MM/DD/
/Icons/    YYYY/MM/DD/
/Downloads/YYYY/MM/DD/
/Media/    YYYY/MM/DD/
/Share/    YYYY/MM/DD/
/Products/ YYYY/MM/DD/
/Images/   YYYY/MM/DD/
/Videos/   YYYY/MM/DD/
/Content/  YYYY/MM/DD/</pre>
    </div>
    <?php
});
