# Andy Core v1.4.0 Release Notes

## Release purpose

Andy Core v1.4.0 is a compatibility-safe product-name transition from YBY Core to Andy Core.

## Changed

- WordPress plugin display name is now **Andy Core**.
- WordPress admin top-level product menu is now **Andy Core**.
- Settings screen product heading is now **Andy Core**.
- Plugin and runtime version are now `1.4.0`.
- Root documentation and WordPress readme metadata now use the Andy Core product identity.
- The active development direction is now feature-driven, with Social Login planned next.

## Compatibility preserved

The following remain unchanged:

- plugin directory: `yby-core/`
- main file: `yby-core.php`
- text domain: `yby-core`
- PHP classes, constants, and functions using `YBY_*` and `yby_*`
- WordPress options using `yby_*`
- `[yby_inquiry_modal]` and `[yby_sticky_cta]`
- `data-yby-*` frontend attributes
- `.yby-*` CSS selectors
- `/wp-json/yby/v1/` REST routes
- `window.YBY*` runtime APIs
- existing `wp_yby_*` database tables
- Case ID behavior and formats
- database schema version `1.1.0`

## Runtime impact

No Lead, Inquiry, Case ID, Thank You, WhatsApp, email, tracking, Bricks, theme, Google Ads, CRM, ERP, or database behavior changed.

## Social Login

Social Login is not included in v1.4.0. It is the next planned feature line and will be developed and validated separately.
