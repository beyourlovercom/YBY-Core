# Andy Core v1.7.0 — RC Package Gate Evidence

Date: 2026-09-20

Branch:

`feature/andy-core-v1.7.0-foundation`

Exact head:

`6fda70644ef3215c9ae480790ab18f1a9cb19013`

Production: **UNTOUCHED**

## Release candidate identity

- plugin version: `1.7.0`
- runtime database version: `1.5.0`
- updater compatibility database version: `1.4.0`
- build context: `RELEASE_CANDIDATE`
- source ref: `feature/andy-core-v1.7.0-foundation`
- source commit: `6fda70644ef3215c9ae480790ab18f1a9cb19013`

## Full regression before build

- Git-tracked PHP lint: **165 PASS**
- PHP harnesses: **58/58 PASS**
- JS harnesses: **7/7 PASS**
- release metadata harness: PASS
- secure updater harness: PASS
- `git diff --check`: PASS

The full matrix includes Article TOC V1, Registry V2, Landing Pages, Andy Content IA, Docs OS, Email OS, Inquiry, Connector, Social Login, updater, and Woo order-status contracts.

## RC artifact

Output directory:

`D:\ai\_release-gates\andy-core-v1.7.0-rc-6fda706`

Package:

`andy-core-v1.7.0.zip`

Package size:

`306997 bytes`

Runtime file count:

`142`

SHA-256:

`c9890568457e77e3998cb80d633c1e5dc927567e0dfe763f7dc6141f7e0a2698`

## Build results

- PHP lint: PASS
- `git diff --check`: PASS
- updater metadata schema: 1
- local RC Ed25519 signature: NOT_SIGNED

NOT_SIGNED is expected for the local Release Candidate build. The governed tagged FINAL_RELEASE workflow requires the repository signing secret and must produce a valid signature.

## Package-level validation

Validated from the actual RC ZIP:

- only `yby-core/` top-level archive root: PASS
- development-only paths excluded: PASS
- `yby-core/yby-core.php` present: PASS
- plugin header version `1.7.0`: PASS
- `YBY_CORE_VERSION=1.7.0`: PASS
- updater compatibility database version `1.4.0`: PASS
- runtime database version `1.5.0`: PASS
- WordPress stable tag `1.7.0`: PASS
- SHA256.txt matches actual package: PASS
- update-metadata.json matches exact package SHA/version/database metadata: PASS

## Backward-compatible updater validation

The v1.5.7 update verifier was extracted from the historical Git tag and executed against the v1.7.0 RC ZIP and metadata.

Result:

**PASS — v1.5.7 updater accepts target metadata + ZIP**

This confirms the current RC remains installable through the preserved signed-updater compatibility contract.

## Release governance

This RC evidence does **not** authorize:

- stable tag creation
- GitHub Release publication
- Production deployment

The RC may now proceed to the controlled Dev UAT environment according to the repository development/deployment rules.
