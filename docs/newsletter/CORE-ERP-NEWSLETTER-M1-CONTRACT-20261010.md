# Andy Core Newsletter → ERP Marketing OS — M1 Contract & Execution Gates

**Date:** 2026-10-10. **Status:** offline foundation only / no runtime hooks / no outbound email / no DB writes.
**GitHub authority:** `beyourlovercom/YBY-Core` is Andy Core's canonical source. `beyourlovercom/erpbeyourlovercom` is the already-established ERP Marketing authority. `beyourlovercom/beyourlover-web-v3` owns the BYL Newsletter page presentation and Dev-only integration. **No new plugin**; B2C checkout/payments continue to belong to Andy Commerce.

## Read-only audit: what exists now (do not discard)

- Andy Core v1.9.0 `[yby_subscribe]` and global subscribe popup already render reusable website-neutral email forms, but `public/js/yby-global-popup.js` intercepts submission and shows **"Subscribe submission is not configured yet; no mailing-list provider was called."** Current forms **do not persist** or confirm subscribers.
- The existing HMAC ERP connector `GET /wp-json/andy-core/v1/erp/snapshot/subscribers` currently reads legacy Elementor `e_submissions` (`Signup`/`Singup` and explicit email field) only. It intentionally does not infer consent from WordPress Users/Orders.
- BYL ERP already has `marketing_subscribers` (email-unique identity) and `marketing_subscriptions` (provider/source/status consent fact), a scoped `WordPressSubscriberSyncService`, signed `HttpWordPressSubscriberGateway`, idempotent writer and `/marketing/subscribers` view. **Do not create new ERP subscriber tables.**
- Historical ERP work-package M1.0A reported 422 real Elementor subscribers imported twice with no duplicate growth. This is historical evidence, **not** a current total or production sync confirmation.
- On BYL Dev, Andy Core v1.9.0 is active. Published WordPress Page `newsletter` (ID 12537) currently contains an HTML document embedded into WordPress content, including a visible **placeholder** `[wpforms id="15708"]` rendered as text inside a div. Do not treat this page as working subscriptions.

## Explicit product boundary

```
Website (BYL / YBY Bottle / YBY Irrigation / future sites)
  Core-owned [yby_subscribe] + popup, each with explicit unchecked email marketing consent
      ↓ POST /wp-json/andy-core/v1/newsletter/subscribe
Andy Core per-site subscriber authority and consent evidence
      ↓ pending → confirmation email token → subscribed → unsubscribed/suppressed
      ↓ signed read-only HMAC snapshot, no public email listing
ERP /sync/wpapi (existing authenticated operator/import orchestration)
      ↓ canonical marketing_subscribers (email identity)
      ↓ marketing_subscriptions (provider+site+external ID; status, source, timestamps)
ERP /marketing/subscribers
      ↓ FUTURE standalone EDM engine (not implemented/authorized here)
```

**Source of truth:** Andy Core owns explicit website consent facts and revocations. ERP Marketing owns cross-site normalized identity and provider/site-specific projection, never changes Core consent truth. WP account registration, checkout transaction, CRM contact, inquiry or customer are **not** opt-in events.

## Portable endpoint and consent safety contract (next work package)

- Public **POST `/wp-json/andy-core/v1/newsletter/subscribe`** receives valid email, explicit consent=true, source form/page, privacy-policy text version, locale and honeypot. No authorization cookie requirements; reject absent consent, invalid email, bots and excessive requests. Rate limit by privacy-safe hashed IP+email, bound payload sizes. Use generic response (no existing-user enumeration) and public API disabled by default until UAT configuration.
- Per-site dedicated **additive table `{prefix}yby_newsletter_subscriptions`**, uniquely keyed by normalized email within the site, with stable hashed `external_subscription_id`, status and consent timestamp, policy version, provenance, last_changed. Confirmation token hash + TTL are private; never send token/plain IP to ERP. Provide explicit schema version/migration and rollback contract, not auto-run outside approved Dev release.
- Default double opt-in: pending **is never EDM eligible**. A cryptographically random, single-use, expiring confirmation token (24h target) makes it subscribed **only after email ownership proof**. Confirmation mail is a transactional external send and requires separate isolated provider gate/Owner authorization. If mail is unconfigured, **fail closed**; do not claim email sent, do not silently create `subscribed` status.
- Repeat submission cannot silently undo unsubscribe; a new explicit intent returns pending pending inbox re-verification. Suppressed/complaint users cannot be reactivated without a separately authorized review. Confirmation replay or old/out-of-order transitions cannot override a newer unsubscribe/suppression. Unsubscribe tokens must be signed and privacy-preserving; one-click user unsubscribe and future List-Unsubscribe headers are separate email-delivery gates. Do not log raw tokens, IPs, or addresses in public errors.
- Portable shortcode and popup use the same stable endpoint. Provide pending/confirmed/unsubscribed truthfully, mobile-first styling and keyboard accessible status; avoid fake success/duplicate modal. Asset/runtime features can be disabled site-by-site.

## Snapshot compatibility and ERP changes (later separate PRs)

- Preserve existing default **legacy Elementor** `snapshot/subscribers` contract and its stable `elementor_signup:SHA256(site\nemail)` IDs; no reset/migration/reinterpretation of 422 historical rows.
- Add **explicit source selector** to the authenticated HMAC snapshot, e.g. `source=andy_core_newsletter` (Core-native table), with the same signed exact request path including query, `limit<=100`, stable source-scoped cursors, and `updated_after` semantics. Neither public subscription POST nor public confirmation route may enumerate subscriber emails.
- Native item fields: `external_subscription_id='andy_core_newsletter:'+sha256(connection_key+'\\n'+normalized_email)`, normalized `email`, `status= pending|subscribed|unsubscribed|suppressed`, explicit `consent_source`, `source_site`, `provider=wordpress`, `subscribed_at`, `unsubscribed_at`, `status_updated_at`. Keep token hashes and IPs out. The current Core M1 pure consent class implements these mappings **without registration or writes**.
- ERP incremental sync will request each source separately, reuse its existing idempotent `marketing_subscribers` email upsert + `marketing_subscriptions` provider/external-ID upsert, preserve legacy rows, update changed native statuses and audit per-item failures.
- Future EDM send eligibility must resolve conflicting site/provider facts safely: all intended destination/site consent must be verified subscribed **and** no effective global suppression/unsubscribe/complaint. Do not infer that one provider's subscribed status overrides another's later opt-out; formal send eligibility/suppression policy is a separate gate.
- If Andy Core or ERP is offline, never fabricate or claim sync success. Source can retain accepted explicit consent (when enabled) for later signed pull, with retry visibility.

## Planned milestones & gates

| Gate | Work | Acceptance |
|---|---|---|
| M0 | Audit + pure state-machine/ERP payload + offline tests (this PR) | CI; zero runtime API/email/DB behavior |
| M1 | Andy Core additive storage, public subscribe + explicit consent, anti-abuse, token confirm/unsubscribe, disabled-by-default | Disposable WP/MySQL UAT + PHP/JS security regression; no real email |
| M2 | Extend signed Core snapshot by `source`, ERP gateway/import for native, legacy coexistence | Two-source pagination, 2× idempotency, resub/unsubscribe, fake-provider failure tests |
| M3 | BYL Dev-only Core package backed up; newsletter Page 12537 governed template; blog sidebar CTA to actual page; tested without real email | 390/1440 mobile/desktop; exact-head/rollback; no www |
| M4 | Separate Owner-authorized isolated confirmation outbound canary + true end-to-end Dev → ERP UAT, privacy notice review | 1 consent→confirmation→ERP subscribed; 1 unsubscribe→ERP unsubscribe; 2× sync no duplicates |
| M5 | Owner signoff / GitHub merges, release packages and later WWW cutover | No production/email/sends without separate authorization |

## Out-of-scope

No WWW Production deployment, ERP Production DB mutation/migration, real marketing email or confirmation sends before bounded authorization, no Klaviyo/third-party list pushes, no customer creation, no historical Elementor data reinterpretation, no changes to Checkout consent, no additional plugin. Keep G2 Gate CLOSED.

**M0 CI PASS does not claim Newsletter works yet.** Every later gate requires evidence at its own boundary.
