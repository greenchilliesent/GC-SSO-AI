<?php
/**
 * Chillies Page Builder — Landing Page Template
 *
 * Use via shortcode: [chillies_page_builder template="landing"]
 */
?>
<style>
.cpt-landing {
    --c-bg: #0f172a;
    --c-card: #1e293b;
    --c-accent: #6366f1;
    --c-text: #e2e8f0;
    --c-muted: #94a3b8;
    font-family: 'Inter', system-ui, sans-serif;
    color: var(--c-text);
    background: var(--c-bg);
}
.cpt-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 60%, #1e1b4b 100%);
    padding: 80px 40px;
    text-align: center;
    border-bottom: 1px solid #334155;
}
.cpt-hero h1 { font-size: clamp(2rem,5vw,4rem); font-weight: 800; margin: 0 0 20px; background: linear-gradient(90deg,#6366f1,#a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.cpt-hero p  { font-size: 1.15rem; color: var(--c-muted); max-width: 600px; margin: 0 auto 32px; }
.cpt-hero a  { display: inline-block; background: var(--c-accent); color: #fff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 1rem; transition: opacity .2s; }
.cpt-hero a:hover { opacity: .85; }
.cpt-features { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 24px; padding: 60px 40px; }
.cpt-feature { background: var(--c-card); border: 1px solid #334155; border-radius: 12px; padding: 28px; transition: border-color .2s; }
.cpt-feature:hover { border-color: var(--c-accent); }
.cpt-feature .icon { font-size: 2rem; margin-bottom: 14px; }
.cpt-feature h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 8px; }
.cpt-feature p  { font-size: .9rem; color: var(--c-muted); margin: 0; }
.cpt-cta { text-align: center; padding: 60px 40px; background: var(--c-card); }
.cpt-cta h2 { font-size: 2rem; font-weight: 800; margin: 0 0 12px; }
.cpt-cta p  { color: var(--c-muted); margin: 0 auto 28px; max-width: 500px; }
.cpt-cta a  { background: var(--c-accent); color: #fff; padding: 14px 36px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 1rem; }
</style>

<div class="cpt-landing">
    <div class="cpt-hero">
        <h1><?php echo get_bloginfo( 'name' ); ?></h1>
        <p><?php echo esc_html( get_bloginfo( 'description' ) ?: 'The all-in-one platform for everything you need.' ); ?></p>
        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Get Started</a>
    </div>

    <div class="cpt-features">
        <div class="cpt-feature">
            <div class="icon">&#128274;</div>
            <h3>Single Sign-On</h3>
            <p>One account. Seamless access across all your subdomains with JWT-secured sessions.</p>
        </div>
        <div class="cpt-feature">
            <div class="icon">&#9729;&#65039;</div>
            <h3>Global CDN</h3>
            <p>Media files served lightning fast from our global CDN network, organized by date and type.</p>
        </div>
        <div class="cpt-feature">
            <div class="icon">&#129302;</div>
            <h3>AI-Powered</h3>
            <p>Auto-generate posts, detect bugs, produce custom CSS, and create shortcodes with AI.</p>
        </div>
        <div class="cpt-feature">
            <div class="icon">&#128241;</div>
            <h3>Cross-Posting</h3>
            <p>Write once, publish everywhere. Push content to all your subdomains in one click.</p>
        </div>
    </div>

    <div class="cpt-cta">
        <h2>Ready to get started?</h2>
        <p>Join thousands of users who trust our platform for their multi-site needs.</p>
        <a href="<?php echo esc_url( wp_registration_url() ); ?>">Create Free Account</a>
    </div>
</div>
