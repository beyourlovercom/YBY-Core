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
- Google provider settings and the original Social Login page remain intact; legacy \`?provider=google\` URLs keep working.
- Only the enabled, authorized modules render these tertiary links. Independent WP auth access checks still apply.

## Scope and default behavior

- Backend native \`wp-login.php\` visual appearance, per-site brand logo/color/background.
- Optional login URL alias, **OFF by default**; \`/wp-login.php\` remains a recovery path.
- URL alias activation checks reserved words, existing pages, and known conflicting URL plugins.
- No WordPress core rewrite, credential changes, session changes, role changes or WooCommerce business policy.
- WooCommerce \`/my-account/\` and Checkout belong to Andy Commerce; do not touch their forms/templates.
- URL hiding/blocking of \`wp-login.php\` is **not** part of V1.
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
