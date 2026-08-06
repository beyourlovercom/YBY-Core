# Andy Core v1.5.1 WordPress/MySQL Validation Report

## Authority and environment

GitHub Actions run `31078688839` is the authoritative PHP validation channel because local PHP execution is unavailable. It used an isolated runner with WordPress `6.6.2`, PHP `8.3.33`, MySQL `8.0.46`, WP-CLI, and synthetic data only.

## Results

- The v1.5.0 release commit installed with database version `1.2.0`.
- The candidate upgraded to database version `1.3.0`.
- Management and Activities tables plus required indexes were created and verified.
- Repeated migration passed; a deliberately removed activity index was repaired.
- Existing synthetic Lead rows were hash-verified unchanged.
- No historical Management or Activity records were backfilled.
- Runtime defaults, datetime conversion, management activities, archive/restore idempotency, invalid owner/status handling, editor toggles, and bounded 50-activity reads passed.
- The report artifact contains `migration-report.json`, `security-report.json`, three performance reports, and `environment-report.txt`.

## Gates

`WORDPRESS_RUNTIME_GATE`, `MYSQL_RUNTIME_GATE`, `PHP_VALIDATION_CHANNEL_GATE`, `DATABASE_MIGRATION_GATE`, `MIGRATION_IDEMPOTENCY_GATE`, `LEAD_IMMUTABILITY_GATE`, `NO_HISTORY_BACKFILL_GATE`, `CAPABILITY_GATE`, `SETTINGS_RUNTIME_BINDING_GATE`, `PERFORMANCE_1K_GATE`, `PERFORMANCE_10K_GATE`, `PERFORMANCE_50K_GATE`, `FRONTEND_ZERO_WRITE_GATE`, and `FRONTEND_ZERO_INBOX_ASSET_GATE` pass in the isolated validation channel.
