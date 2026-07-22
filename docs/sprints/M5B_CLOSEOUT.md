# M5B Closeout

- Milestone: `M5B Brand Portability`
- Status: `CLOSED / PASS`
- Code branch: `feature/v1.3.0-inquiry-component-system`
- Accepted code commit: `9e0363aa2f1516c0ec2a7f785cfb0521a25ba83c`
- Development environment: `dev.ybyirrigation.com`
- Plugin version: `1.3.0-dev`
- Database version: `1.1.0`

## Scope Completed

- reusable Inquiry component system
- server-owned Site Profile identity
- configurable Brand Profile
- Brand Runtime presentation
- public Runtime security hardening
- server-generated brand Case IDs
- Lead persistence
- notification provider
- email presentation
- Thank You runtime
- Catalog/Return/YouTube Runtime precedence
- WhatsApp Runtime templates
- WhatsApp formatting and Case ID enforcement
- `sessionStorage`
- Thank You tracking
- `generate_lead` deduplication
- Irrigation development deployment
- rollback evidence
- deterministic Harness coverage

## Accepted Milestone Gates

- M5A notification closure: PASS
- M5B-1 trusted Site Identity: PASS
- M5B-2 configurable Brand Presentation: PASS
- M5B-2R1 public Runtime hardening: PASS
- M5B-3A Irrigation deployment: PASS
- M5B-3B Irrigation end-to-end closure: PASS
- M5B-3C Thank You and WhatsApp Runtime closure: PASS
- M5B final development acceptance: PASS

## Final Operational Evidence

- Lead rows remained `15`
- Latest Lead remained ID `15`
- Latest Case ID remained `YBY-IRR-20260721-98CLV9`
- Post SMTP latest remained `39`
- No new Lead
- No email
- No real analytics request
- No schema change
- No rollback
- No Bottle deployment
- No production deployment

## Accepted Architectural Decisions

- shared default WhatsApp copy remains neutral
- site-specific copy is configured through Brand Runtime
- Project/Page legacy WhatsApp messages are retired from active output
- old stored values may remain but are ignored
- Brand Runtime presentation and server-owned identity remain separate
- public Runtime exposes only safe customer-facing configuration
- Case ID remains the cross-system inquiry identity
- M5B does not include WhatsApp Cloud API or AI chat

## Known Non-Blocking Notes

- the development rendered WhatsApp example included only `Country` because other summary fields were empty
- the public test helper is strictly gated
- external GTM and YouTube failures observed during QA were caused by deliberate request blocking
- no active M5B Runtime blocker remains

## Deferred Work

Not part of M5B:

- Bottle development deployment
- production deployment
- main merge
- release tag
- final `v1.3.0` release publication
- WhatsApp Cloud API
- Conversation database
- AI chat
- unified Inbox
- `v1.4` Conversation Widget

## Next Milestone

`YBY Core v1.4.0 - WhatsApp Conversation Widget`

Planned scope:

- floating Launcher
- teaser
- branded pre-chat window
- editable generated WhatsApp draft
- Continue on WhatsApp
- admin configuration
- responsive behavior
- accessibility
- non-PII Tracking
- future transport abstraction

`v1.4.0` planning may begin only after this documentation freeze is accepted.
