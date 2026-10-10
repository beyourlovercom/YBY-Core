# Andy Core Newsletter M3 — Single-recipient isolated E2E Owner UAT

**Stage:** Code-only / offline transport E2E. The approval for this stage permits isolated synthetic development and no-network tests. It does **not** authorize public Newsletter activation, writing to `dev.beyourlover.com` subscriber records, real email/SMTP, ERP live synchronization, Production/WWW, or GitHub main merge.

## Authoritative source and environments

- Code authority: `beyourlovercom/YBY-Core` GitHub main (recheck exact current head before release). This branch only adds tests + CI + contract documentation. No runtime REST/controller, email, storage, schema, or browser production logic is changed.
- Disposable WordPress 6.6.2 + MySQL 8.0 fixture already provisioned by `.github/workflows/newsletter-wordpress-mysql.yml` on GitHub Actions. Tests run **inside** this fixture, with fake `newsletter-validation.test` origin and `@example.test` mailbox; no real subscribers, keys or mail services.
- Tests do not initiate network calls. Provisioning the ephemeral CI runner and WordPress binary requires standard CI dependency downloads; this is **not a live provider integration test**.
- The existing Dev Safety shared MU file resides in Website repo `beyourlovercom/beyourlover-web-v3`; it is **not** changed/deployed as part of this Core test branch. Its separate offline one-use/mail/G5 protections are validated by Website Issue #67 regression.

## Test flow — must be demonstrated end-to-end in the disposable fixture

1. Verify the actual WordPress REST route and isolated MySQL table exist. Reject all non-fixture hosts and non-CLI execution **before** touching database flags or registering test mail interception.
2. Snapshot fixture's two Newsletter options and baseline row count. Start with both API/outbound options OFF; REST POST to subscribe returns **503** without creating a row or attempting a message.
3. Install a **terminal, fail-closed `pre_wp_mail` test interceptor** before changing fixture options. Only one `@example.test` recipient, exact Core confirmation subject/body, no custom mail headers or attachments and precisely one call may be intercepted. Return `true` to the Core REST handler without invoking PHPMailer or sending SMTP. Block all WP HTTP requests during the test.
4. Turn on both options **in the disposable fixture only** (separate test-process constants are also required). Verify invalid consent/mail rejected. Execute one valid POST subscription, confirm single `pending` row and exactly one fake mail; inspect confirmation and opt-out links **in test memory only**, compare token HMAC with database hashes, never print links/tokens or recipient.
5. Simulate a link-scanner `GET` request for confirm: no state change. Deliberate `POST` confirm succeeds, persists `subscribed`, consumes token and denies replay. An already-subscribed duplicate subscribe does not send a second message.
6. Confirm unsubscribe GET is inert; opt-out POST sets `unsubscribed`, replay is blocked. The browser JS VM harness separately verifies initial landing makes **zero fetch calls** and renders an explicit button; click issues exactly one same-origin, anonymous POST, removes the link token from the address bar.
7. `finally`: delete **only** the synthetic row matched by randomized mailbox + email hash + site key, restore both fixture-local options exactly, verify baseline row count. On a test failure, CI fixture is disposable; nothing is written to Dev/WWW/ERP.

## Evidence and Owner Code UAT gate

- `tests/newsletter-m3-single-recipient-wordpress-mysql.php`: WordPress/MySQL REST/token/double-opt-in E2E, no mail/network, precise cleanup.
- `tests/newsletter-m3-token-landing-harness.js`: pure Node VM browser link-scanner and explicit-click client behavior; no HTTP/network.
- `.github/workflows/newsletter-wordpress-mysql.yml`: runs new M3 test after the existing M1 and M2 disposable WordPress/MySQL suites.
- Existing `.github/workflows/core-regression.yml` auto-runs JS harness files, so the new one is included without weakening earlier tests.

**Owner UAT must review:**
1. Is the simulated `pending → subscribed → unsubscribed` behavior the intended Newsletter business flow, including requiring human POST confirmation and single-use/replay prevention?
2. Is exactly one simulated confirmation message to a fake mailbox acceptable as the **offline** gate, without implying SMTP delivery or public browser→mail E2E?
3. Does the exact-row cleanup and two-option restoration satisfy synthetic-only isolation?
4. Are WWW, Dev public API/outbound, the shared G5 security MU, live users, and real ERP connector excluded?

A PASS here means **isolated E2E Code UAT only**, not actual email delivery, subscriber import, Dev configuration change or production activation. Merge this Draft PR only after separate GitHub exact-head+CI authorization. True Gmail receipt and full public HTTP/SMTP E2E remain separately permissioned and subject to the earlier execution safety gate, which must not be bypassed.

**Roadmap:** Core M1/M2 synthetic baseline → M3 isolated one-mailbox E2E (this PR) → Owner isolated E2E UAT → independently approved Dev integration design → real mail/receipt UAT if permitted → live ERP connector E2E separately → Final Closure.
