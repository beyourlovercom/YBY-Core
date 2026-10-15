# Andy Core V2.2 — Hostinger Dev-only preflight / release gate

**Status: BLOCKED — not an upload instruction, not a deployable ZIP, not a production release.** Document generated 2026-10-15 from PR #111 exact HEAD `669576d457371ad102c4be50073a7f331013af40` (CI 4/4 PASS at preparation). Must revalidate exact HEAD/CI immediately before use.

## Ground truth and source contract

- Dev origin: `https://dev.beyourlover.com`.
- Historical Dev WordPress Andy Core root: `/home/u247343654/domains/dev.beyourlover.com/public_html/wp-content/plugins/yby-core/`; this must be reconfirmed live.
- Independent Andy Commerce plugin root must be discovered live, not assumed from GitHub `packages/andy-commerce/`.
- Historical PR #107 six-file V2 upload was independently verified, but no current Dev source hashes/backup evidence for this V2.2 release have been obtained.
- Current Dev Chrome DOM before release: one old `.andy-core-l3-v2`, zero V2.2 `.andy-core-shell-v22`; loaded `andy-core-native-l3-v2.js` and old L3 CSS on Email OS.
- CN Mac `jutdo-dev-hostinger` SSH identity was rejected with `Permission denied (publickey,password)`; working `jutdo-hostinger` identity points to **another Hostinger user** `u686797605`, without the expected Dev root. Never redirect this release to that account. No SSH credentials or server settings changed.

## Exact source candidate (NOT an unconditional upload)

See [allowlist.json](./allowlist.json): 26 runtime paths in two separately updateable plugins:
- 21 Andy Core PHP/CSS paths;
- 5 independently packaged Andy Commerce PHP paths;
- Source Git blob SHA1 captured directly from the source commit's Git tree; current Dev SHA1 values deliberately `null`; backup flags deliberately `false`.
- New files must be confirmed absent; existing files must have pre-release SHA1 captured, backed up and reconciled against the GitHub source. Source diffs may need to be rebuilt from the Dev version; **do not overwrite newer Dev-only features**.

## M6 strict sequence

1. **Read only**: reconnect to the actual Hostinger Dev account (or authorize its Dev-specific SSH identity). Confirm domain and installed plugin directories, active plugins, versions, file list, exact Git blob SHA1 for every candidate and checkout/marketing Dev-specific drift. No secret values logged.
2. Compare the real Dev files to PR #111 source. For diverged files, review upstream additions and adapt only the V2.2 module wrapper; rerun CI and static role/nonce regression. Do not restore from an outdated plugin archive.
3. Record and verify Dev-only reversible backups stored outside the active plugin directory, plus rollback hashes. Confirm no existing DB migration is required; do not write business records or send mail.
4. Release independently by plugin, exact allowlist only, staged Core then Commerce as compatible, with pre- and post-deploy PHP lint. On any fatal/403, stop and immediately restore matched originals; no READY device restart.
5. Dev desktop and iPhone 14 / 390 px UAT: L1 = Andy Core native left; L2 = Inquiry/Newsletter/Email/Content/Marketing/Commerce/System as authorized; right L3 only, no old tab duplication/DOMContentLoaded rail movement, original handlers, old URLs and role boundaries. Check UI render without creating real inquiries, orders, payments, newsletter/email or analytics events.
6. Only after parent V2.2 Owner PASS, allow separate four-file **Draft PR #114** Premium Popup Dev canary, based on its own source-hash and backup gate. Its `bottle_oem` profile remains fail-closed outside OEM pages. Compare to actual YBY Bottle OEM visual reference with separately authorized real screenshots; WWW release needs separate approval.

## Stop conditions

- Any unknown or different Dev path/host, stale source hash, unreachable release account, missing rollback pointer or independent Commerce drift = **STOP**.
- CI success does not equal live Dev acceptance; document unknowns.
- Do not merge Draft PRs into `main`, deploy WWW, update Ads/CRM/ERP/orders/checkout, enable Worker/SMTP, submit real leads, or restart READY machines.
