# Andy Core v1.5.1 Inquiry Inbox Specification

- Follow-up Management uses a fixed label/control grid on desktop and a single column below 800px.
- Inquiry Settings provides Salespeople / Inquiry Owners configuration and a shared Owner selector with `Unassigned`.
- The Andy Core submenu is exactly Inquiry, Project Studio, Projects, Brand, Social Login, and Settings; the auto-generated duplicate parent item is removed.

## Scope

Local, site-scoped management of the existing `yby_leads` records. The original Lead row remains immutable and is the source of truth.

## Admin Contract

- Existing top-level slug: `yby-os`.
- Existing Project Studio, Projects, Brand, Social Login, and Settings routes remain available.
- Add exactly one submenu: `andy-core-leads` labeled Inquiry.
- The top-level Andy Core callback defaults to the Inquiry list while the existing Project Studio URL remains directly reachable.
- Settings remains `?page=yby-core` with `tab=general`, `tab=inquiry`, or `tab=system-status`; unknown tabs fall back to General.

## Inbox

The list uses a bounded select from `yby_leads`, left-joins one management row, sorts by `created_at DESC`, supports search and allowlisted filters, and paginates at 30/50/100. It does not select `project_details`, `custom_fields`, or activity rows.

## Detail and Management

The left column renders all original Lead fields read-only. The right column manages status, owner, priority, follow-up time, notes, archive, and restore. Management is created lazily on the first management action. Every write creates an activity row.

## Explicit Non-Goals

No cross-site inbox, ERP, quotes, orders, commissions, WhatsApp Cloud conversations, Gmail sync, AI scoring, automatic assignment, marketing, or BI.
