<?php
/**
 * Chillies Page Builder — Auth / Login Template
 *
 * Use via shortcode: [chillies_page_builder template="auth"]
 */
$sso      = get_option( 'chillies_sso_settings', [] );
$auth_url = $sso['auth_domain'] ?? home_url();
?>
<style>
.cpt-auth { font-family: 'Inter', system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 80vh; background: #0f172a; padding: 40px 20px; }
.cpt-auth-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 40px 36px; width: 100%; max-width: 420px; }
.cpt-auth-logo { text-align: center; margin-bottom: 28px; }
.cpt-auth-logo h2 { font-size: 1.5rem; font-weight: 800; color: #e2e8f0; margin: 0; }
.cpt-auth-logo p  { font-size: .875rem; color: #94a3b8; margin: 6px 0 0; }
.cpt-auth-tabs { display: flex; margin-bottom: 24px; border-bottom: 1px solid #334155; }
.cpt-auth-tab { flex: 1; text-align: center; padding: 10px; font-size: .875rem; font-weight: 600; color: #94a3b8; cursor: pointer; border-bottom: 2px solid transparent; transition: all .2s; }
.cpt-auth-tab.active { color: #6366f1; border-bottom-color: #6366f1; }
.cpt-auth-form { display: none; }
.cpt-auth-form.active { display: block; }
.cpt-auth-field { margin-bottom: 16px; }
.cpt-auth-field label { display: block; font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.cpt-auth-field input { width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 10px 14px; font-size: .9rem; color: #e2e8f0; box-sizing: border-box; transition: border-color .15s; }
.cpt-auth-field input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }
.cpt-auth-submit { width: 100%; background: #6366f1; color: #fff; border: none; border-radius: 8px; padding: 12px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s; margin-top: 8px; }
.cpt-auth-submit:hover { background: #818cf8; }
.cpt-auth-footer { text-align: center; margin-top: 20px; font-size: .8rem; color: #94a3b8; }
.cpt-auth-footer a { color: #6366f1; text-decoration: none; }
</style>

<div class="cpt-auth">
    <div class="cpt-auth-card">
        <div class="cpt-auth-logo">
            <h2><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
            <p>Sign in to your account</p>
        </div>

        <div class="cpt-auth-tabs">
            <div class="cpt-auth-tab active" data-tab="login">Sign In</div>
            <div class="cpt-auth-tab"        data-tab="register">Register</div>
        </div>

        <!-- Login -->
        <form class="cpt-auth-form active" id="cpt-login-form" method="post" action="<?php echo esc_url( $auth_url . '/wp-login.php' ); ?>">
            <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url() ); ?>">
            <?php wp_nonce_field( 'chillies_login' ); ?>
            <div class="cpt-auth-field">
                <label>Username or Email</label>
                <input type="text" name="log" autocomplete="username" required>
            </div>
            <div class="cpt-auth-field">
                <label>Password</label>
                <input type="password" name="pwd" autocomplete="current-password" required>
            </div>
            <button type="submit" class="cpt-auth-submit">Sign In</button>
            <div class="cpt-auth-footer">
                <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Forgot password?</a>
            </div>
        </form>

        <!-- Register -->
        <form class="cpt-auth-form" id="cpt-register-form" method="post" action="<?php echo esc_url( $auth_url . '/wp-login.php?action=register' ); ?>">
            <?php wp_nonce_field( 'chillies_register' ); ?>
            <div class="cpt-auth-field">
                <label>Username</label>
                <input type="text" name="user_login" required>
            </div>
            <div class="cpt-auth-field">
                <label>Email</label>
                <input type="email" name="user_email" required>
            </div>
            <button type="submit" class="cpt-auth-submit">Create Account</button>
        </form>
    </div>
</div>

<script>
(function() {
    var tabs  = document.querySelectorAll('.cpt-auth-tab');
    var forms = document.querySelectorAll('.cpt-auth-form');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            tabs.forEach(function(t) { t.classList.remove('active'); });
            forms.forEach(function(f) { f.classList.remove('active'); });
            this.classList.add('active');
            document.getElementById('cpt-' + this.dataset.tab + '-form').classList.add('active');
        });
    });
})();
</script>
