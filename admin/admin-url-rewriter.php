<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

chillies_admin_wrap( 'URL Rewriter', function() {
    $rules = Chillies_URL_Rewriter::get_instance()->get_rules();
    ?>
    <div class="chillies-card">
        <p>Rename default WordPress URL slugs. Changes update .htaccess / rewrite rules automatically.</p>
        <form method="post">
            <?php wp_nonce_field( 'chillies_url_rewriter_save' ); ?>
            <table class="chillies-table widefat" id="chillies-url-rules-table">
                <thead>
                    <tr>
                        <th>Original Slug</th>
                        <th>Custom Slug</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rules as $orig => $custom ) : ?>
                    <tr>
                        <td><input type="text" name="rule_orig[]" value="<?php echo esc_attr( $orig ); ?>" readonly style="background:var(--chillies-bg);"></td>
                        <td><input type="text" name="rule_custom[]" value="<?php echo esc_attr( $custom ); ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:12px;display:flex;gap:10px;">
                <button type="submit" name="chillies_save_url_rewriter" class="chillies-btn">
                    <span class="dashicons dashicons-yes"></span> Save Rules
                </button>
                <button type="button" class="chillies-btn chillies-btn-secondary" id="chillies-add-url-rule">
                    <span class="dashicons dashicons-plus-alt"></span> Add Custom Rule
                </button>
            </div>
        </form>
    </div>
    <?php
});
