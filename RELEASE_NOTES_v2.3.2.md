# Andy Core v2.3.2 — Login OS V1.1 signed Stable

## Scope
- Independent 后台登录 L3 menu under Andy Core → 系统, immediately after existing 社交登录 (Google settings remain separate).
- Optional WordPress native login styling and alias; opt-in Hide Legacy returns generic 404 at /wp-login.php, /admin and unauthenticated /wp-admin/ without revealing the alias.
- Private login returns HTTP X-Robots-Tag noindex/nofollow/noarchive and Cache-Control no-store; never publish secret path to robots.txt.
- Password reset, logout, logged-in admin, admin-ajax/admin-post, WooCommerce My Account preserved.
- No 2FA added (Owner decision); hiding a URL does not eliminate all brute-force vectors.

## Evidence / compatibility
- BYL Dev anonymous HTTP guest routes 404; private login and lost password 200; noindex/no-store; robots.txt clean; Google settings and authenticated admin available.
- Real Google OAuth round trip, reset email delivery and a fresh authenticated login were not independently executed.
- Four CI checks on Login OS source PR #250 PASS. No database migrations; updater compatibility 1.4.0, runtime 1.5.0.
- Preserve yby_lead_notifications and WooCommerce orders, customers and payment records.
- Previous signed v2.3.1 remains immutable; public release does not automatically install to WWW.
