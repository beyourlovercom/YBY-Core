# YBY Core v1.3.1 Release Notes

## Scope

YBY Core v1.3.1 extends the stable v1.3.0 Inquiry Component System with
site-wide trigger and sticky CTA capabilities.

## Included

- `data-yby-inquiry-trigger` and `data-yby-quote-trigger`
- default `[yby_inquiry_modal]` preset fallback
- `[yby_sticky_cta]` Core-owned floating inquiry CTA
- backward compatibility for `data-yby-modal-open`
- source and page-profile context propagation

## Boundary

Lead submission, Case ID, Thank You, WhatsApp, tracking, and configuration
remain owned by the existing Core runtime. No page-specific visual system is
included.

## Release status

Prepared for validation. PHP CLI lint and package validation must pass before
Dev or production installation.
