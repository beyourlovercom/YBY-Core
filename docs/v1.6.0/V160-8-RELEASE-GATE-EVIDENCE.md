# V160-8 — Release Gate Evidence

Date: 2026-09-18
Branch: `feature/andy-core-v1.6.0-docs-os-v1`
Baseline: `672aa3e26f07e6ddff1db26f2c69782cf2299e8e`

## Version / metadata

- Plugin header: `1.6.0`
- `YBY_CORE_VERSION`: `1.6.0`
- WordPress stable tag: `1.6.0`
- Runtime database version: `1.5.0`
- Updater compatibility database version: `1.4.0`
- No database migration.

## Regression

- PHP harnesses: all pass when run with the same required OpenSSL CLI environment used by crypto tests.
- JavaScript harnesses: 6/6 pass.
- Docs OS module/runtime/canonical/R2/reusable-contract harnesses: pass.
- Release metadata harness: pass.
- `git diff --check`: pass.
## Release candidate

- Package: `andy-core-v1.6.0.zip`
- File count: **126**
- PHP lint: PASS
- Archive top-level: `yby-core/` only
- Development-only paths excluded
- Build context: `RELEASE_CANDIDATE`
- Ed25519: `NOT_SIGNED` at PR candidate stage
- SHA-256: `8f488d4bb8ffe8931dc6e98c77b041a0c4b930ed69f6816cfc772df9558d23de`
- Metadata schema: `1`
- Metadata version: `1.6.0`

Formal release signing and Production deployment remain outside V160-8 and require the V160-9 gate.

## Backward compatibility

- v1.5.7 updater verifier accepts the v1.6.0 metadata + ZIP when the compatibility harness derives the target version/package from signed metadata: PASS.
