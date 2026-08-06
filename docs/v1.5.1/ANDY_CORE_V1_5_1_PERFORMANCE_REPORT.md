# Andy Core v1.5.1 Performance Report

## Validation environment

GitHub Actions run `31078688839` executed the isolated validation against WordPress `6.6.2`, PHP `8.3.33`, and MySQL `8.0.46`. The database contains synthetic data only.

## Measured results

| Synthetic Leads | Generation | Default list | Queries | Result rows | Peak memory |
| --- | ---: | ---: | ---: | ---: | ---: |
| 1,000 | 0.0455 s | 0.0026 s | 2 | 50 | 51,707,904 bytes |
| 10,000 | 0.4168 s | 0.0174 s | 2 | 50 | 51,707,904 bytes |
| 50,000 | 6.8468 s | 0.0931 s | 2 | 50 | 51,707,904 bytes |

The list query excludes `project_details` and `custom_fields`, does not load Activities, and reports no N+1 behavior. The isolated run confirms zero frontend management writes and zero frontend Inbox assets. The migration creates no Management or Activity history backfill and does not scan or mutate existing Leads.

## Scope

These figures are CI-environment observations, not production performance claims. Owner UAT must confirm the same invariants in its isolated Dev environment with the approved test data.
