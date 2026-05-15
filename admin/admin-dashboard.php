<?php
/**
 * Chillies SSO AI — Main Admin Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register all admin menus.
 */
function chillies_register_admin_menus() {
    add_menu_page(
        'Chillies SSO AI',
        'Chillies SSO AI',
        'manage_options',
        'chillies-dashboard',
        'chillies_render_dashboard',
        'dashicons-networking',
        58
    );
    $tabs = [
        [ 'chillies-url-rewriter',  'URL Rewriter',    'chillies_render_url_rewriter'  ],
        [ 'chillies-cdn',           'CDN Settings',    'chillies_render_cdn'           ],
        [ 'chillies-sso',           'SSO',             'chillies_render_sso'           ],
        [ 'chillies-ai',            'AI Settings',     'chillies_render_ai'            ],
        [ 'chillies-cross-post',    'Cross-Posting',   'chillies_render_cross_post'    ],
        [ 'chillies-skills',        'Skills Manager',  'chillies_render_skills'        ],
        [ 'chillies-api',           'API Keys',        'chillies_render_api'           ],
        [ 'chillies-shortcodes',    'Shortcodes',      'chillies_render_shortcodes'    ],
        [ 'chillies-page-builder',  'Page Builder',    'chillies_render_page_builder'  ],
        [ 'chillies-custom-css',    'Custom CSS',      'chillies_render_custom_css'    ],
        [ 'chillies-db',            'Database',        'chillies_render_db'            ],
        [ 'chillies-superadmin',    'Superadmin',      'chillies_render_superadmin'    ],
        [ 'chillies-appearance',    'Appearance',      'chillies_render_appearance'    ],
    ];
    foreach ( $tabs as $t ) {
        add_submenu_page( 'chillies-dashboard', $t[0], $t[1], 'manage_options', $t[0], $t[2] );
    }
}
add_action( 'admin_menu', 'chillies_register_admin_menus' );

/**
 * Handle all admin form saves (unified).
 */
function chillies_handle_admin_saves() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    // SSO settings
    if ( isset( $_POST['chillies_save_sso'] ) && check_admin_referer( 'chillies_sso_save' ) ) {
        update_option( 'chillies_sso_settings', [
            'enabled'       => isset( $_POST['sso_enabled'] ) ? 1 : 0,
            'auth_domain'   => esc_url_raw( $_POST['auth_domain'] ?? '' ),
            'cookie_domain' => sanitize_text_field( $_POST['cookie_domain'] ?? '' ),
            'cookie_name'   => sanitize_text_field( $_POST['cookie_name'] ?? 'chillies_sso_token' ),
            'token_ttl'     => absint( $_POST['token_ttl'] ?? 604800 ),
            'jwt_secret'    => sanitize_text_field( $_POST['jwt_secret'] ?? '' ),
        ] );
        add_settings_error( 'chillies', 'saved', 'SSO settings saved.', 'updated' );
    }

    // CDN settings
    if ( isset( $_POST['chillies_save_cdn'] ) && check_admin_referer( 'chillies_cdn_save' ) ) {
        update_option( 'chillies_cdn_settings', [
            'enabled'    => isset( $_POST['cdn_enabled'] ) ? 1 : 0,
            'cdn_domain' => esc_url_raw( $_POST['cdn_domain'] ?? '' ),
        ] );
        add_settings_error( 'chillies', 'saved', 'CDN settings saved.', 'updated' );
    }

    // AI settings
    if ( isset( $_POST['chillies_save_ai'] ) && check_admin_referer( 'chillies_ai_save' ) ) {
        $ai = Chillies_AI::get_instance();
        if ( ! empty( $_POST['ai_api_key'] ) ) {
            $ai->save_api_key( sanitize_text_field( $_POST['ai_api_key'] ) );
        }
        $current = get_option( 'chillies_ai_settings', [] );
        update_option( 'chillies_ai_settings', array_merge( $current, [
            'github_ai_key'     => sanitize_text_field( $_POST['github_ai_key'] ?? '' ),
            'enable_bug_detect' => isset( $_POST['enable_bug_detect'] ) ? 1 : 0,
            'enable_news_feed'  => isset( $_POST['enable_news_feed'] ) ? 1 : 0,
            'enable_auto_post'  => isset( $_POST['enable_auto_post'] ) ? 1 : 0,
            'enable_custom_css' => isset( $_POST['enable_custom_css'] ) ? 1 : 0,
            'enable_templates'  => isset( $_POST['enable_templates'] ) ? 1 : 0,
            'enable_shortcode'  => isset( $_POST['enable_shortcode'] ) ? 1 : 0,
        ] ) );
        add_settings_error( 'chillies', 'saved', 'AI settings saved.', 'updated' );
    }

    // URL Rewriter
    if ( isset( $_POST['chillies_save_url_rewriter'] ) && check_admin_referer( 'chillies_url_rewriter_save' ) ) {
        $originals = array_map( 'sanitize_key', $_POST['rule_orig'] ?? [] );
        $customs   = array_map( 'sanitize_text_field', $_POST['rule_custom'] ?? [] );
        $rules     = array_combine( $originals, $customs );
        Chillies_URL_Rewriter::get_instance()->save_rules( $rules );
        add_settings_error( 'chillies', 'saved', 'URL rules saved.', 'updated' );
    }

    // Database settings
    if ( isset( $_POST['chillies_save_db'] ) && check_admin_referer( 'chillies_db_save' ) ) {
        update_option( 'chillies_db_settings', [
            'db_host' => sanitize_text_field( $_POST['db_host'] ?? '' ),
            'db_name' => sanitize_text_field( $_POST['db_name'] ?? '' ),
            'db_user' => sanitize_text_field( $_POST['db_user'] ?? '' ),
            'db_pass' => sanitize_text_field( $_POST['db_pass'] ?? '' ),
        ] );
        add_settings_error( 'chillies', 'saved', 'Database settings saved.', 'updated' );
    }

    // Custom CSS
    if ( isset( $_POST['chillies_save_css'] ) && check_admin_referer( 'chillies_css_save' ) ) {
        update_option( 'chillies_custom_css', wp_strip_all_tags( $_POST['custom_css'] ?? '' ) );
        add_settings_error( 'chillies', 'saved', 'Custom CSS saved.', 'updated' );
    }

    // Appearance
    if ( isset( $_POST['chillies_save_appearance'] ) && check_admin_referer( 'chillies_appearance_save' ) ) {
        update_option( 'chillies_appearance', [
            'font_family'  => sanitize_text_field( $_POST['font_family'] ?? 'Inter' ),
            'font_color'   => sanitize_hex_color( $_POST['font_color'] ?? '#e2e8f0' ),
            'bg_color'     => sanitize_hex_color( $_POST['bg_color'] ?? '#0f172a' ),
            'accent_color' => sanitize_hex_color( $_POST['accent_color'] ?? '#6366f1' ),
            'card_bg'      => sanitize_hex_color( $_POST['card_bg'] ?? '#1e293b' ),
            'sidebar_bg'   => sanitize_hex_color( $_POST['sidebar_bg'] ?? '#0f172a' ),
            'border_color' => sanitize_hex_color( $_POST['border_color'] ?? '#334155' ),
            'font_size'    => sanitize_text_field( $_POST['font_size'] ?? '14' ),
            'border_radius'=> sanitize_text_field( $_POST['border_radius'] ?? '8' ),
            'google_font_url' => esc_url_raw( $_POST['google_font_url'] ?? '' ),
        ] );
        add_settings_error( 'chillies', 'saved', 'Appearance settings saved.', 'updated' );
    }
}
add_action( 'admin_init', 'chillies_handle_admin_saves' );

// ── Helper: get appearance settings ──────────────────────────────────────────

function chillies_get_appearance() {
    return wp_parse_args( get_option( 'chillies_appearance', [] ), [
        'font_family'    => 'Inter',
        'font_color'     => '#e2e8f0',
        'bg_color'       => '#0f172a',
        'accent_color'   => '#6366f1',
        'card_bg'        => '#1e293b',
        'sidebar_bg'     => '#0f172a',
        'border_color'   => '#334155',
        'font_size'      => '14',
        'border_radius'  => '8',
        'google_font_url'=> '',
    ] );
}

// ── Helper: render admin page wrapper ────────────────────────────────────────

function chillies_admin_wrap( $title, $content_callback ) {
    $app = chillies_get_appearance();
    ?>
    <style>
        #chillies-admin-wrap {
            --chillies-bg: <?php echo esc_attr( $app['bg_color'] ); ?>;
            --chillies-card: <?php echo esc_attr( $app['card_bg'] ); ?>;
            --chillies-accent: <?php echo esc_attr( $app['accent_color'] ); ?>;
            --chillies-text: <?php echo esc_attr( $app['font_color'] ); ?>;
            --chillies-border: <?php echo esc_attr( $app['border_color'] ); ?>;
            --chillies-radius: <?php echo esc_attr( $app['border_radius'] ); ?>px;
            --chillies-font: '<?php echo esc_attr( $app['font_family'] ); ?>', system-ui, sans-serif;
            --chillies-font-size: <?php echo esc_attr( $app['font_size'] ); ?>px;
        }
    </style>
    <?php if ( ! empty( $app['google_font_url'] ) ) : ?>
    <link rel="stylesheet" href="<?php echo esc_url( $app['google_font_url'] ); ?>">
    <?php endif; ?>
    <div id="chillies-admin-wrap">
        <?php chillies_render_status_bar(); ?>
        <div class="chillies-content">
            <h1 class="chillies-page-title">
                <span class="dashicons dashicons-networking"></span>
                <?php echo esc_html( $title ); ?>
            </h1>
            <?php settings_errors( 'chillies' ); ?>
            <?php $content_callback(); ?>
        </div>
    </div>
    <?php
}

function chillies_render_status_bar() {
    $sso_on = ! empty( get_option( 'chillies_sso_settings', [] )['enabled'] );
    $cdn_on = ! empty( get_option( 'chillies_cdn_settings', [] )['enabled'] );
    $ai_key = Chillies_AI::get_instance()->get_api_key();
    ?>
    <div class="chillies-status-bar">
        <span class="chillies-logo">Chillies SSO AI <small>v<?php echo CHILLIES_VERSION; ?></small></span>
        <span class="chillies-status-item <?php echo $sso_on ? 'status-on' : 'status-off'; ?>">
            <span class="dashicons dashicons-admin-users"></span> SSO <?php echo $sso_on ? 'Active' : 'Inactive'; ?>
        </span>
        <span class="chillies-status-item <?php echo $cdn_on ? 'status-on' : 'status-off'; ?>">
            <span class="dashicons dashicons-cloud"></span> CDN <?php echo $cdn_on ? 'Active' : 'Inactive'; ?>
        </span>
        <span class="chillies-status-item <?php echo $ai_key ? 'status-on' : 'status-off'; ?>">
            <span class="dashicons dashicons-superhero-alt"></span> AI <?php echo $ai_key ? 'Connected' : 'Not configured'; ?>
        </span>
    </div>
    <?php
}

// ── Page renderers ────────────────────────────────────────────────────────────

function chillies_render_dashboard() {
    chillies_admin_wrap( 'Dashboard Overview', function() {
        global $wpdb;
        $user_count     = count_users()['total_users'];
        $subdomain_count= count( Chillies_Superadmin::get_instance()->get_subdomains() );
        $session_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}chillies_sso_sessions WHERE expires_at > NOW()" );
        $api_key_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}chillies_api_keys" );
        ?>
        <div class="chillies-stats-grid">
            <div class="chillies-stat-card">
                <span class="dashicons dashicons-admin-users chillies-stat-icon"></span>
                <div>
                    <div class="chillies-stat-num"><?php echo esc_html( $user_count ); ?></div>
                    <div class="chillies-stat-label">Total Users</div>
                </div>
            </div>
            <div class="chillies-stat-card">
                <span class="dashicons dashicons-networking chillies-stat-icon"></span>
                <div>
                    <div class="chillies-stat-num"><?php echo esc_html( $subdomain_count ); ?></div>
                    <div class="chillies-stat-label">Active Subdomains</div>
                </div>
            </div>
            <div class="chillies-stat-card">
                <span class="dashicons dashicons-lock chillies-stat-icon"></span>
                <div>
                    <div class="chillies-stat-num"><?php echo esc_html( $session_count ?: 0 ); ?></div>
                    <div class="chillies-stat-label">SSO Sessions</div>
                </div>
            </div>
            <div class="chillies-stat-card">
                <span class="dashicons dashicons-admin-generic chillies-stat-icon"></span>
                <div>
                    <div class="chillies-stat-num"><?php echo esc_html( $api_key_count ?: 0 ); ?></div>
                    <div class="chillies-stat-label">API Keys</div>
                </div>
            </div>
        </div>

        <div class="chillies-card" style="margin-top:24px;">
            <h3><span class="dashicons dashicons-superhero-alt"></span> AI — Trending Topics</h3>
            <div id="chillies-news-widget">
                <?php echo esc_html( get_transient( 'chillies_ai_news_feed' ) ?: 'Click Refresh to load trending topics.' ); ?>
            </div>
            <button class="chillies-btn" id="chillies-refresh-news">
                <span class="dashicons dashicons-update"></span> Refresh
            </button>
        </div>
        <?php
    });
}

function chillies_render_appearance() {
    chillies_admin_wrap( 'Appearance Settings', function() {
        $app = chillies_get_appearance();
        ?>
        <div class="chillies-card">
            <p>Customize the admin panel fonts, colors, and styles. Changes apply instantly to all admin pages.</p>
            <form method="post">
                <?php wp_nonce_field( 'chillies_appearance_save' ); ?>

                <div class="chillies-form-grid">
                    <div class="chillies-field-group">
                        <label>Font Family</label>
                        <input type="text" name="font_family" value="<?php echo esc_attr( $app['font_family'] ); ?>" placeholder="Inter">
                        <small>Any Google Font name (e.g. Roboto, Poppins, Inter)</small>
                    </div>
                    <div class="chillies-field-group">
                        <label>Google Font URL</label>
                        <input type="url" name="google_font_url" value="<?php echo esc_attr( $app['google_font_url'] ); ?>" placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
                        <small>Full Google Fonts CSS URL to load the font</small>
                    </div>
                    <div class="chillies-field-group">
                        <label>Font Size (px)</label>
                        <input type="number" name="font_size" value="<?php echo esc_attr( $app['font_size'] ); ?>" min="10" max="24">
                    </div>
                    <div class="chillies-field-group">
                        <label>Border Radius (px)</label>
                        <input type="number" name="border_radius" value="<?php echo esc_attr( $app['border_radius'] ); ?>" min="0" max="32">
                    </div>
                </div>

                <h3 style="margin-top:24px;">Colors</h3>
                <div class="chillies-color-grid">
                    <?php
                    $colors = [
                        'font_color'   => 'Text Color',
                        'bg_color'     => 'Background Color',
                        'card_bg'      => 'Card Background',
                        'accent_color' => 'Accent / Button Color',
                        'sidebar_bg'   => 'Sidebar Background',
                        'border_color' => 'Border Color',
                    ];
                    foreach ( $colors as $key => $label ) : ?>
                    <div class="chillies-color-field">
                        <label><?php echo esc_html( $label ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $app[ $key ] ); ?>" class="chillies-color-picker">
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" name="chillies_save_appearance" class="chillies-btn">
                        <span class="dashicons dashicons-yes"></span> Save Appearance
                    </button>
                </div>
            </form>
        </div>
        <?php
    });
}

function chillies_render_sso() {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-sso.php';
}
function chillies_render_cdn() {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-cdn.php';
}
function chillies_render_ai() {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-ai.php';
}
function chillies_render_skills() {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-skills.php';
}
function chillies_render_cross_post() {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-cross-post.php';
}
function chillies_render_superadmin() {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-subdomains.php';
}
function chillies_render_url_rewriter() {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-url-rewriter.php';
}
function chillies_render_api() {
    require_once CHILLIES_PLUGIN_DIR . 'admin/admin-api.php';
}

function chillies_render_shortcodes() {
    chillies_admin_wrap( 'Shortcodes Reference', function() {
        $shortcodes = [
            '[chillies_login]'                     => 'Renders the SSO login form (redirects to auth subdomain).',
            '[chillies_register]'                  => 'Renders the SSO registration form.',
            '[chillies_sso_status]'                => "Displays the user's current SSO session status.",
            '[chillies_cdn_url file="image.jpg"]'  => 'Returns the CDN URL for a given file. Use folder="" to specify sub-folder.',
            '[chillies_cross_post]'                => 'Renders a cross-post widget (editor role required).',
            '[chillies_skill name="SEO"]'          => 'Displays a skill card by name.',
            '[chillies_news_feed]'                 => 'Displays the AI-powered trending topics feed.',
            '[chillies_page_builder template="landing"]' => 'Renders a built-in page builder template (landing, blog, auth).',
            '[chillies_api_key]'                   => "Displays the current user's API key.",
        ];
        ?>
        <div class="chillies-card">
            <table class="chillies-table widefat">
                <thead>
                    <tr>
                        <th>Shortcode</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $shortcodes as $sc => $desc ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $sc ); ?></code></td>
                        <td><?php echo esc_html( $desc ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    });
}

function chillies_render_page_builder() {
    chillies_admin_wrap( 'Page Builder', function() {
        $pb = Chillies_Page_Builder::get_instance();
        ?>
        <div class="chillies-card">
            <h3>Built-in Templates</h3>
            <div class="chillies-template-grid">
                <?php foreach ( $pb->get_templates() as $slug => $label ) : ?>
                <div class="chillies-template-card">
                    <span class="dashicons dashicons-layout" style="font-size:32px;height:32px;width:32px;"></span>
                    <strong><?php echo esc_html( $label ); ?></strong>
                    <code>[chillies_page_builder template="<?php echo esc_attr( $slug ); ?>"]</code>
                    <a href="<?php echo esc_url( add_query_arg( 'preview_template', $slug, admin_url( 'admin.php?page=chillies-page-builder' ) ) ); ?>" class="chillies-btn-sm">Preview</a>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ( ! empty( $_GET['preview_template'] ) ) :
                $tpl = sanitize_key( $_GET['preview_template'] );
                $file = CHILLIES_PLUGIN_DIR . "templates/page-builder/template-$tpl.php";
                if ( file_exists( $file ) ) :
                    echo '<hr><div class="chillies-template-preview">';
                    include $file;
                    echo '</div>';
                endif;
            endif; ?>

            <hr>
            <h3><span class="dashicons dashicons-superhero-alt"></span> AI Template Generator</h3>
            <p>Describe the page you want and AI will generate a PHP/HTML template for you.</p>
            <textarea id="chillies-ai-template-prompt" rows="3" placeholder="e.g. A product showcase page with hero section and pricing cards" style="width:100%;margin-bottom:10px;"></textarea>
            <button class="chillies-btn" id="chillies-ai-template-btn">
                <span class="dashicons dashicons-superhero-alt"></span> Generate Template
            </button>
            <div id="chillies-ai-template-result" style="margin-top:16px;display:none;">
                <textarea id="chillies-ai-template-output" rows="20" style="width:100%;font-family:monospace;"></textarea>
            </div>
        </div>
        <?php
    });
}

function chillies_render_custom_css() {
    chillies_admin_wrap( 'Custom CSS', function() {
        $css = get_option( 'chillies_custom_css', '' );
        ?>
        <div class="chillies-card">
            <p>CSS entered here is injected into your site's <code>&lt;head&gt;</code> on every front-end page load.</p>
            <form method="post">
                <?php wp_nonce_field( 'chillies_css_save' ); ?>
                <textarea name="custom_css" rows="24" style="width:100%;font-family:monospace;font-size:13px;"><?php echo esc_textarea( $css ); ?></textarea>

                <div style="display:flex;gap:12px;margin-top:12px;">
                    <button type="submit" name="chillies_save_css" class="chillies-btn">
                        <span class="dashicons dashicons-yes"></span> Save CSS
                    </button>
                </div>
            </form>

            <hr>
            <h3><span class="dashicons dashicons-superhero-alt"></span> AI CSS Assist</h3>
            <textarea id="chillies-ai-css-prompt" rows="2" placeholder="e.g. Make all headings a deep purple gradient" style="width:100%;margin-bottom:10px;"></textarea>
            <button class="chillies-btn" id="chillies-ai-css-btn">
                <span class="dashicons dashicons-superhero-alt"></span> Generate CSS
            </button>
            <div id="chillies-ai-css-result" style="margin-top:16px;display:none;">
                <textarea id="chillies-ai-css-output" rows="12" style="width:100%;font-family:monospace;"></textarea>
                <button class="chillies-btn-sm" id="chillies-ai-css-apply" style="margin-top:8px;">
                    <span class="dashicons dashicons-plus-alt"></span> Append to editor above
                </button>
            </div>
        </div>
        <?php
    });
}

function chillies_render_db() {
    chillies_admin_wrap( 'Database Settings', function() {
        $db = wp_parse_args( get_option( 'chillies_db_settings', [] ), [
            'db_host' => '', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
        ] );
        ?>
        <div class="chillies-card">
            <p>Configure the shared database used across all subdomains. These credentials override WordPress defaults for multi-subdomain setups.</p>
            <form method="post">
                <?php wp_nonce_field( 'chillies_db_save' ); ?>
                <div class="chillies-form-grid">
                    <div class="chillies-field-group">
                        <label>DB Host</label>
                        <input type="text" name="db_host" value="<?php echo esc_attr( $db['db_host'] ); ?>" placeholder="localhost">
                    </div>
                    <div class="chillies-field-group">
                        <label>DB Name</label>
                        <input type="text" name="db_name" value="<?php echo esc_attr( $db['db_name'] ); ?>">
                    </div>
                    <div class="chillies-field-group">
                        <label>DB User</label>
                        <input type="text" name="db_user" value="<?php echo esc_attr( $db['db_user'] ); ?>">
                    </div>
                    <div class="chillies-field-group">
                        <label>DB Password</label>
                        <input type="password" name="db_pass" value="<?php echo esc_attr( $db['db_pass'] ); ?>">
                    </div>
                </div>
                <button type="submit" name="chillies_save_db" class="chillies-btn">
                    <span class="dashicons dashicons-database"></span> Save Database Settings
                </button>
            </form>
        </div>
        <?php
    });
}

// Placeholder for admin-url-rewriter
function chillies_render_url_rewriter_page() {}
