# Andy Core Login OS V1 · Backend Login

Status: isolated source implementation / Dev UAT not yet signed.
Repository: beyourlovercom/YBY-Core
Branch: feat/andy-core-login-os-v1-20261028

## Owner-frozen navigation contract

Do **not** put Login OS tabs in the existing Social Login page.

- WordPress L1: Andy Core.
- WordPress L2: 系统 (existing; no additional left-side WordPress menu).
- Existing L3: 社交登录 → admin.php?page=yby-social-login.
- New independent L3 immediately beneath it: 后台登录 → admin.php?page=yby-login-os.
- The new page alone contains in-page content tabs: 基础设置 / 视觉与品牌 / 登录 URL / 兼容与安全.
- Google provider settings and the original Social Login page remain intact; legacy `?provider=google` URLs keep working.
- Only the enabled, authorized modules render these tertiary links. Independent WP auth access checks still apply.

## Scope and default behavior

- Backend native `wp-login.php` visual appearance, per-site brand logo/color/background.
- Optional login URL alias, **OFF by default**. `/wp-login.php` remains a recovery path.
- URL alias activation checks reserved words, existing pages, and known conflicting URL plugins.
- No WordPress core rewrite, credential changes, session changes, role changes or WooCommerce business policy.
- WooCommerce `/my-account/` and Checkout belong to Andy Commerce; do not touch their forms/templates.
- V1.1 opt-in stealth mode: hide legacy /wp-login.php, /admin and unauthenticated /wp-admin/ with generic 404; no private slug redirect.
- Existing sites retain the old fail-open behavior until an administrator explicitly checks Hide legacy entries.
- Hide switch only operates when a working custom alias is enabled; disabling alias or the module immediately disables hiding.
- Logged-in admin pages, admin-ajax.php/admin-post.php and WooCommerce /my-account/ remain accessible.
- WordPress native forgot/reset-password URL links need the network_site_url rewrite filter so old login URL doesn't get exposed in email.
- Private login returns X-Robots-Tag: noindex, nofollow, noarchive and no-store; do NOT add the secret slug to publicly readable robots.txt.
- The original wp-login.php is unavailable as a **web** recovery route while hide mode is enabled; rollback via the WordPress Login OS settings or disabling the Core plugin in hosting file manager/CLI.
- Security disclaimer: an undisclosed login slug does not prevent password attacks on XML-RPC, REST, WooCommerce or other auth surfaces. Rate limiting, 2FA and WAF are separate protection layers.
- Existing Google Social Login/One Tap retains its own settings and hooks.
- Login style toggle is OFF by default and independent of URL alias toggle.
- New menu/module is enrolled for existing installations but causes no login changes until opt-in.

## Gates

1. Static lint, L3 navigation order, settings and URL safety harness.
2. Existing Google auth and module-registry regression harnesses.
3. Dev-only exact source validation, backup and rollback before applying to runtime.
4. Visual Owner UAT, direct login, wrong password, forgot/reset password, Google login,
   logout, /wp-admin redirect, alias enabled and disabled, native fallback, conflict checks.
5. Separate Owner PASS required for Stable/Production; never modify www as part of Dev UAT.

## Known boundaries

- Other vendors can provide login rewrites that known-plugin detection does not cover.
- URL alias requires normal WordPress routing/permalink configuration; verify on each Dev site.
- A custom alias is not a security feature by itself; separate rate limiting/2FA/WAF remain necessary.
- Do not claim browser-tested behavior from a PHP harness alone.

## V1.1 stealth / noindex acceptance

- GET/POST old /wp-login.php: HTTP 404, no login HTML, no redirect to secret slug.
- GET /admin, /admin/: HTTP 404 (or server's early redirect to /wp-admin/ followed by 404); never redirect to secret slug.
- GET /wp-admin/: anonymous 404 without secret URL in Location; authenticated GET still accessible.
- Password reset and logout generated links: use the secret path; Google Social Login callback unaffected.
- GET and POST secret path: native login works; HTTP X-Robots-Tag includes noindex, nofollow, noarchive; Cache-Control no-store.
- robots.txt and XML sitemap must not contain the secret slug.
- Deactivation of alias or hide switch restores WordPress native login; verify in private browser before release.
- Do not enable hide switch on any shared Dev site before verifying the new alias from a private session.
