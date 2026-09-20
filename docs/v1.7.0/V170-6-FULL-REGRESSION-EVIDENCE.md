# V170-6 — Full Regression Evidence

Date: 2026-09-20

Branch:

`feature/andy-core-v1.7.0-foundation`

Validated head:

`9fc29f72b752a47a0c49edc5c209750f984f1e4e`

## Validation basis

The local regression matrix was taken from the repository's canonical `.github/workflows/core-regression.yml` rather than a v1.7-only subset.

Validation covered:

1. all Git-tracked PHP files
2. every `tests/*-harness.php`
3. every `tests/*-harness.js`
4. `git diff --check`

## Test runtime

Windows Local Lightning PHP:

`PHP 8.2.29 / OpenSSL 3.0.15`

The Local site's PHP configuration provides:

- OpenSSL
- ZipArchive
- Sodium

The Local PHP ini referenced a missing Imagick DLL. A temporary regression-only ini was therefore created with only that unavailable Imagick extension line disabled. The Local site configuration itself was not modified.

Windows OpenSSL also required its packaged configuration path:

`OPENSSL_CONF=C:\Users\Administrator\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win64\extras\ssl\openssl.cnf`

Before setting this process-local environment variable, `openssl_pkey_new()` failed with configuration-file errors. With the packaged OpenSSL config:

- RSA 2048 key generation: PASS
- private key export: PASS
- Google Auth harness: PASS

No application-code change was made to work around the environment issue.

## Results

### PHP lint

- Git-tracked PHP files: **162**
- Result: **PASS**

### PHP harnesses

- Harness count: **57**
- Result: **ALL PASS**

Coverage includes:

- brand/site/profile runtime
- connector foundation/security/idempotency/affiliate/coupon/payout
- Andy Docs admin/runtime/canonical/R2/reusable contracts
- Email OS / native store / Woo bridge / ERP contract
- Global Popup
- Google Auth
- Inquiry Dock / Inbox / Notification / Shortcodes
- Module Registry / Boot Gate
- v1.7 Admin IA
- v1.7 Andy Content IA
- v1.7 Landing Page CPT
- v1.7 Module Adoption
- v1.7 Registry V2
- v1.7 Extension Runtime
- v1.7 Versioned Settings Store
- updater / release metadata
- Social Login
- WooCommerce completed/delivered status

### JavaScript harnesses

- Harness count: **6**
- Result: **ALL PASS**

Validated:

- global popup runtime
- Google One Tap runtime
- inquiry modal default runtime
- inquiry page-profile runtime
- lead SDK page-profile runtime
- thank-you runtime

### Repository hygiene

`git diff --check`: **PASS**

## V170-5 Conditional Asset Runtime

The full regression includes the V170-5 asset contract:

- extension module must be enabled
- dependencies must pass
- both enqueue and condition callbacks are required
- missing condition fails closed
- false condition performs zero enqueue
- disabled module performs zero asset registration
- dependency-blocked module performs zero asset registration
- repeated discovery does not duplicate asset hooks
- runtime performs zero settings/business-data writes

## Result

**PASS — V170-6 Full Regression**

The v1.7 Foundation changes are ready to proceed to Local WordPress Admin UAT. Production remains untouched.
