                                                                                        ****Chillies SSO AI — v2.0.1****

All-in-one SSO, CDN, AI, URL Rewriting, Cross-Posting, and Subdomain Management WordPress Plugin
Author: Chillies Entertainment
License: GPL v2 or later

Overview

Chillies SSO AI is a production-ready WordPress plugin that gives you:

Single Sign-On across all your subdomains using JWT-secured cookies
CDN integration with automatic media URL rewriting
URL rewriting to hide or rename default WordPress slugs
AI features: auto posts, CSS generation, bug detection, shortcode generation, page templates
Cross-posting: push posts to multiple subdomains simultaneously
Skills manager: custom skill cards with token limits
REST API system with key management and rate limiting
Superadmin panel: manage all subdomains from one place
Appearance settings: customize fonts, colors, and styles from the admin panel
Installation

Upload the chillies-sso-ai folder to /wp-content/plugins/
Activate the plugin in WordPress Admin > Plugins
Navigate to Chillies SSO AI in the sidebar
Configure each section (SSO, CDN, AI keys, etc.)
Configuration Guide

Database Settings (Admin > Database)

Set DB_HOST, DB_NAME, DB_USER, DB_PASSWORD to share one database across subdomains.

SSO (Admin > SSO)

Set Auth Domain to https://auth.greenchilliesent.com
Set Cookie Domain to .greenchilliesent.com
Enable SSO and configure token TTL
CDN (Admin > CDN Settings)

Set CDN Domain to https://cdn.greenchilliesent.com
Enable CDN rewriting — all media src/href attributes are rewritten automatically
AI (Admin > AI Settings)

Enter your OpenAI API key (stored encrypted in the database)
Enter your GitHub AI API key
Toggle individual AI features on/off
URL Rewriter (Admin > URL Rewriter)

Default rewrites:

Original	Custom
wp-admin	admin
wp-login.php	login
wp-content	content
wp-includes	includes
wp-json	api
wp-uploads	uploads
Add custom rules from the admin panel. All mappings stored in the database.

Appearance (Admin > Appearance)

Customize:

Font family (any Google Font name)
Font size, border radius
Text color, background color, card background, accent color, sidebar background, border color
All changes apply instantly across all plugin admin pages.

Shortcodes

Shortcode	Description
[chillies_login]	Renders SSO login form (redirects to auth subdomain)
[chillies_register]	Renders registration form
[chillies_sso_status]	Shows user's SSO session status
[chillies_cdn_url file="image.jpg" folder="Images"]	Returns CDN URL for a file
[chillies_cross_post]	Cross-post widget (editor role required)
[chillies_skill name="SEO"]	Displays a skill card by name
[chillies_news_feed]	AI-powered trending news feed
[chillies_page_builder template="landing"]	Renders a built-in template (landing, blog, auth)
[chillies_api_key]	Shows current user's API key
API Endpoints

Base URL: https://yourdomain.com/wp-json/chillies/v1/

Method	Endpoint	Auth	Description
GET	/posts	Public	Fetch posts (supports ?type=post&per_page=10&page=1)
GET	/profile	Logged in	Current user profile
GET	/sso/validate	Bearer JWT	Validate an SSO token
POST	/admin/push-settings	Admin	Push settings from superadmin to this subdomain
Authentication: Pass X-Chillies-API-Key: ck_... header or ?api_key=ck_... query param.

cookies.php Deployment Guide

Copy cookies.php to the root of each subdomain (e.g. shop.greenchilliesent.com/cookies.php)
Edit the config constants at the top of the file:
define('CHILLIES_MAIN_DOMAIN', 'https://www.greenchilliesent.com');
define('CHILLIES_AUTH_DOMAIN', 'https://auth.greenchilliesent.com');
define('CHILLIES_COOKIE_DOMAIN', '.greenchilliesent.com');

Include it early in each subdomain's WordPress bootstrap:
// In wp-config.php or a must-use plugin:
require_once __DIR__ . '/cookies.php';

Check chillies-sso.log for connection events
Subdomain Setup

Add subdomains in Admin > Superadmin or by editing domains.txt
Deploy cookies.php to each subdomain root
Configure all subdomains to share the same database (Admin > Database)
Use Push Settings to All in the Superadmin panel to broadcast configuration
Security

All API keys encrypted with AES-256-CBC using WordPress salts
DB credentials entered via admin UI, never hardcoded
Nonce verification on all admin form submissions
All user inputs sanitized and validated
JWT tokens use HMAC-SHA256 signing
SSO cookies: HttpOnly, Secure, SameSite=None, scoped to .greenchilliesent.com
API keys never exposed on the frontend
Changelog

v2.0.1 (2026-05-15)

Initial public release
SSO with JWT and shared cookies
CDN integration with date-based folder structure
URL rewriting for all WordPress slugs
AI integration (bug detection, auto posts, CSS, templates, shortcodes)
Cross-posting to unlimited subdomains
Skills manager with token system
REST API with key management and rate limiting
3 page builder templates (landing, blog, auth)
Full shortcode library
Superadmin subdomain control panel
Appearance settings (font, color, style customization)
Standalone cookies.php bridge file
License

GPL v2 or later — https://www.gnu.org/licenses/gpl-2.0.html
