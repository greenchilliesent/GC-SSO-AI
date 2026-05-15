<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

chillies_admin_wrap( 'AI Settings & Features', function() {
    $s = wp_parse_args( get_option( 'chillies_ai_settings', [] ), [
        'github_ai_key'     => '',
        'enable_bug_detect' => 1,
        'enable_news_feed'  => 1,
        'enable_auto_post'  => 1,
        'enable_custom_css' => 1,
        'enable_templates'  => 1,
        'enable_shortcode'  => 1,
    ] );
    ?>
    <div class="chillies-card">
        <form method="post">
            <?php wp_nonce_field( 'chillies_ai_save' ); ?>
            <div class="chillies-form-grid">
                <div class="chillies-field-group" style="grid-column:span 2;">
                    <label>AI API Key (OpenAI)</label>
                    <input type="password" name="ai_api_key" placeholder="sk-... (leave blank to keep current)">
                    <small>Stored encrypted in the database. Never displayed or logged.</small>
                </div>
                <div class="chillies-field-group" style="grid-column:span 2;">
                    <label>GitHub AI API Key</label>
                    <input type="password" name="github_ai_key" value="<?php echo esc_attr( $s['github_ai_key'] ); ?>">
                </div>
            </div>

            <h3 style="margin-top:20px;"><span class="dashicons dashicons-admin-settings"></span> Feature Toggles</h3>
            <div class="chillies-toggle-grid">
                <?php
                $features = [
                    'enable_bug_detect' => [ 'Auto Bug Detection',      'dashicons-warning' ],
                    'enable_news_feed'  => [ 'Trending News Feed',       'dashicons-rss' ],
                    'enable_auto_post'  => [ 'Auto Post Generator',      'dashicons-edit' ],
                    'enable_custom_css' => [ 'AI Custom CSS',            'dashicons-editor-code' ],
                    'enable_templates'  => [ 'AI Page Templates',        'dashicons-layout' ],
                    'enable_shortcode'  => [ 'AI Shortcode Generator',   'dashicons-shortcode' ],
                ];
                foreach ( $features as $key => [ $label, $icon ] ) : ?>
                <label class="chillies-toggle-label">
                    <span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
                    <?php echo esc_html( $label ); ?>
                    <input type="checkbox" name="<?php echo esc_attr( $key ); ?>" <?php checked( $s[ $key ] ); ?>>
                    <span class="chillies-toggle-slider"></span>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="chillies_save_ai" class="chillies-btn" style="margin-top:20px;">
                <span class="dashicons dashicons-yes"></span> Save AI Settings
            </button>
        </form>
    </div>

    <?php if ( ! empty( $s['enable_bug_detect'] ) ) : ?>
    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-warning"></span> AI Bug Detection</h3>
        <p>Scans plugin files for common PHP/JS errors and security issues.</p>
        <button class="chillies-btn" id="chillies-bug-detect-btn">
            <span class="dashicons dashicons-search"></span> Run Bug Scan
        </button>
        <div id="chillies-bug-detect-result" style="margin-top:16px;white-space:pre-wrap;display:none;"></div>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $s['enable_auto_post'] ) ) : ?>
    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-edit"></span> AI Auto Post</h3>
        <input type="text" id="chillies-auto-post-topic" placeholder="Enter a topic or keyword" style="width:100%;margin-bottom:10px;">
        <button class="chillies-btn" id="chillies-auto-post-btn">
            <span class="dashicons dashicons-superhero-alt"></span> Generate &amp; Create Draft Post
        </button>
        <div id="chillies-auto-post-result" style="margin-top:12px;"></div>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $s['enable_shortcode'] ) ) : ?>
    <div class="chillies-card" style="margin-top:20px;">
        <h3><span class="dashicons dashicons-shortcode"></span> AI Shortcode Generator</h3>
        <textarea id="chillies-sc-desc" rows="2" placeholder="Describe what you want the shortcode to do..." style="width:100%;margin-bottom:10px;"></textarea>
        <button class="chillies-btn" id="chillies-sc-gen-btn">
            <span class="dashicons dashicons-superhero-alt"></span> Generate Shortcode
        </button>
        <div id="chillies-sc-gen-result" style="margin-top:12px;display:none;">
            <textarea id="chillies-sc-gen-output" rows="16" style="width:100%;font-family:monospace;"></textarea>
        </div>
    </div>
    <?php endif; ?>
    <?php
});
