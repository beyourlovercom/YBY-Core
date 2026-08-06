# Andy Core v1.5.1 Database Contract

## Existing Fact Table

`{$wpdb->prefix}yby_leads` remains unchanged. Existing columns, Case ID uniqueness, and all historical rows are preserved.

## New Tables

`{$wpdb->prefix}yby_lead_management`

- `id` bigint unsigned primary key
- `lead_id` bigint unsigned unique
- `status` varchar(50) not null default `new`
- `owner_user_id` bigint unsigned not null default 0
- `priority` varchar(20) not null default `normal`
- `next_follow_up_at` datetime nullable
- `last_activity_at` datetime nullable
- `archived_at` datetime nullable
- `created_at` datetime not null
- `updated_at` datetime not null
- indexes on `(status, archived_at)`, `(owner_user_id, archived_at)`, and `created_at`

`{$wpdb->prefix}yby_lead_activities`

- `id` bigint unsigned primary key
- `lead_id` bigint unsigned not null
- `activity_type` varchar(40) not null
- `content` text nullable
- `old_value` text nullable
- `new_value` text nullable
- `created_by` bigint unsigned not null default 0
- `created_at` datetime not null
- indexes on `(lead_id, created_at)` and `activity_type`

No foreign keys are used. No historical management or activity rows are backfilled.

## Migration

The existing installer advances from `1.2.0` to `1.3.0` only after both tables and their indexes are verified. It is idempotent, uses `dbDelta`, does not alter `yby_leads`, and records no production Lead writes during normal front-end submission.
