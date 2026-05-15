<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

chillies_admin_wrap( 'Cross-Posting', function() {
    $subdomains = Chillies_Superadmin::get_instance()->get_subdomains();
    $posts      = get_posts( [ 'numberposts' => 100, 'post_status' => 'publish,draft' ] );
    $log        = Chillies_Cross_Post::get_instance()->get_log( 30 );
    ?>
    <div class="chillies-card">
        <h3><span class="dashicons dashicons-share-alt2"></span> Push a Post to Subdomains</h3>

        <div class="chillies-form-grid">
            <div class="chillies-field-group">
                <label>Select Post</label>
                <select id="chillies-cp-post-select">
                    <?php foreach ( $posts as $p ) : ?>
                    <option value="<?php echo esc_attr( $p->ID ); ?>"><?php echo esc_html( $p->post_title ); ?> [<?php echo esc_html( $p->post_status ); ?>]</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="chillies-field-group">
                <label>API Key (for target subdomains)</label>
                <input type="text" id="chillies-cp-apikey" placeholder="ck_...">
            </div>
        </div>

        <div class="chillies-field-group">
            <label>Select Subdomains</label>
            <div class="chillies-checkbox-grid">
                <?php foreach ( $subdomains as $sd ) : ?>
                <label class="chillies-checkbox-label">
                    <input type="checkbox" class="chillies-cp-subdomain" value="<?php echo esc_attr( $sd ); ?>">
                    <?php echo esc_html( $sd ); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="chillies-btn" id="chillies-cp-submit">
            <span class="dashicons dashicons-share-alt2"></span> Cross Post
        </button>
        <div id="chillies-cp-result" style="margin-top:16px;"></div>
    </div>

    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-list-view"></span> Cross-Post Activity Log</h3>
        <?php if ( $log ) : ?>
        <table class="chillies-table widefat">
            <thead><tr><th>Post</th><th>Subdomain</th><th>Status</th><th>Message</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ( $log as $l ) : ?>
                <tr>
                    <td><?php echo esc_html( get_the_title( $l->post_id ) ?: "#$l->post_id" ); ?></td>
                    <td><?php echo esc_html( $l->subdomain ); ?></td>
                    <td>
                        <span class="chillies-status-badge <?php echo $l->status === 'success' ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo esc_html( $l->status ); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html( $l->message ); ?></td>
                    <td><?php echo esc_html( $l->created_at ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p>No cross-post activity yet.</p>
        <?php endif; ?>
    </div>
    <?php
});
