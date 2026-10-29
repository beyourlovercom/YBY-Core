# R7 Dev runtime drift and safe recovery gate — 2026-10-29

Status: **R7 BLOCKED / payment NO-GO**, not caused by OAuth expiration. Do not reopen frozen R6 or reauthorize Stripe merely because the currently connected ChatGPT Stripe app has a different merchant.

## Proven historical Stripe authorization
- Owner explicitly elected the TEST Stripe account **already linked to Dev**: `acct_1RdAGvPKD6QEqtsG`. History Issue #234 comment `6459230722` confirms live Dev test-key API `GET /v1/account HTTP200`, TEST account MATCHED, webhook `we_1UOBqSPKD6QEqtsGAZhwqgui` pointing at Dev `/?wc-api=wc_stripe`. No secret value recorded here.
- US Mac mini disposable Woo sandbox account `acct_1R57M7GR9MwFzg43` is DIFFERENT, with older successful five-currency native TEST payments and an Owner-performed CAD13.51 partial refund; cannot substitute sandbox proof for Dev payments.
- ChatGPT Stripe app currently connected to `acct_1SrJdHCxlWhQx8Rb`, another independent TEST account; connector identity does not imply Dev authentication. **No new OAuth needed** just to preserve the prior Dev entitlement.
- Real Dev Stripe TEST card payment, webhook payment processing and refund were never signed off. A scoped authorized Checkout POST was blocked by the platform safety gate in Issue #234. Do not use other tools/channels to evade that block.

## Current live readback
- Dev `https://dev.beyourlover.com`, WooCommerce 11.2.1, Andy Commerce 1.0.12 ACTIVE, Campaign 0.1.0 and BF2026 0.1.0 ACTIVE but master `no`; legacy marketing flags `no/no`; `/gift/` draft #40051, no live gift catalog.
- Separate Stripe gateway `enabled=no, testmode=yes`; WooPayments `enabled=yes, test_mode=yes`; R13 canary option not armed, stored proof absent; no payment attempted in this investigation.
- Dev safety MU file SHA256 `9824d5618ff7983013f2a480aed6f5fe122c00d9c58bf3dd10d11c7d266d7265`, unchanged.
- Home/cart/Store API HTTP200; /gift/ correctly 404 and not misdirected to fee product.
- **Unexpected full-plugin drift:** Commerce plugin bootstrap `includes/class-andy-commerce.php` SHA256 `b9fe51f8559b50c4dc94a809c2c9ac79821c56dbbd1e07e855a896419d520a66`, modified as of 2026-10-28 07:37 on Hostinger, lacks R13 requires and `R13::boot`. These seven exact PR #213 source files are **absent** from current Commerce plugin:
  - `class-andy-commerce-currency-r13-dual-ledger.php`
  - `class-andy-commerce-currency-r13-native-tax-shipping.php`
  - `class-andy-commerce-currency-r13-settlement-audit.php`
  - `class-andy-commerce-currency-r13-fx-quote-guard.php`
  - `class-andy-commerce-currency-r13-dev-canary.php`
  - `class-andy-commerce-currency-r13-stripe-refund-ledger.php`
  - `class-andy-commerce-campaign-usd-basis-v1.php`
- Exact Commerce publisher **Draft PR #213** HEAD `9610d92ed2e1bc421f4d8aca79c6ba30c9aabdef` does include all seven files. Reference R13 Canary source SHA256 `88c4e293006dc78fbdec47d62352c1bf295f07256e3d969f67373d29ab686bd8`, source USD quote SHA256 `d399153578355412f5e6ad17044f7ba3ab4d6c300ff04af4c6c764b8fb39e330`. Do not blindly install full PR ZIP, as other parallel Commerce files/runtime might differ.
- Protected rollback archives outside webroot: `/home/u247343654/aicc-dev-rollback/andy-commerce-pre-d8bb8b5-20261028.tar.gz` SHA256 `60a0f872708a9a28780f49eca87a164389d15603eba5a7f75932d55ee863b6c4` **contains R13 Canary/dual ledger**, but **not the newer Campaign source-USD quote**. Older archive `andy-commerce-pre-r13-20261027.tar.gz` SHA256 `6bb621827397261df818fbabceaf732f78818dd6ce807020ece0aafbb1b17987`, includes dual ledger only. These archives should NOT be blindly restored wholesale.

## Blocked remediation, explicit stop
An exact-HEAD, SHA-guarded self-built DC recovery attempt was **blocked by OpenAI safety review before execution** on 2026-10-29. No stage, source download, bootstrap patch or Dev write from this attempt is claimed. **Do not route around the block using an alternate tool or change execution method.** Platform-side authorization/approved change process is required.

## Preventing a repeat: enforced canonical release-package gate
The R7 Draft child PR now adds `scripts/check-commerce-r7-bundle.py`, which validates the **seven required files**, exactly one `require_once` for each file in the installed Commerce bootstrap, and exactly one `Andy_Commerce_Currency_R13_Dev_Canary::boot()` call. Its CI deliberately creates three malformed candidate ZIPs (remove R13 class; remove R13 require; remove Canary boot) and verifies rejection.

**Important second protection:** the same check is now mandatory inside **`scripts/prepare-andy-commerce-dev-release.sh`**, the exact-HEAD guarded **sole Commerce publisher**. If its generated candidate ZIP fails, release preparation denies the artifact, deletes the failed ZIP and does not write a successful attestation. The passing attestation adds `R7_R13_Bundle_Contract=PASS`. Local US Mac mini candidate ZIP and regression `R7_STRIPE_TEST_ACCOUNT_GATE_PASS=24` have passed; GitHub R7 child CI is tracked at Draft PR #254.

This guard will be operative for canonical PR #213 **after its approved integration of R7 child PR #254**; a standalone Draft child branch cannot retroactively protect a user who deploys a different old ZIP. It is **not** evidence that Dev has been repaired, and no actual installation has been attempted through the blocked recovery path.

## Added R7 exact source-byte parity protection (2026-10-29)
The package checker now accepts `--source-root packages/andy-commerce` and compares the **exact eight PHP byte payloads** in the installable ZIP — seven Currency R13/source-USD classes **plus the Commerce bootstrap** — against the exact reviewed branch source, *in addition to* checking the seven requires and Canary boot call. Source parity is required by both CI and the sole canonical publisher preparation script.

- Local US Mac mini built the R7 candidate ZIP with `R7_COMMERCE_BUNDLE_CONTRACT_PASS=7_FILES_7_REQUIRES_CANARY_BOOT_AND_SOURCE_PARITY`.
- A deliberately modified R13 payload, keeping its name, file existence, required boot call and all seven bootstrap requires intact, was rejected with `source_payload_mismatch:andy-commerce/includes/class-andy-commerce-currency-r13-dev-canary.php`.
- CI now rejects **four** corrupted candidates: missing R13 file, missing require, missing Canary boot and a stale/modified R13 payload with valid file names.
- Mocked TEST-only Stripe account proof negative contract remains `24 PASS`. No live Dev write, test charge or permission change was performed.

**Important:** parity only compares a release candidate to the reviewed checkout; it does not prove that currently installed Dev matches the candidate. Installing this R7 child work into the canonical PR #213 remains a separately controlled release task. The previously blocked repair was **not** retried.

## Safe repair acceptance when authorized
1. Re-read live Commerce bootstrap SHA and original R13 file inventory; ensure no concurrent Commerce publisher writes. Preserve full plugin-only rollback outside webroot and verify hashes.
2. PR #213 sole publisher only: restore the missing seven source files with exact source SHA256, and insert only the corresponding requires plus default-OFF Canary bootstrap into existing live bootstrap. Never overwrite currency settings, Woo payment settings, user sessions, third-party integrations or the entire plugin.
3. PHP lint seven files + bootstrap, real WP class-load readback, Canary OFF, currency base USD, separate Stripe TEST gateway OFF, Dev MU original SHA, gift master OFF, Woo cart/Store API HTTP200, no orders, protected admin paths and rollback. No merchant switch or OAuth.
4. Only once fully restored and with permitted execution safety: named admin, 10-minute TEST Canary, retained scoped Dev MU rules, confirmed Dev Stripe TEST merchant `acct_1RdAGvPKD6QEqtsG`, TEST-only gateway; execute genuine Dev Checkout/HPOS/payment/webhook/refund through authorized browser/approved tools. Final rollback of armed gate, Stripe gateway OFF and MU original hash.
5. Leave PR #213/#254 Draft and no WWW/main change without Owner exact-head approval.

**R7 cannot be marked CLOSED until actual Dev TEST transactions and refunds are confirmed, and safety approval has been resolved.**
